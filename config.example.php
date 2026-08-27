<?php

// Copy this file to config.php and fill in your database credentials
$DB_HOST = 'localhost';
$DB_USER = 'your_db_username';
$DB_PASS = 'your_db_password';
$DB_NAME = 'your_db_name';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
