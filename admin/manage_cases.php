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
    <title>Manage Cases - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> <span>Manage Users</span></a></li>
                <li><a href="manage_cases.php" class="active"><i class="fas fa-gavel"></i> <span>Manage Cases</span></a></li>
                <li><a href="manage_appointments.php"><i class="fas fa-calendar"></i> <span>Manage Appointments</span></a></li>
                <li><a href="admin_documents.php"><i class="fas fa-file-alt"></i> <span>Document Storage</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
                <li><a href="admin_audit.php"><i class="fas fa-history"></i> <span>Audit Trail</span></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <h2>Manage Cases</h2>
                <div class="user-info">
                    <span>Welcome, <?php echo $_SESSION['admin_name']; ?></span>
                </div>
            </div>

            <div class="content">
                <div class="action-bar">
                    <button class="btn-primary" onclick="showAddCaseModal()">
                        <i class="fas fa-plus"></i> Add New Case
                    </button>
                    <div class="filters">
                        <select>
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="closed">Closed</option>
                            <option value="pending">Pending</option>
                        </select>
                        <select>
                            <option value="">All Types</option>
                            <option value="criminal">Criminal</option>
                            <option value="civil">Civil</option>
                            <option value="family">Family</option>
                        </select>
                    </div>
                    <div class="search-bar">
                        <input type="text" placeholder="Search cases...">
                        <button><i class="fas fa-search"></i></button>
                    </div>
                </div>

                <div class="cases-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Title</th>
                                <th>Client</th>
                                <th>Attorney</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Date Filed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#2023-001</td>
                                <td>Smith vs. Johnson</td>
                                <td>John Smith</td>
                                <td>Sarah Wilson</td>
                                <td>Civil</td>
                                <td><span class="status active">Active</span></td>
                                <td>2023-01-15</td>
                                <td>
                                    <button class="btn-view"><i class="fas fa-eye"></i></button>
                                    <button class="btn-edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn-delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>#2023-002</td>
                                <td>State vs. Brown</td>
                                <td>Michael Brown</td>
                                <td>David Lee</td>
                                <td>Criminal</td>
                                <td><span class="status pending">Pending</span></td>
                                <td>2023-02-20</td>
                                <td>
                                    <button class="btn-view"><i class="fas fa-eye"></i></button>
                                    <button class="btn-edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn-delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <button class="btn-prev"><i class="fas fa-chevron-left"></i></button>
                    <span>Page 1 of 5</span>
                    <button class="btn-next"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Case Modal -->
    <div id="addCaseModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add New Case</h3>
            <form>
                <div class="form-group">
                    <label>Case Title</label>
                    <input type="text" required>
                </div>
                <div class="form-group">
                    <label>Case Type</label>
                    <select required>
                        <option value="">Select Type</option>
                        <option value="criminal">Criminal</option>
                        <option value="civil">Civil</option>
                        <option value="family">Family</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Client</label>
                    <select required>
                        <option value="">Select Client</option>
                        <option value="1">John Smith</option>
                        <option value="2">Jane Doe</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Attorney</label>
                    <select required>
                        <option value="">Select Attorney</option>
                        <option value="1">Sarah Wilson</option>
                        <option value="2">David Lee</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea rows="4" required></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-save">Save Case</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddCaseModal() {
            document.getElementById('addCaseModal').style.display = 'block';
        }

        // Close modal when clicking the X button
        document.querySelector('.close').onclick = function() {
            document.getElementById('addCaseModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('addCaseModal')) {
                document.getElementById('addCaseModal').style.display = 'none';
            }
        }
    </script>
</body>
</html> 