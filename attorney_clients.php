<?php
// AJAX handler for modal content (MUST be before any HTML output)
if (isset($_GET['ajax_client_details']) && isset($_GET['client_id'])) {
    require_once 'config.php';
    session_start();
    $attorney_id = $_SESSION['user_id'];
    $cid = intval($_GET['client_id']);
    // Get client info
    $cinfo = $conn->query("SELECT id, name, email, phone_number FROM user_form WHERE id=$cid")->fetch_assoc();
    // Get all cases for this client-attorney pair
    $cases = [];
    $cres = $conn->query("SELECT * FROM attorney_cases WHERE attorney_id=$attorney_id AND client_id=$cid ORDER BY created_at DESC");
    while ($row = $cres->fetch_assoc()) $cases[] = $row;
    // Get recent messages (last 10)
    $msgs = [];
    $mres = $conn->query("SELECT message, sent_at, 'client' as sender FROM client_messages WHERE client_id=$cid AND recipient_id=$attorney_id
        UNION ALL
        SELECT message, sent_at, 'attorney' as sender FROM attorney_messages WHERE attorney_id=$attorney_id AND recipient_id=$cid
        ORDER BY sent_at DESC LIMIT 10");
    while ($row = $mres->fetch_assoc()) $msgs[] = $row;
    ?>
    <div style="margin-bottom:18px;">
        <h2 style="margin-bottom:6px;">Client: <?= htmlspecialchars($cinfo['name']) ?></h2>
        <div><b>Email:</b> <?= htmlspecialchars($cinfo['email']) ?> | <b>Phone:</b> <?= htmlspecialchars($cinfo['phone_number']) ?></div>
    </div>
    <div>
        <h3>Cases</h3>
        <div class="case-list">
            <?php if (count($cases) === 0): ?>
                <div style="color:#888;">No cases for this client.</div>
            <?php else: foreach ($cases as $case): ?>
                <div class="case-item">
                    <b>#<?= htmlspecialchars($case['id']) ?> - <?= htmlspecialchars($case['title']) ?></b> (<?= htmlspecialchars($case['status']) ?>)
                    <div style="font-size:0.97em; color:#666;">Type: <?= htmlspecialchars($case['case_type']) ?> | Next Hearing: <?= htmlspecialchars($case['next_hearing']) ?></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
    <div class="chat-area">
        <h3 style="margin-bottom:8px;">Recent Messages</h3>
        <?php if (count($msgs) === 0): ?>
            <div style="color:#888;">No messages yet.</div>
        <?php else: foreach (array_reverse($msgs) as $m): ?>
            <div class="chat-bubble <?= $m['sender'] === 'attorney' ? 'sent' : 'received' ?>">
                <b><?= $m['sender'] === 'attorney' ? 'You' : 'Client' ?>:</b> <?= htmlspecialchars($m['message']) ?>
                <div class="chat-meta">Sent at: <?= htmlspecialchars($m['sent_at']) ?></div>
            </div>
        <?php endforeach; endif; ?>
    </div>
    <?php
    exit();
}
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
// Fetch all clients for this attorney
$clients = [];
$res = $conn->query("SELECT uf.id, uf.name, uf.email, uf.phone_number FROM user_form uf WHERE uf.user_type='client' AND uf.id IN (SELECT client_id FROM attorney_cases WHERE attorney_id=$attorney_id)");
while ($row = $res->fetch_assoc()) $clients[] = $row;
// Total clients
$total_clients = count($clients);
// Active cases for this attorney
$active_cases = $conn->query("SELECT COUNT(*) FROM attorney_cases WHERE attorney_id=$attorney_id AND status='Active'")->fetch_row()[0];
// Unread messages for this attorney (from all clients)
$unread_messages = $conn->query("SELECT COUNT(*) FROM client_messages WHERE recipient_id=$attorney_id")->fetch_row()[0];
// Upcoming appointments (next 7 days)
$today = date('Y-m-d');
$next_week = date('Y-m-d', strtotime('+7 days'));
$upcoming_appointments = $conn->query("SELECT COUNT(*) FROM case_schedules WHERE attorney_id=$attorney_id AND date BETWEEN '$today' AND '$next_week'")->fetch_row()[0];
// For each client, get their active cases count and last contact (last message or case update)
$client_details = [];
foreach ($clients as $c) {
    $cid = $c['id'];
    $active = $conn->query("SELECT COUNT(*) FROM attorney_cases WHERE attorney_id=$attorney_id AND client_id=$cid AND status='Active'")->fetch_row()[0];
    $last_msg = $conn->query("SELECT sent_at FROM (
        SELECT sent_at FROM client_messages WHERE client_id=$cid AND recipient_id=$attorney_id
        UNION ALL
        SELECT sent_at FROM attorney_messages WHERE attorney_id=$attorney_id AND recipient_id=$cid
        ORDER BY sent_at DESC LIMIT 1
    ) as t ORDER BY sent_at DESC LIMIT 1")->fetch_row()[0] ?? '-';
    $status = $active > 0 ? 'Active' : 'Inactive';
    $client_details[] = [
        'id' => $cid,
        'name' => $c['name'],
        'email' => $c['email'],
        'phone' => $c['phone_number'],
        'active_cases' => $active,
        'last_contact' => $last_msg,
        'status' => $status
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management - Opiña Law Office</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .clickable-row { cursor: pointer; transition: background 0.15s; }
        .clickable-row:hover { background: #f0f6ff; }
        .modal-bg { display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.3); z-index:1000; }
        .modal-content {
            background:#fff;
            border-radius:10px;
            max-width:600px;
            margin:60px auto;
            padding:32px;
            position:relative;
            max-height:80vh;
            overflow-y:auto;
            word-wrap:break-word;
        }
        .close-modal { position:absolute; top:16px; right:20px; font-size:1.5em; cursor:pointer; color:#888; }
        .case-list { margin-top:18px; }
        .case-item { background:#f8f9fa; border-radius:8px; padding:10px 16px; margin-bottom:10px; }
        .section-divider { border-bottom:1px solid #e0e0e0; margin:24px 0 16px 0; }
        .recent-messages {
            margin-top:28px;
            max-height:180px;
            overflow-y:auto;
        }
        .recent-messages .msg { margin-bottom:10px; padding:10px 14px; border-radius:8px; background:#f3f7fa; display:inline-block; word-break:break-word; }
        .recent-messages .msg.you { background:#e3f0ff; }
        .section-title { font-size:1.2em; font-weight:600; margin-bottom:10px; margin-top:18px; }
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
            <li><a href="attorney_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="attorney_cases.php"><i class="fas fa-gavel"></i><span>Manage Cases</span></a></li>
            <li><a href="attorney_documents.php"><i class="fas fa-file-alt"></i><span>Document Storage</span></a></li>
            <li><a href="attorney_document_generation.php"><i class="fas fa-file-alt"></i><span>Document Generation</span></a></li>
            <li><a href="attorney_schedule.php"><i class="fas fa-calendar-alt"></i><span>My Schedule</span></a></li>
            <li><a href="attorney_clients.php" class="active"><i class="fas fa-users"></i><span>My Clients</span></a></li>
            <li><a href="attorney_messages.php"><i class="fas fa-envelope"></i><span>Messages</span></a></li>
            <li><a href="attorney_efiling.php"><i class="fas fa-paper-plane"></i><span>E-Filing</span></a></li>
            <li><a href="attorney_audit.php"><i class="fas fa-history"></i><span>Audit Trail</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <div class="header-title">
                <h1>Client Management</h1>
                <p>Manage your clients and their cases</p>
            </div>
            <div class="user-info">
                <img src="<?= htmlspecialchars($profile_image) ?>" alt="Attorney" style="object-fit:cover;width:60px;height:60px;border-radius:50%;border:2px solid #1976d2;">
                <div class="user-details">
                    <h3><?php echo $_SESSION['attorney_name']; ?></h3>
                    <p>Attorney at Law</p>
                </div>
            </div>
        </div>
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-icon"><i class="fas fa-users"></i></div>
                <div class="card-info"><h3>Total Clients</h3><p><?= $total_clients ?></p></div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-gavel"></i></div>
                <div class="card-info"><h3>Active Cases</h3><p><?= $active_cases ?></p></div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-envelope"></i></div>
                <div class="card-info"><h3>Unread Messages</h3><p><?= $unread_messages ?></p></div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="card-info"><h3>Upcoming Appointments</h3><p><?= $upcoming_appointments ?></p></div>
            </div>
        </div>
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Client List</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Client ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Active Cases</th>
                        <th>Last Contact</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($client_details as $c): ?>
                    <tr class="clickable-row" data-client-id="<?= $c['id'] ?>" data-client-name="<?= htmlspecialchars($c['name']) ?>">
                        <td><?= htmlspecialchars($c['id']) ?></td>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><?= htmlspecialchars($c['phone']) ?></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><?= $c['active_cases'] ?></td>
                        <td><?= $c['last_contact'] ?></td>
                        <td><span class="status-badge status-<?= strtolower($c['status']) ?>"><?= $c['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Client Details Modal -->
    <div class="modal-bg" id="clientModalBg">
        <div class="modal-content" id="client-modal-content">
            <span class="close-modal" onclick="closeClientModal()">&times;</span>
            <div id="clientModalBody">
                <!-- AJAX content here -->
            </div>
        </div>
    </div>
    <script>
    function closeClientModal() {
        document.getElementById('clientModalBg').style.display = 'none';
        document.getElementById('clientModalBody').innerHTML = '';
    }
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function() {
            const clientId = this.getAttribute('data-client-id');
            const clientName = this.getAttribute('data-client-name');
            fetch('attorney_clients.php?ajax_client_details=1&client_id=' + clientId)
                .then(r => r.text())
                .then(html => {
                    document.getElementById('clientModalBody').innerHTML = html;
                    document.getElementById('clientModalBg').style.display = 'block';
                });
        });
    });
    </script>
<?php
?> 