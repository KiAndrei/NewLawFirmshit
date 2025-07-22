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
// Fetch all clients for dropdown
$clients = [];
$res = $conn->query("SELECT id, name FROM user_form WHERE user_type='client'");
while ($row = $res->fetch_assoc()) $clients[] = $row;
// Handle AJAX add case
if (isset($_POST['action']) && $_POST['action'] === 'add_case') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $client_id = intval($_POST['client_id']);
    $case_type = $_POST['case_type'];
    $status = $_POST['status'];
    $next_hearing = empty($_POST['next_hearing']) ? null : $_POST['next_hearing'];
    $stmt = $conn->prepare("INSERT INTO attorney_cases (title, description, attorney_id, client_id, case_type, status, next_hearing) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssiisss', $title, $description, $attorney_id, $client_id, $case_type, $status, $next_hearing);
    $stmt->execute();

    // Notify client about the new case
    if ($stmt->affected_rows > 0) {
        $notif_msg = "A new case has been created for you: $title";
        $stmt2 = $conn->prepare("INSERT INTO client_messages (client_id, recipient_id, message) VALUES (?, ?, ?)");
        $stmt2->bind_param('iis', $client_id, $attorney_id, $notif_msg);
        $stmt2->execute();
    }

    echo $stmt->affected_rows > 0 ? 'success' : 'error';
    exit();
}
// Handle AJAX fetch conversation for a case
if (isset($_POST['action']) && $_POST['action'] === 'fetch_conversation') {
    $client_id = intval($_POST['client_id']);
    $msgs = [];
    // Attorney to client (all messages)
    $stmt1 = $conn->prepare("SELECT message, sent_at, 'attorney' as sender FROM attorney_messages WHERE attorney_id=? AND recipient_id=?");
    $stmt1->bind_param('ii', $attorney_id, $client_id);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    while ($row = $result1->fetch_assoc()) $msgs[] = $row;
    // Client to attorney (all messages)
    $stmt2 = $conn->prepare("SELECT message, sent_at, 'client' as sender FROM client_messages WHERE client_id=? AND recipient_id=?");
    $stmt2->bind_param('ii', $client_id, $attorney_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) $msgs[] = $row;
    // Sort by sent_at
    usort($msgs, function($a, $b) { return strtotime($a['sent_at']) - strtotime($b['sent_at']); });
    header('Content-Type: application/json');
    echo json_encode($msgs);
    exit();
}
// Handle AJAX update case (edit)
if (isset($_POST['action']) && $_POST['action'] === 'edit_case') {
    $case_id = intval($_POST['case_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE attorney_cases SET status=? WHERE id=? AND attorney_id=?");
    $stmt->bind_param('sii', $status, $case_id, $attorney_id);
    $stmt->execute();
    echo $stmt->affected_rows > 0 ? 'success' : 'error';
    exit();
}
// Handle AJAX delete case
if (isset($_POST['action']) && $_POST['action'] === 'delete_case') {
    $case_id = intval($_POST['case_id']);
    $stmt = $conn->prepare("DELETE FROM attorney_cases WHERE id=? AND attorney_id=?");
    $stmt->bind_param('ii', $case_id, $attorney_id);
    $stmt->execute();
    echo $stmt->affected_rows > 0 ? 'success' : 'error';
    exit();
}
// Fetch cases for this attorney (with client name)
$cases = [];
$sql = "SELECT ac.*, uf.name as client_name FROM attorney_cases ac LEFT JOIN user_form uf ON ac.client_id = uf.id WHERE ac.attorney_id=? ORDER BY ac.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $attorney_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cases[] = $row;
}
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
            <li><a href="attorney_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="attorney_cases.php" class="active"><i class="fas fa-gavel"></i><span>Manage Cases</span></a></li>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="header-title">
                <h1>My Cases</h1>
                <p>All cases you are handling as an attorney</p>
            </div>
            <div class="user-info">
                <img src="<?= htmlspecialchars($profile_image) ?>" alt="Attorney" style="object-fit:cover;width:60px;height:60px;border-radius:50%;border:2px solid #1976d2;">
                <div class="user-details">
                    <h3><?php echo $_SESSION['attorney_name']; ?></h3>
                    <p>Attorney at Law</p>
                </div>
            </div>
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
                        <th>Client</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Next Hearing</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="casesTableBody">
                    <?php foreach ($cases as $case): ?>
                    <tr>
                        <td><?= htmlspecialchars($case['id']) ?></td>
                        <td><?= htmlspecialchars($case['title']) ?></td>
                        <td><?= htmlspecialchars($case['client_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($case['case_type'] ?? '-') ?></td>
                        <td><span class="status-badge status-<?= strtolower($case['status'] ?? 'active') ?>"><?= htmlspecialchars($case['status'] ?? '-') ?></span></td>
                        <td><?= htmlspecialchars($case['next_hearing'] ?? '-') ?></td>
                        <td>
                            <button class="btn btn-primary btn-xs" onclick="openConversationModal(<?= $case['client_id'] ?>)"><i class='fas fa-comments'></i> View Conversation</button>
                            <button class="btn btn-info btn-xs" onclick="openSummaryModal('<?= htmlspecialchars(addslashes($case['description'])) ?>')"><i class='fas fa-info-circle'></i> View Summary</button>
                            <button class="btn btn-secondary btn-xs" onclick="openEditCaseModal(<?= htmlspecialchars(json_encode($case)) ?>)"><i class='fas fa-edit'></i> Edit</button>
                            <button class="btn btn-danger btn-xs" onclick="deleteCase(<?= $case['id'] ?>)"><i class='fas fa-trash'></i> Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Add Case Modal -->
        <div class="modal" id="addCaseModal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Case</h2>
                    <button class="close-modal" onclick="closeAddCaseModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="addCaseForm">
                        <div class="form-group">
                            <label>Client</label>
                            <select name="client_id" id="clientSelect" required>
                                <option value="">Select Client</option>
                                <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Case Title</label>
                            <input type="text" name="title" id="caseTitle" required>
                        </div>
                        <div class="form-group">
                            <label>Case Type</label>
                            <select name="case_type" required>
                                <option value="">Select Type</option>
                                <option value="Criminal">Criminal</option>
                                <option value="Civil">Civil</option>
                                <option value="Family">Family</option>
                                <option value="Corporate">Corporate</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Summary</label>
                            <textarea name="description" id="caseDescription" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" required>
                                <option value="Active">Active</option>
                                <option value="Pending">Pending</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Next Hearing</label>
                            <input type="date" name="next_hearing">
                            <small style="color:#888;">(Optional. Leave blank if not yet scheduled.)</small>
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
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Conversation with Client</h2>
                    <button class="close-modal" onclick="closeConversationModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="chat-messages" id="modalChatMessages" style="max-height:300px;overflow-y:auto;">
                        <!-- Dynamic chat here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeConversationModal()">Close</button>
                </div>
            </div>
        </div>
        <!-- Edit Case Modal -->
        <div class="modal" id="editCaseModal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Edit Case Status</h2>
                    <button class="close-modal" onclick="closeEditCaseModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="editCaseForm">
                        <input type="hidden" name="case_id" id="editCaseId">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="editCaseStatus" required>
                                <option value="Active">Active</option>
                                <option value="Pending">Pending</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeEditCaseModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                    <div id="editCaseSuccessMsg" style="display:none; color:green; margin-top:10px;">Status updated successfully!</div>
                </div>
            </div>
        </div>
        <!-- Add this modal after the Edit Case Modal -->
        <div class="modal" id="summaryModal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Case Summary</h2>
                    <button class="close-modal" onclick="closeSummaryModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p id="summaryText" style="white-space: pre-line;"></p>
                </div>
            </div>
        </div>
    </div>
    <style>
        .cases-container { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 24px; margin-top: 24px; }
        .cases-header { display: flex; justify-content: flex-end; margin-bottom: 18px; }
        .cases-table { width: 100%; border-collapse: collapse; background: #fff; }
        .cases-table th, .cases-table td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #eee; }
        .cases-table th { background: #f7f7f7; color: #1976d2; font-weight: 600; }
        .cases-table tr:last-child td { border-bottom: none; }
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 500; }
        .status-active { background: #28a745; color: white; }
        .status-pending { background: #ffc107; color: #333; }
        .btn-xs { font-size: 0.9em; padding: 4px 10px; margin-right: 4px; }
        @media (max-width: 900px) { .cases-container { padding: 10px; } .cases-table th, .cases-table td { padding: 8px 4px; } }
    </style>
    <script>
        function openAddCaseModal() {
            document.getElementById('addCaseModal').style.display = 'block';
        }
        function closeAddCaseModal() {
            document.getElementById('addCaseModal').style.display = 'none';
        }
        document.getElementById('addCaseForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add_case');
            fetch('attorney_cases.php', {
                method: 'POST',
                body: formData
            }).then(r => r.text()).then(res => {
                if (res === 'success') {
                    document.getElementById('caseSuccessMsg').style.display = 'block';
                    setTimeout(() => { location.reload(); }, 1000);
                } else {
                    alert('Error adding case.');
                }
            });
        };
        function openConversationModal(clientId) {
            // Generic: fetch all messages between attorney and client
            const fd = new FormData();
            fd.append('action', 'fetch_conversation');
            fd.append('client_id', clientId);
            fetch('attorney_cases.php', { method: 'POST', body: fd })
                .then(r => r.json()).then(msgs => {
                    const chat = document.getElementById('modalChatMessages');
                    chat.innerHTML = '';
                    if (msgs.length === 0) {
                        chat.innerHTML = '<p style="color:#888;text-align:center;">No conversation yet.</p>';
                    } else {
                        msgs.forEach(m => {
                            const sent = m.sender === 'attorney';
                            chat.innerHTML += `<div class='message-bubble ${sent ? 'sent' : 'received'}'><div class='message-text'><p>${m.message}</p></div><div class='message-meta'><span class='message-time'>${m.sent_at}</span></div></div>`;
                        });
                    }
                    document.getElementById('conversationModal').style.display = 'block';
                });
        }
        function closeConversationModal() {
            document.getElementById('conversationModal').style.display = 'none';
        }
        function openEditCaseModal(caseObj) {
            const c = typeof caseObj === 'string' ? JSON.parse(caseObj) : caseObj;
            document.getElementById('editCaseId').value = c.id;
            document.getElementById('editCaseStatus').value = c.status || 'Active';
            document.getElementById('editCaseModal').style.display = 'block';
        }
        function closeEditCaseModal() {
            document.getElementById('editCaseModal').style.display = 'none';
        }
        document.getElementById('editCaseForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'edit_case');
            fetch('attorney_cases.php', {
                method: 'POST',
                body: formData
            }).then(r => r.text()).then(res => {
                if (res === 'success') {
                    document.getElementById('editCaseSuccessMsg').style.display = 'block';
                    setTimeout(() => { location.reload(); }, 1000);
                } else {
                    alert('Error updating status.');
                }
            });
        };
        function deleteCase(caseId) {
            if (!confirm('Are you sure you want to delete this case?')) return;
            const fd = new FormData();
            fd.append('action', 'delete_case');
            fd.append('case_id', caseId);
            fetch('attorney_cases.php', { method: 'POST', body: fd })
                .then(r => r.text()).then(res => {
                    if (res === 'success') {
                        location.reload();
                    } else {
                        alert('Error deleting case.');
                    }
                });
        }
        function openSummaryModal(summary) {
            document.getElementById('summaryText').innerText = summary;
            document.getElementById('summaryModal').style.display = 'block';
        }
        function closeSummaryModal() {
            document.getElementById('summaryModal').style.display = 'none';
        }
    </script>
</body>
</html> 