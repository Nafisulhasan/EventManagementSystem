<?php
session_start();
// Security check: Kick out anyone who isn't an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}
require 'db_connect.php';

// --- DATABASE ANALYTICS QUERIES ---

// 1. Count Total Events
$sql1 = "SELECT COUNT(e_id) AS total_events FROM Event";
$stid1 = oci_parse($conn, $sql1);
oci_execute($stid1);
$row1 = oci_fetch_array($stid1, OCI_ASSOC);
$total_events = $row1 ? $row1['TOTAL_EVENTS'] : 0;
oci_free_statement($stid1);

// 2. Count Active Events (Events that have a venue assigned in the Hosts table)
$sql2 = "SELECT COUNT(DISTINCT e_id) AS active_events FROM Hosts";
$stid2 = oci_parse($conn, $sql2);
oci_execute($stid2);
$row2 = oci_fetch_array($stid2, OCI_ASSOC);
$active_events = $row2 ? $row2['ACTIVE_EVENTS'] : 0;
oci_free_statement($stid2);

// 3. Count Total Student Registrations
$sql3 = "SELECT COUNT(*) AS total_regs FROM Register";
$stid3 = oci_parse($conn, $sql3);
oci_execute($stid3);
$row3 = oci_fetch_array($stid3, OCI_ASSOC);
$total_regs = $row3 ? $row3['TOTAL_REGS'] : 0;
oci_free_statement($stid3);

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AIUB</title>
    <style>
        /* General Layout */
        body {
            background-color: #050505; /* Deepest black for UI matching */
            color: #E0E0E0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
        }
        
        /* Main Content Area (Offset by the 260px Sidebar) */
        .main-content {
            margin-left: 260px; 
            padding: 50px;
            width: calc(100% - 260px);
            box-sizing: border-box;
        }

        /* Header Styling */
        .page-header {
            margin-bottom: 40px;
        }
        .page-header h4 {
            color: #00E5FF;
            font-size: 12px;
            letter-spacing: 2px;
            margin: 0 0 10px 0;
            text-transform: uppercase;
        }
        .page-header h1 {
            color: #FFFFFF;
            font-size: 28px;
            margin: 0;
            font-weight: bold;
        }

        /* Stat Cards Grid */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        .stat-card {
            background-color: #0A0A0A;
            border: 1px solid #1E1E1E;
            padding: 30px;
            transition: 0.3s;
        }
        .stat-card:hover {
            border-color: #00E5FF;
            box-shadow: 0 0 15px rgba(0, 229, 255, 0.05);
        }
        .stat-title {
            color: #A0A0A0;
            font-size: 12px;
            letter-spacing: 1px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        .stat-number {
            color: #00E5FF;
            font-size: 48px;
            font-weight: bold;
            margin: 0;
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h4>OVERVIEW</h4>
            <h1>System Dashboard</h1>
        </div>

        <div class="stat-grid">
            
            <div class="stat-card">
                <div class="stat-title">TOTAL EVENTS</div>
                <div class="stat-number"><?php echo $total_events; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-title">ACTIVE EVENTS</div>
                <div class="stat-number"><?php echo $active_events; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-title">REGISTRATIONS</div>
                <div class="stat-number"><?php echo $total_regs; ?></div>
            </div>

        </div>
    </div>

</body>
</html>