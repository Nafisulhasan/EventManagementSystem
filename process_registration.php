<?php
session_start();
require 'db_connect.php';

// Security: Ensure the user is logged in and an event ID was passed
if (!isset($_SESSION['user_id']) || !isset($_GET['e_id'])) {
    header("Location: index.php");
    exit();
}

$s_id = $_SESSION['user_id'];
$e_id = $_GET['e_id'];

// 1. CHECK IF ALREADY REGISTERED
$check_sql = "SELECT * FROM Register WHERE s_id = :sid AND e_id = :eid";
$check_stmt = oci_parse($conn, $check_sql);
oci_bind_by_name($check_stmt, ':sid', $s_id);
oci_bind_by_name($check_stmt, ':eid', $e_id);
oci_execute($check_stmt);

if (oci_fetch_array($check_stmt, OCI_ASSOC)) {
    // Already registered, send them back with an error
    header("Location: student_dashboard.php?status=exists");
    exit();
}

// 2. INSERT THE NEW REGISTRATION
$insert_sql = "INSERT INTO Register (s_id, e_id) VALUES (:sid, :eid)";
$insert_stmt = oci_parse($conn, $insert_sql);
oci_bind_by_name($insert_stmt, ':sid', $s_id);
oci_bind_by_name($insert_stmt, ':eid', $e_id);

$result = oci_execute($insert_stmt);

if ($result) {
    // Save the changes permanently to Oracle
    oci_commit($conn);
    header("Location: student_dashboard.php?status=success");
} else {
    echo "Database Error occurred.";
}

oci_free_statement($check_stmt);
oci_free_statement($insert_stmt);
oci_close($conn);
?>