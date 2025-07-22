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

// Fetch all cases for dropdown
$cases = [];
$res = $conn->query("SELECT ac.id, ac.title, uf.name as client_name FROM attorney_cases ac LEFT JOIN user_form uf ON ac.client_id = uf.id WHERE ac.attorney_id=$attorney_id");
while ($row = $res->fetch_assoc()) $cases[] = $row;

// Handle add event
if (isset($_POST['action']) && $_POST['action'] === 'add_event') {
    $title = $_POST['title'];
    $type = $_POST['type'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $location = $_POST['location'];
    $case_id = !empty($_POST['case_id']) ? intval($_POST['case_id']) : null;
    $description = $_POST['description'];
    $client_id = null;
    if ($case_id) {
        $q = $conn->query("SELECT client_id FROM attorney_cases WHERE id=$case_id");
        if ($r = $q->fetch_assoc()) $client_id = $r['client_id'];
    }
    $stmt = $conn->prepare("INSERT INTO case_schedules (case_id, attorney_id, client_id, type, title, description, date, time, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iiissssss', $case_id, $attorney_id, $client_id, $type, $title, $description, $date, $time, $location);
    $stmt->execute();
    echo $stmt->affected_rows > 0 ? 'success' : 'error';
    exit();
}
// Fetch all events for this attorney
$events = [];
$res = $conn->query("SELECT cs.*, ac.title as case_title, uf.name as client_name FROM case_schedules cs LEFT JOIN attorney_cases ac ON cs.case_id = ac.id LEFT JOIN user_form uf ON cs.client_id = uf.id WHERE cs.attorney_id=$attorney_id ORDER BY cs.date, cs.time");
while ($row = $res->fetch_assoc()) $events[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management - Opiña Law Office</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
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
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Schedule Management</h1>
                <p>Manage your court hearings and appointments</p>
            </div>
            <div class="user-info">
                <img src="<?= htmlspecialchars($profile_image) ?>" alt="Attorney" style="object-fit:cover;width:60px;height:60px;border-radius:50%;border:2px solid #1976d2;">
                <div class="user-details">
                    <h3><?php echo $_SESSION['attorney_name']; ?></h3>
                    <p>Attorney at Law</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-primary" id="addEventBtn">
                <i class="fas fa-plus"></i> Add Event
            </button>
            <button class="btn btn-secondary" id="viewDayBtn">
                <i class="fas fa-calendar-day"></i> Day View
            </button>
            <button class="btn btn-secondary" id="viewWeekBtn">
                <i class="fas fa-calendar-week"></i> Week View
            </button>
            <button class="btn btn-secondary" id="viewMonthBtn">
                <i class="fas fa-calendar"></i> Month View
            </button>
        </div>

        <!-- Calendar Container -->
        <div class="calendar-container">
            <div id="calendar"></div>
        </div>

        <!-- Upcoming Events -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Upcoming Events</h2>
                <button class="btn btn-primary">View All Events</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Event</th>
                        <th>Location</th>
                        <th>Case</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $ev): ?>
                    <tr>
                        <td><?= htmlspecialchars($ev['date']) ?></td>
                        <td><?= htmlspecialchars(date('h:i A', strtotime($ev['time']))) ?></td>
                        <td><?= htmlspecialchars($ev['type']) ?></td>
                        <td><?= htmlspecialchars($ev['location']) ?></td>
                        <td><?= htmlspecialchars($ev['case_title'] ?? '-') ?></td>
                        <td><span class="status-badge status-active"><?= htmlspecialchars($ev['status']) ?></span></td>
                        <td>
                            <button class="btn btn-info btn-xs view-info-btn" 
                                data-type="<?= htmlspecialchars($ev['type']) ?>"
                                data-date="<?= htmlspecialchars($ev['date']) ?>"
                                data-time="<?= htmlspecialchars($ev['time']) ?>"
                                data-location="<?= htmlspecialchars($ev['location']) ?>"
                                data-case="<?= htmlspecialchars($ev['case_title'] ?? '-') ?>"
                                data-client="<?= htmlspecialchars($ev['client_name'] ?? '-') ?>"
                                data-description="<?= htmlspecialchars($ev['description'] ?? '-') ?>"
                                style="font-size:0.95em; padding:3px 10px; border-radius:6px; background:#1976d2; color:#fff; border:none; cursor:pointer;">View Info</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div class="modal" id="addEventModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Event</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="eventForm" class="event-form-grid">
                    <div class="form-group">
                        <label for="eventTitle">Event Title</label>
                        <input type="text" id="eventTitle" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="eventDate">Date</label>
                        <input type="date" id="eventDate" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="eventTime">Time</label>
                        <input type="time" id="eventTime" name="time" required>
                    </div>
                    <div class="form-group">
                        <label for="eventLocation">Location</label>
                        <input type="text" id="eventLocation" name="location" required>
                    </div>
                    <div class="form-group">
                        <label for="eventCase">Related Case</label>
                        <select id="eventCase" name="case_id">
                            <option value="">Select Case</option>
                            <?php foreach ($cases as $c): ?>
                            <option value="<?= $c['id'] ?>">#<?= $c['id'] ?> - <?= htmlspecialchars($c['title']) ?> (<?= htmlspecialchars($c['client_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="eventType">Event Type</label>
                        <select id="eventType" name="type" required>
                            <option value="Hearing">Hearing</option>
                            <option value="Appointment">Appointment</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="eventDescription">Description</label>
                        <textarea id="eventDescription" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelEvent">Cancel</button>
                <button class="btn btn-primary" id="saveEvent">Save Event</button>
            </div>
        </div>
    </div>

    <!-- Add Event Details Modal -->
    <div class="modal" id="eventModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Event Details</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="event-info">
                    <div class="info-group">
                        <h3>Event Information</h3>
                        <div class="info-item"><span class="label">Type:</span><span class="value" id="modalType"></span></div>
                        <div class="info-item"><span class="label">Date:</span><span class="value" id="modalDate"></span></div>
                        <div class="info-item"><span class="label">Time:</span><span class="value" id="modalTime"></span></div>
                        <div class="info-item"><span class="label">Location:</span><span class="value" id="modalLocation"></span></div>
                        <div class="info-item"><span class="label">Case:</span><span class="value" id="modalCase"></span></div>
                        <div class="info-item"><span class="label">Client:</span><span class="value" id="modalClient"></span></div>
                        <div class="info-item"><span class="label">Description:</span><span class="value" id="modalDescription"></span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="closeEventModal">Close</button>
            </div>
        </div>
    </div>

    <style>
        .calendar-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        #calendar {
            height: 600px;
        }

        .fc-event {
            cursor: pointer;
        }

        .fc-event-title {
            font-weight: 500;
        }

        .fc-event-time {
            font-size: 0.8em;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background: #fff;
            border-radius: 12px;
            padding: 28px 28px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            max-width: 600px;
            margin: 40px auto;
        }
        .modal-header h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: #1976d2;
        }
        .event-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 24px;
        }
        .event-form-grid .form-group {
            margin-bottom: 0;
        }
        .event-form-grid .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            font-size: 1rem;
            color: #555;
            margin-bottom: 4px;
            display: block;
            font-weight: 500;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            background: #fafbfc;
            margin-top: 2px;
            transition: border 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #1976d2;
            outline: none;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 18px;
        }
        .btn-primary {
            background: #1976d2;
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: #125ea2;
        }
        .btn-secondary {
            background: #f5f5f5;
            color: #333;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        @media (max-width: 700px) {
            .modal-content { max-width: 98vw; padding: 10px 4vw; }
            .event-form-grid { grid-template-columns: 1fr; gap: 12px; }
        }
    </style>

    <script>
        // Use json_encode to safely pass PHP events to JS
        var events = <?php echo json_encode(array_map(function($ev) {
            return [
                "title" => ($ev['type'] ?? '') . ': ' . ($ev['title'] ?? ''),
                "start" => ($ev['date'] ?? '') . 'T' . ($ev['time'] ?? ''),
                "description" => $ev['description'] ?? '',
                "location" => $ev['location'] ?? '',
                "case" => $ev['case_title'] ?? '',
                "client" => $ev['client_name'] ?? '',
                "color" => ($ev['type'] ?? '') === 'Hearing' ? '#4CAF50' : '#2196F3'
            ];
        }, $events), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: events,
                eventClick: function(info) {
                    alert('Event: ' + info.event.title + '\n' +
                          'Start: ' + info.event.start + '\n' +
                          'Location: ' + (info.event.extendedProps.location || '') + '\n' +
                          'Case: ' + (info.event.extendedProps.case || '') + '\n' +
                          'Client: ' + (info.event.extendedProps.client || '') + '\n' +
                          'Description: ' + (info.event.extendedProps.description || ''));
                }
            });
            calendar.render();

            // Modal functionality
            const modal = document.getElementById('addEventModal');
            const addEventBtn = document.getElementById('addEventBtn');
            const closeModal = document.querySelector('.close-modal');
            const cancelEvent = document.getElementById('cancelEvent');

            addEventBtn.onclick = function() {
                modal.style.display = "block";
            }

            closeModal.onclick = function() {
                modal.style.display = "none";
            }

            cancelEvent.onclick = function() {
                modal.style.display = "none";
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }

            // View buttons functionality
            document.getElementById('viewDayBtn').onclick = function() {
                calendar.changeView('timeGridDay');
            }

            document.getElementById('viewWeekBtn').onclick = function() {
                calendar.changeView('timeGridWeek');
            }

            document.getElementById('viewMonthBtn').onclick = function() {
                calendar.changeView('dayGridMonth');
            }

            // Populate event table with PHP events
            const tbody = document.querySelector('.table-container tbody');
            tbody.innerHTML = `<?php foreach ($events as $ev): ?>
<tr>
    <td><?= htmlspecialchars($ev['date']) ?></td>
    <td><?= htmlspecialchars(date('h:i A', strtotime($ev['time']))) ?></td>
    <td><?= htmlspecialchars($ev['type']) ?></td>
    <td><?= htmlspecialchars($ev['location']) ?></td>
    <td><?= htmlspecialchars($ev['case_title'] ?? '-') ?></td>
    <td><span class="status-badge status-active"><?= htmlspecialchars($ev['status']) ?></span></td>
    <td>
        <button class="btn btn-info btn-xs view-info-btn" 
            data-type="<?= htmlspecialchars($ev['type']) ?>"
            data-date="<?= htmlspecialchars($ev['date']) ?>"
            data-time="<?= htmlspecialchars($ev['time']) ?>"
            data-location="<?= htmlspecialchars($ev['location']) ?>"
            data-case="<?= htmlspecialchars($ev['case_title'] ?? '-') ?>"
            data-client="<?= htmlspecialchars($ev['client_name'] ?? '-') ?>"
            data-description="<?= htmlspecialchars($ev['description'] ?? '-') ?>"
            style="font-size:0.95em; padding:3px 10px; border-radius:6px; background:#1976d2; color:#fff; border:none; cursor:pointer;">View Info</button>
    </td>
</tr>
<?php endforeach; ?>`;

            // Add AJAX for saving event
            document.getElementById('saveEvent').onclick = function() {
                const fd = new FormData(document.getElementById('eventForm'));
                fd.append('action', 'add_event');
                fetch('attorney_schedule.php', { method: 'POST', body: fd })
                    .then(r => r.text()).then(res => {
                        if (res === 'success') location.reload();
                        else alert('Error saving event.');
                    });
            };

            // Add functionality for event details modal
            const eventModal = document.getElementById('eventModal');
            const closeEventModal = document.getElementById('closeEventModal');

            closeEventModal.onclick = function() {
                eventModal.style.display = "none";
            }

            window.onclick = function(event) {
                if (event.target == eventModal) {
                    eventModal.style.display = "none";
                }
            }

            document.querySelectorAll('.view-info-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    document.getElementById('modalType').innerText = this.dataset.type || '';
                    document.getElementById('modalDate').innerText = this.dataset.date || '';
                    document.getElementById('modalTime').innerText = this.dataset.time ? new Date('1970-01-01T' + this.dataset.time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
                    document.getElementById('modalLocation').innerText = this.dataset.location || '';
                    document.getElementById('modalCase').innerText = this.dataset.case || '';
                    document.getElementById('modalClient').innerText = this.dataset.client || '';
                    document.getElementById('modalDescription').innerText = this.dataset.description || '';
                    eventModal.style.display = "block";
                });
            });
        });
    </script>
</body>
</html> 