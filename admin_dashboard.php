<?php
session_start();
if (!isset($_SESSION['admin_name']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login_form.php');
    exit();
}
require_once 'config.php';

// Total cases
$total_cases = $conn->query("SELECT COUNT(*) FROM attorney_cases")->fetch_row()[0];
// Total documents
$total_documents = $conn->query("SELECT COUNT(*) FROM admin_documents")->fetch_row()[0] + $conn->query("SELECT COUNT(*) FROM attorney_documents")->fetch_row()[0];
// Total users
$total_users = $conn->query("SELECT COUNT(*) FROM user_form")->fetch_row()[0];
// Upcoming hearings (next 7 days)
$today = date('Y-m-d');
$next_week = date('Y-m-d', strtotime('+7 days'));
$upcoming_hearings = $conn->query("SELECT COUNT(*) FROM case_schedules WHERE date BETWEEN '$today' AND '$next_week'")->fetch_row()[0];
// Case status distribution
$status_counts = [];
$res = $conn->query("SELECT status, COUNT(*) as cnt FROM attorney_cases GROUP BY status");
while ($row = $res->fetch_assoc()) {
    $status_counts[$row['status'] ?? 'Unknown'] = (int)$row['cnt'];
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
    <title>Admin Dashboard - Opiña Law Office</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-cards { display: flex; gap: 24px; margin-bottom: 32px; flex-wrap: wrap; }
        .card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 24px 32px; flex: 1; min-width: 220px; }
        .card-title { font-size: 1.1em; color: #1976d2; margin-bottom: 8px; }
        .card-value { font-size: 2.2em; font-weight: 700; color: #222; }
        .card-description { color: #888; font-size: 1em; }
        .dashboard-graph { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 24px 32px; margin-bottom: 32px; }
        @media (max-width: 900px) { .dashboard-cards { flex-direction: column; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="images/logo.jpg" alt="Logo">
            <h2>Opiña Law Office</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="admin_documents.php"><i class="fas fa-file-alt"></i><span>Document Storage</span></a></li>
            <li><a href="admin_document_generation.php"><i class="fas fa-file-alt"></i><span>Document Generations</span></a></li>
            <li><a href="admin_schedule.php"><i class="fas fa-calendar-alt"></i><span>Schedule</span></a></li>
            <li><a href="admin_usermanagement.php"><i class="fas fa-users-cog"></i><span>User Management</span></a></li>
            <li><a href="admin_audit.php"><i class="fas fa-history"></i><span>Audit Trail</span></a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Dashboard</h1>
                <p>Overview of your activities and statistics</p>
            </div>
            <div class="user-info">
                <div class="profile-dropdown" id="profileDropdown">
                    <button class="profile-dropdown-btn" onclick="toggleProfileDropdown(event)">
                        <img src="<?= htmlspecialchars($profile_image) ?>" alt="Admin" style="object-fit:cover;width:60px;height:60px;border-radius:50%;border:2px solid #1976d2;">
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="profile-dropdown-content" id="profileDropdownContent">
                        <a href="#" onclick="document.getElementById('profileUpload').click(); return false;">Update Profile Image</a>
                        <a href="logout.php">Logout</a>
                    </div>
                    <form action="upload_profile_image.php" method="POST" enctype="multipart/form-data" style="display:none;" id="profileUploadForm">
                        <input type="file" id="profileUpload" name="profile_image" onchange="document.getElementById('profileUploadForm').submit()">
                    </form>
                </div>
                <div class="user-details">
                    <h3><?php echo $_SESSION['admin_name']; ?></h3>
                    <p>Administrator</p>
                </div>
            </div>
        </div>
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-title">Total Cases</div>
                <div class="card-value"><?= $total_cases ?></div>
                <div class="card-description">All cases in the system</div>
            </div>
            <div class="card">
                <div class="card-title">Total Documents</div>
                <div class="card-value"><?= $total_documents ?></div>
                <div class="card-description">All uploaded documents</div>
            </div>
            <div class="card">
                <div class="card-title">Total Users</div>
                <div class="card-value"><?= $total_users ?></div>
                <div class="card-description">All registered users</div>
            </div>
            <div class="card">
                <div class="card-title">Upcoming Hearings (7 days)</div>
                <div class="card-value"><?= $upcoming_hearings ?></div>
                <div class="card-description">Hearings scheduled this week</div>
            </div>
        </div>
        <div class="dashboard-graph">
            <h2 style="margin-bottom:18px;">Case Status Distribution</h2>
            <canvas id="caseStatusChart" height="100"></canvas>
        </div>
    </div>
    <script>
    const ctx = document.getElementById('caseStatusChart').getContext('2d');
    const data = {
        labels: <?= json_encode(array_keys($status_counts)) ?>,
        datasets: [{
            label: 'Cases',
            data: <?= json_encode(array_values($status_counts)) ?>,
            backgroundColor: [
                '#1976d2', '#43a047', '#fbc02d', '#e53935', '#8e24aa', '#00acc1', '#f57c00', '#757575'
            ],
        }]
    };
    new Chart(ctx, {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    </script>
    <script>
function toggleProfileDropdown(e) {
    e.preventDefault();
    document.getElementById('profileDropdown').classList.toggle('show');
    document.addEventListener('click', function handler(event) {
        if (!document.getElementById('profileDropdown').contains(event.target)) {
            document.getElementById('profileDropdown').classList.remove('show');
            document.removeEventListener('click', handler);
        }
    });
}
</script>
</body>
</html> 