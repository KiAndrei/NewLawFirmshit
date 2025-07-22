<?php
session_start();
if (!isset($_SESSION['attorney_name']) || $_SESSION['user_type'] !== 'attorney') {
    header('Location: login_form.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attorney Dashboard - Opiña Law Office</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/images/logo.png" alt="Logo">
            <h2>Opiña Law Office</h2>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="attorney_dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="attorney_cases.php">
                    <i class="fas fa-gavel"></i>
                    <span>Manage Cases</span>
                </a>
            </li>
            <li>
                <a href="attorney_documents.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Legal Documents</span>
                </a>
            </li>
            <li>
                <a href="attorney_schedule.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>My Schedule</span>
                </a>
            </li>
            <li>
                <a href="attorney_clients.php">
                    <i class="fas fa-users"></i>
                    <span>My Clients</span>
                </a>
            </li>
            <li>
                <a href="attorney_messages.php">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
            <li>
                <a href="attorney_efiling.php">
                    <i class="fas fa-paper-plane"></i>
                    <span>E-Filing</span>
                </a>
            </li>
            <li>
                <a href="attorney_settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Attorney Dashboard</h1>
            </div>
            <div class="user-info">
                <img src="assets/images/attorney-avatar.png" alt="Attorney">
                <div class="user-details">
                    <h3><?php echo $_SESSION['attorney_name']; ?></h3>
                    <p>Attorney</p>
                </div>
            </div>
        </div>

        <!-- Dashboard Cards -->
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Active Cases</h3>
                    <div class="card-icon">
                        <i class="fas fa-gavel"></i>
                    </div>
                </div>
                <div class="card-value">25</div>
                <div class="card-description">Cases you're handling</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming Hearings</h3>
                    <div class="card-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="card-value">8</div>
                <div class="card-description">Hearings this week</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pending Documents</h3>
                    <div class="card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div class="card-value">15</div>
                <div class="card-description">Documents to review</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Client Messages</h3>
                    <div class="card-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                <div class="card-value">5</div>
                <div class="card-description">Unread messages</div>
            </div>
        </div>

        <!-- Today's Schedule -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Today's Schedule</h2>
                <button class="btn btn-primary">View Calendar</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Case</th>
                        <th>Client</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>09:00 AM</td>
                        <td>Civil Case #2024-001</td>
                        <td>John Doe</td>
                        <td>Court Hearing</td>
                        <td>Regional Trial Court</td>
                        <td>
                            <button class="btn btn-secondary">View Details</button>
                        </td>
                    </tr>
                    <tr>
                        <td>02:00 PM</td>
                        <td>Criminal Case #2024-002</td>
                        <td>Jane Smith</td>
                        <td>Client Meeting</td>
                        <td>Office</td>
                        <td>
                            <button class="btn btn-secondary">View Details</button>
                        </td>
                    </tr>
                    <tr>
                        <td>04:30 PM</td>
                        <td>Labor Case #2024-003</td>
                        <td>Robert Johnson</td>
                        <td>Document Review</td>
                        <td>Office</td>
                        <td>
                            <button class="btn btn-secondary">View Details</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 