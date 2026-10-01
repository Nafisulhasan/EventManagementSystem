<?php
// Start the session engine to check who is logged in
session_start();

// SECURITY CHECK: If they haven't logged in, instantly kick them back to index.php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit();
}

// Turn on error reporting to catch SQL issues
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Call the database connection
require 'db_connect.php';

// Write the SQL Query (Notice we pull e.e_id so the Register button knows which event it is)
$sql = "SELECT e.e_id, e.title, TO_CHAR(e.event_date, 'DD-MON-YYYY') AS event_date, e.event_time, v.venue 
        FROM Event e 
        JOIN Hosts h ON e.e_id = h.e_id 
        JOIN Venue v ON h.v_id = v.v_id 
        ORDER BY e.event_date ASC";

// Prepare the query
$stid = oci_parse($conn, $sql);

// ERROR CATCHER: Check if the SQL syntax is valid
if (!$stid) {
    $e = oci_error($conn);
    die("<h2 style='color:red; text-align:center;'>SQL Parse Error: " . htmlentities($e['message']) . "</h2>");
}

// Execute the query
$r = oci_execute($stid);

// ERROR CATCHER: Check if the execution failed
if (!$r) {
    $e = oci_error($stid);
    die("<h2 style='color:red; text-align:center;'>SQL Execution Error: " . htmlentities($e['message']) . "</h2><p style='color:white; text-align:center;'>Check your table and column names!</p>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Events</title>
    <style>
        /* Cyber-Minimalist Dashboard Theme */
        body {
            background-color: #0A0A0A;
            color: #E0E0E0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #333;
            padding-bottom: 20px;
            margin-bottom: 40px;
        }
        .header h1 {
            color: white;
            font-weight: 300;
            margin: 0;
            letter-spacing: 2px;
        }
        .nav-links a {
            color: #00E5FF;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .nav-links a:hover {
            color: #00b3cc;
        }
        .nav-links a.logout-btn {
    background-color: #00E5FF;
    color: #121212 !important; 
    padding: 8px 20px;
    margin-left: 20px;
    text-decoration: none;
    font-weight: bold;
    border-radius: 0px;
    transition: 0.3s;
}
        .logout-btn:hover {
            background-color: #00b3cc;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        .event-card {
            background-color: #1E1E1E;
            border: 1px solid #333;
            padding: 25px;
            position: relative;
            transition: 0.3s;
        }
        .event-card:hover {
            border-color: #00E5FF;
            box-shadow: 0 0 15px rgba(0, 229, 255, 0.1);
        }
        .event-title {
            color: white;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        .event-detail {
            font-size: 13px;
            color: #A0A0A0;
            margin-bottom: 8px;
            display: flex;
        }
        .event-detail strong {
            color: #00E5FF;
            width: 70px;
        }
        .register-btn {
            display: block;
            width: 100%;
            text-align: center;
            padding: 10px;
            margin-top: 20px;
            background: transparent;
            color: #00E5FF;
            border: 1px solid #00E5FF;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            box-sizing: border-box;
        }
        .register-btn:hover {
            background: #00E5FF;
            color: #121212;
        }
        .success-msg {
            background-color: rgba(0, 229, 255, 0.1);
            color: #00E5FF;
            padding: 15px;
            border: 1px solid #00E5FF;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .error-msg {
            background-color: rgba(255, 51, 102, 0.1);
            color: #ff3366;
            padding: 15px;
            border: 1px solid #ff3366;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .no-data {
            color: #ff3366;
            font-size: 18px;
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            border: 1px dashed #333;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>WELCOME, <?php echo htmlspecialchars(strtoupper($_SESSION['user_name'])); ?></h1>
        <div class="nav-links">
            <a href="logout.php" class="logout-btn">LOGOUT</a>
        </div>
    </div>

    <?php 
        if(isset($_GET['status']) && $_GET['status'] == 'success') {
            echo "<div class='success-msg'>SUCCESS: Registration confirmed.</div>";
        } elseif(isset($_GET['status']) && $_GET['status'] == 'exists') {
            echo "<div class='error-msg'>SYSTEM ALERT: You are already registered for this event.</div>";
        }
    ?>

    <div class="grid-container">
        <?php
        $has_events = false;

        // Fetch the data row by row and generate an HTML card for each
        while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
            $has_events = true;
            echo "<div class='event-card'>";
            
            // Using htmlspecialchars for security
            $title = isset($row['TITLE']) ? htmlspecialchars($row['TITLE']) : 'N/A';
            $date = isset($row['EVENT_DATE']) ? htmlspecialchars($row['EVENT_DATE']) : 'TBD';
            $time = isset($row['EVENT_TIME']) ? htmlspecialchars($row['EVENT_TIME']) : 'TBD';
            $venue = isset($row['VENUE']) ? htmlspecialchars($row['VENUE']) : 'TBD';

            echo "<div class='event-title'>" . $title . "</div>";
            echo "<div class='event-detail'><strong>DATE:</strong> " . $date . "</div>";
            echo "<div class='event-detail'><strong>TIME:</strong> " . $time . "</div>";
            echo "<div class='event-detail'><strong>VENUE:</strong> " . $venue . "</div>";
            
            // The button dynamically sends the Event ID (e_id) to the processor script
            echo "<a href='process_registration.php?e_id=" . $row['E_ID'] . "' class='register-btn'>REGISTER NOW</a>";
            echo "</div>";
        }

        if (!$has_events) {
            echo "<div class='no-data'>No events currently found in the database.</div>";
        }

        // Free the resources
        oci_free_statement($stid);
        oci_close($conn);
        ?>
    </div>

</body>
</html>