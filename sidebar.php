<?php
// Get the current file name so we can highlight the active menu link
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* Sidebar CSS */
    .sidebar {
        width: 260px;
        background-color: #000000;
        border-right: 1px solid #00E5FF; /* Cyan Border */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 30px 0;
        box-sizing: border-box;
        position: fixed;
        height: 100vh;
        left: 0;
        top: 0;
    }
    .sidebar-header {
        padding: 0 30px;
        margin-bottom: 40px;
    }
    .sidebar-header .role {
        color: #00E5FF;
        font-size: 12px;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }
    .sidebar-header h2 {
        color: #FFFFFF;
        font-size: 20px;
        margin: 0;
        font-weight: 400;
        border-bottom: 1px solid #333;
        padding-bottom: 20px;
    }
    .nav-links {
        list-style: none;
        padding: 0;
        margin: 0;
        flex-grow: 1;
    }
    .nav-links li {
        margin-bottom: 5px;
    }
    .nav-links a {
        display: block;
        padding: 15px 30px;
        color: #FFFFFF;
        text-decoration: none;
        font-size: 14px;
        font-weight: bold;
        letter-spacing: 1px;
        transition: 0.3s;
        border-left: 3px solid transparent;
    }
    .nav-links a:hover {
        background-color: rgba(0, 229, 255, 0.05);
        color: #00E5FF;
    }
    /* Active Link Styling */
    .nav-links a.active {
        border-left: 3px solid #00E5FF;
        background-color: rgba(0, 229, 255, 0.05);
        color: #00E5FF;
    }
    .sidebar-footer {
        padding: 0 30px;
    }
    .logout-btn {
        display: inline-block;
        background-color: #00E5FF;
        color: #000000;
        padding: 10px 20px;
        text-decoration: none;
        font-weight: bold;
        font-size: 12px;
        transition: 0.3s;
    }
    .logout-btn:hover {
        background-color: #00b3cc;
    }
</style>

<div class="sidebar">
    <div>
        <div class="sidebar-header">
            <div class="role">&#128100; ADMIN</div>
            <h2>Control Panel</h2>
        </div>
        <ul class="nav-links">
            <li><a href="admin_dashboard.php" class="<?= ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">DASHBOARD</a></li>
            <li><a href="admin_manage_events.php" class="<?= ($current_page == 'admin_manage_events.php') ? 'active' : ''; ?>">MANAGE EVENTS</a></li>
            <li><a href="admin_assign_staff.php" class="<?= ($current_page == 'admin_assign_staff.php') ? 'active' : ''; ?>">ASSIGN STAFF</a></li>
            <li><a href="admin_sponsors.php" class="<?= ($current_page == 'admin_sponsors.php') ? 'active' : ''; ?>">SPONSORS</a></li>
        </ul>
    </div>
    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>