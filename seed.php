<?php
include 'includes/db.php';

$username = 'UyandaTest'; 
$password = 'password123';
$email = 'uyanda@eduvos.ac.za';
$role = 'user';

// Create the hash using PHP's built-in tool
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);

if ($stmt->execute()) {
    echo "SUCCESS: Test user '$username' created with password '$password'";
} else {
    echo "ERROR: " . $stmt->error;
}
?>