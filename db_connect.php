<?php
// Establish the connection to Oracle 10g XE
$username = "event_admin";
$password = "admin123";
$database = "localhost/XE";

$conn = oci_connect($username, $password, $database);

if (!$conn) {
    $e = oci_error();
    die("Database connection failed: " . $e['message']);
}
?>