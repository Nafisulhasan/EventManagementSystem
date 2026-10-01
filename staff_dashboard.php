<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: index.php");
    exit();
}
require 'db_connect.php';

$staff_id = $_SESSION['user_id'];
$status_msg = "";

// ==========================================
// 1. HANDLE ADDING NEW PHONE NUMBER (POST)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_number'])) {
    $new_number = trim($_POST['new_number']);
    
    $ins_sql = "INSERT INTO Staff_Phone (staff_id, phone_number) VALUES (:sid, :pnum)";
    $ins_stid = oci_parse($conn, $ins_sql);
    oci_bind_by_name($ins_stid, ':sid', $staff_id);
    oci_bind_by_name($ins_stid, ':pnum', $new_number);
    
    if (oci_execute($ins_stid)) {
        oci_commit($conn);
        $status_msg = "<div class='success-msg'>SUCCESS: Contact number added.</div>";
    } else {
        $status_msg = "<div class='error-msg'>ERROR: Number already exists or invalid.</div>";
    }
    oci_free_statement($ins_stid);
}

// ==========================================
// 2. HANDLE DELETING PHONE NUMBER (GET)
// ==========================================
if (isset($_GET['delete_number'])) {
    $del_number = $_GET['delete_number'];
    
    $del_sql = "DELETE FROM Staff_Phone WHERE staff_id = :sid AND phone_number = :pnum";
    $del_stid = oci_parse($conn, $del_sql);
    oci_bind_by_name($del_stid, ':sid', $staff_id);
    oci_bind_by_name($del_stid, ':pnum', $del_number);
    
    if (oci_execute($del_stid)) {
        oci_commit($conn);
        $status_msg = "<div class='success-msg'>SUCCESS: Contact number removed.</div>";
    }
    oci_free_statement($del_stid);
}

// ==========================================
// 3. FETCH DATA FOR UI
// ==========================================
// A. Fetch Assigned Events for this Staff Member
$event_sql = "SELECT e.title, TO_CHAR(e.event_date, 'Month YYYY') AS edate_month, 
              TO_CHAR(e.event_date, 'DD-MON-YYYY') AS edate_full, e.event_time, v.venue 
              FROM Event e 
              JOIN Event_Staff es ON e.e_id = es.e_id 
              JOIN Hosts h ON e.e_id = h.e_id 
              JOIN Venue v ON h.v_id = v.v_id 
              WHERE es.staff_id = :sid 
              ORDER BY e.event_date ASC";
$event_stid = oci_parse($conn, $event_sql);
oci_bind_by_name($event_stid, ':sid', $staff_id);
oci_execute($event_stid);

// B. Fetch Phone Numbers for this Staff Member
$phone_sql = "SELECT phone_number FROM Staff_Phone WHERE staff_id = :sid ORDER BY phone_number ASC";
$phone_stid = oci_parse($conn, $phone_sql);
oci_bind_by_name($phone_stid, ':sid', $staff_id);
oci_execute($phone_stid);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - AIUB</title>
    <style>
        body { background-color: #050505; color: #E0E0E0; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; display: flex; }
        
        /* STAFF SIDEBAR */
        .sidebar { width: 260px; background-color: #000000; border-right: 1px solid #00E5FF; display: flex; flex-direction: column; justify-content: space-between; padding: 30px 0; box-sizing: border-box; position: fixed; height: 100vh; left: 0; top: 0; }
        .sidebar-header { padding: 0 30px; margin-bottom: 40px; }
        .sidebar-header .role { color: #00E5FF; font-size: 12px; font-weight: bold; display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        .sidebar-header h2 { color: #FFFFFF; font-size: 20px; margin: 0; font-weight: 400; border-bottom: 1px solid #333; padding-bottom: 20px; }
        .nav-links { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        .nav-links a { display: block; padding: 15px 30px; color: #FFFFFF; text-decoration: none; font-size: 14px; font-weight: bold; letter-spacing: 1px; border-left: 3px solid transparent; }
        .nav-links a.active { border-left: 3px solid #00E5FF; background-color: rgba(0, 229, 255, 0.05); color: #00E5FF; }
        .sidebar-footer { padding: 0 30px; }
        .logout-btn { display: inline-block; background-color: #00E5FF; color: #000000; padding: 10px 20px; text-decoration: none; font-weight: bold; font-size: 12px; transition: 0.3s; }
        .logout-btn:hover { background-color: #00b3cc; }

        /* MAIN CONTENT */
        .main-content { margin-left: 260px; padding: 50px; width: calc(100% - 260px); box-sizing: border-box; }
        .page-header { margin-bottom: 40px; }
        .page-header h4 { color: #00E5FF; font-size: 12px; letter-spacing: 2px; margin: 0 0 10px 0; text-transform: uppercase; }
        .page-header h1 { color: #FFFFFF; font-size: 28px; margin: 0; font-weight: bold; }
        
        .split-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start; }

        /* LEFT PANEL: UPCOMING EVENTS */
        .event-panel { background-color: #0A0A0A; border: 1px solid #1E1E1E; border-radius: 8px; padding: 30px; }
        .event-panel h3 { color: white; font-size: 14px; margin-top: 0; margin-bottom: 20px; }
        .event-card { border: 1px solid #333; padding: 20px; margin-bottom: 15px; border-radius: 6px; display: grid; grid-template-columns: 2fr 1fr; gap: 20px; transition: 0.3s; }
        .event-card:hover { border-color: #00E5FF; }
        .event-info h4 { color: white; font-size: 14px; margin: 0 0 10px 0; text-transform: uppercase; }
        .event-info p { margin: 0 0 5px 0; font-size: 11px; color: #888; display: flex; align-items: center; gap: 5px; }
        .event-info p span { color: #00E5FF; }
        .event-time { text-align: right; border-left: 1px solid #333; padding-left: 20px; }
        .event-time p { color: #A0A0A0; font-size: 11px; margin: 0 0 5px 0; }
        .event-time h5 { color: white; font-size: 13px; margin: 0; }

        /* RIGHT PANEL: PROFILE SETTINGS */
        .profile-panel { background-color: #0A0A0A; border: 1px solid #1E1E1E; border-radius: 8px; padding: 30px; }
        .profile-panel h3 { color: white; font-size: 14px; margin-top: 0; margin-bottom: 20px; }
        .profile-name { font-size: 16px; color: #00E5FF; font-weight: bold; border-bottom: 1px solid #333; padding-bottom: 15px; margin-bottom: 20px; text-transform: uppercase; }
        
        .contact-list { margin-bottom: 25px; }
        .contact-list h4 { color: white; font-size: 13px; margin-top: 0; margin-bottom: 15px; }
        .contact-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #1E1E1E; }
        .contact-item span { color: #E0E0E0; font-size: 13px; }
        .delete-icon { color: #ff3366; text-decoration: none; font-size: 16px; transition: 0.3s; }
        .delete-icon:hover { color: #cc0033; }

        .form-group input { width: 100%; padding: 12px; background-color: transparent; border: 1px solid #333; color: #FFFFFF; box-sizing: border-box; outline: none; margin-bottom: 10px; transition: 0.3s; }
        .form-group input:focus { border-color: #00E5FF; }
        .btn-cyan { width: 100%; background-color: #00E5FF; color: #000000; border: none; padding: 12px; font-weight: bold; font-size: 12px; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-cyan:hover { background-color: #00b3cc; }

        .success-msg { padding: 10px; margin-bottom: 20px; text-align: center; font-size: 12px; font-weight: bold; color: #00E5FF; border: 1px solid #00E5FF; background: rgba(0,229,255,0.1); }
        .error-msg { padding: 10px; margin-bottom: 20px; text-align: center; font-size: 12px; font-weight: bold; color: #ff3366; border: 1px solid #ff3366; background: rgba(255,51,102,0.1); }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="sidebar-header">
                <div class="role">&#128100; STAFF</div>
                <h2>Dashboard</h2>
            </div>
            <ul class="nav-links">
                <li><a href="staff_dashboard.php" class="active">OVERVIEW</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h4>EVENT MANAGEMENT</h4>
            <h1>Staff Operations Dashboard</h1>
        </div>

        <?php echo $status_msg; ?>

        <div class="split-layout">
            
            <div class="event-panel">
                <h3>Upcoming Events</h3>
                <?php
                $has_events = false;
                while ($e_row = oci_fetch_array($event_stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
                    $has_events = true;
                    echo "<div class='event-card'>";
                    echo "<div class='event-info'>";
                    echo "<h4>" . htmlspecialchars($e_row['TITLE']) . "</h4>";
                    echo "<p><span>&#128197;</span> " . htmlspecialchars($e_row['EDATE_MONTH']) . "</p>";
                    echo "<p><span>&#128205;</span> " . htmlspecialchars($e_row['VENUE']) . "</p>";
                    echo "</div>";
                    echo "<div class='event-time'>";
                    echo "<p>Date & Time</p>";
                    echo "<h5>" . htmlspecialchars($e_row['EDATE_FULL']) . "</h5>";
                    echo "<h5>" . htmlspecialchars($e_row['EVENT_TIME']) . "</h5>";
                    echo "</div>";
                    echo "</div>";
                }
                if (!$has_events) {
                    echo "<p style='color:#888; font-size:13px;'>You have no assigned events.</p>";
                }
                oci_free_statement($event_stid);
                ?>
            </div>

            <div class="profile-panel">
                <h3>Profile Settings</h3>
                <div class="profile-name">
                    Staff_<?php echo htmlspecialchars($_SESSION['user_id']); ?> <br>
                    <span style="color:white; font-size: 12px; font-weight: normal;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
                
                <div class="contact-list">
                    <h4>Contact Number</h4>
                    <?php
                    while ($p_row = oci_fetch_array($phone_stid, OCI_ASSOC)) {
                        echo "<div class='contact-item'>";
                        echo "<span>" . htmlspecialchars($p_row['PHONE_NUMBER']) . "</span>";
                        // Delete icon (Unicode Trash Can)
                        echo "<a href='staff_dashboard.php?delete_number=" . $p_row['PHONE_NUMBER'] . "' class='delete-icon' onclick=\"return confirm('Delete this number?');\">&#128465;</a>";
                        echo "</div>";
                    }
                    oci_free_statement($phone_stid);
                    oci_close($conn);
                    ?>
                </div>

                <form action="staff_dashboard.php" method="POST">
                    <div class="form-group">
                        <input type="text" name="new_number" placeholder="New Number" required autocomplete="off">
                    </div>
                    <button type="submit" name="add_number" class="btn-cyan">ADD NUMBER</button>
                </form>
            </div>

        </div>
    </div>

</body>
</html>