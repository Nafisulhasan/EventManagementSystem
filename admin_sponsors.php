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
// 1. HANDLE SPONSOR REGISTRATION (POST)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['allocate_funds'])) {
    $c_name = trim($_POST['company_name']);
    $c_number = trim($_POST['contact_number']);
    $amount = $_POST['amount'];
    $e_id = $_POST['e_id'];
    $s_id = trim($_POST['sponsor_id']);

    $ins_sql = "INSERT INTO Sponsor (sponsor_id, company_name, contact_number, amount, e_id) 
                VALUES (:sid, :cname, :cnum, :amt, :eid)";
    $ins_stid = oci_parse($conn, $ins_sql);
    
    oci_bind_by_name($ins_stid, ':sid', $s_id);
    oci_bind_by_name($ins_stid, ':cname', $c_name);
    oci_bind_by_name($ins_stid, ':cnum', $c_number);
    oci_bind_by_name($ins_stid, ':amt', $amount);
    oci_bind_by_name($ins_stid, ':eid', $e_id);

    if (oci_execute($ins_stid)) {
        oci_commit($conn);
        $status_msg = "<div class='success-msg'>SUCCESS: Sponsorship funds allocated to event.</div>";
    } else {
        $e = oci_error($ins_stid);
        $status_msg = "<div class='error-msg'>DATABASE ERROR: " . htmlentities($e['message']) . "</div>";
    }
    oci_free_statement($ins_stid);
}

// ==========================================
// 2. FETCH DATA FOR UI
// ==========================================
// A. Fetch Events for the Select Dropdown
$event_sql = "SELECT e_id, title FROM Event ORDER BY title ASC";
$event_stid = oci_parse($conn, $event_sql);
oci_execute($event_stid);

// B. Fetch Sponsor Data for the Table (JOIN with Event to get the title)
$table_sql = "SELECT s.sponsor_id, s.company_name, s.contact_number, e.title, s.amount 
              FROM Sponsor s 
              JOIN Event e ON s.e_id = e.e_id 
              ORDER BY s.sponsor_id ASC";
$table_stid = oci_parse($conn, $table_sql);
oci_execute($table_stid);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sponsors - Admin Panel</title>
    <style>
        body { background-color: #050505; color: #E0E0E0; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; display: flex; }
        .main-content { margin-left: 260px; padding: 50px; width: calc(100% - 260px); box-sizing: border-box; }
        .page-header { margin-bottom: 40px; }
        .page-header h4 { color: #00E5FF; font-size: 12px; letter-spacing: 2px; margin: 0 0 10px 0; text-transform: uppercase; }
        .page-header h1 { color: #FFFFFF; font-size: 28px; margin: 0; font-weight: bold; }
        
        /* Form Panel */
        .form-panel {
            background-color: #0A0A0A;
            border: 1px solid #1E1E1E;
            padding: 30px;
            margin-bottom: 40px;
            border-radius: 4px;
        }
        .form-panel h3 { color: #FFFFFF; font-size: 13px; letter-spacing: 1px; margin-top: 0; margin-bottom: 25px; text-transform: uppercase; }
        
        .grid-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        /* To make the Select Event and Enter ID sit next to each other exactly like the UI */
        .sub-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .form-group label { display: block; color: #A0A0A0; font-size: 12px; margin-bottom: 8px; }
        .form-group input, .form-group select {
            width: 100%; padding: 12px; background-color: transparent; border: 1px solid #333; 
            color: #FFFFFF; box-sizing: border-box; outline: none; transition: 0.3s;
        }
        .form-group input:focus, .form-group select:focus { border-color: #00E5FF; }
        
        .btn-cyan {
            width: 100%; background-color: #00E5FF; color: #000000; border: none; 
            padding: 15px; font-weight: bold; font-size: 14px; letter-spacing: 1px; 
            cursor: pointer; text-transform: uppercase; transition: 0.3s;
        }
        .btn-cyan:hover { background-color: #00b3cc; }

        /* The Data Table */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 15px 10px; text-align: left; border-bottom: 1px solid #1E1E1E; }
        .data-table th { color: #A0A0A0; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: normal; }
        .data-table td { color: #FFFFFF; font-size: 13px; }
        .money-text { color: #00E5FF; font-weight: bold; font-size: 14px; } /* Matches UI styling for money */
        
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
            <h1>Register New Sponsorship</h1>
        </div>

        <?php echo $status_msg; ?>

        <div class="form-panel">
            <h3>ADD NEW SPONSORSHIP</h3>
            <form action="admin_sponsors.php" method="POST">
                <div class="grid-form">
                    <div class="form-group">
                        <label>Company Name</label>
                        <input type="text" name="company_name" placeholder="Enter Name Here" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" placeholder="0XXXXXXXXXX" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" name="amount" placeholder="Enter Amount Here" required>
                    </div>
                    
                    <div class="sub-grid">
                        <div class="form-group">
                            <label>Select Event</label>
                            <select name="e_id" required>
                                <option value="" disabled selected>Select Event</option>
                                <?php
                                while ($e_row = oci_fetch_array($event_stid, OCI_ASSOC)) {
                                    echo "<option value='" . $e_row['E_ID'] . "'>" . htmlspecialchars($e_row['TITLE']) . "</option>";
                                }
                                oci_free_statement($event_stid);
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Enter ID</label>
                            <input type="text" name="sponsor_id" placeholder="xxx" required autocomplete="off">
                        </div>
                    </div>
                </div>
                <button type="submit" name="allocate_funds" class="btn-cyan">ALLOCATE FUNDS</button>
            </form>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Contact Number</th>
                    <th>EVENT</th>
                    <th>Funding Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($t_row = oci_fetch_array($table_stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($t_row['SPONSOR_ID']) . "</td>";
                    echo "<td>" . htmlspecialchars($t_row['COMPANY_NAME']) . "</td>";
                    echo "<td>" . htmlspecialchars($t_row['CONTACT_NUMBER']) . "</td>";
                    echo "<td>" . htmlspecialchars($t_row['TITLE']) . "</td>";
                    // Format the amount as $ X,XXX
                    echo "<td class='money-text'>$ " . number_format($t_row['AMOUNT']) . "</td>";
                    echo "</tr>";
                }
                oci_free_statement($table_stid);
                oci_close($conn);
                ?>
            </tbody>
        </table>

    </div>

</body>
</html>