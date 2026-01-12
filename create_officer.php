<?php
// 1. Database connection
$servername = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbname = "etrackhub";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Officer information
$id_number = "235377";           // New officer's ID number
$name = "Filjoy Adala";                // Officer's full name
$username = "admin2@ics.ph";         // Officer's username
$plainPassword = "admin123";        // Password you want

// 3. Hash the password using bcrypt
$hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

// 4. Prepare SQL statement to prevent SQL injection
$stmt = $conn->prepare("INSERT INTO officers (id_number, name, username, password, role, status) VALUES (?, ?, ?, ?, 'officer', 'Active')");
$stmt->bind_param("ssss", $id_number, $name, $username, $hashedPassword);

// 5. Execute statement
if ($stmt->execute()) {
    echo "New officer added successfully!<br>";
    echo "Username: $username<br>";
    echo "Password: $plainPassword";
} else {
    echo "Error: " . $stmt->error;
}

// 6. Close connection
$stmt->close();
$conn->close();
?>
