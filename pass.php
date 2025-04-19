<?php
// Password to hash
$password = "admin123"; // Replace this with the plaintext password
// Generate hashed password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
echo "Hashed Password: " . $hashedPassword;
?>