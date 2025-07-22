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
    <title>Manage Users - Admin Dashboard</title>
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
                <li><a href="manage_users.php" class="active"><i class="fas fa-users"></i> <span>Manage Users</span></a></li>
                <li><a href="manage_cases.php"><i class="fas fa-gavel"></i> <span>Manage Cases</span></a></li>
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
                <h2>Manage Users</h2>
                <div class="user-info">
                    <span>Welcome, <?php echo $_SESSION['admin_name']; ?></span>
                </div>
            </div>

            <div class="content">
                <div class="action-bar">
                    <button class="btn-primary" onclick="showAddUserModal()">
                        <i class="fas fa-plus"></i> Add New User
                    </button>
                    <div class="search-bar">
                        <input type="text" placeholder="Search users...">
                        <button><i class="fas fa-search"></i></button>
                    </div>
                </div>

                <div class="users-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>John Doe</td>
                                <td>john@example.com</td>
                                <td>Attorney</td>
                                <td><span class="status active">Active</span></td>
                                <td>
                                    <button class="btn-edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn-delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Jane Smith</td>
                                <td>jane@example.com</td>
                                <td>Client</td>
                                <td><span class="status inactive">Inactive</span></td>
                                <td>
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

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add New User</h3>
            <form>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select required>
                        <option value="">Select Role</option>
                        <option value="attorney">Attorney</option>
                        <option value="client">Client</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-save">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddUserModal() {
            document.getElementById('addUserModal').style.display = 'block';
        }

        // Close modal when clicking the X button
        document.querySelector('.close').onclick = function() {
            document.getElementById('addUserModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('addUserModal')) {
                document.getElementById('addUserModal').style.display = 'none';
            }
        }
    </script>
</body>
</html> 