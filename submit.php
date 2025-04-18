<?php
header('Content-Type: application/json');

// Database configuration
$host = 'YOUR_FREEDB_HOST'; // e.g., sql.freedb.tech
$dbname = 'YOUR_FREEDB_DATABASE'; // e.g., freedb_automax
$username = 'YOUR_FREEDB_USERNAME';
$password = 'YOUR_FREEDB_PASSWORD';

// Validate input data
function validateInput($data) {
    $errors = [];

    if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
        $errors[] = 'Name must be at least 2 characters long.';
    }

    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($data['whatsapp']) || !preg_match('/^\d{10}$/', $data['whatsapp'])) {
        $errors[] = 'WhatsApp number must be 10 digits.';
    }

    if (empty($data['department']) || strlen(trim($data['department'])) < 2) {
        $errors[] = 'Department must be at least 2 characters long.';
    }

    if (empty($data['year']) || !in_array($data['year'], ['1st', '2nd', '3rd', '4th'])) {
        $errors[] = 'Please select a valid year.';
    }

    return $errors;
}

// Initialize response
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
        'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
        'whatsapp' => isset($_POST['whatsapp']) ? trim($_POST['whatsapp']) : '',
        'department' => isset($_POST['department']) ? trim($_POST['department']) : '',
        'year' => isset($_POST['year']) ? trim($_POST['year']) : ''
    ];

    // Validate inputs
    $errors = validateInput($input);

    if (empty($errors)) {
        try {
            // Connect to the database
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            // Insert registration data
            $stmt = $pdo->prepare('INSERT INTO registrations (name, email, whatsapp, department, year) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$input['name'], $input['email'], $input['whatsapp'], $input['department'], $input['year']]);

            $response['success'] = true;
            $response['message'] = 'Registration successful! We will contact you soon.';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $response['message'] = 'This email is already registered.';
            } else {
                $response['message'] = 'Database error: Unable to save registration.';
                error_log('PDOException: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
            error_log('Exception: ' . $e->getMessage());
        }
    } else {
        $response['message'] = implode(' ', $errors);
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit;
?>