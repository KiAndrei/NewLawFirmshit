<?php
session_start();
if (!isset($_SESSION['client_name']) || $_SESSION['user_type'] !== 'client') {
    header('Location: login_form.php');
    exit();
}
require_once 'config.php';
$client_id = $_SESSION['user_id'];
$res = $conn->query("SELECT profile_image FROM user_form WHERE id=$client_id");
$profile_image = '';
if ($res && $row = $res->fetch_assoc()) {
    $profile_image = $row['profile_image'];
}
if (!$profile_image || !file_exists($profile_image)) {
    $profile_image = 'assets/images/client-avatar.png';
}
// Total cases
$total_cases_attorney = $conn->query("SELECT COUNT(*) FROM attorney_cases WHERE client_id=$client_id")->fetch_row()[0];
$total_cases_client = $conn->query("SELECT COUNT(*) FROM client_cases WHERE client_id=$client_id")->fetch_row()[0];
$total_cases = $total_cases_attorney + $total_cases_client;
// Total documents (remove client_documents)
$total_documents = $conn->query("SELECT COUNT(*) FROM client_cases WHERE client_id=$client_id")->fetch_row()[0];
// Upcoming hearings (next 7 days)
$today = date('Y-m-d');
$next_week = date('Y-m-d', strtotime('+7 days'));
$upcoming_hearings = $conn->query("SELECT COUNT(*) FROM case_schedules WHERE client_id=$client_id AND date BETWEEN '$today' AND '$next_week'")->fetch_row()[0];
// Recent chat: get the latest message between this client and any attorney
$recent_chat = null;
$sql = "SELECT message, sent_at, 'client' as sender, recipient_id as attorney_id FROM client_messages WHERE client_id=$client_id
        UNION ALL
        SELECT message, sent_at, 'attorney' as sender, attorney_id as attorney_id FROM attorney_messages WHERE recipient_id=$client_id
        ORDER BY sent_at DESC LIMIT 1";
$res = $conn->query($sql);
if ($res && $row = $res->fetch_assoc()) {
    $recent_chat = $row;
}
// Case status distribution for this client
$status_counts = [];
$res = $conn->query("SELECT status, COUNT(*) as cnt FROM attorney_cases WHERE client_id=$client_id GROUP BY status");
while ($row = $res->fetch_assoc()) {
    $status_counts[$row['status'] ?? 'Unknown'] = (int)$row['cnt'];
}
// Fetch recent cases for notification (last 10)
$case_notifications = [];
$notif_stmt = $conn->prepare("SELECT title, created_at FROM attorney_cases WHERE client_id=? ORDER BY created_at DESC LIMIT 10");
$notif_stmt->bind_param('i', $client_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
while ($row = $notif_result->fetch_assoc()) {
    $case_notifications[] = $row;
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-cards { display: flex; gap: 24px; margin-bottom: 32px; flex-wrap: wrap; }
        .card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 24px 32px; flex: 1; min-width: 220px; }
        .card-title { font-size: 1.1em; color: #1976d2; margin-bottom: 8px; }
        .card-value { font-size: 2.2em; font-weight: 700; color: #222; }
        .card-description { color: #888; font-size: 1em; }
        .dashboard-graph { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 24px 32px; margin-bottom: 32px; }
        @media (max-width: 900px) { .dashboard-cards { flex-direction: column; } }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 18px;
            min-height: 56px;
        }
        .notification-btn {
            background: none;
            border: none;
            font-size: 1.4rem;
            color: #a94442;
            cursor: pointer;
            position: relative;
            padding: 0 6px;
        }
        .notif-badge {
            position: absolute;
            top: -6px;
            right: -2px;
            background: #a94442;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            border-radius: 50%;
            padding: 2px 6px;
            min-width: 18px;
            text-align: center;
        }
        .notification-list-dropdown {
            position: absolute;
            right: 0;
            top: 36px;
            background: #fff;
            min-width: 260px;
            max-width: 90vw;
            box-shadow: 0 4px 16px rgba(44,0,0,0.13);
            border-radius: 10px;
            z-index: 2000;
            padding: 0;
            display: none;
        }
        .notification-list-dropdown ul {
            list-style: none;
            margin: 0;
            padding: 0 0 6px 0;
            max-height: 260px;
            overflow-y: auto;
        }
        .notification-list-dropdown li {
            padding: 10px 18px 6px 18px;
            font-size: 0.97rem;
            border-bottom: 1px solid #f0eaea;
            color: #800000;
        }
        .notification-list-dropdown li:last-child { border-bottom: none; }
        .notif-dropdown-header {
            font-weight: 700;
            color: #a94442;
            padding: 10px 18px 6px 18px;
            border-bottom: 1px solid #f0eaea;
            background: #faf9f6;
            border-radius: 10px 10px 0 0;
        }
        .notif-empty { color: #888; text-align: center; padding: 18px 0; }
        .notif-date { color: #b8860b; font-size: 0.85em; margin-right: 6px; }
        .notif-title { color: #800000; }
        @media (max-width: 900px) { .header { flex-direction: column; align-items: flex-start; gap: 10px; } }
        .profile-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: #fff;
            min-width: 160px;
            box-shadow: 0 4px 16px rgba(44,0,0,0.13);
            z-index: 100;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
        }
        .profile-dropdown.show .profile-dropdown-content {
            display: block;
        }
        .profile-dropdown-content a {
            color: #800000;
            padding: 12px 18px;
            text-decoration: none;
            display: block;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
        }
        .profile-dropdown-content a:hover {
            background: #faf9f6;
            color: #a94442;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="images/logo.jpg" alt="Logo">
            <h2>Opiña Law Office</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="client_dashboard.php" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="client_documents.php"><i class="fas fa-file-alt"></i><span>Document Generation</span></a></li>
            <li><a href="client_cases.php"><i class="fas fa-gavel"></i><span>My Cases</span></a></li>
            <li><a href="client_schedule.php"><i class="fas fa-calendar-alt"></i><span>My Schedule</span></a></li>
            <li><a href="client_messages.php"><i class="fas fa-envelope"></i><span>Messages</span></a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>
    <div class="main-content">
        <!-- Header -->
        <div class="header" style="padding: 10px 20px; min-height: 56px;">
            <div class="header-title">
                <h1 style="font-size: 1.3rem; margin-bottom: 2px;">Dashboard</h1>
                <p style="font-size: 0.95rem; margin-bottom: 0;">Overview of your activities and statistics</p>
            </div>
            <div style="display: flex; align-items: center; gap: 18px;">
                <div class="notification-dropdown" style="position: relative;">
                    <button class="notification-btn" onclick="toggleNotifDropdown(event)">
                        <i class="fas fa-bell"></i>
                        <?php if (count($case_notifications) > 0): ?>
                        <span class="notif-badge"><?= count($case_notifications) ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="notification-list-dropdown" id="notifDropdown" style="display:none;">
                        <div class="notif-dropdown-header">Notifications</div>
                        <ul>
                        <?php if (count($case_notifications) === 0): ?>
                            <li class="notif-empty">No notifications yet.</li>
                        <?php else: ?>
                            <?php foreach ($case_notifications as $notif): ?>
                                <li>
                                    <span class="notif-date"><?= htmlspecialchars($notif['created_at']) ?>:</span>
                                    <span class="notif-title">New case: <b><?= htmlspecialchars($notif['title']) ?></b></span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <div class="user-info">
                    <div class="profile-dropdown" id="profileDropdown">
                        <button class="profile-dropdown-btn" onclick="toggleProfileDropdown(event)" style="background:none;border:none;padding:0;">
                            <img src="<?= htmlspecialchars($profile_image) ?>" alt="Client" style="object-fit:cover;width:44px;height:44px;border-radius:50%;border:2px solid #1976d2;box-shadow:0 2px 8px rgba(44,0,0,0.10);transition:box-shadow 0.2s;">
                        </button>
                        <div class="profile-dropdown-content" id="profileDropdownContent" style="right:0;min-width:160px;box-shadow:0 4px 16px rgba(44,0,0,0.13);border-radius:10px;overflow:hidden;background:#fff;">
                            <a href="#" onclick="document.getElementById('profileUpload').click(); return false;" style="padding:12px 18px;display:block;color:#800000;font-weight:500;text-decoration:none;transition:background 0.2s;">Update Profile</a>
                            <a href="logout.php" style="padding:12px 18px;display:block;color:#800000;font-weight:500;text-decoration:none;transition:background 0.2s;">Logout</a>
                        </div>
                        <form action="upload_profile_image.php" method="POST" enctype="multipart/form-data" style="display:none;" id="profileUploadForm">
                            <input type="file" id="profileUpload" name="profile_image" onchange="document.getElementById('profileUploadForm').submit()">
                        </form>
                    </div>
                    <div class="user-details">
                        <h3 style="font-size: 1.05rem; margin-bottom: 2px;"><?php echo $_SESSION['client_name']; ?></h3>
                        <p style="font-size: 0.85rem; color: #888; margin-bottom: 0;">Client</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-title">Total Cases</div>
                <div class="card-value"><?= $total_cases ?></div>
                <div class="card-description">Your cases</div>
            </div>
            <div class="card">
                <div class="card-title">Total Documents</div>
                <div class="card-value"><?= $total_documents ?></div>
                <div class="card-description">Your uploaded documents</div>
            </div>
            <div class="card">
                <div class="card-title">Upcoming Hearings (7 days)</div>
                <div class="card-value"><?= $upcoming_hearings ?></div>
                <div class="card-description">Hearings scheduled this week</div>
            </div>
            <div class="card">
                <div class="card-title">Recent Chat</div>
                <?php if ($recent_chat): ?>
                    <div style="font-size:1.1em;margin-bottom:4px;"><b><?= $recent_chat['sender'] === 'client' ? 'You' : 'Attorney' ?>:</b> <?= htmlspecialchars($recent_chat['message']) ?></div>
                    <div style="color:#888;font-size:0.95em;">Sent at: <?= htmlspecialchars($recent_chat['sent_at']) ?></div>
                <?php else: ?>
                    <div style="color:#888;">No recent chat yet.</div>
                <?php endif; ?>
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
        function toggleNotifDropdown(e) {
            e.stopPropagation();
            var dropdown = document.getElementById('notifDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            document.addEventListener('click', function handler(event) {
                if (!dropdown.contains(event.target) && event.target !== e.target) {
                    dropdown.style.display = 'none';
                    document.removeEventListener('click', handler);
                }
            });
        }
    </script>
</body>
</html> 