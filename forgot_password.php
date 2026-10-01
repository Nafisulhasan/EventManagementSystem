<?php
// Include the database connection
require 'db_connect.php';

$message = "";
$status = ""; // 'success' or 'error'

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $user_id = trim($_POST['user_id']);
    $new_password = trim($_POST['new_password']);
    
    if ($role == 'Student') {
        // Students don't have passwords in our DB schema
        $status = "error";
        $message = "SYSTEM NOTE: Students do not require passwords. Please return to login and enter only your Student ID.";
        
    } elseif ($role == 'Admin') {
        // 1. Check if the Admin ID exists
        $check_sql = "SELECT admin_id FROM System_Admin WHERE admin_id = :uid";
        $check_stid = oci_parse($conn, $check_sql);
        oci_bind_by_name($check_stid, ':uid', $user_id);
        oci_execute($check_stid);
        
        if (oci_fetch_array($check_stid, OCI_ASSOC)) {
            // 2. Update the password
            $update_sql = "UPDATE System_Admin SET password = :pass WHERE admin_id = :uid";
            $update_stid = oci_parse($conn, $update_sql);
            oci_bind_by_name($update_stid, ':pass', $new_password);
            oci_bind_by_name($update_stid, ':uid', $user_id);
            
            if(oci_execute($update_stid)) {
                oci_commit($conn);
                $status = "success";
                $message = "SUCCESS: Admin password has been reset.";
            }
            oci_free_statement($update_stid);
        } else {
            $status = "error";
            $message = "ERROR: Admin ID not found in the system.";
        }
        oci_free_statement($check_stid);
        
    } elseif ($role == 'Staff') {
        // 1. Check if the Staff ID exists
        $check_sql = "SELECT staff_id FROM Staff WHERE staff_id = :uid";
        $check_stid = oci_parse($conn, $check_sql);
        oci_bind_by_name($check_stid, ':uid', $user_id);
        oci_execute($check_stid);
        
        if (oci_fetch_array($check_stid, OCI_ASSOC)) {
            // 2. Update the password
            $update_sql = "UPDATE Staff SET password = :pass WHERE staff_id = :uid";
            $update_stid = oci_parse($conn, $update_sql);
            oci_bind_by_name($update_stid, ':pass', $new_password);
            oci_bind_by_name($update_stid, ':uid', $user_id);
            
            if(oci_execute($update_stid)) {
                oci_commit($conn);
                $status = "success";
                $message = "SUCCESS: Staff password has been reset.";
            }
            oci_free_statement($update_stid);
        } else {
            $status = "error";
            $message = "ERROR: Staff ID not found in the system.";
        }
        oci_free_statement($check_stid);
    }
    
    oci_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIUB Event Management - Password Recovery</title>
    <style>
        /* Inherit the beautiful UI from the index page */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('your-bg-image.jpg'); /* MAKE SURE THIS MATCHES YOUR INDEX FILE */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4); z-index: 1;
        }

        .top-logo { position: absolute; top: 30px; left: 40px; z-index: 2; }
        .top-logo h1 { color: #00E5FF; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 1px; }

        .login-card {
            position: relative; z-index: 2;
            background: linear-gradient(180deg, #e0f7fa 0%, #ffffff 40%);
            border-radius: 15px; padding: 40px; width: 300px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        .login-card h2 {
            text-align: center; color: #000; font-weight: 500;
            margin-top: 0; margin-bottom: 30px; font-size: 20px; line-height: 1.4;
        }

        .form-group { margin-bottom: 15px; position: relative; }

        .form-group select {
            width: 100%; padding: 12px 15px; border-radius: 20px; border: 1px solid #b2ebf2;
            background: linear-gradient(to right, #e0f7fa, #ffffff); color: #000;
            font-size: 14px; outline: none; cursor: pointer; box-sizing: border-box; appearance: none;
        }
        
        .select-wrapper::after {
            content: '▼'; font-size: 10px; color: #000; position: absolute; right: 15px; top: 15px; pointer-events: none;
        }

        .input-icon {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #000; font-size: 16px;
        }
        
        .form-group input {
            width: 100%; padding: 12px 12px 12px 35px; border-radius: 8px; border: none;
            background-color: #f5f5f5; color: #333; box-sizing: border-box; outline: none; font-size: 13px;
        }
        
        .form-group input::placeholder { color: #999; }

        .btn-submit {
            width: 100%; padding: 14px; border-radius: 8px; border: none;
            background: linear-gradient(to bottom, #2b2b2b, #000000); color: #ffffff;
            font-weight: normal; cursor: pointer; font-size: 14px; transition: 0.3s; margin-top: 10px;
        }
        .btn-submit:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.4); }

        .back-link {
            display: block; text-align: center; margin-top: 20px; color: #666;
            text-decoration: none; font-size: 12px; font-weight: bold;
        }
        .back-link:hover { color: #000; text-decoration: underline; }

        .status-box { padding: 10px; margin-bottom: 20px; text-align: center; font-size: 12px; font-weight: bold; border-radius: 5px; }
        .success { color: #008080; background: #e0f2f1; border: 1px solid #008080; }
        .error { color: #c62828; background: #ffebee; border: 1px solid #c62828; }
    </style>
</head>
<body>

    <div class="overlay"></div>

    <div class="top-logo">
        <h1>University Event Management System</h1>
    </div>

    <div class="login-card">
        <h2>Password Recovery</h2>
        
        <?php 
        if(!empty($message)) { 
            echo "<div class='status-box " . $status . "'>" . htmlspecialchars($message) . "</div>"; 
        } 
        ?>

        <form action="forgot_password.php" method="POST">
            
            <div class="form-group select-wrapper">
                <select name="role" required>
                    <option value="Admin">Admin</option>
                    <option value="Staff">Staff</option>
                    <option value="Student">Student</option>
                </select>
            </div>

            <div class="form-group">
                <span class="input-icon">&#128100;</span>
                <input type="text" name="user_id" required autocomplete="off" placeholder="Enter User ID">
            </div>
            
            <div class="form-group">
                <span class="input-icon">&#128274;</span>
                <input type="password" name="new_password" required placeholder="Enter New Password">
            </div>

            <button type="submit" class="btn-submit">Reset Password</button>
        </form>

        <a href="index.php" class="back-link">← Return to Login</a>
    </div>

</body>
</html>