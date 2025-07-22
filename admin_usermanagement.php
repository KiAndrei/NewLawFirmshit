<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['admin_name']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login_form.php');
    exit();
}

// Fetch users by type
$admins = [];
$attorneys = [];
$clients = [];

$res = $conn->query("SELECT id, name, email, phone_number, user_type, last_login, account_locked FROM user_form ORDER BY user_type, name");
while ($row = $res->fetch_assoc()) {
    if ($row['user_type'] === 'admin') $admins[] = $row;
    elseif ($row['user_type'] === 'attorney') $attorneys[] = $row;
    elseif ($row['user_type'] === 'client') $clients[] = $row;
}

if (isset($_POST['delete_user_btn']) && isset($_POST['delete_user_id'])) {
    $delete_id = intval($_POST['delete_user_id']);
    // Prevent admin from deleting themselves
    if ($delete_id !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM user_form WHERE id = ?");
        $stmt->bind_param('i', $delete_id);
        $stmt->execute();
    }
    // Refresh to update the list
    header('Location: admin_usermanagement.php');
    exit();
}

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
    <title>User Management - Opiña Law Office</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .user-section {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            padding: 24px 20px;
        }
        .user-section h2 {
            margin-bottom: 10px;
            color: #2d3a4b;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .user-table th, .user-table td {
            padding: 10px 14px;
            text-align: left;
        }
        .user-table th {
            background: #f4f6f8;
            font-weight: 600;
        }
        .user-table tr:not(:last-child) {
            border-bottom: 1px solid #eee;
        }
        .user-table tr:hover {
            background: #f9fafb;
        }
        @media (max-width: 700px) {
            .user-section { padding: 10px 2px; }
            .user-table th, .user-table td { padding: 7px 4px; }
        }
    </style>
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
            <li><a href="admin_usermanagement.php" class="active"><i class="fas fa-users-cog"></i><span>User Management</span></a></li>
            <li><a href="admin_audit.php"><i class="fas fa-history"></i><span>Audit Trail</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header" style="display:flex;justify-content:space-between;align-items:center;padding:28px 32px 28px 32px;background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.09);margin-bottom:36px;">
            <div class="header-title" style="display:flex;flex-direction:column;gap:6px;">
                <h1 style="font-size:2.1em;color:#1976d2;margin:0;">User Management</h1>
                <p style="color:#555;font-size:1.08em;margin:0;">View all registered users by type: Admin, Attorney, and Client.</p>
            </div>
            <div class="user-info" style="display:flex;align-items:center;">
                <img src="<?= htmlspecialchars($profile_image) ?>" alt="Admin" style="object-fit:cover;width:60px;height:60px;border-radius:50%;border:2.5px solid #1976d2;margin-right:16px;">
                <div class="user-details">
                    <h3 style="font-size:1.15em;margin-bottom:4px;"><?php echo $_SESSION['admin_name']; ?></h3>
                    <p style="color:#1976d2;font-size:0.98em;margin:0;">System Administrator</p>
                </div>
            </div>
        </div>
        <div class="user-section">
            <h2>Admin Users</h2>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($admins) === 0): ?>
                        <tr><td colspan="4">No admin users found.</td></tr>
                    <?php else: foreach ($admins as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone_number']) ?></td>
                            <td><?= $user['last_login'] ? htmlspecialchars($user['last_login']) : 'Never' ?></td>
                            <td><span style="color:<?= $user['account_locked'] ? 'red' : 'green' ?>;font-weight:bold;">
                                <?= $user['account_locked'] ? 'Inactive' : 'Active' ?>
                            </span></td>
                            <td>
                                <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this user?');" style="display:inline;">
                                    <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="delete_user_btn" style="color:red;background:none;border:none;cursor:pointer;">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="user-section">
            <h2>Attorney Users</h2>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($attorneys) === 0): ?>
                        <tr><td colspan="4">No attorney users found.</td></tr>
                    <?php else: foreach ($attorneys as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone_number']) ?></td>
                            <td><?= $user['last_login'] ? htmlspecialchars($user['last_login']) : 'Never' ?></td>
                            <td><span style="color:<?= $user['account_locked'] ? 'red' : 'green' ?>;font-weight:bold;">
                                <?= $user['account_locked'] ? 'Inactive' : 'Active' ?>
                            </span></td>
                            <td>
                                <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this user?');" style="display:inline;">
                                    <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="delete_user_btn" style="color:red;background:none;border:none;cursor:pointer;">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="user-section">
            <h2>Client Users</h2>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($clients) === 0): ?>
                        <tr><td colspan="4">No client users found.</td></tr>
                    <?php else: foreach ($clients as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone_number']) ?></td>
                            <td><?= $user['last_login'] ? htmlspecialchars($user['last_login']) : 'Never' ?></td>
                            <td><span style="color:<?= $user['account_locked'] ? 'red' : 'green' ?>;font-weight:bold;">
                                <?= $user['account_locked'] ? 'Inactive' : 'Active' ?>
                            </span></td>
                            <td>
                                <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this user?');" style="display:inline;">
                                    <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="delete_user_btn" style="color:red;background:none;border:none;cursor:pointer;">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 