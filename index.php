<?php
session_start(); 

require 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = trim($_POST['user_id']);
    $password_input = trim($_POST['password']);
    $role = $_POST['role'];
    
    if ($role == 'Student') {
        //  STUDENT LOGIN 
        $sql = "SELECT s_name FROM Student WHERE s_id = :userid";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':userid', $user_id);
        oci_execute($stid);
        $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
        
        if ($row) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $row['S_NAME'];
            $_SESSION['role'] = 'Student';
            header("Location: student_dashboard.php");
            exit();
        } else {
            $message = "ACCESS DENIED: Invalid Student ID.";
        }
        oci_free_statement($stid);
        
    } elseif ($role == 'Admin') {
        // --- ADMIN LOGIN ---
        $sql = "SELECT admin_name FROM System_Admin WHERE admin_id = :adminid AND password = :pass";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':adminid', $user_id);
        oci_bind_by_name($stid, ':pass', $password_input);
        oci_execute($stid);
        $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);

        if ($row) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $row['ADMIN_NAME'];
            $_SESSION['role'] = 'Admin';
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $message = "ACCESS DENIED: Invalid Admin Credentials.";
        }
        oci_free_statement($stid);

    } elseif ($role == 'Staff') {
        // --- STAFF LOGIN ---
        $sql = "SELECT staff_name FROM Staff WHERE staff_id = :userid AND password = :pass";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':userid', $user_id);
        oci_bind_by_name($stid, ':pass', $password_input);
        oci_execute($stid);
        $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);

        if ($row) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $row['STAFF_NAME'];
            $_SESSION['role'] = 'Staff';
            header("Location: staff_dashboard.php");
            exit();
        } else {
            $message = "ACCESS DENIED: Invalid Staff Credentials.";
        }
        oci_free_statement($stid);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIUB Event Management - Login</title>
    <style>
        /* ====================================================
        BACKGROUND IMAGE SETUP
        ====================================================
        Put your background image file in the same folder as index.php 
        and change 'your-bg-image.jpg' to your actual file name! 
        */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('your-bg-image.jpg'); /* <--- CHANGE THIS FILENAME */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        /* Dark overlay to make the text and card readable over the image */
        .overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }

        /* Top Left Header */
        .top-logo {
            position: absolute;
            top: 30px;
            left: 40px;
            z-index: 2;
        }
        .top-logo h1 {
            color: #00E5FF;
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        /* The Login Card Container */
        .login-card {
            position: relative;
            z-index: 2;
            background: linear-gradient(180deg, #e0f7fa 0%, #ffffff 40%);
            border-radius: 15px;
            padding: 40px;
            width: 300px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        .login-card h2 {
            text-align: center;
            color: #000;
            font-weight: 500;
            margin-top: 0;
            margin-bottom: 30px;
            font-size: 22px;
            line-height: 1.4;
        }

        .form-group {
            margin-bottom: 15px;
            position: relative;
        }

        /* The Custom Select Dropdown */
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 20px;
            border: 1px solid #b2ebf2;
            background: linear-gradient(to right, #e0f7fa, #ffffff);
            color: #000;
            font-size: 14px;
            outline: none;
            cursor: pointer;
            box-sizing: border-box;
            appearance: none; /* Hides default arrow */
        }
        
        /* Custom arrow for select */
        .select-wrapper::after {
            content: '▼';
            font-size: 10px;
            color: #000;
            position: absolute;
            right: 15px;
            top: 15px;
            pointer-events: none;
        }

        /* Input Fields with Icons */
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #000;
            font-size: 16px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 12px 12px 35px;
            border-radius: 8px;
            border: none;
            background-color: #f5f5f5;
            color: #333;
            box-sizing: border-box;
            outline: none;
            font-size: 13px;
        }
        .form-group input::placeholder {
            color: #999;
        }

        /* Links Row */
        .links-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            margin-top: 5px;
        }
        .links-row a {
            color: #000;
            text-decoration: none;
            font-size: 11px;
            font-weight: bold;
        }
        .links-row a:hover {
            text-decoration: underline;
        }

        /* The Black Get Started Button */
        .btn-submit {
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            border: none;
            background: linear-gradient(to bottom, #2b2b2b, #000000);
            color: #ffffff;
            font-weight: normal;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
        }
        .btn-submit:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.4);
        }

        .error-msg {
            color: #ff3366;
            font-size: 12px;
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="overlay"></div>

    <div class="top-logo">
        <h1>University Event Management System</h1>
    </div>

    <div class="login-card">
        <h2>Sign in to Access<br>Dashboard</h2>
        
        <?php if(!empty($message)) { echo "<div class='error-msg'>" . htmlspecialchars($message) . "</div>"; } ?>

        <form action="index.php" method="POST">
            
            <div class="form-group select-wrapper">
                <select name="role" required>
                    <option value="Student">Student</option>
                    <option value="Admin">Admin</option>
                    <option value="Staff">Staff</option>
                </select>
            </div>

            <div class="form-group">
                <span class="input-icon">&#128100;</span> <input type="text" name="user_id" required autocomplete="off" placeholder="userid">
            </div>
            
            <div class="form-group">
                <span class="input-icon">&#128274;</span> <input type="password" name="password" required placeholder="password">
            </div>

            <div class="links-row">
                <a href="register.php">Create a new account</a>
                <a href="forgot_password.php">Forgot password?</a>
            </div>

            <button type="submit" class="btn-submit">Get Started</button>
        </form>
    </div>

</body>
</html>