<?php
require_once 'config.php';

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->bind_param("s", $hash);

if ($stmt->execute()) {
    echo "Password updated successfully to 'admin123'.<br>";
} else {
    echo "Error updating password: " . $conn->error . "<br>";
}

echo "<a href='login.php'>Go to Login</a>";
?>
