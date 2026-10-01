<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}
require 'db_connect.php';

// --- 1. DETERMINE THE ACTIVE EVENT ---
// Default to 0, will update once we fetch the events
$active_e_id = isset($_GET['e_id']) ? $_GET['e_id'] : 0; 

// --- 2. HANDLE THE TOGGLE SWITCH ACTION ---
if (isset($_GET['toggle_staff']) && $active_e_id != 0) {
    $t_staff_id = $_GET['toggle_staff'];

    // Check if they are currently allocated
    $check_sql = "SELECT * FROM Event_Staff WHERE e_id = :eid AND staff_id = :sid";
    $check_stid = oci_parse($conn, $check_sql);
    oci_bind_by_name($check_stid, ':eid', $active_e_id);
    oci_bind_by_name($check_stid, ':sid', $t_staff_id);
    oci_execute($check_stid);

    if (oci_fetch_array($check_stid, OCI_ASSOC)) {
        // They are allocated -> UNASSIGN THEM
        $del_sql = "DELETE FROM Event_Staff WHERE e_id = :eid AND staff_id = :sid";
        $toggle_stid = oci_parse($conn, $del_sql);
    } else {
        // They are NOT allocated -> ASSIGN THEM
        $ins_sql = "INSERT INTO Event_Staff (e_id, staff_id) VALUES (:eid, :sid)";
        $toggle_stid = oci_parse($conn, $ins_sql);
    }
    
    oci_bind_by_name($toggle_stid, ':eid', $active_e_id);
    oci_bind_by_name($toggle_stid, ':sid', $t_staff_id);
    oci_execute($toggle_stid);
    oci_commit($conn);
    oci_free_statement($check_stid);
    oci_free_statement($toggle_stid);
    
    // Clean redirect to remove the toggle parameter from the URL
    header("Location: admin_assign_staff.php?e_id=" . $active_e_id);
    exit();
}

// --- 3. FETCH ALL EVENTS FOR THE LEFT PANEL ---
$events_sql = "SELECT e.e_id, e.title, TO_CHAR(e.event_date, 'Month YYYY') AS fmt_date, v.venue 
               FROM Event e 
               JOIN Hosts h ON e.e_id = h.e_id 
               JOIN Venue v ON h.v_id = v.v_id 
               ORDER BY e.event_date ASC";
$events_stid = oci_parse($conn, $events_sql);
oci_execute($events_stid);

// Cache the events into an array so we can establish the active one if not set
$events = [];
while ($row = oci_fetch_array($events_stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $events[] = $row;
}
oci_free_statement($events_stid);

if ($active_e_id == 0 && count($events) > 0) {
    $active_e_id = $events[0]['E_ID']; // Auto-select the first event
}

// --- 4. FETCH STAFF AND THEIR ALLOCATION STATUS FOR THE RIGHT PANEL ---
// Uses a LEFT JOIN to see if a record exists in Event_Staff for the active event
$staff_sql = "SELECT s.staff_id, s.staff_name, s.contact_number, 
              CASE WHEN es.e_id IS NOT NULL THEN 1 ELSE 0 END AS is_allocated 
              FROM Staff s 
              LEFT JOIN Event_Staff es ON s.staff_id = es.staff_id AND es.e_id = :active_eid 
              ORDER BY s.staff_id ASC";
$staff_stid = oci_parse($conn, $staff_sql);
oci_bind_by_name($staff_stid, ':active_eid', $active_e_id);
oci_execute($staff_stid);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Staff - Admin Panel</title>
    <style>
        body { background-color: #050505; color: #E0E0E0; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; display: flex; }
        .main-content { margin-left: 260px; padding: 50px; width: calc(100% - 260px); box-sizing: border-box; }
        .page-header { margin-bottom: 40px; }
        .page-header h4 { color: #00E5FF; font-size: 12px; letter-spacing: 2px; margin: 0 0 10px 0; text-transform: uppercase; }
        .page-header h1 { color: #FFFFFF; font-size: 28px; margin: 0; font-weight: bold; }
        
        .split-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            align-items: start;
        }

        /* Left Panel: Event List */
        .event-list {
            background-color: #0A0A0A;
            border: 1px solid #1E1E1E;
            border-radius: 8px;
            padding: 20px;
        }
        .event-list h3 { color: white; font-size: 14px; margin-top: 0; margin-bottom: 20px; }
        .event-card {
            background-color: transparent;
            border: 1px solid #333;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: block;
            transition: 0.3s;
        }
        .event-card:hover { border-color: #555; }
        .event-card.active { border-color: #00E5FF; background-color: rgba(0, 229, 255, 0.05); }
        .event-card h4 { color: white; font-size: 13px; margin: 0 0 10px 0; text-transform: uppercase; }
        .event-card p { margin: 0 0 5px 0; font-size: 11px; color: #888; display: flex; align-items: center; gap: 5px; }
        .event-card p span { color: #00E5FF; }

        /* Right Panel: Staff Table */
        .staff-panel {
            background-color: #0A0A0A;
            border: 1px solid #1E1E1E;
            border-radius: 8px;
            padding: 20px;
        }
        .staff-panel h3 { color: white; font-size: 14px; margin-top: 0; margin-bottom: 20px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 15px 10px; text-align: left; border-bottom: 1px solid #1E1E1E; }
        .data-table th { color: #A0A0A0; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: normal; }
        .data-table td { color: #FFFFFF; font-size: 13px; }
        
        /* CSS Toggle Switch logic */
        .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #333; transition: .4s; border-radius: 20px;
        }
        .slider:before {
            position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px;
            background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: #00E5FF; }
        input:checked + .slider:before { transform: translateX(20px); background-color: #000; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h4>EVENT MANAGEMENT</h4>
            <h1>Staff Allocation</h1>
        </div>

        <div class="split-layout">
            
            <div class="event-list">
                <h3>Active Events</h3>
                <?php
                if (count($events) > 0) {
                    foreach ($events as $e) {
                        $is_active = ($e['E_ID'] == $active_e_id) ? "active" : "";
                        echo "<a href='admin_assign_staff.php?e_id=" . $e['E_ID'] . "' class='event-card " . $is_active . "'>";
                        echo "<h4>" . htmlspecialchars($e['TITLE']) . "</h4>";
                        echo "<p><span>&#128197;</span> " . htmlspecialchars($e['FMT_DATE']) . "</p>";
                        echo "<p><span>&#128205;</span> " . htmlspecialchars($e['VENUE']) . "</p>";
                        echo "</a>";
                    }
                } else {
                    echo "<p style='color:#888; font-size:12px;'>No active events found.</p>";
                }
                ?>
            </div>

            <div class="staff-panel">
                <h3>Staff Roster</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Staff Name</th>
                            <th>Staff ID</th>
                            <th>Contact Number</th>
                            <th>Allocation Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($active_e_id != 0) {
                            while ($s_row = oci_fetch_array($staff_stid, OCI_ASSOC)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($s_row['STAFF_NAME']) . "</td>";
                                echo "<td>" . htmlspecialchars($s_row['STAFF_ID']) . "</td>";
                                echo "<td>" . htmlspecialchars($s_row['CONTACT_NUMBER']) . "</td>";
                                
                                // Setup the Toggle UI
                                $is_checked = ($s_row['IS_ALLOCATED'] == 1) ? "checked" : "";
                                echo "<td>
                                        <label class='switch'>
                                          <input type='checkbox' " . $is_checked . " 
                                                 onchange=\"window.location.href='admin_assign_staff.php?e_id=" . $active_e_id . "&toggle_staff=" . $s_row['STAFF_ID'] . "'\">
                                          <span class='slider'></span>
                                        </label>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center; color:#888;'>Select an event to view staff.</td></tr>";
                        }
                        oci_free_statement($staff_stid);
                        oci_close($conn);
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</body>
</html>