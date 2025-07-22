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
            <li><a href="attorney_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="attorney_cases.php" class="active"><i class="fas fa-gavel"></i><span>Manage Cases</span></a></li>
            <li><a href="attorney_documents.php"><i class="fas fa-file-alt"></i><span>Document Storage</span></a></li>
            <li><a href="attorney_document_generation.php"><i class="fas fa-file-alt"></i><span>Document Generation</span></a></li>
            <li><a href="attorney_schedule.php"><i class="fas fa-calendar-alt"></i><span>My Schedule</span></a></li>
            <li><a href="attorney_clients.php"><i class="fas fa-users"></i><span>My Clients</span></a></li>
            <li><a href="attorney_messages.php"><i class="fas fa-envelope"></i><span>Messages</span></a></li>
            <li><a href="attorney_efiling.php"><i class="fas fa-paper-plane"></i><span>E-Filing</span></a></li>
            <li><a href="attorney_audit.php"><i class="fas fa-history"></i><span>Audit Trail</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Audit Trail Dashboard -->
        <div class="dashboard-section" style="margin-bottom: 30px;">
            <h1>Audit Trail Dashboard</h1>
            <p>Overview of your recent activities and system actions.</p>
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-file-pen"></i>
                    </div>
                    <div class="card-info">
                        <h3>Files Edited</h3>
                        <p>5</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="card-info">
                        <h3>Case Records Updated</h3>
                        <p>3</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-clock-rotate-left"></i>
                    </div>
                    <div class="card-info">
                        <h3>Recent Activities</h3>
                        <p>8</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monitor My Activities Section -->
        <div class="monitor-section" style="margin-bottom: 30px;">
            <h2>Monitor My Activities</h2>
            <p>This timeline shows your recent actions, including file edits and case record updates. All activities are recorded for transparency.</p>
            <div class="timeline-container">
                <h3>My Recent Activities</h3>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-file-pen"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <h3>Edited File</h3>
                                <span class="timeline-time">10:30 AM</span>
                            </div>
                            <p>Updated "Case #2024-001 - Initial Brief"</p>
                            <div class="timeline-details">
                                <span class="badge badge-warning">File Edit</span>
                                <span class="badge badge-success">Success</span>
                            </div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <h3>Updated Case Record</h3>
                                <span class="timeline-time">09:45 AM</span>
                            </div>
                            <p>Updated status for "Case #2024-002 - Motion to Dismiss"</p>
                            <div class="timeline-details">
                                <span class="badge badge-info">Case Update</span>
                                <span class="badge badge-success">Success</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Audit Log Table Section -->
        <div class="audit-log-section">
            <h2>My Audit Log</h2>
            <p>This table lists all your recorded actions for review and compliance.</p>
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">My Activity Log</h3>
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
                            <th>Action</th>
                            <th>Case/File</th>
                            <th>Details</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2024-03-18 10:30:15</td>
                            <td>File Edit</td>
                            <td>Case #2024-001</td>
                            <td>Updated initial brief</td>
                            <td><span class="status-badge status-active">Success</span></td>
                        </tr>
                        <tr>
                            <td>2024-03-18 09:45:22</td>
                            <td>Case Update</td>
                            <td>Case #2024-002</td>
                            <td>Changed status to 'Reviewed'</td>
                            <td><span class="status-badge status-active">Success</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Audit Log Recording Section -->
        <div class="audit-log-recording-note" style="background: #f8f9fa; border-radius: 10px; padding: 20px;">
            <h2>Audit Log Recording</h2>
            <p>All your critical actions such as editing files and updating case records are automatically recorded in the audit log for transparency and accountability.</p>
        </div>
    </div>
    <style>
        /* Remove most custom styles, use dashboard.css for layout */
        .dashboard-section { margin-bottom: 30px; }
        .monitor-section { margin-bottom: 30px; }
        .timeline-container { background: white; border-radius: 10px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .timeline { position: relative; padding: 20px 0; }
        .timeline-item { display: flex; margin-bottom: 20px; position: relative; }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-icon { width: 40px; height: 40px; background: var(--secondary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; margin-right: 20px; flex-shrink: 0; font-size: 1.2rem; }
        .timeline-content { flex: 1; background: #f8f9fa; padding: 15px; border-radius: 5px; }
        .timeline-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .timeline-time { color: #666; font-size: 0.9rem; }
        .timeline-details { margin-top: 10px; display: flex; gap: 10px; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 500; }
        .badge-info { background: #17a2b8; color: white; }
        .badge-warning { background: #ffc107; color: #212529; }
        .badge-success { background: #28a745; color: white; }
        .audit-log-section { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .audit-log-recording-note { background: #f8f9fa; border-radius: 10px; padding: 20px; }
    </style>
</body>
</html> 