<?php
declare(strict_types=1);

/**
 * Run from PowerShell: & "C:\xampp\php\php.exe" ".\tests\TestPlan.php"
 *
 * Requires the project schema and seed data. Temporary test records are removed
 * when the runner exits.
 */

final class HttpClient
{
    private array $cookies = [];

    public function request(string $url, string $method = 'GET', array $post = []): array
    {
        $headers = ['Accept: text/html,application/json'];
        if ($this->cookies) {
            $headers[] = 'Cookie: ' . implode('; ', $this->cookies);
        }

        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'follow_location' => 0,
                'timeout' => 10,
            ],
        ];
        if ($method === 'POST') {
            $body = http_build_query($post);
            $options['http']['header'] .= "\r\nContent-Type: application/x-www-form-urlencoded";
            $options['http']['content'] = $body;
        }

        $context = stream_context_create($options);
        $body = @file_get_contents($url, false, $context);
        $responseHeaders = $http_response_header ?? [];
        $status = 0;
        foreach ($responseHeaders as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $match)) {
                $status = (int)$match[1];
            }
            if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]*)/i', $header, $match)) {
                $this->cookies[$match[1]] = $match[1] . '=' . $match[2];
            }
        }

        return ['status' => $status, 'body' => (string)$body, 'headers' => $responseHeaders];
    }
}

$project = dirname(__DIR__);
$public = $project . DIRECTORY_SEPARATOR . 'public';
$host = '127.0.0.1';
$process = null;
$pipes = [];
$temporaryBookingIds = [];
$temporaryCustomerEmail = '';
$tests = [];
$runtimeError = '';
$check = static function (string $name, bool $passed, string $evidence = '') use (&$tests): void {
    $tests[] = ['name' => $name, 'passed' => $passed, 'evidence' => $evidence];
};

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        getenv('DB_HOST') ?: '127.0.0.1',
        getenv('DB_PORT') ?: '3306',
        getenv('DB_NAME') ?: 'wedding_venue_booking'
    );
    $pdo = new PDO($dsn, getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 3,
    ]);

    $manager = $pdo->query("SELECT customer_id FROM customers WHERE email='anitahvenue@gmail.com' AND role='manager'")->fetch();
    $couple = $pdo->query("SELECT customer_id, password_hash FROM customers WHERE email='Girlbertfamily@gmail.com' AND role='customer'")->fetch();
    $otherCouple = $pdo->query("SELECT customer_id FROM customers WHERE email='alice.family@gmail.com' AND role='customer'")->fetch();
    $venue = $pdo->query('SELECT venue_id, name, price_per_day FROM venues WHERE available=1 ORDER BY venue_id LIMIT 1')->fetch();
    if (!$manager || !$couple || !$otherCouple || !$venue) {
        throw new RuntimeException('Seed data is incomplete. Import database/schema.sql and database/seed.sql first.');
    }

    $portSocket = stream_socket_server('tcp://127.0.0.1:0', $socketError, $socketMessage);
    if (!$portSocket) {
        throw new RuntimeException('Cannot reserve a local port for the PHP test server: ' . $socketMessage);
    }
    $socketAddress = stream_socket_get_name($portSocket, false);
    fclose($portSocket);
    $port = (int)substr(strrchr((string)$socketAddress, ':'), 1);
    $base = "http://{$host}:{$port}";

    $findFreeDate = static function (PDO $pdo, int $venueId, array $exclude = []): string {
        for ($offset = 500; $offset < 5000; $offset++) {
            $date = (new DateTimeImmutable('today'))->modify('+' . $offset . ' days')->format('Y-m-d');
            if (in_array($date, $exclude, true)) {
                continue;
            }
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM bookings WHERE venue_id=:venue_id AND event_date=:event_date AND status<>'cancelled'"
            );
            $stmt->execute(['venue_id' => $venueId, 'event_date' => $date]);
            if ((int)$stmt->fetchColumn() === 0) {
                return $date;
            }
        }
        throw new RuntimeException('Could not find an unused future date for booking tests.');
    };

    $conflictDate = $findFreeDate($pdo, (int)$venue['venue_id']);
    $otherBookingDate = $findFreeDate($pdo, (int)$venue['venue_id'], [$conflictDate]);
    $insertFixture = $pdo->prepare(
        "INSERT INTO bookings (venue_id, customer_id, manager_id, event_date, status)
         VALUES (:venue_id, :customer_id, :manager_id, :event_date, 'pending')"
    );
    $insertFixture->execute([
        'venue_id' => (int)$venue['venue_id'],
        'customer_id' => (int)$couple['customer_id'],
        'manager_id' => (int)$manager['customer_id'],
        'event_date' => $conflictDate,
    ]);
    $temporaryBookingIds[] = (int)$pdo->lastInsertId();
    $otherBookingId = (int)$pdo->lastInsertId();
    $insertFixture->execute([
        'venue_id' => (int)$venue['venue_id'],
        'customer_id' => (int)$otherCouple['customer_id'],
        'manager_id' => (int)$manager['customer_id'],
        'event_date' => $otherBookingDate,
    ]);
    $temporaryBookingIds[] = (int)$pdo->lastInsertId();
    $otherBookingId = (int)$pdo->lastInsertId();

    $process = proc_open(
        [PHP_BINARY, '-S', "{$host}:{$port}", '-t', $public, $public . DIRECTORY_SEPARATOR . 'index.php'],
        [0 => ['file', 'NUL', 'r'], 1 => ['file', 'NUL', 'a'], 2 => ['file', 'NUL', 'a']],
        $pipes,
        $project
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start PHP test server.');
    }

    $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $connection = @stream_socket_client("tcp://{$host}:{$port}", $errorNumber, $errorMessage, 0.2);
        if (is_resource($connection)) {
            fclose($connection);
            $ready = true;
            break;
        }
        usleep(100000);
    }
    if (!$ready) {
        throw new RuntimeException('PHP test server did not become ready.');
    }

    $routeUrl = static function (string $path, array $query = []) use ($base): string {
        return $base . '/index.php?' . http_build_query(['_route' => $path] + $query);
    };
    $csrfFrom = static function (array $response): string {
        return preg_match('/name="_csrf"\s+value="([^"]+)"/', $response['body'], $match) ? $match[1] : '';
    };
    $login = static function (HttpClient $client, string $email, string $password) use ($routeUrl, $csrfFrom): array {
        $form = $client->request($routeUrl('/login'));
        return $client->request($routeUrl('/login/process'), 'POST', [
            '_csrf' => $csrfFrom($form),
            'email' => $email,
            'password' => $password,
        ]);
    };

    $home = (new HttpClient())->request($routeUrl('/'));
    $check('Home page loads', $home['status'] === 200 && str_contains($home['body'], 'Urukundo'), 'HTTP ' . $home['status']);
    $check('Security headers are present', in_array('X-Content-Type-Options: nosniff', $home['headers'], true), 'nosniff header checked');

    $publicClient = new HttpClient();
    $venues = $publicClient->request($routeUrl('/venues'));
    $check('Venue catalogue renders seeded listing', $venues['status'] === 200 && str_contains($venues['body'], 'Kigali Garden Pavilion'), 'HTTP ' . $venues['status']);
    $api = $publicClient->request($routeUrl('/api/venues', ['q' => 'Kigali']));
    $payload = json_decode($api['body'], true);
    $check('Venue API returns matching records', $api['status'] === 200 && is_array($payload) && ($payload['ok'] ?? false) && count($payload['data']) > 0, 'HTTP ' . $api['status']);
    $emptySearch = $publicClient->request($routeUrl('/api/venues', ['q' => 'NoSuchVenue987654']));
    $payload = json_decode($emptySearch['body'], true);
    $check('Unmatched venue search returns an empty list', $emptySearch['status'] === 200 && is_array($payload) && ($payload['data'] ?? null) === [], 'HTTP ' . $emptySearch['status']);

    $anonymous = new HttpClient();
    $anonymousBookings = $anonymous->request($routeUrl('/bookings'));
    $check('Anonymous booking access redirects to sign-in', $anonymousBookings['status'] === 303 && str_contains(rawurldecode(implode("\n", $anonymousBookings['headers'])), '/login'), 'HTTP ' . $anonymousBookings['status']);

    $registration = new HttpClient();
    $registerForm = $registration->request($routeUrl('/register'));
    $invalidRegistration = $registration->request($routeUrl('/register/process'), 'POST', [
        '_csrf' => $csrfFrom($registerForm),
        'username' => 'x',
        'email' => 'not-an-email',
        'password' => 'short',
        'confirm_password' => 'different',
    ]);
    $invalidRegistrationPage = $registration->request($routeUrl('/register'));
    $check('Invalid registration fields are rejected', $invalidRegistration['status'] === 303 && str_contains($invalidRegistrationPage['body'], 'valid email'), 'validation message shown');

    $duplicateForm = $registration->request($routeUrl('/register'));
    $duplicateRegistration = $registration->request($routeUrl('/register/process'), 'POST', [
        '_csrf' => $csrfFrom($duplicateForm),
        'username' => 'unused_test_username',
        'email' => 'Girlbertfamily@gmail.com',
        'password' => 'temporary-password',
        'confirm_password' => 'temporary-password',
    ]);
    $duplicateCount = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE email='Girlbertfamily@gmail.com'")->fetchColumn();
    $check('Duplicate customer email is rejected', $duplicateRegistration['status'] === 303 && $duplicateCount === 1, "{$duplicateCount} seeded account");

    $temporaryCustomerEmail = 'test-' . bin2hex(random_bytes(6)) . '@example.test';
    $temporaryUsername = 'test_' . bin2hex(random_bytes(5));
    $validRegisterForm = $registration->request($routeUrl('/register'));
    $validRegistration = $registration->request($routeUrl('/register/process'), 'POST', [
        '_csrf' => $csrfFrom($validRegisterForm),
        'username' => $temporaryUsername,
        'email' => $temporaryCustomerEmail,
        'password' => 'temporary-password-123',
        'confirm_password' => 'temporary-password-123',
        'groom_name' => 'Test Groom',
        'bride_name' => 'Test Bride',
        'phone' => '+250700000000',
    ]);
    $registeredStmt = $pdo->prepare('SELECT password_hash FROM customers WHERE email=:email');
    $registeredStmt->execute(['email' => $temporaryCustomerEmail]);
    $registeredHash = $registeredStmt->fetchColumn();
    $check('Valid registration creates a hashed customer', $validRegistration['status'] === 303 && is_string($registeredHash) && password_verify('temporary-password-123', $registeredHash), 'temporary account verified');

    $wrongPassword = new HttpClient();
    $wrongLogin = $login($wrongPassword, 'Girlbertfamily@gmail.com', 'incorrect-password');
    $wrongLoginPage = $wrongPassword->request($routeUrl('/login'));
    $check('Incorrect password is rejected with a generic error', $wrongLogin['status'] === 303 && str_contains($wrongLoginPage['body'], 'Email or password is incorrect'), 'HTTP ' . $wrongLogin['status']);

    $coupleClient = new HttpClient();
    $coupleLogin = $login($coupleClient, 'Girlbertfamily@gmail.com', 'password');
    $check('Prepared couple account signs in', $coupleLogin['status'] === 303, 'HTTP ' . $coupleLogin['status']);
    $weddings = $coupleClient->request($routeUrl('/my-weddings'));
    $check('Couple wedding dashboard loads', $weddings['status'] === 200 && str_contains($weddings['body'], 'My Weddings') && str_contains($weddings['body'], 'Budget'), 'HTTP ' . $weddings['status']);
    $coupleBookings = $coupleClient->request($routeUrl('/bookings'));
    $check('Couple booking list is scoped to their account', $coupleBookings['status'] === 200 && str_contains($coupleBookings['body'], 'Your celebrations'), 'HTTP ' . $coupleBookings['status']);
    $coupleVenueForm = $coupleClient->request($routeUrl('/venue/form'));
    $check('Couple is denied venue management', $coupleVenueForm['status'] === 403, 'HTTP ' . $coupleVenueForm['status']);

    $bookingForm = $coupleClient->request($routeUrl('/booking/form'));
    $pastDateResponse = $coupleClient->request($routeUrl('/booking/save'), 'POST', [
        '_csrf' => $csrfFrom($bookingForm),
        'venue_id' => (string)$venue['venue_id'],
        'event_date' => (new DateTimeImmutable('yesterday'))->format('Y-m-d'),
    ]);
    $pastDateForm = $coupleClient->request($routeUrl('/booking/form'));
    $check('Past event date is rejected', $pastDateResponse['status'] === 303 && str_contains($pastDateForm['body'], 'valid date today or later'), 'date validation shown');

    $conflictForm = $coupleClient->request($routeUrl('/booking/form'));
    $conflictResponse = $coupleClient->request($routeUrl('/booking/save'), 'POST', [
        '_csrf' => $csrfFrom($conflictForm),
        'venue_id' => (string)$venue['venue_id'],
        'event_date' => $conflictDate,
    ]);
    $conflictErrorForm = $coupleClient->request($routeUrl('/booking/form'));
    $check('Duplicate venue and date booking is rejected', $conflictResponse['status'] === 303 && str_contains($conflictErrorForm['body'], 'already has an active booking'), 'conflict validation shown');

    $otherBookingForm = $coupleClient->request($routeUrl('/booking/form', ['id' => $otherBookingId]));
    $check('Couple cannot edit another customer booking', $otherBookingForm['status'] === 404, 'HTTP ' . $otherBookingForm['status']);

    $newBookingDate = $findFreeDate($pdo, (int)$venue['venue_id'], [$conflictDate, $otherBookingDate]);
    $createForm = $coupleClient->request($routeUrl('/booking/form'));
    $createResponse = $coupleClient->request($routeUrl('/booking/save'), 'POST', [
        '_csrf' => $csrfFrom($createForm),
        'venue_id' => (string)$venue['venue_id'],
        'event_date' => $newBookingDate,
    ]);
    $findNewBooking = $pdo->prepare(
        "SELECT booking_id, status FROM bookings WHERE customer_id=:customer_id AND venue_id=:venue_id AND event_date=:event_date"
    );
    $findNewBooking->execute([
        'customer_id' => (int)$couple['customer_id'],
        'venue_id' => (int)$venue['venue_id'],
        'event_date' => $newBookingDate,
    ]);
    $createdBooking = $findNewBooking->fetch();
    if ($createdBooking) {
        $temporaryBookingIds[] = (int)$createdBooking['booking_id'];
    }
    $check('Valid future booking is created pending', $createResponse['status'] === 303 && $createdBooking && $createdBooking['status'] === 'pending', 'booking persisted with pending status');

    $depositRecorded = false;
    if ($createdBooking) {
        $bookingList = $coupleClient->request($routeUrl('/bookings'));
        $payResponse = $coupleClient->request($routeUrl('/booking/pay'), 'POST', [
            '_csrf' => $csrfFrom($bookingList),
            'booking_id' => (string)$createdBooking['booking_id'],
        ]);
        $paymentStmt = $pdo->prepare(
            "SELECT amount FROM payments WHERE booking_id=:booking_id AND status='completed'"
        );
        $paymentStmt->execute(['booking_id' => (int)$createdBooking['booking_id']]);
        $paymentAmount = $paymentStmt->fetchColumn();
        $depositRecorded = $payResponse['status'] === 303
            && $paymentAmount !== false
            && (float)$paymentAmount === round((float)$venue['price_per_day'] * 0.20, 2);
    }
    $check('Coursework deposit records the expected 20 percent', $depositRecorded, 'temporary booking payment checked');

    $duplicateDepositResponse = false;
    $completedPaymentCount = 0;
    if ($createdBooking) {
        $bookingList = $coupleClient->request($routeUrl('/bookings'));
        $duplicateDepositResponse = $coupleClient->request($routeUrl('/booking/pay'), 'POST', [
            '_csrf' => $csrfFrom($bookingList),
            'booking_id' => (string)$createdBooking['booking_id'],
        ]);
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE booking_id=:booking_id AND status='completed'");
        $countStmt->execute(['booking_id' => (int)$createdBooking['booking_id']]);
        $completedPaymentCount = (int)$countStmt->fetchColumn();
    }
    $check('Repeated deposit action does not duplicate payment', is_array($duplicateDepositResponse) && $duplicateDepositResponse['status'] === 303 && $completedPaymentCount === 1, "{$completedPaymentCount} completed payment record(s)");

    $deleteSucceeded = false;
    if ($createdBooking) {
        $bookingList = $coupleClient->request($routeUrl('/bookings'));
        $deleteResponse = $coupleClient->request($routeUrl('/booking/delete'), 'POST', [
            '_csrf' => $csrfFrom($bookingList),
            'booking_id' => (string)$createdBooking['booking_id'],
        ]);
        $existsStmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE booking_id=:booking_id');
        $existsStmt->execute(['booking_id' => (int)$createdBooking['booking_id']]);
        $deleteSucceeded = $deleteResponse['status'] === 303 && (int)$existsStmt->fetchColumn() === 0;
        $temporaryBookingIds = array_values(array_diff($temporaryBookingIds, [(int)$createdBooking['booking_id']]));
    }
    $check('Customer can delete their own booking', $deleteSucceeded, 'booking removed and cascading payment cleaned');

    $csrfFailure = $coupleClient->request($routeUrl('/booking/delete'), 'POST', ['booking_id' => '999999']);
    $check('Booking mutation without CSRF token is rejected', $csrfFailure['status'] === 419, 'HTTP ' . $csrfFailure['status']);

    $managerClient = new HttpClient();
    $managerLogin = $login($managerClient, 'anitahvenue@gmail.com', 'password');
    $managerForm = $managerClient->request($routeUrl('/venue/form'));
    $check('Prepared manager can sign in and open venue form', $managerLogin['status'] === 303 && $managerForm['status'] === 200, 'login/form HTTP ' . $managerLogin['status'] . '/' . $managerForm['status']);
    $managerBookingForm = $managerClient->request($routeUrl('/booking/form'));
    $check('Manager cannot create a couple booking', $managerBookingForm['status'] === 403, 'HTTP ' . $managerBookingForm['status']);

    $logoutForm = $coupleClient->request($routeUrl('/bookings'));
    $logout = $coupleClient->request($routeUrl('/logout'), 'POST', ['_csrf' => $csrfFrom($logoutForm)]);
    $afterLogout = $coupleClient->request($routeUrl('/bookings'));
    $check('Valid logout invalidates the session', $logout['status'] === 303 && $afterLogout['status'] === 303, 'protected route redirects after logout');
    $loggedOutCsrf = $coupleClient->request($routeUrl('/logout'), 'POST', []);
    $check('Logout without CSRF token is rejected', $loggedOutCsrf['status'] === 419, 'HTTP ' . $loggedOutCsrf['status']);

    $freshAnonymous = new HttpClient();
    $noCsrfEmail = 'no-csrf-' . bin2hex(random_bytes(5)) . '@example.test';
    $noCsrfRegistration = $freshAnonymous->request($routeUrl('/register/process'), 'POST', [
        'username' => 'no_csrf_' . bin2hex(random_bytes(4)),
        'email' => $noCsrfEmail,
        'password' => 'temporary-password-123',
        'confirm_password' => 'temporary-password-123',
    ]);
    $noCsrfCount = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE email=:email');
    $noCsrfCount->execute(['email' => $noCsrfEmail]);
    $check('Fresh session cannot register without a CSRF token', $noCsrfRegistration['status'] === 419 && (int)$noCsrfCount->fetchColumn() === 0, 'request rejected before account creation');
} catch (Throwable $error) {
    $runtimeError = $error->getMessage();
} finally {
    if (isset($pdo) && $pdo instanceof PDO) {
        if ($temporaryBookingIds) {
            $placeholders = implode(',', array_fill(0, count($temporaryBookingIds), '?'));
            $cleanupBookings = $pdo->prepare("DELETE FROM bookings WHERE booking_id IN ({$placeholders})");
            $cleanupBookings->execute($temporaryBookingIds);
        }
        if ($temporaryCustomerEmail !== '') {
            $cleanupCustomer = $pdo->prepare('DELETE FROM customers WHERE email=:email');
            $cleanupCustomer->execute(['email' => $temporaryCustomerEmail]);
        }
    }
    if (is_resource($process)) {
        proc_terminate($process);
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        proc_close($process);
    }
}

if (!$tests) {
    fwrite(STDERR, "SETUP ERROR: {$runtimeError}\n");
    exit(2);
}
if ($runtimeError !== '') {
    $check('Test runner stopped unexpectedly', false, $runtimeError);
}
while (count($tests) < 27) {
    $tests[] = ['name' => 'Not run - test runner stopped before this case', 'passed' => false, 'evidence' => ''];
}

foreach ($tests as $index => $test) {
    printf(
        "[%s] TC%02d %s%s\n",
        $test['passed'] ? 'PASS' : 'FAIL',
        $index + 1,
        $test['name'],
        $test['evidence'] !== '' ? ' - ' . $test['evidence'] : ''
    );
}

$failed = count(array_filter($tests, static fn(array $test): bool => !$test['passed']));
printf("\nResult: %d passed, %d failed, %d total\n", count($tests) - $failed, $failed, count($tests));
exit($failed === 0 && count($tests) === 27 ? 0 : 1);
