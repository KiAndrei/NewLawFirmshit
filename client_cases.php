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
$cases = [];
// Fetch attorney-assigned cases
$sql1 = "SELECT ac.*, uf.name as attorney_name, 'attorney' as source FROM attorney_cases ac LEFT JOIN user_form uf ON ac.attorney_id = uf.id WHERE ac.client_id=? ORDER BY ac.created_at DESC";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param('i', $client_id);
$stmt1->execute();
$result1 = $stmt1->get_result();
while ($row = $result1->fetch_assoc()) {
    $cases[] = $row;
}
// Fetch client-created cases
$sql2 = "SELECT cc.*, NULL as attorney_name, 'client' as source FROM client_cases cc WHERE cc.client_id=? ORDER BY cc.created_at DESC";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param('i', $client_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
while ($row = $result2->fetch_assoc()) {
    $cases[] = $row;
}
// Sort all cases by created_at DESC, id DESC
usort($cases, function($a, $b) {
    $dateA = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
    $dateB = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
    if ($dateA === $dateB) return ($b['id'] ?? 0) - ($a['id'] ?? 0);
    return $dateB - $dateA;
});
// Fetch recent cases for notification (last 10)
$case_notifications = [];
$notif_stmt = $conn->prepare("SELECT title, created_at FROM attorney_cases WHERE client_id=? ORDER BY created_at DESC LIMIT 10");
$notif_stmt->bind_param('i', $client_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
while ($row = $notif_result->fetch_assoc()) {
    $case_notifications[] = $row;
}
// Mark all cases as read for this client
$conn->query("UPDATE client_cases SET is_read=1 WHERE client_id=$client_id AND is_read=0");
if (isset($_POST['action']) && $_POST['action'] === 'add_case') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $case_type = $_POST['case_type'];
    $status = 'Pending'; // Always set to Pending for new cases
    $next_hearing = !empty($_POST['next_hearing']) ? $_POST['next_hearing'] : null;
    $stmt = $conn->prepare("INSERT INTO client_cases (title, description, case_type, status, next_hearing, client_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssi', $title, $description, $case_type, $status, $next_hearing, $client_id);
    $stmt->execute();
    echo $stmt->affected_rows > 0 ? 'success' : 'error';
    exit();
}
if (isset($_GET['fetch_last_case']) && $_GET['fetch_last_case'] == 1) {
    $sql = "SELECT * FROM client_cases WHERE client_id=? ORDER BY created_at DESC, id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode($row);
    exit();
}
// Notification logic
$new_cases = array_filter($cases, function($c) { return isset($c['is_read']) && $c['is_read'] == 0; });
if (count($new_cases) > 0): ?>
<div class="notification-area" style="background:#eaffea; border:1px solid #28a745; color:#28a745; margin-bottom:20px; border-radius:8px; padding:12px;">
    <i class="fas fa-bell"></i> You have <?= count($new_cases) ?> new case<?= count($new_cases) > 1 ? 's' : '' ?> assigned by your attorney!
</div>
<?php endif; ?>
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Tracking - Opiña Law Office</title>
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
            <li><a href="client_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="client_documents.php"><i class="fas fa-file-alt"></i><span>Document Generation</span></a></li>
            <li><a href="client_cases.php" class="active"><i class="fas fa-gavel"></i><span>My Cases</span></a></li>
            <li><a href="client_schedule.php"><i class="fas fa-calendar-alt"></i><span>My Schedule</span></a></li>
            <li><a href="client_messages.php"><i class="fas fa-envelope"></i><span>Messages</span></a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header" style="padding: 10px 20px; min-height: 56px;">
            <div class="header-title">
                <h1 style="font-size: 1.3rem; margin-bottom: 2px;">My Cases</h1>
                <p style="font-size: 0.95rem; margin-bottom: 0;">Track your cases, status, and schedule</p>
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
        <div class="notification-area">
            <div class="notification-header">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </div>
            <ul class="notification-list" id="notificationList">
                <?php if (count($case_notifications) === 0): ?>
                    <li>No notifications yet.</li>
                <?php else: ?>
                    <?php foreach ($case_notifications as $notif): ?>
                        <li>
                            <span class="notif-date"><?= htmlspecialchars($notif['created_at']) ?>:</span>
                            You have a new case assigned by your attorney: <b><?= htmlspecialchars($notif['title']) ?></b>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="cases-container">
            <div class="cases-header">
                <button class="btn btn-primary" onclick="openAddCaseModal()"><i class="fas fa-plus"></i> Add New Case</button>
            </div>
            <table class="cases-table">
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Attorney</th>
                        <th>Next Hearing</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="casesTableBody">
                    <?php foreach ($cases as $case): ?>
                    <tr>
                        <td><?= htmlspecialchars($case['id']) ?></td>
                        <td><?= htmlspecialchars($case['title']) ?></td>
                        <td><?= htmlspecialchars(ucfirst(strtolower($case['case_type'] ?? '-'))) ?></td>
                        <td><span class="status-badge status-<?= strtolower($case['status'] ?? 'active') ?>"><?= htmlspecialchars($case['status'] ?? '-') ?></span></td>
                        <td><?= $case['source'] === 'attorney' ? htmlspecialchars($case['attorney_name'] ?? '-') : '<span style=\'color:#888\'>-</span>' ?></td>
                        <td><?= htmlspecialchars($case['next_hearing'] ?? '-') ?></td>
                        <td>
                            <?php if ($case['source'] === 'attorney' && isset($case['attorney_id'])): ?>
                                <button class="btn btn-primary btn-xs" onclick="openConversationModal(<?= $case['attorney_id'] ?>, '<?= htmlspecialchars(addslashes($case['attorney_name'])) ?>')">
                                    <i class="fas fa-comments"></i> View Conversation
                                </button>
                            <?php else: ?>
                                <span style="color:#888;font-size:0.95em;">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Case Details Modal -->
        <div class="modal" id="caseModal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Case Details</h2>
                    <button class="close-modal" onclick="closeCaseModal()">&times;</button>
                </div>
                <div class="modal-body" id="caseModalBody">
                    <!-- Dynamic case details here -->
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeCaseModal()">Close</button>
                </div>
            </div>
        </div>
        <!-- Add Case Modal -->
        <div class="modal-overlay" id="addCaseOverlay" style="display:none;"></div>
        <div class="modal" id="addCaseModal" style="display:none;z-index:1001;">
            <div class="modal-content modern-modal">
                <div class="modal-header">
                    <h2>Add New Case</h2>
                    <button class="close-modal" onclick="closeAddCaseModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="addCaseForm">
                        <div class="form-group">
                            <label>Case Title</label>
                            <input type="text" name="title" id="caseTitle" required>
                        </div>
                        <div class="form-group">
                            <label>Case Type</label>
                            <select name="case_type" id="caseType" required>
                                <option value="">Select Type</option>
                                <option value="Criminal">Criminal</option>
                                <option value="Civil">Civil</option>
                                <option value="Family">Family</option>
                                <option value="Corporate">Corporate</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Next Hearing</label>
                            <input type="date" name="next_hearing" id="caseNextHearing">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" id="caseDescription" required></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeAddCaseModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Case</button>
                        </div>
                    </form>
                    <div id="caseSuccessMsg" style="display:none; color:green; margin-top:10px;">Case added successfully!</div>
                </div>
            </div>
        </div>
        <!-- Conversation Modal -->
        <div class="modal" id="conversationModal" style="display:none;">
            <div class="modal-content" style="max-width:600px;">
                <div class="modal-header">
                    <h2>Conversation with <span id="convAttorneyName"></span></h2>
                    <button class="close-modal" onclick="closeConversationModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="chat-messages" id="convChatMessages" style="height:300px;overflow-y:auto;background:#f9f9f9;padding:16px;border-radius:8px;margin-bottom:10px;"></div>
                    <div class="chat-compose" id="convChatCompose" style="display:flex;gap:10px;">
                        <textarea id="convMessageInput" placeholder="Type your message..." style="flex:1;border-radius:8px;border:1px solid #ddd;padding:10px;resize:none;font-size:1rem;"></textarea>
                        <button class="btn btn-primary" onclick="sendConvMessage()">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        .cases-container { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 24px; margin-top: 24px; }
        .cases-table { width: 100%; border-collapse: collapse; background: #fff; }
        .cases-table th, .cases-table td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #eee; }
        .cases-table th { background: #f7f7f7; color: #1976d2; font-weight: 600; }
        .cases-table tr:last-child td { border-bottom: none; }
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 500; }
        .status-active { background: #28a745; color: white; }
        .btn-xs { font-size: 0.9em; padding: 4px 10px; margin-right: 4px; }
        .notification-area { background: #fffbe6; border-radius: 10px; padding: 15px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .notification-header { font-weight: 600; font-size: 1.1rem; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .notification-list { list-style: none; padding: 0; margin: 0; }
        .notification-list li { margin-bottom: 8px; font-size: 0.95rem; }
        .notif-date { color: #b8860b; font-weight: 500; margin-right: 8px; }
        .timeline { border-left: 2px solid #28a745; padding-left: 15px; }
        .timeline-item { margin-bottom: 15px; }
        .timeline-item.new-case { background: #eaffea; border-left: 4px solid #28a745; border-radius: 6px; padding-left: 10px; }
        .timeline-date { font-weight: bold; color: #28a745; margin-bottom: 3px; }
        .timeline-content h4 { margin: 0 0 2px 0; font-size: 1rem; }
        .timeline-content p { margin: 0; font-size: 0.95rem; color: #444; }
        @media (max-width: 900px) { .cases-container { padding: 10px; } .cases-table th, .cases-table td { padding: 8px 4px; } }
        .message-bubble { max-width: 70%; margin-bottom: 14px; padding: 12px 18px; border-radius: 16px; font-size: 1rem; position: relative; }
        .message-bubble.sent { background: #e3f2fd; margin-left: auto; }
        .message-bubble.received { background: #fff; border: 1px solid #eee; }
        .message-meta { font-size: 0.85em; color: #888; margin-top: 4px; text-align: right; }
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100vw; height: 100vh;
            background: rgba(30, 30, 30, 0.25);
            backdrop-filter: blur(6px);
            z-index: 1000;
        }
        .modal {
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modern-modal {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44,0,0,0.18);
            padding: 32px 32px 24px 32px;
            min-width: 340px;
            max-width: 95vw;
            width: 400px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalPop .25s cubic-bezier(.4,2,.6,1) 1;
            scrollbar-width: none; /* Firefox */
        }
        .modern-modal::-webkit-scrollbar {
            display: none;
        }
        @keyframes modalPop {
            from { opacity: 0; transform: translate(-50%, -60%) scale(0.95); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }
        .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
        .modal-header h2 { font-size: 1.3rem; font-weight: 700; color: #800000; }
        .close-modal { background: none; border: none; font-size: 2rem; color: #a94442; cursor: pointer; }
        .form-group { margin-bottom: 16px; }
        .form-group label { font-weight: 500; color: #800000; margin-bottom: 6px; display: block; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #e0e0e0;
            border-radius: 7px;
            font-size: 1rem;
            background: #faf9f6;
            color: #333;
            margin-top: 2px;
            transition: border 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border: 1.5px solid #a94442;
            outline: none;
        }
        .form-group textarea { min-height: 70px; resize: vertical; }
        .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
        .btn-primary { background: linear-gradient(90deg, #800000 60%, #a94442 100%); color: #fff0f0; border: none; }
        .btn-secondary { background: #f5f5f5; color: #800000; border: 1.5px solid #a94442; }
        .btn-primary, .btn-secondary { padding: 10px 22px; border-radius: 7px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: background 0.2s, color 0.2s; }
        .btn-primary:hover { background: linear-gradient(90deg, #a94442 60%, #800000 100%); color: #fff; }
        .btn-secondary:hover { background: #fff0f0; color: #a94442; }
        /* Remove notification area from main content */
        .notification-area { display: none !important; }
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
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }
        .profile-dropdown-btn {
            border: none;
            background: none;
            padding: 0;
            cursor: pointer;
        }
        .profile-dropdown-btn img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px solid #1976d2;
            box-shadow: 0 2px 8px rgba(44,0,0,0.10);
            transition: box-shadow 0.2s;
        }
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
    <script>
        let convAttorneyId = null;
        function openConversationModal(attorneyId, attorneyName) {
            convAttorneyId = attorneyId;
            document.getElementById('convAttorneyName').innerText = attorneyName;
            document.getElementById('conversationModal').style.display = 'block';
            fetchConvMessages();
        }
        function closeConversationModal() {
            document.getElementById('conversationModal').style.display = 'none';
            document.getElementById('convChatMessages').innerHTML = '';
            document.getElementById('convMessageInput').value = '';
        }
        function fetchConvMessages() {
            if (!convAttorneyId) return;
            const fd = new FormData();
            fd.append('action', 'fetch_messages');
            fd.append('attorney_id', convAttorneyId);
            fetch('client_messages.php', { method: 'POST', body: fd })
                .then(r => r.json()).then(msgs => {
                    const chat = document.getElementById('convChatMessages');
                    chat.innerHTML = '';
                    msgs.forEach(m => {
                        const sent = m.sender === 'client';
                        chat.innerHTML += `<div class='message-bubble ${sent ? 'sent' : 'received'}'><div class='message-text'><p>${m.message}</p></div><div class='message-meta'><span class='message-time'>${m.sent_at}</span></div></div>`;
                    });
                    chat.scrollTop = chat.scrollHeight;
                });
        }
        function sendConvMessage() {
            const input = document.getElementById('convMessageInput');
            if (!input.value.trim() || !convAttorneyId) return;
            const fd = new FormData();
            fd.append('action', 'send_message');
            fd.append('attorney_id', convAttorneyId);
            fd.append('message', input.value);
            fetch('client_messages.php', { method: 'POST', body: fd })
                .then(r => r.text()).then(res => {
                    if (res === 'success') {
                        input.value = '';
                        fetchConvMessages();
                    } else {
                        alert('Error sending message.');
                    }
                });
        }
        function openAddCaseModal() {
            document.getElementById('addCaseOverlay').style.display = 'block';
            document.getElementById('addCaseModal').style.display = 'block';
        }
        function closeAddCaseModal() {
            document.getElementById('addCaseOverlay').style.display = 'none';
            document.getElementById('addCaseModal').style.display = 'none';
            document.getElementById('addCaseForm').reset();
            document.getElementById('caseSuccessMsg').style.display = 'none';
        }
        document.getElementById('addCaseForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add_case');
            fetch('client_cases.php', {
                method: 'POST',
                body: formData
            }).then(r => r.text()).then(res => {
                if (res === 'success') {
                    document.getElementById('caseSuccessMsg').style.display = 'block';
                    // Add the new case to the table dynamically
                    // Fetch the latest case (assume it's the last inserted for this client)
                    fetch('client_cases.php?fetch_last_case=1')
                        .then(r => r.json())
                        .then(newCase => {
                            if (newCase && newCase.id) {
                                const tbody = document.getElementById('casesTableBody');
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td>${newCase.id}</td>
                                    <td>${newCase.title}</td>
                                    <td>${newCase.case_type ? newCase.case_type.charAt(0).toUpperCase() + newCase.case_type.slice(1).toLowerCase() : '-'}</td>
                                    <td><span class="status-badge status-${(newCase.status || 'active').toLowerCase()}">${newCase.status || '-'}</span></td>
                                    <td>-</td>
                                    <td>${newCase.next_hearing || '-'}</td>
                                    <td></td>
                                `;
                                tbody.prepend(tr);
                            }
                        });
                    setTimeout(() => { closeAddCaseModal(); }, 1000);
                } else {
                    alert('Error adding case.');
                }
            });
        };
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
        function toggleProfileDropdown(e) {
            e.stopPropagation();
            var dropdown = document.getElementById('profileDropdownContent');
            dropdown.classList.toggle('show');
            document.addEventListener('click', function handler(event) {
                if (!dropdown.contains(event.target) && event.target !== e.target) {
                    dropdown.classList.remove('show');
                    document.removeEventListener('click', handler);
                }
            });
        }
    </script>
</body>
</html> 