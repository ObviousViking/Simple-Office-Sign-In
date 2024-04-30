<?php

// Handles the connection to the database
// Can be included on each required page
// Using include 'DB_Connect.php';

// SQLite database file path
$dbPath = __DIR__ . '/../db/checkin.sqlite';  

// Create connection
$db = new SQLite3($dbPath);

// Check connection
if (!$db) {
    die("Connection failed: " . $db->lastErrorMsg());
}
?>
