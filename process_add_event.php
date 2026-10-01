<?php
session_start();
require 'db_connect.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $e_id = $_POST['e_id'];
    $title = $_POST['title'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];

    // Insert into Oracle Event table
    // We use TO_DATE to ensure Oracle reads the date string correctly
    $sql = "INSERT INTO Event (e_id, title, event_date, event_time) 
            VALUES (:eid, :title, TO_DATE(:edate, 'DD-MON-YYYY'), :etime)";
            
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ':eid', $e_id);
    oci_bind_by_name($stid, ':title', $title);
    oci_bind_by_name($stid, ':edate', $event_date);
    oci_bind_by_name($stid, ':etime', $event_time);

    $result = oci_execute($stid);

    if ($result) {
        oci_commit($conn);
        header("Location: admin_dashboard.php?status=added");
    } else {
        $e = oci_error($stid);
        die("Error adding event: " . htmlentities($e['message']));
    }

    oci_free_statement($stid);
    oci_close($conn);
}
?>