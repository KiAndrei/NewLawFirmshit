<?php
session_start();
if (!isset($_SESSION['client_name']) || $_SESSION['user_type'] !== 'client') {
    header('Location: login_form.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Opiña Law Office</title>
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
                <a href="client_dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="client_documents.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Legal Document Generation</span>
                </a>
            </li>
            <li>
                <a href="client_cases.php">
                    <i class="fas fa-gavel"></i>
                    <span>My Cases</span>
                </a>
            </li>
            <li>
                <a href="client_documents.php">
                    <i class="fas fa-file-alt"></i>
                    <span>My Documents</span>
                </a>
            </li>
            <li>
                <a href="client_schedule.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>My Schedule</span>
                </a>
            </li>
            <li>
                <a href="client_messages.php">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
            <li>
                <a href="client_settings.php">
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
                <h1>Client Dashboard</h1>
            </div>
            <div class="user-info">
                <img src="assets/images/client-avatar.png" alt="Client">
                <div class="user-details">
                    <h3><?php echo $_SESSION['client_name']; ?></h3>
                    <p>Client</p>
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
                <div class="card-value">3</div>
                <div class="card-description">Cases in progress</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming Hearings</h3>
                    <div class="card-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="card-value">2</div>
                <div class="card-description">Hearings this week</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Documents</h3>
                    <div class="card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div class="card-value">25</div>
                <div class="card-description">Total documents</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Messages</h3>
                    <div class="card-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                <div class="card-value">3</div>
                <div class="card-description">Unread messages</div>
            </div>
        </div>

        <!-- My Cases Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">My Cases</h2>
                <button class="btn btn-primary">View All Cases</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>Case Title</th>
                        <th>Attorney</th>
                        <th>Status</th>
                        <th>Next Hearing</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#2024-001</td>
                        <td>Civil Case - Property Dispute</td>
                        <td>Atty. Juan Dela Cruz</td>
                        <td><span style="color: #28a745;">Active</span></td>
                        <td>2024-03-15</td>
                        <td>
                            <button class="btn btn-secondary">View Details</button>
                        </td>
                    </tr>
                    <tr>
                        <td>#2024-002</td>
                        <td>Criminal Case - Theft</td>
                        <td>Atty. Maria Santos</td>
                        <td><span style="color: #ffc107;">Pending</span></td>
                        <td>2024-03-20</td>
                        <td>
                            <button class="btn btn-secondary">View Details</button>
                        </td>
                    </tr>
                    <tr>
                        <td>#2024-003</td>
                        <td>Labor Case - Unfair Dismissal</td>
                        <td>Atty. Pedro Reyes</td>
                        <td><span style="color: #28a745;">Active</span></td>
                        <td>2024-03-25</td>
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