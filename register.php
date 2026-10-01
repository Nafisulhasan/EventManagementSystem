<?php
// Include the database connection
require 'db_connect.php';

$message = "";
$status = ""; // 'success' or 'error'

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $s_id = trim($_POST['userid']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $dept = trim($_POST['department']);

    // 1. Verify that this Student ID doesn't already exist to prevent database crashes
    $check_sql = "SELECT s_id FROM Student WHERE s_id = :sid";
    $check_stid = oci_parse($conn, $check_sql);
    oci_bind_by_name($check_stid, ':sid', $s_id);
    oci_execute($check_stid);

    if (oci_fetch_array($check_stid, OCI_ASSOC)) {
        $status = "error";
        $message = "SYSTEM ALERT: Student ID already exists in the database.";
    } else {
        // 2. The ID is new, so we INSERT them into the Oracle database
        $insert_sql = "INSERT INTO Student (s_id, s_name, email, dept_name) VALUES (:sid, :sname, :semail, :sdept)";
        $insert_stid = oci_parse($conn, $insert_sql);
        
        oci_bind_by_name($insert_stid, ':sid', $s_id);
        oci_bind_by_name($insert_stid, ':sname', $name);
        oci_bind_by_name($insert_stid, ':semail', $email);
        oci_bind_by_name($insert_stid, ':sdept', $dept);

        $r = oci_execute($insert_stid);

        if ($r) {
            // Commit the changes permanently to Oracle 10g
            oci_commit($conn);
            $status = "success";
            $message = "ACCOUNT INITIALIZED. You may now return to the login portal.";
        } else {
            $e = oci_error($insert_stid);
            $status = "error";
            $message = "DATABASE ERROR: " . htmlentities($e['message']);
        }
        oci_free_statement($insert_stid);
    }
    oci_free_statement($check_stid);
    oci_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Your Account - AIUB</title>
    <style>
        /* UI Match for Image 1 (Bottom) */
        body {
            background-color: #000000;
            color: #E0E0E0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .register-container {
            border: 1px solid #00E5FF; /* Cyan Outline */
            padding: 40px;
            width: 350px;
            background-color: rgba(0, 229, 255, 0.02);
            box-shadow: 0 0 20px rgba(0, 229, 255, 0.05);
        }
        .register-container h2 {
            text-align: center;
            color: #00E5FF;
            font-weight: 300;
            letter-spacing: 2px;
            margin-top: 0;
            margin-bottom: 30px;
            text-transform: uppercase;
            font-size: 20px;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        /* Dark input fields matching the UI */
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 12px 12px 40px; /* Padding for the icon space */
            background-color: #0A0A0A;
            border: 1px solid #1E1E1E;
            color: #00E5FF;
            box-sizing: border-box;
            outline: none;
            transition: 0.3s;
            font-size: 13px;
            border-radius: 4px;
        }
        .form-group input::placeholder {
            color: #444;
            letter-spacing: 1px;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #00E5FF;
            box-shadow: 0 0 5px rgba(0, 229, 255, 0.2);
        }
        /* Unicode Icons for the inputs */
        .icon {
            position: absolute;
            left: 12px;
            top: 12px;
            color: #00E5FF;
            font-size: 14px;
        }
        /* The Outline Button */
        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: transparent;
            color: #00E5FF;
            border: 1px solid #00E5FF;
            font-weight: bold;
            letter-spacing: 2px;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
            text-transform: uppercase;
            font-size: 12px;
        }
        .submit-btn:hover {
            background-color: #00E5FF;
            color: #000000;
        }
        .status-box {
            padding: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .success { color: #00E5FF; border: 1px solid #00E5FF; background: rgba(0, 229, 255, 0.1); }
        .error { color: #ff3366; border: 1px solid #ff3366; background: rgba(255, 51, 102, 0.1); }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-size: 12px;
            transition: 0.3s;
        }
        .back-link:hover { color: #00E5FF; }
    </style>
</head>
<body>

    <div class="register-container">
        <h2>CREATE YOUR ACCOUNT</h2>
        
        <?php 
        if(!empty($message)) { 
            echo "<div class='status-box " . $status . "'>" . htmlspecialchars($message) . "</div>"; 
        } 
        ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <span class="icon">&#128100;</span> <input type="number" name="userid" required placeholder="USER ID (e.g., 201)" autocomplete="off">
            </div>
            
            <div class="form-group">
                <span class="icon">&#9998;</span> <input type="text" name="name" required placeholder="FULL NAME" autocomplete="off">
            </div>
            
            <div class="form-group">
                <span class="icon">&#9993;</span> <input type="email" name="email" required placeholder="EMAIL ADDRESS" autocomplete="off">
            </div>
            
            <div class="form-group">
                <span class="icon">&#127970;</span> <select name="department" required>
                    <option value="" disabled selected>SELECT DEPARTMENT</option>
                    <option value="CS">Computer Science (CS)</option>
                    <option value="EEE">Electrical Engineering (EEE)</option>
                    <option value="BBA">Business Administration (BBA)</option>
                    <option value="Arch">Architecture</option>
                </select>
            </div>

            <button type="submit" class="submit-btn">REGISTER</button>
        </form>

        <a href="index.php" class="back-link">Return to System Login</a>
    </div>

</body>
</html>