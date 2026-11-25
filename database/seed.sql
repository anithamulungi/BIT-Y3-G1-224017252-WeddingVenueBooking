USE wedding_venue_booking;

INSERT INTO customers (customer_id, username, email, password_hash, role, groom_name, bride_name, phone, budget_total, budget_spent)
VALUES
  (1, 'venue manager', 'anitahvenue@gmail.com', '$2y$10$hUxbzEyqRHdkW2VD/Bna6O.qOCT64IDm3c7yhD4mgpaUYomZtPoWm', 'manager', NULL, NULL, NULL, 0.00, 0.00),
  (2, 'Girlbert''s_family', 'Girlbertfamily@gmail.com', '$2y$10$hUxbzEyqRHdkW2VD/Bna6O.qOCT64IDm3c7yhD4mgpaUYomZtPoWm', 'customer', 'TWIZERIMANA Girlbert', 'MUKANTWARI Anitha', '+250 788 000 001', 15000000.00, 4200000.00),
  (3, 'alice_family', 'alice.family@gmail.com', '$2y$10$hUxbzEyqRHdkW2VD/Bna6O.qOCT64IDm3c7yhD4mgpaUYomZtPoWm', 'customer', 'Alice Family', 'Alice Mukamana', '+250 788 000 003', 12000000.00, 3000000.00),
  (4, 'bob_family', 'bob.family@gmail.com', '$2y$10$hUxbzEyqRHdkW2VD/Bna6O.qOCT64IDm3c7yhD4mgpaUYomZtPoWm', 'customer', 'Bob Family', 'Bob Uwase', '+250 788 000 004', 10000000.00, 2500000.00)
ON DUPLICATE KEY UPDATE
  username = VALUES(username),
  email = VALUES(email),
  password_hash = VALUES(password_hash),
  role = VALUES(role),
  groom_name = VALUES(groom_name),
  bride_name = VALUES(bride_name),
  phone = VALUES(phone),
  budget_total = VALUES(budget_total),
  budget_spent = VALUES(budget_spent);

INSERT INTO venues (name, location, capacity, price_per_day, available, description)
VALUES
  ('Kigali Garden Pavilion', 'Kigali, Rwanda', 180, 2400000.00, 1, 'A leafy Kigali celebration garden with a shaded ceremony lawn, local flower arrangements and a spacious reception pavilion.'),
  ('Lake Kivu Wedding Hall', 'Rubavu, Rwanda', 240, 3200000.00, 1, 'A lakeside celebration space in Rubavu with Lake Kivu views, a broad dance floor and an open-air reception terrace.'),
  ('Huye Heritage Courtyard', 'Huye, Rwanda', 120, 1650000.00, 1, 'A welcoming Southern Province courtyard with garden seating and a relaxed setting for a family wedding celebration.'),
  ('Musanze Volcano View Gardens', 'Musanze, Rwanda', 300, 4100000.00, 1, 'A spacious Northern Province garden venue with views of the Virunga volcanoes, landscaped grounds and a large reception hall.'),
  ('Nyungwe Forest Celebration Hall', 'Nyamagabe, Rwanda', 90, 1950000.00, 1, 'An intimate Southern Province gathering hall with green highland scenery, garden details and flexible family seating.')
ON DUPLICATE KEY UPDATE
  location = VALUES(location),
  capacity = VALUES(capacity),
  price_per_day = VALUES(price_per_day),
  available = VALUES(available),
  description = VALUES(description);

INSERT INTO bookings (booking_id, venue_id, customer_id, manager_id, event_date, status)
VALUES
  (3, 1, 2, 1, '2027-06-12', 'confirmed'),
  (4, 2, 2, 1, '2027-09-18', 'pending')
ON DUPLICATE KEY UPDATE
  venue_id = VALUES(venue_id),
  customer_id = VALUES(customer_id),
  manager_id = VALUES(manager_id),
  event_date = VALUES(event_date),
  status = VALUES(status);

INSERT INTO payments (booking_id, amount, payment_method, status)
SELECT 3, ROUND(price_per_day * 0.20, 2), 'Mobile Money', 'completed'
FROM venues WHERE venue_id = 1
AND NOT EXISTS (SELECT 1 FROM payments WHERE booking_id = 3);

INSERT INTO payments (booking_id, amount, payment_method, status)
SELECT 4, 0.00, 'Mobile Money', 'pending'
WHERE NOT EXISTS (SELECT 1 FROM payments WHERE booking_id = 4);

INSERT INTO reviews (customer_id, venue_id, rating, comment)
SELECT 2, 1, 5, 'Beautiful venue and excellent support.'
WHERE NOT EXISTS (
  SELECT 1 FROM reviews
  WHERE customer_id = 2 AND venue_id = 1
);
