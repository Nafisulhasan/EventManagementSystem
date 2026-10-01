<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}
require 'db_connect.php';

$status_msg = "";

// ==========================================
// 1. HANDLE EVENT CREATION (POST REQUEST)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_event'])) {
    $title = trim($_POST['title']);
    $v_id = $_POST['venue'];
    $event_date = $_POST['event_date']; // HTML5 date input sends YYYY-MM-DD
    $event_time = trim($_POST['event_time']);

    // Step A: Auto-generate the next available Event ID
    $id_sql = "SELECT NVL(MAX(e_id), 400) + 1 AS NEXT_ID FROM Event";
    $id_stid = oci_parse($conn, $id_sql);
    oci_execute($id_stid);
    $id_row = oci_fetch_array($id_stid, OCI_ASSOC);
    $new_e_id = $id_row['NEXT_ID'];
    oci_free_statement($id_stid);

    // Step B: Insert into the Event table
    $ins_event = "INSERT INTO Event (e_id, title, event_date, event_time) 
                  VALUES (:eid, :title, TO_DATE(:edate, 'YYYY-MM-DD'), :etime)";
    $st_event = oci_parse($conn, $ins_event);
    oci_bind_by_name($st_event, ':eid', $new_e_id);
    oci_bind_by_name($st_event, ':title', $title);
    oci_bind_by_name($st_event, ':edate', $event_date);
    oci_bind_by_name($st_event, ':etime', $event_time);
    
    if (oci_execute($st_event)) {
        // Step C: Link the Event to the Venue in the Hosts table
        $ins_hosts = "INSERT INTO Hosts (e_id, v_id) VALUES (:eid, :vid)";
        $st_hosts = oci_parse($conn, $ins_hosts);
        oci_bind_by_name($st_hosts, ':eid', $new_e_id);
        oci_bind_by_name($st_hosts, ':vid', $v_id);
        oci_execute($st_hosts);
        
        oci_commit($conn);
        $status_msg = "<div class='success-msg'>SUCCESS: Event created successfully!</div>";
        oci_free_statement($st_hosts);
    } else {
        $e = oci_error($st_event);
        $status_msg = "<div class='error-msg'>ERROR: " . htmlentities($e['message']) . "</div>";
    }
    oci_free_statement($st_event);
}

// ==========================================
// 2. HANDLE EVENT DELETION (GET REQUEST)
// ==========================================
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];

    // To prevent Foreign Key constraint crashes, we must delete children first!
    // A: Remove from Register table
    $del_reg = oci_parse($conn, "DELETE FROM Register WHERE e_id = :eid");
    oci_bind_by_name($del_reg, ':eid', $del_id);
    oci_execute($del_reg);

    // B: Remove from Hosts table
    $del_hosts = oci_parse($conn, "DELETE FROM Hosts WHERE e_id = :eid");
    oci_bind_by_name($del_hosts, ':eid', $del_id);
    oci_execute($del_hosts);

    // C: Finally, delete the Event itself
    $del_event = oci_parse($conn, "DELETE FROM Event WHERE e_id = :eid");
    oci_bind_by_name($del_event, ':eid', $del_id);
    
    if(oci_execute($del_event)) {
        oci_commit($conn);
        $status_msg = "<div class='success-msg'>SUCCESS: Event securely removed from database.</div>";
    }
}

// ==========================================
// 3. FETCH DATA FOR UI
// ==========================================
// Fetch Venues for the Dropdown
$venue_sql = "SELECT v_id, venue FROM Venue";
$v_stid = oci_parse($conn, $venue_sql);
oci_execute($v_stid);

// Fetch Events for the Table
$table_sql = "SELECT e.e_id, e.title, TO_CHAR(e.event_date, 'DD/MM/YY') AS edate, e.event_time, v.venue 
              FROM Event e 
              JOIN Hosts h ON e.e_id = h.e_id 
              JOIN Venue v ON h.v_id = v.v_id 
              ORDER BY e.e_id ASC";
$t_stid = oci_parse($conn, $table_sql);
oci_execute($t_stid);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Admin Panel</title>
    <style>
        body {
            background-color: #050505;
            color: #E0E0E0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .main-content {
            margin-left: 260px; 
            padding: 50px;
            width: calc(100% - 260px);
            box-sizing: border-box;
        }
        .page-header { margin-bottom: 40px; }
        .page-header h4 { color: #00E5FF; font-size: 12px; letter-spacing: 2px; margin: 0 0 10px 0; text-transform: uppercase; }
        .page-header h1 { color: #FFFFFF; font-size: 28px; margin: 0; font-weight: bold; }
        
        /* The "ADD NEW EVENTS" Box */
        .form-panel {
            background-color: #0A0A0A;
            border: 1px solid #1E1E1E;
            padding: 30px;
            margin-bottom: 40px;
        }
        .form-panel h3 {
            color: #FFFFFF;
            font-size: 14px;
            letter-spacing: 1px;
            margin-top: 0;
            margin-bottom: 25px;
            text-transform: uppercase;
        }
        .grid-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            color: #A0A0A0;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            background-color: transparent;
            border: 1px solid #333;
            color: #FFFFFF;
            box-sizing: border-box;
            outline: none;
            transition: 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #00E5FF;
        }
        /* Style specifically for HTML5 Date picker icon */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 0.5;
        }
        .btn-cyan {
            background-color: #00E5FF;
            color: #000000;
            border: none;
            padding: 12px 30px;
            font-weight: bold;
            font-size: 12px;
            letter-spacing: 1px;
            cursor: pointer;
            text-transform: uppercase;
            transition: 0.3s;
        }
        .btn-cyan:hover { background-color: #00b3cc; }

        /* The Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #1E1E1E;
        }
        .data-table th {
            color: #A0A0A0;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: normal;
        }
        .data-table td {
            color: #FFFFFF;
            font-size: 14px;
        }
        .data-table tr:hover td {
            background-color: rgba(0, 229, 255, 0.03);
        }
        .action-delete {
            color: #ff3366;
            text-decoration: none;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            transition: 0.3s;
        }
        .action-delete:hover {
            color: #cc0033;
            text-shadow: 0 0 5px rgba(255, 51, 102, 0.5);
        }

        /* Status Messages */
        .success-msg { padding: 15px; margin-bottom: 20px; text-align: center; font-weight: bold; font-size: 13px; color: #00E5FF; border: 1px solid #00E5FF; background: rgba(0, 229, 255, 0.1); }
        .error-msg { padding: 15px; margin-bottom: 20px; text-align: center; font-weight: bold; font-size: 13px; color: #ff3366; border: 1px solid #ff3366; background: rgba(255, 51, 102, 0.1); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h4>EVENT MANAGEMENT</h4>
            <h1>Event Dashboard</h1>
        </div>

        <?php echo $status_msg; ?>

        <div class="form-panel">
            <h3>ADD NEW EVENTS</h3>
            <form action="admin_manage_events.php" method="POST">
                <div class="grid-form">
                    <div class="form-group">
                        <label>TITLE</label>
                        <input type="text" name="title" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>VENUE</label>
                        <select name="venue" required>
                            <option value="" disabled selected>Select a Venue</option>
                            <?php
                            while ($v_row = oci_fetch_array($v_stid, OCI_ASSOC)) {
                                echo "<option value='" . $v_row['V_ID'] . "'>" . htmlspecialchars($v_row['VENUE']) . "</option>";
                            }
                            oci_free_statement($v_stid);
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>DATE</label>
                        <input type="date" name="event_date" required>
                    </div>
                    <div class="form-group">
                        <label>TIME</label>
                        <input type="text" name="event_time" placeholder="e.g., 04:20 PM" required autocomplete="off">
                    </div>
                </div>
                <button type="submit" name="create_event" class="btn-cyan">CREATE EVENT</button>
            </form>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>TITLE</th>
                    <th>DATE</th>
                    <th>TIME</th>
                    <th>VENUE</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($t_row = oci_fetch_array($t_stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($t_row['E_ID']) . "</td>";
                    echo "<td><strong>" . htmlspecialchars($t_row['TITLE']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($t_row['EDATE']) . "</td>";
                    echo "<td>" . htmlspecialchars($t_row['EVENT_TIME']) . "</td>";
                    echo "<td>" . htmlspecialchars($t_row['VENUE']) . "</td>";
                    // Delete Link - passes the ID in the URL to trigger the deletion block at the top
                    echo "<td><a href='admin_manage_events.php?delete_id=" . $t_row['E_ID'] . "' class='action-delete' onclick=\"return confirm('SYSTEM ALERT: Are you sure you want to permanently delete this event?');\">DELETE</a></td>";
                    echo "</tr>";
                }
                oci_free_statement($t_stid);
                oci_close($conn);
                ?>
            </tbody>
        </table>

    </div>

</body>
</html>