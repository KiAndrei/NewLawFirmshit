<?php
session_start();
if (!isset($_SESSION['admin_name']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login_form.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Opiña Law Office</title>
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
                <a href="admin_dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="admin_cases.php">
                    <i class="fas fa-gavel"></i>
                    <span>Case Management</span>
                </a>
            </li>
            <li>
                <a href="admin_documents.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Document Storage</span>
                </a>
            </li>
            <li>
                <a href="admin_document_generation.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Document Generations</span>
                </a>
            </li>
            <li>
                <a href="admin_schedule.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Schedule & Calendar</span>
                </a>
            </li>
            <li>
                <a href="admin_users.php">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                </a>
            </li>
            <li>
                <a href="admin_audit.php">
                    <i class="fas fa-history"></i>
                    <span>Audit Trail</span>
                </a>
            </li>
            <li>
                <a href="admin_settings.php">
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
                <h1>Admin Dashboard</h1>
            </div>
            <div class="user-info">
                <img src="assets/images/admin-avatar.png" alt="Admin">
                <div class="user-details">
                    <h3><?php echo $_SESSION['admin_name']; ?></h3>
                    <p>Administrator</p>
                </div>
            </div>
        </div>

        <!-- Dashboard Cards -->
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Total Cases</h3>
                    <div class="card-icon">
                        <i class="fas fa-gavel"></i>
                    </div>
                </div>
                <div class="card-value">150</div>
                <div class="card-description">Active cases in the system</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Total Documents</h3>
                    <div class="card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div class="card-value">1,250</div>
                <div class="card-description">Documents stored</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Active Users</h3>
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="card-value">45</div>
                <div class="card-description">Users in the system</div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming Hearings</h3>
                    <div class="card-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="card-value">12</div>
                <div class="card-description">Hearings this week</div>
            </div>
        </div>

        <!-- Recent Cases Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Recent Cases</h2>
                <button class="btn btn-primary">View All</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>Case Title</th>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Next Hearing</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#2024-001</td>
                        <td>Civil Case - Property Dispute</td>
                        <td>John Doe</td>
                        <td><span style="color: #28a745;">Active</span></td>
                        <td>2024-03-15</td>
                        <td>
                            <button class="btn btn-secondary">View</button>
                        </td>
                    </tr>
                    <tr>
                        <td>#2024-002</td>
                        <td>Criminal Case - Theft</td>
                        <td>Jane Smith</td>
                        <td><span style="color: #ffc107;">Pending</span></td>
                        <td>2024-03-20</td>
                        <td>
                            <button class="btn btn-secondary">View</button>
                        </td>
                    </tr>
                    <tr>
                        <td>#2024-003</td>
                        <td>Labor Case - Unfair Dismissal</td>
                        <td>Robert Johnson</td>
                        <td><span style="color: #28a745;">Active</span></td>
                        <td>2024-03-25</td>
                        <td>
                            <button class="btn btn-secondary">View</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 