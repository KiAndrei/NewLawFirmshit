<?php
session_start();
if (!isset($_SESSION['attorney_name']) || $_SESSION['user_type'] !== 'attorney') {
    header('Location: login_form.php');
    exit();
}
require_once 'config.php';
$attorney_id = $_SESSION['user_id'];
// Fetch all clients with profile images
$clients = [];
$res = $conn->query("SELECT id, name, profile_image FROM user_form WHERE user_type='client'");
while ($row = $res->fetch_assoc()) {
    $img = $row['profile_image'];
    if (!$img || !file_exists($img)) $img = 'assets/images/client-avatar.png';
    $row['profile_image'] = $img;
    $clients[] = $row;
}
// Handle AJAX fetch messages
if (isset($_POST['action']) && $_POST['action'] === 'fetch_messages') {
    $client_id = intval($_POST['client_id']);
    $msgs = [];
    // Fetch attorney profile image
    $attorney_img = '';
    $res = $conn->query("SELECT profile_image FROM user_form WHERE id=$attorney_id");
    if ($res && $row = $res->fetch_assoc()) $attorney_img = $row['profile_image'];
    if (!$attorney_img || !file_exists($attorney_img)) $attorney_img = 'assets/images/attorney-avatar.png';
    // Fetch client profile image
    $client_img = '';
    $res = $conn->query("SELECT profile_image FROM user_form WHERE id=$client_id");
    if ($res && $row = $res->fetch_assoc()) $client_img = $row['profile_image'];
    if (!$client_img || !file_exists($client_img)) $client_img = 'assets/images/client-avatar.png';
    // Fetch attorney to client
    $stmt1 = $conn->prepare("SELECT message, sent_at, 'attorney' as sender FROM attorney_messages WHERE attorney_id=? AND recipient_id=?");
    $stmt1->bind_param('ii', $attorney_id, $client_id);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    while ($row = $result1->fetch_assoc()) {
        $row['profile_image'] = $attorney_img;
        $msgs[] = $row;
    }
    // Fetch client to attorney
    $stmt2 = $conn->prepare("SELECT message, sent_at, 'client' as sender FROM client_messages WHERE client_id=? AND recipient_id=?");
    $stmt2->bind_param('ii', $client_id, $attorney_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $row['profile_image'] = $client_img;
        $msgs[] = $row;
    }
    // Sort by sent_at
    usort($msgs, function($a, $b) { return strtotime($a['sent_at']) - strtotime($b['sent_at']); });
    header('Content-Type: application/json');
    echo json_encode($msgs);
    exit();
}
// Handle AJAX send message
if (isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $client_id = intval($_POST['client_id']);
    $msg = $_POST['message'];
    $stmt = $conn->prepare("INSERT INTO attorney_messages (attorney_id, recipient_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param('iis', $attorney_id, $client_id, $msg);
    $stmt->execute();
    echo $stmt->affected_rows > 0 ? 'success' : 'error';
    exit();
}
// Handle AJAX create case from chat
if (isset($_POST['action']) && $_POST['action'] === 'create_case_from_chat') {
    $client_id = intval($_POST['client_id']);
    $title = $_POST['case_title'];
    $description = $_POST['summary'];
    $case_type = isset($_POST['case_type']) ? $_POST['case_type'] : null;
    $status = isset($_POST['status']) && $_POST['status'] ? $_POST['status'] : 'Active';
    $next_hearing = empty($_POST['next_hearing']) ? null : $_POST['next_hearing'];
    $stmt = $conn->prepare("INSERT INTO attorney_cases (title, description, attorney_id, client_id, case_type, status, next_hearing) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssiisss', $title, $description, $attorney_id, $client_id, $case_type, $status, $next_hearing);
    $stmt->execute();
    echo $stmt->affected_rows > 0 ? 'success' : 'error';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Opiña Law Office</title>
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
            <li><a href="attorney/attorney_audit.php"><i class="fas fa-history"></i><span>Audit Trail</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="chat-container">
            <!-- Client List -->
            <div class="client-list">
                <h3>Clients</h3>
                <ul id="clientList">
                    <?php foreach ($clients as $c): ?>
                    <li class="client-item" data-id="<?= $c['id'] ?>" onclick="selectClient(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name']) ?>')">
                        <img src='<?= htmlspecialchars($c['profile_image']) ?>' alt='Client' style='width:38px;height:38px;border-radius:50%;border:1.5px solid #1976d2;object-fit:cover;'><span><?= htmlspecialchars($c['name']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <!-- Chat Area -->
            <div class="chat-area">
                <div class="chat-header">
                    <h2 id="selectedClient">Select a client</h2>
                    <button class="btn btn-primary" id="createCaseBtn" style="display:none;" onclick="openCreateCaseModal()"><i class="fas fa-gavel"></i> Create Case</button>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <p style="color:#888;text-align:center;">Select a client to start conversation.</p>
                </div>
                <div class="chat-compose" id="chatCompose" style="display:none;">
                    <textarea id="messageInput" placeholder="Type your message..."></textarea>
                    <button class="btn btn-primary" onclick="sendMessage()">Send</button>
                </div>
            </div>
        </div>
        <!-- Create Case Modal -->
        <div class="modal" id="createCaseModal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Create Case from Conversation</h2>
                    <button class="close-modal" onclick="closeCreateCaseModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="createCaseForm">
                        <div class="form-group">
                            <label>Client Name</label>
                            <input type="text" name="client_name" id="caseClientName" readonly>
                        </div>
                        <div class="form-group">
                            <label>Case Title</label>
                            <input type="text" name="case_title" required>
                        </div>
                        <div class="form-group">
                            <label>Case Type</label>
                            <select name="case_type" required>
                                <option value="">Select Type</option>
                                <option value="criminal">Criminal</option>
                                <option value="civil">Civil</option>
                                <option value="family">Family</option>
                                <option value="corporate">Corporate</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Summary</label>
                            <textarea name="summary" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Next Hearing</label>
                            <input type="date" name="next_hearing">
                            <small style="color:#888;">(Optional. Leave blank if not yet scheduled.)</small>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeCreateCaseModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Case</button>
                        </div>
                    </form>
                    <div id="caseSuccessMsg" style="display:none; color:green; margin-top:10px;">Case created successfully!</div>
                </div>
            </div>
        </div>
    </div>
    <style>
        .chat-container { display: flex; height: 80vh; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); overflow: hidden; }
        .client-list { width: 260px; background: #f7f7f7; border-right: 1px solid #eee; padding: 20px 0; }
        .client-list h3 { text-align: center; margin-bottom: 18px; color: #1976d2; }
        .client-list ul { list-style: none; padding: 0; margin: 0; }
        .client-item { display: flex; align-items: center; gap: 12px; padding: 12px 24px; cursor: pointer; border-radius: 8px; transition: background 0.2s; }
        .client-item.active, .client-item:hover { background: #e3f2fd; }
        .client-item img { width: 38px; height: 38px; border-radius: 50%; }
        .chat-area { flex: 1; display: flex; flex-direction: column; }
        .chat-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; border-bottom: 1px solid #eee; background: #fafafa; }
        .chat-header h2 { margin: 0; font-size: 1.2rem; color: #1976d2; }
        .chat-header button { margin-left: 10px; }
        .chat-messages { flex: 1; padding: 24px; overflow-y: auto; background: #f9f9f9; }
        .message-bubble { max-width: 70%; margin-bottom: 14px; padding: 12px 18px; border-radius: 16px; font-size: 1rem; position: relative; }
        .message-bubble.sent { background: #e3f2fd; margin-left: auto; }
        .message-bubble.received { background: #fff; border: 1px solid #eee; }
        .message-meta { font-size: 0.85em; color: #888; margin-top: 4px; text-align: right; }
        .chat-compose { display: flex; gap: 10px; padding: 18px 24px; border-top: 1px solid #eee; background: #fafafa; }
        .chat-compose textarea { flex: 1; border-radius: 8px; border: 1px solid #ddd; padding: 10px; resize: none; font-size: 1rem; }
        .chat-compose button { padding: 10px 24px; border-radius: 8px; background: #1976d2; color: #fff; border: none; font-weight: 500; cursor: pointer; }
        @media (max-width: 900px) { .chat-container { flex-direction: column; height: auto; } .client-list { width: 100%; border-right: none; border-bottom: 1px solid #eee; } }
    </style>
    <script>
        let selectedClientId = null;
        function selectClient(id, name) {
            selectedClientId = id;
            document.getElementById('selectedClient').innerText = name;
            document.getElementById('createCaseBtn').style.display = 'inline-block';
            document.getElementById('chatCompose').style.display = 'flex';
            fetchMessages();
        }
        function fetchMessages() {
            if (!selectedClientId) return;
            const fd = new FormData();
            fd.append('action', 'fetch_messages');
            fd.append('client_id', selectedClientId);
            fetch('attorney_messages.php', { method: 'POST', body: fd })
                .then(r => r.json()).then(msgs => {
                    const chat = document.getElementById('chatMessages');
                    chat.innerHTML = '';
                    msgs.forEach(m => {
                        const sent = m.sender === 'attorney';
                        chat.innerHTML += `
                <div class='message-bubble ${sent ? 'sent' : 'received'}' style='display:flex;align-items:flex-end;gap:10px;'>
                    ${sent ? '' : `<img src='${m.profile_image}' alt='Client' style='width:38px;height:38px;border-radius:50%;border:1.5px solid #1976d2;object-fit:cover;'>`}
                    <div style='flex:1;'>
                        <div class='message-text'><p>${m.message}</p></div>
                        <div class='message-meta'><span class='message-time'>${m.sent_at}</span></div>
                    </div>
                    ${sent ? `<img src='${m.profile_image}' alt='Attorney' style='width:38px;height:38px;border-radius:50%;border:1.5px solid #1976d2;object-fit:cover;'>` : ''}
                </div>`;
                    });
                    chat.scrollTop = chat.scrollHeight;
                });
        }
        function sendMessage() {
            const input = document.getElementById('messageInput');
            if (!input.value.trim() || !selectedClientId) return;
            const fd = new FormData();
            fd.append('action', 'send_message');
            fd.append('client_id', selectedClientId);
            fd.append('message', input.value);
            fetch('attorney_messages.php', { method: 'POST', body: fd })
                .then(r => r.text()).then(res => {
                    if (res === 'success') {
                        input.value = '';
                        fetchMessages();
                    } else {
                        alert('Error sending message.');
                    }
                });
        }
        document.getElementById('createCaseForm').onsubmit = function(e) {
            e.preventDefault();
            if (!selectedClientId) return;
            const fd = new FormData(this);
            fd.append('action', 'create_case_from_chat');
            fd.append('client_id', selectedClientId);
            fetch('attorney_messages.php', { method: 'POST', body: fd })
                .then(r => r.text()).then(res => {
                    if (res === 'success') {
                        document.getElementById('caseSuccessMsg').style.display = 'block';
                        setTimeout(() => {
                            closeCreateCaseModal();
                            document.getElementById('caseSuccessMsg').style.display = 'none';
                        }, 1000);
                    } else {
                        alert('Error creating case.');
                    }
                });
        };
        function openCreateCaseModal() {
            if (selectedClientId !== null) {
                const clientName = document.querySelector('.client-item[data-id="'+selectedClientId+'"] span').innerText;
                document.getElementById('caseClientName').value = clientName;
                document.getElementById('createCaseModal').style.display = 'block';
            }
        }
        function closeCreateCaseModal() {
            document.getElementById('createCaseModal').style.display = 'none';
        }
    </script>
</body>
</html> 