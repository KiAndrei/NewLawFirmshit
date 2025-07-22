<?php
session_start();
if (!isset($_SESSION['admin_name']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login_form.php');
    exit();
}
require_once 'config.php';
$admin_id = $_SESSION['user_id'];
$res = $conn->query("SELECT profile_image FROM user_form WHERE id=$admin_id");
$profile_image = '';
if ($res && $row = $res->fetch_assoc()) {
    $profile_image = $row['profile_image'];
}
if (!$profile_image || !file_exists($profile_image)) {
    $profile_image = 'assets/images/admin-avatar.png';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - Opiña Law Office</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
        <img src="images/logo.jpg" alt="Logo">
            <h2>Opiña Law Office</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="admin_documents.php"><i class="fas fa-file-alt"></i><span>Document Storage</span></a></li>
            <li><a href="admin_document_generation.php"><i class="fas fa-file-alt"></i><span>Document Generations</span></a></li>
            <li><a href="admin_schedule.php"><i class="fas fa-calendar-alt"></i><span>Schedule</span></a></li>
            <li><a href="admin_usermanagement.php" class="active"><i class="fas fa-user-tie"></i><span>User Management</span></a></li>
            <li><a href="admin_audit.php"><i class="fas fa-history"></i><span>Audit Trail</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Audit Trail</h1>
                <p>Track system activities and user actions</p>
            </div>
            <div class="user-info">
                <img src="<?= htmlspecialchars($profile_image) ?>" alt="Admin" style="object-fit:cover;width:60px;height:60px;border-radius:50%;border:2px solid #1976d2;">
                <div class="user-details">
                    <h3><?php echo $_SESSION['admin_name']; ?></h3>
                    <p>System Administrator</p>
                </div>
            </div>
        </div>

        <!-- Audit Trail Dashboard -->
        <div class="dashboard-section" style="margin-bottom: 30px;">
            <h1>Audit Trail Dashboard</h1>
            <p>Overview of system activities, user actions, and security events.</p>
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="card-info">
                        <h3>Today's Activities</h3>
                        <p>42</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-file-edit"></i>
                    </div>
                    <div class="card-info">
                        <h3>Document Changes</h3>
                        <p>18</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="card-info">
                        <h3>Security Events</h3>
                        <p>3</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="card-info">
                        <h3>System Alerts</h3>
                        <p>2</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Management Section -->
        <div class="user-management-section" style="background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 30px;">
            <h2>User Management</h2>
            <p>Admins can manage user roles and assign access levels. All changes are monitored and recorded in the audit log.</p>
            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <a href="admin_users.php" class="btn btn-secondary">
                    <i class="fas fa-user-cog"></i> Manage User Roles
                </a>
                <a href="admin_users.php#access" class="btn btn-secondary">
                    <i class="fas fa-user-shield"></i> Assign Access Levels
                </a>
            </div>
        </div>

        <!-- Monitor System Activities Section -->
        <div class="monitor-section" style="margin-bottom: 30px;">
            <h2>Monitor System Activities</h2>
            <p>All user actions, file edits, case record updates, and security events are tracked in real-time. This timeline provides a quick view of recent activities.</p>
            <div class="timeline-container">
                <h3>Recent Activities</h3>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <h3>User Profile Updated</h3>
                                <span class="timeline-time">10:30 AM</span>
                            </div>
                            <p>Maria Santos updated her profile information</p>
                            <div class="timeline-details">
                                <span class="badge badge-info">User Management</span>
                                <span class="badge badge-success">Low Priority</span>
                            </div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-file-edit"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <h3>Document Modified</h3>
                                <span class="timeline-time">09:45 AM</span>
                            </div>
                            <p>John Smith modified "Case #2024-001 - Initial Brief"</p>
                            <div class="timeline-details">
                                <span class="badge badge-warning">Document Storage</span>
                                <span class="badge badge-success">Medium Priority</span>
                            </div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <h3>Failed Login Attempt</h3>
                                <span class="timeline-time">09:15 AM</span>
                            </div>
                            <p>Multiple failed login attempts from IP: 192.168.1.100</p>
                            <div class="timeline-details">
                                <span class="badge badge-danger">Security</span>
                                <span class="badge badge-danger">High Priority</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit Log Table Section -->
        <div class="audit-log-section" style="margin-bottom: 30px;">
            <h2>Detailed Audit Log</h2>
            <p>This table lists all recorded actions for compliance and security review. Use the search and filter options to find specific events.</p>
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">Activity Log</h3>
                    <div class="table-actions">
                        <button class="btn btn-secondary">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Details</th>
                            <th>IP Address</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2024-03-18 10:30:15</td>
                            <td>Maria Santos</td>
                            <td>Profile Update</td>
                            <td>User Management</td>
                            <td>Updated contact information</td>
                            <td>192.168.1.101</td>
                            <td><span class="status-badge status-success">Success</span></td>
                        </tr>
                        <tr>
                            <td>2024-03-18 09:45:22</td>
                            <td>John Smith</td>
                            <td>Document Edit</td>
                            <td>Document Storage</td>
                            <td>Modified case brief</td>
                            <td>192.168.1.102</td>
                            <td><span class="status-badge status-success">Success</span></td>
                        </tr>
                        <tr>
                            <td>2024-03-18 09:15:10</td>
                            <td>Unknown</td>
                            <td>Login Attempt</td>
                            <td>Security</td>
                            <td>Failed authentication</td>
                            <td>192.168.1.100</td>
                            <td><span class="status-badge status-danger">Failed</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Audit Log Recording Section -->
        <div class="audit-log-recording-note" style="background: #f8f9fa; border-radius: 10px; padding: 20px;">
            <h2>Audit Log Recording</h2>
            <p>All critical actions such as editing files, updating case records, managing user roles, and assigning access levels are automatically recorded in the audit log for security and compliance. This ensures transparency and accountability for all system activities.</p>
        </div>
    </div>

    <style>
        .timeline-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline-item {
            display: flex;
            margin-bottom: 20px;
            position: relative;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .timeline-content {
            flex: 1;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .timeline-time {
            color: #666;
            font-size: 0.9rem;
        }

        .timeline-details {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-info {
            background: #17a2b8;
            color: white;
        }

        .badge-warning {
            background: #ffc107;
            color: #212529;
        }

        .badge-danger {
            background: #dc3545;
            color: white;
        }

        .badge-success {
            background: #28a745;
            color: white;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-success {
            background: var(--success-color);
            color: white;
        }

        .status-danger {
            background: var(--danger-color);
            color: white;
        }

        @media (max-width: 768px) {
            .timeline-item {
                flex-direction: column;
            }

            .timeline-icon {
                margin-bottom: 10px;
            }

            .timeline-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .timeline-time {
                margin-top: 5px;
            }
        }
    </style>
</body>
</html> 