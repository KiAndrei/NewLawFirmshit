<?php
session_start();
if (!isset($_SESSION['admin_name']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login_form.php');
    exit();
}
require_once 'config.php';
$admin_id = $_SESSION['user_id'];
$res = $conn->query("SELECT profile_image FROM user_form WHERE id=$admin_id");
$profile_image = '';
if ($res && $row = $res->fetch_assoc()) {
    $profile_image = $row['profile_image'];
}
if (!$profile_image || !file_exists($profile_image)) {
    $profile_image = 'assets/images/admin-avatar.png';
}
// Fetch all events with joins
$events = [];
$res = $conn->query("SELECT cs.*, ac.title as case_title, ac.attorney_id, uf1.name as attorney_name, uf2.name as client_name FROM case_schedules cs
    LEFT JOIN attorney_cases ac ON cs.case_id = ac.id
    LEFT JOIN user_form uf1 ON ac.attorney_id = uf1.id
    LEFT JOIN user_form uf2 ON cs.client_id = uf2.id
    ORDER BY cs.date, cs.time");
while ($row = $res->fetch_assoc()) $events[] = $row;
$js_events = [];
foreach ($events as $ev) {
    $js_events[] = [
        'title' => $ev['type'] . ': ' . ($ev['case_title'] ?? ''),
        'start' => $ev['date'] . 'T' . $ev['time'],
        'type' => $ev['type'],
        'description' => $ev['description'],
        'location' => $ev['location'],
        'case' => $ev['case_title'],
        'attorney' => $ev['attorney_name'],
        'client' => $ev['client_name'],
        'color' => $ev['type'] === 'Hearing' ? '#1976d2' : '#43a047',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management - Opiña Law Office</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
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
            <li><a href="admin_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="admin_documents.php"><i class="fas fa-file-alt"></i><span>Document Storage</span></a></li>
            <li><a href="admin_document_generation.php"><i class="fas fa-file-alt"></i><span>Document Generations</span></a></li>
            <li><a href="admin_schedule.php"><i class="fas fa-calendar-alt"></i><span>Schedule</span></a></li>
            <li><a href="admin_usermanagement.php" class="active"><i class="fas fa-user-tie"></i><span>User Management</span></a></li>
            <li><a href="admin_audit.php"><i class="fas fa-history"></i><span>Audit Trail</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Schedule Management</h1>
                <p>Manage court hearings, meetings, and appointments</p>
            </div>
            <div class="user-info">
                <img src="<?= htmlspecialchars($profile_image) ?>" alt="Admin" style="object-fit:cover;width:60px;height:60px;border-radius:50%;border:2px solid #1976d2;">
                <div class="user-details">
                    <h3><?php echo $_SESSION['admin_name']; ?></h3>
                    <p>System Administrator</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <div class="view-options">
                <button class="btn btn-secondary active" data-view="month">
                    <i class="fas fa-calendar"></i> Month
                </button>
                <button class="btn btn-secondary" data-view="week">
                    <i class="fas fa-calendar-week"></i> Week
                </button>
                <button class="btn btn-secondary" data-view="day">
                    <i class="fas fa-calendar-day"></i> Day
                </button>
            </div>
        </div>
        <!-- Calendar Container -->
        <div class="calendar-container">
            <div id="calendar"></div>
        </div>

        <!-- Upcoming Events -->
        <div class="upcoming-events">
            <h2>Upcoming Events</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Case</th>
                        <th>Attorney</th>
                        <th>Client</th>
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
                        <td><?= htmlspecialchars($ev['attorney_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($ev['client_name'] ?? '-') ?></td>
                        <td><span class="status-badge status-scheduled"><?= htmlspecialchars($ev['status']) ?></span></td>
                        <td>
                            <button class="btn btn-info btn-xs view-info-btn"
                                data-type="<?= htmlspecialchars($ev['type']) ?>"
                                data-date="<?= htmlspecialchars($ev['date']) ?>"
                                data-time="<?= htmlspecialchars($ev['time']) ?>"
                                data-location="<?= htmlspecialchars($ev['location']) ?>"
                                data-case="<?= htmlspecialchars($ev['case_title'] ?? '-') ?>"
                                data-attorney="<?= htmlspecialchars($ev['attorney_name'] ?? '-') ?>"
                                data-client="<?= htmlspecialchars($ev['client_name'] ?? '-') ?>"
                                data-description="<?= htmlspecialchars($ev['description'] ?? '-') ?>"
                                style="font-size:0.95em; padding:3px 10px; border-radius:6px; background:#1976d2; color:#fff; border:none; cursor:pointer;">View Info</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Event Details Modal -->
        <div class="modal" id="eventInfoModal">
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
                            <div class="info-item"><span class="label">Attorney:</span><span class="value" id="modalAttorney"></span></div>
                            <div class="info-item"><span class="label">Client:</span><span class="value" id="modalClient"></span></div>
                            <div class="info-item"><span class="label">Description:</span><span class="value" id="modalDescription"></span></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" id="closeEventInfoModal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .calendar-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.08);
        }
        .fc .fc-toolbar-title {
            font-size: 1.6em;
            font-weight: 600;
            color: #1976d2;
        }
        .fc .fc-daygrid-day.fc-day-today {
            background: #e3f2fd;
        }
        .fc .fc-daygrid-event {
            border-radius: 6px;
            font-size: 1em;
            box-shadow: 0 1px 4px rgba(25, 118, 210, 0.08);
        }
        .fc .fc-button {
            background: #1976d2;
            border: none;
            color: #fff;
            border-radius: 5px;
            padding: 6px 14px;
            font-weight: 500;
            margin: 0 2px;
            transition: background 0.2s;
        }
        .fc .fc-button:hover, .fc .fc-button:focus {
            background: #1565c0;
        }
        .fc .fc-button-primary:not(:disabled).fc-button-active, .fc .fc-button-primary:not(:disabled):active {
            background: #43a047;
        }
        .view-options .btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            background: #f4f6f8;
            color: #1976d2;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
            }
        .view-options .btn.active, .view-options .btn:active {
            background: #1976d2;
            color: #fff;
            }
        .legend {
            font-size: 1em;
        }
        @media (max-width: 900px) {
            .calendar-container { padding: 5px; }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var events = <?php echo json_encode($js_events); ?>;
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 600,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: events,
                eventClick: function(info) {
                    showEventModal(info.event.extendedProps, info.event);
                }
            });
            calendar.render();
            // View Info button in table
            document.querySelectorAll('.view-info-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    showEventModal({
                        type: this.dataset.type,
                        date: this.dataset.date,
                        time: this.dataset.time,
                        location: this.dataset.location,
                        case: this.dataset.case,
                        attorney: this.dataset.attorney,
                        client: this.dataset.client,
                        description: this.dataset.description
                    });
                });
            });
            // Modal logic
            function showEventModal(props, eventObj) {
                document.getElementById('modalType').innerText = props.type || (eventObj && eventObj.title ? eventObj.title.split(':')[0] : '') || '';
                document.getElementById('modalDate').innerText = props.date || (eventObj && eventObj.start ? eventObj.start.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' }) : '');
                document.getElementById('modalTime').innerText = props.time || (eventObj && eventObj.start ? eventObj.start.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }) : '');
                document.getElementById('modalLocation').innerText = props.location || '';
                document.getElementById('modalCase').innerText = props.case || '';
                document.getElementById('modalAttorney').innerText = props.attorney || '';
                document.getElementById('modalClient').innerText = props.client || '';
                document.getElementById('modalDescription').innerText = props.description || '';
                document.getElementById('eventInfoModal').style.display = "block";
            }
            document.querySelectorAll('.close-modal, #closeEventInfoModal').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.getElementById('eventInfoModal').style.display = "none";
                });
            });
            window.onclick = function(event) {
                if (event.target == document.getElementById('eventInfoModal')) {
                    document.getElementById('eventInfoModal').style.display = "none";
                }
            }
        });
    </script>
</body>
</html> 