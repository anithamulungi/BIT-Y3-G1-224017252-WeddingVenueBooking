<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Flash;
use App\Support\View;

final class AuthController {
    public function login(): void {
        View::render('auth/login', [
            'title' => 'Welcome back',
            'error' => Flash::get('error'),
            'old' => Flash::old('email'),
        ]);
        Flash::clearOld();
    }

    public function processLogin(): void {
        $this->postOnly('/login');
        Csrf::require();
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $stmt = Database::pdo()->prepare('SELECT customer_id AS user_id, username, email, password_hash, role FROM customers WHERE email=:email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Flash::set('error', 'Email or password is incorrect.');
            Flash::preserve(['email' => $email]);
            $this->redirect('/login');
        }
        Auth::signIn($user);
        Flash::set('success', 'Welcome back, ' . $user['username'] . '.');
        $this->redirect('/');
    }

    public function register(): void {
        if (Auth::user()) {
            $this->redirect('/');
        }
        View::render('auth/register', [
            'title' => 'Create an account',
            'error' => Flash::get('error'),
            'errors' => Flash::get('errors', []),
            'old' => [
                'username' => Flash::old('username'),
                'email' => Flash::old('email'),
                'groom_name' => Flash::old('groom_name'),
                'bride_name' => Flash::old('bride_name'),
                'phone' => Flash::old('phone'),
            ],
        ]);
        Flash::clearOld();
    }

    public function processRegister(): void {
        $this->postOnly('/register');
        Csrf::require();

        $username = trim((string)($_POST['username'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        $groomName = trim((string)($_POST['groom_name'] ?? ''));
        $brideName = trim((string)($_POST['bride_name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));

        $errors = [];
        if (mb_strlen($username) < 2 || mb_strlen($username) > 60) $errors[] = 'Username must be between 2 and 60 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $errors[] = 'Enter a valid email address.';
        if (mb_strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';
        if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

        if ($errors) {
            Flash::set('errors', $errors);
            Flash::preserve(['username' => $username, 'email' => $email, 'groom_name' => $groomName, 'bride_name' => $brideName, 'phone' => $phone]);
            $this->redirect('/register');
        }

        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO customers (username, email, password_hash, role, groom_name, bride_name, phone)
                 VALUES (:username, :email, :password_hash, :role, :groom_name, :bride_name, :phone)'
            );
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'customer',
                'groom_name' => $groomName ?: null,
                'bride_name' => $brideName ?: null,
                'phone' => $phone ?: null,
            ]);
            $userId = (int)Database::pdo()->lastInsertId();
            Auth::signIn(['user_id' => $userId]);
            Flash::set('success', 'Your account has been created.');
            $this->redirect('/');
        } catch (\PDOException $error) {
            if ((int)$error->getCode() === 23000) {
                Flash::set('errors', ['That username or email is already registered.']);
            } else {
                throw $error;
            }
            Flash::preserve(['username' => $username, 'email' => $email, 'groom_name' => $groomName, 'bride_name' => $brideName, 'phone' => $phone]);
            $this->redirect('/register');
        }
    }

    public function logout(): void {
        $this->postOnly('/');
        Csrf::require();
        Auth::signOut();
        header('Location: ' . View::url('/'), true, 303);
        exit;
    }

    private function postOnly(string $redirect): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect($redirect);
        }
    }

    private function redirect(string $path): never {
        header('Location: ' . View::url($path), true, 303);
        exit;
    }
}
