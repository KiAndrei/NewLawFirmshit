<?php
session_start();
if (!isset($_SESSION['attorney_name']) || $_SESSION['user_type'] !== 'attorney') {
    header('Location: login_form.php');
    exit();
}
require_once 'config.php';
$attorney_id = $_SESSION['user_id'];
$res = $conn->query("SELECT profile_image FROM user_form WHERE id=$attorney_id");
$profile_image = '';
if ($res && $row = $res->fetch_assoc()) {
    $profile_image = $row['profile_image'];
}
if (!$profile_image || !file_exists($profile_image)) {
    $profile_image = 'assets/images/attorney-avatar.png';
}
// Total cases handled
$total_cases = $conn->query("SELECT COUNT(*) FROM attorney_cases WHERE attorney_id=$attorney_id")->fetch_row()[0];
// Total documents uploaded
$total_documents = $conn->query("SELECT COUNT(*) FROM attorney_documents WHERE uploaded_by=$attorney_id AND uploaded_by IS NOT NULL AND uploaded_by > 0")->fetch_row()[0];
// Total clients
//$total_clients = $conn->query("SELECT COUNT(DISTINCT client_id) FROM attorney_cases WHERE attorney_id=$attorney_id AND client_id IS NOT NULL AND client_id > 0")->fetch_row()[0];
$clients_res = $conn->query("SELECT uf.id FROM user_form uf WHERE uf.user_type='client' AND uf.id IN (SELECT client_id FROM attorney_cases WHERE attorney_id=$attorney_id)");
$total_clients = $clients_res ? $clients_res->num_rows : 0;
// Upcoming hearings (next 7 days)
$today = date('Y-m-d');
$next_week = date('Y-m-d', strtotime('+7 days'));
$upcoming_events = $conn->query("SELECT COUNT(*) FROM case_schedules WHERE attorney_id=$attorney_id AND date BETWEEN '$today' AND '$next_week' AND type IN ('Hearing','Appointment')")->fetch_row()[0];
// Case status distribution for this attorney
$status_counts = [];
$res = $conn->query("SELECT status, COUNT(*) as cnt FROM attorney_cases WHERE attorney_id=$attorney_id GROUP BY status");
while ($row = $res->fetch_assoc()) {
    $status_counts[$row['status'] ?? 'Unknown'] = (int)$row['cnt'];
}
// Upcoming hearings table (next 5)
$hearings = [];
$res = $conn->query("SELECT cs.*, ac.title as case_title, uf.name as client_name FROM case_schedules cs LEFT JOIN attorney_cases ac ON cs.case_id = ac.id LEFT JOIN user_form uf ON cs.client_id = uf.id WHERE cs.attorney_id=$attorney_id AND cs.date >= '$today' ORDER BY cs.date, cs.time LIMIT 5");
while ($row = $res->fetch_assoc()) $hearings[] = $row;
// Recent activity (last 5): cases, documents, messages, hearings
$recent = [];
// Cases
$res = $conn->query("SELECT id, title, created_at FROM attorney_cases WHERE attorney_id=$attorney_id ORDER BY created_at DESC LIMIT 2");
while ($row = $res->fetch_assoc()) $recent[] = ['type'=>'Case','title'=>$row['title'],'date'=>$row['created_at']];
// Documents
$res = $conn->query("SELECT file_name, upload_date FROM attorney_documents WHERE uploaded_by=$attorney_id ORDER BY upload_date DESC LIMIT 2");
while ($row = $res->fetch_assoc()) $recent[] = ['type'=>'Document','title'=>$row['file_name'],'date'=>$row['upload_date']];
// Messages
$res = $conn->query("SELECT message, sent_at FROM attorney_messages WHERE attorney_id=$attorney_id ORDER BY sent_at DESC LIMIT 1");
while ($row = $res->fetch_assoc()) $recent[] = ['type'=>'Message','title'=>mb_strimwidth($row['message'],0,30,'...'),'date'=>$row['sent_at']];
// Hearings
$res = $conn->query("SELECT title, date, time FROM case_schedules WHERE attorney_id=$attorney_id ORDER BY date DESC, time DESC LIMIT 1");
while ($row = $res->fetch_assoc()) $recent[] = ['type'=>'Hearing','title'=>$row['title'],'date'=>$row['date'].' '.$row['time']];
// Sort by date desc
usort($recent, function($a,$b){ return strtotime($b['date'])-strtotime($a['date']); });
$recent = array_slice($recent,0,5);
// Cases per month (bar chart)
$cases_per_month = array_fill(1,12,0);
$year = date('Y');
$res = $conn->query("SELECT MONTH(created_at) as m, COUNT(*) as cnt FROM attorney_cases WHERE attorney_id=$attorney_id AND YEAR(created_at)=$year GROUP BY m");
while ($row = $res->fetch_assoc()) $cases_per_month[(int)$row['m']] = (int)$row['cnt'];
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-cards { display: flex; gap: 24px; margin-bottom: 32px; flex-wrap: wrap; }
        .card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 24px 32px; flex: 1; min-width: 220px; }
        .card-title { font-size: 1.1em; color: #1976d2; margin-bottom: 8px; }
        .card-value { font-size: 2.2em; font-weight: 700; color: #222; }
        .card-description { color: #888; font-size: 1em; }
        .dashboard-graph { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 24px 32px; margin-bottom: 32px; }
        .quick-actions { display: flex; gap: 16px; margin-bottom: 28px; }
        .quick-actions .btn { padding: 12px 22px; border-radius: 8px; font-size: 1em; font-weight: 500; border: none; background: #1976d2; color: #fff; cursor: pointer; transition: background 0.2s; }
        .quick-actions .btn:hover { background: #1251a3; }
        .upcoming-table, .recent-table { width: 100%; border-collapse: collapse; margin-bottom: 32px; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
        .upcoming-table th, .upcoming-table td, .recent-table th, .recent-table td { padding: 10px 14px; text-align: left; }
        .upcoming-table th, .recent-table th { background: #f3f6fa; color: #1976d2; }
        .upcoming-table tr:not(:last-child) td, .recent-table tr:not(:last-child) td { border-bottom: 1px solid #eee; }
        .recent-table th, .recent-table td { font-size: 0.98em; }
        @media (max-width: 900px) { .dashboard-cards { flex-direction: column; } .quick-actions { flex-direction: column; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="images/logo.jpg" alt="Logo">
            <h2>Opiña Law Office</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="attorney_dashboard.php" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="attorney_cases.php"><i class="fas fa-gavel"></i><span>Manage Cases</span></a></li>
            <li><a href="attorney_documents.php"><i class="fas fa-file-alt"></i><span>Document Storage</span></a></li>
            <li><a href="attorney_document_generation.php"><i class="fas fa-file-alt"></i><span>Document Generation</span></a></li>
            <li><a href="attorney_schedule.php"><i class="fas fa-calendar-alt"></i><span>My Schedule</span></a></li>
            <li><a href="attorney_clients.php"><i class="fas fa-users"></i><span>My Clients</span></a></li>
            <li><a href="attorney_messages.php"><i class="fas fa-envelope"></i><span>Messages</span></a></li>
            <li><a href="attorney_efiling.php"><i class="fas fa-paper-plane"></i><span>E-Filing</span></a></li>
            <li><a href="attorney_audit.php"><i class="fas fa-history"></i><span>Audit Trail</span></a></li>
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
                <form action="upload_profile_image.php" method="POST" enctype="multipart/form-data" style="display:inline;">
                    <label for="profileUpload" style="cursor:pointer;">
                        <img src="<?= htmlspecialchars($profile_image) ?>" alt="Attorney" style="object-fit:cover;width:60px;height:60px;border-radius:50%;border:2px solid #1976d2;">
                        <input type="file" id="profileUpload" name="profile_image" style="display:none;" onchange="this.form.submit()">
                    </label>
                </form>
                <div class="user-details">
                    <h3><?php echo $_SESSION['attorney_name']; ?></h3>
                    <p>Attorney at Law</p>
                </div>
            </div>
        </div>
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-title">Total Cases Handled</div>
                <div class="card-value"><?= $total_cases ?></div>
                <div class="card-description">Cases you are handling</div>
            </div>
            <div class="card">
                <div class="card-title">Total Documents Uploaded</div>
                <div class="card-value"><?= $total_documents ?></div>
                <div class="card-description">Your uploaded documents</div>
            </div>
            <div class="card">
                <div class="card-title">Total Clients</div>
                <div class="card-value"><?= $total_clients ?></div>
                <div class="card-description">Unique clients you handle</div>
            </div>
            <div class="card">
                <div class="card-title">Upcoming Appointments & Hearings (7 days)</div>
                <div class="card-value"><?= $upcoming_events ?></div>
                <div class="card-description">Appointments and hearings scheduled this week</div>
            </div>
        </div>
        <div class="dashboard-graph">
            <h2 style="margin-bottom:18px;">Case Status Distribution</h2>
            <canvas id="caseStatusChart" height="100"></canvas>
        </div>
        <div class="dashboard-graph">
            <h2 style="margin-bottom:18px;">Cases Per Month (<?= $year ?>)</h2>
            <canvas id="casesPerMonthChart" height="100"></canvas>
        </div>
        <div style="margin-bottom:32px;">
            <h2 style="margin-bottom:12px;">Upcoming Hearings & Appointments</h2>
            <table class="upcoming-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Case</th>
                        <th>Client</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hearings as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['date']) ?></td>
                        <td><?= htmlspecialchars(date('h:i A', strtotime($h['time']))) ?></td>
                        <td><?= htmlspecialchars($h['type']) ?></td>
                        <td><?= htmlspecialchars($h['location']) ?></td>
                        <td><?= htmlspecialchars($h['case_title'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($h['client_name'] ?? '-') ?></td>
                        <td><span class="status-badge status-<?= strtolower($h['status'] ?? 'scheduled') ?>"><?= htmlspecialchars($h['status'] ?? '-') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($hearings) === 0): ?>
                    <tr><td colspan="7" style="color:#888;text-align:center;">No upcoming hearings or appointments.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-bottom:32px;">
            <h2 style="margin-bottom:12px;">Recent Activity</h2>
            <table class="recent-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title/Message</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['type']) ?></td>
                        <td><?= htmlspecialchars($r['title']) ?></td>
                        <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($r['date']))) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($recent) === 0): ?>
                    <tr><td colspan="3" style="color:#888;text-align:center;">No recent activity.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
    // Pie chart for case status
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
    // Bar chart for cases per month
    const ctx2 = document.getElementById('casesPerMonthChart').getContext('2d');
    const data2 = {
        labels: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
        datasets: [{
            label: 'Cases Created',
            data: <?= json_encode(array_values($cases_per_month)) ?>,
            backgroundColor: '#1976d2',
        }]
    };
    new Chart(ctx2, {
        type: 'bar',
        data: data2,
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, stepSize: 1 } }
        }
    });
    </script>
</body>
</html> 