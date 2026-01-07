<?php

// Huwag nang maglagay ng session_start() dito kung meron na sa config.php
require_once 'api/config.php';

// Check kung naka-login at kung may officer role (not Member or Member Staff)
$officerRoles = ['Admin', 'Executive Director', 'Program Officer', 'Regional Convenor', 'Local Coordinator', 'Finance Officer', 'Meal Officer'];

if (!isset($_SESSION['role'])) {
    // Not logged in - redirect to login
    header("Location: login.html");
    exit();
}

if (!in_array($_SESSION['role'], $officerRoles) && $_SESSION['role'] !== 'Member Staff') {
    // Invalid role - redirect to login
    header("Location: login.html?error=invalid_role");
    exit();
}

if ($_SESSION['role'] === 'Member Staff') {
    // Member Staff users should access member dashboard
    header("Location: member-dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Reboot PH</title>
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/style.css">
    <script src="assets/vendor/qrcode.min.js"></script>
    <script src="assets/vendor/html2canvas.min.js"></script>
    <script src="assets/vendor/html5-qrcode.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        .sidebar-menu .list-group-item {
            padding: 12px 16px;
            border: none;
            border-radius: 6px;
            margin: 2px 12px;
            transition: 0.2s;
        }

        .sidebar-menu .list-group-item:hover {
            background: #f0f7ff;
        }

        .sidebar-menu .list-group-item.active {
            background: #035996 !important;
            color: #fff !important;
            font-weight: 600;
            border-radius: 6px;
        }

        /* for admin & member btn switch */
        /* Normal State */
        .custom-toggle-btn {
            background-color: #E6F0FA !important;
            color: #035996 !important;
            border: 1px solid #E6F0FA !important;
            transition: 0.2s;
        }

        /* Hover */
        .custom-toggle-btn:hover {
            background-color: #d9e9f7 !important;
            color: #035996 !important;
        }

        /* Active State */
        .custom-toggle-btn.active {
            background-color: #4BB949 !important;
            color: #ffffff !important;
            border-color: #4BB949 !important;
        }

        /* Metric Cards Hover Effect */
        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
        }

        /* ID Card Responsive Styling */
        .id-card {
            background: #ffffff;
            border: 2px solid #035996;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            aspect-ratio: 85 / 54;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(8px, 2vw, 16px);
            padding: clamp(8px, 2%, 16px);
            box-sizing: border-box;
            overflow: hidden;
        }

        .id-card img {
            max-width: 100%;
            height: auto;
        }

        .id-card .profile-image {
            width: clamp(40px, 20%, 80px);
            height: clamp(40px, 20%, 80px);
            border-radius: 50%;
            object-fit: cover;
        }

        .id-card .logo {
            width: clamp(30px, 15%, 50px);
            height: auto;
        }

        .id-card .qr-code {
            width: clamp(60px, 25%, 100px);
            height: clamp(60px, 25%, 100px);
        }

        .id-card h3,
        .id-card h4,
        .id-card h5,
        .id-card h6 {
            font-size: clamp(10px, 3vw, 14px);
            margin: clamp(2px, 1%, 4px) 0;
        }

        .id-card p {
            font-size: clamp(8px, 2vw, 12px);
            margin: clamp(1px, 0.5%, 2px) 0;
        }

        .id-card small {
            font-size: clamp(7px, 1.5vw, 10px);
        }
    </style>

</head>

<body class="bg-light d-flex flex-column min-vh-100"
    style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;">
    <nav class="navbar navbar-expand-lg fixed-top bg-light bg-opacity-100 shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-2 brand-logo" href="index.html">
                <img src="assets/image/reboot-logo.png" alt="Reboot PH logo">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                style="border:2px solid #035996; --bs-navbar-toggler-icon-bg:url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 30 30\'><path stroke=\'%23035996\' stroke-width=\'2\' stroke-linecap=\'round\' d=\'M4 7h22M4 15h22M4 23h22\'/></svg>');">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn custom-toggle-btn active"
                                onclick="switchView('admin')">Admin View</button>
                            <button type="button" class="btn custom-toggle-btn" onclick="switchView('member')">Member
                                View</button>
                        </div>
                    </li>
                    <li class="nav-item">
                        <span class="text-blue fw-bold me-3">Welcome,
                            <?php echo htmlspecialchars($_SESSION['firstName'] . ' ' . $_SESSION['lastName']); ?>!
                        </span>
                    </li>
                    <li class="nav-item">
                        <a href="login.html">
                            <button class="btn btn-outline-success">Logout</button>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container-fluid my-5 pt-5 flex-grow-1">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-3 col-lg-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <!-- Admin View Navigation -->
                        <div id="adminNav">
                            <div class="p-3 pb-2">
                                <h6 class="text-uppercase text-muted fw-bold small mb-2">Navigation</h6>
                                <div class="list-group list-group-flush sidebar-menu align-items-center">
                                    <a href="#dashboard"
                                        class="list-group-item list-group-item-action d-flex align-items-center active">
                                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                                    </a>
                                    <a href="#members"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-people me-2"></i> Members Management
                                    </a>
                                    <a href="#applications"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-file-earmark-text me-2"></i> Member Application Management
                                    </a>
                                    <a href="#questionnaire"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-question-circle me-2"></i> Application Questionnaire
                                    </a>
                                    <a href="#events"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-calendar-event me-2"></i> Event Management
                                    </a>
                                    <a href="#event-proposals"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-lightbulb me-2"></i> Event Proposals
                                    </a>
                                    <a href="#content"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-layout-text-window-reverse me-2"></i> Content Management
                                    </a>
                                    <a href="#reports"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-bar-chart me-2"></i> Reports
                                    </a>
                                    <a href="#event-attendance"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-clipboard-check me-2"></i> Event Attendance
                                    </a>
                                    <a href="#feedback-management"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-chat-quote me-2"></i> Feedback Management
                                    </a>
                                    <a href="#admin-settings"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-gear me-2"></i> Admin Settings
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Member View Navigation (Initially Hidden) -->
                        <div id="memberNav" class="d-none">
                            <div class="p-3 pb-2">
                                <h6 class="text-uppercase text-muted fw-bold small mb-2">Navigation</h6>
                                <div class="list-group list-group-flush sidebar-menu align-items-center">
                                    <a href="#member-dashboard"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                                    </a>
                                    <a href="#profile"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-person me-2"></i> Profile
                                    </a>
                                    <a href="#activities"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-list-task me-2"></i> My Activities
                                    </a>
                                    <a href="#history"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-clock-history me-2"></i> Activity History
                                    </a>
                                    <a href="#settings"
                                        class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-gear me-2"></i> Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-9 col-lg-10">
                <!-- Updated Dashboard Overview Section -->
                <div id="dashboard" class="active-section">
                    <h2 class="h4 mb-4">Admin Dashboard</h2>
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="card shadow-sm border-0 rounded-3 metric-card" id="totalMembersCard"
                                style="cursor: pointer; transition: all 0.2s;">
                                <div class="card-body text-center">
                                    <img src="assets/image/Total Members.svg" alt="Total Members Icon" height="32px">
                                    <h5 class="text-secondary fw-bold">Total Members</h5>
                                    <p class="display-5 fw-bold text-blue mb-0" id="dashboardTotalMembers">0</p>
                                    <small class="text-success fw-semibold" id="dashboardMembersSubtext">0 new this
                                        month</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow-sm border-0 rounded-3 metric-card" id="activeInitiativesCard"
                                style="cursor: pointer; transition: all 0.2s;">
                                <div class="card-body text-center">
                                    <img src="assets/image/Active Initiatives.svg" alt="Active Initiatives Icon"
                                        height="32px">
                                    <h5 class="text-secondary fw-bold">Active Initiatives</h5>
                                    <p class="display-5 fw-bold text-blue mb-0" id="dashboardActiveInitiatives">0</p>
                                    <small class="text-success fw-semibold" id="dashboardInitiativesSubtext">0
                                        available</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow-sm border-0 rounded-3 metric-card" id="pendingApplicationsCard"
                                style="cursor: pointer; transition: all 0.2s;">
                                <div class="card-body text-center">
                                    <img src="assets/image/Pending Applications.svg" alt="Pending Applications Icon"
                                        height="32px">
                                    <h5 class="text-secondary fw-bold">Pending Applications</h5>
                                    <p class="display-5 fw-bold text-blue mb-0" id="dashboardPendingApplications">0</p>
                                    <small class="text-warning fw-semibold">Needs review</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow-sm border-0 rounded-3 metric-card" id="eventProposalsCard"
                                style="cursor: pointer; transition: all 0.2s;">
                                <div class="card-body text-center">
                                    <img src="assets/image/Event Proposals.svg" alt="Event Proposals Icon"
                                        height="32px">
                                    <h5 class="text-secondary fw-semibold">Event Proposals</h5>
                                    <p class="display-5 fw-bold text-blue mb-0" id="dashboardEventProposals">0</p>
                                    <small class="text-success fw-semibold" id="dashboardProposalsSubtext">0 awaiting
                                        approval</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm border-0 rounded-3">
                                <div class="card-body">
                                    <h5 class="fw-bold text-blue mb-3">Quick Actions</h5>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-success d-flex align-items-center gap-1 fw-semibold"
                                            data-bs-toggle="modal" data-bs-target="#newEventModal">
                                            <i class="bi bi-plus-circle"></i> Create Event
                                        </button>
                                        <button class="btn btn-primary d-flex align-items-center gap-1 fw-semibold"
                                            onclick="navigateToMembers()">
                                            <i class="bi bi-people"></i> Update Member Roles
                                        </button>
                                        <!-- <button
                                            class="btn btn-primary text-white d-flex align-items-center gap-1 fw-semibold"
                                            data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
                                            <i class="bi bi-megaphone"></i> New Announcement
                                        </button> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm border-0 rounded-3">
                                <div class="card-body">
                                    <h5 class="fw-bold text-blue mb-3">Recent Applications</h5>
                                    <div class="list-group list-group-flush" id="recentApplicationsList">
                                        <p class="text-muted">Loading...</p>
                                    </div>
                                    <button id="viewAllApplicationsBtn" class="btn btn-success fw-semibold mt-3"
                                        style="width: 100%;">View All
                                        Applications</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm border-0 rounded-3">
                                <div class="card-body">
                                    <h5 class="fw-bold text-blue mb-3">Upcoming Events</h5>
                                    <div class="list-group list-group-flush" id="dashboardEventsList">
                                        <p class="text-muted">Loading...</p>
                                    </div>
                                    <button id="viewAllEventsBtn" class="btn btn-success fw-semibold mt-3"
                                        style="width: 100%;">View All
                                        Events</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Members Management Section -->
                <div id="members" class="d-none active-section">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0">Active Members</h5>
                                <span class="badge bg-primary rounded-pill" id="memberCount">0</span>
                            </div>

                            <!-- Search Bar and Filters -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path
                                                    d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.2.215.34.607.34 1.055a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z" />
                                            </svg>
                                        </span>
                                        <input type="text" class="form-control" id="memberSearchInput"
                                            placeholder="Search by name or email..." onkeyup="filterMembers()">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="memberRoleFilter" onchange="filterMembers()">
                                        <option value="">All Roles</option>
                                        <option value="Member">Member</option>
                                        <option value="Member Staff">Member Staff</option>
                                        <option value="Executive Director">Executive Director</option>
                                        <option value="Program Officer">Program Officer</option>
                                        <option value="Regional Convenor">Regional Convenor</option>
                                        <option value="Local Coordinator">Local Coordinator</option>
                                        <option value="Finance Officer">Finance Officer</option>
                                        <option value="Meal Officer">Meal Officer</option>
                                        <option value="Admin">Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="memberStatusFilter" onchange="filterMembers()">
                                        <option value="">All Status</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">No.</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Join Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="membersTableBody">
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Loading members...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Applications Section -->
                <div id="applications" class="d-none active-section">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0">Member Application Management</h5>
                                <span class="badge bg-info rounded-pill" id="pendingApplicationCount">0</span>
                            </div>

                            <!-- Status Filters -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path
                                                    d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.2.215.34.607.34 1.055a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z" />
                                            </svg>
                                        </span>
                                        <input type="text" class="form-control" id="applicationSearchInput"
                                            placeholder="Search by name or email..." onkeyup="filterApplications()">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" id="applicationStatusFilter"
                                        onchange="filterApplications()">
                                        <option value="">All Status</option>
                                        <option value="0">Under Review (Pending)</option>
                                        <option value="1">Approved</option>
                                        <option value="2">Rejected</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Applications List -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Submission Date</th>
                                            <th>Status</th>
                                            <th>Review Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="applicationsTableBody">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Loading applications...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Application Review Modal -->
                    <div class="modal fade" id="applicationModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Application Review</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-4">
                                        <h6 class="border-bottom pb-2">Personal Information</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted">First Name</label>
                                                <p id="appFName" class="mb-0"></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted">Last Name</label>
                                                <p id="appLName" class="mb-0"></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted">Middle Name</label>
                                                <p id="appMName" class="mb-0"></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted">Extension Name</label>
                                                <p id="appExtName" class="mb-0"></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted">Gender</label>
                                                <p id="appGender" class="mb-0"></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted">Birth Date</label>
                                                <p id="appBirthDate" class="mb-0"></p>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small text-muted">Email</label>
                                                <p id="appEmail" class="mb-0"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <h6 class="border-bottom pb-2">Assessment Answers</h6>
                                        <div id="appAnswersContainer"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small text-muted">Submission Date</label>
                                        <p id="appSubmissionDate" class="mb-0"></p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" id="approveBtn" class="btn btn-success" onclick="approveApplication()">Approve</button>
                                    <button type="button" class="btn btn-danger" onclick="rejectApplication()">Reject</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Questionnaire Section -->
                <div id="questionnaire" class="d-none active-section">
                    <div class="row mb-4">
                        <!-- Active Questionnaires Card -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Active Questionnaires (Registration)</h5>
                                </div>
                                <div class="card-body">
                                    <div id="activeQuestionnairesDisplay">
                                        <p class="text-muted">Loading active questionnaires...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats Card -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Questionnaire Statistics</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <p class="text-muted mb-1">Total Questions</p>
                                            <h4 id="totalQuestionsCount">0</h4>
                                        </div>
                                        <div class="col-6">
                                            <p class="text-muted mb-1">Active Questions</p>
                                            <h4 id="activeQuestionsCount">2</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Manage Questionnaires Card -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Manage Questions</h5>
                            <div class="d-flex gap-2">
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#setActiveModal" onclick="loadActiveQuestionsModal()">
                                    <i class="bi bi-gear"></i> Configure Active Questions
                                </button>
                                <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#addQuestionnaireModal" onclick="openAddQuestionnaireModal()">
                                    <i class="bi bi-plus-lg"></i> Add New Question
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Question ID</th>
                                            <th>Question Text</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="questionnairesTableBody">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Loading questions...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add/Edit Questionnaire Modal -->
                <div class="modal fade" id="addQuestionnaireModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="questionnaireModalTitle">Add New Question</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="questionnaireForm">
                                    <div class="mb-3">
                                        <label class="form-label">Question Text</label>
                                        <textarea class="form-control" id="questionText" rows="4"
                                            placeholder="Enter the question for applicants..." required></textarea>
                                        <small class="text-muted">This question will appear in the registration form for
                                            applicants.</small>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="setAsActive">
                                            <label class="form-check-label" for="setAsActive">
                                                Set as one of the active questions
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">Active questions will appear in the
                                            registration form (max 2 active).</small>
                                    </div>
                                    <input type="hidden" id="questionIdInput">
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="saveQuestionnaire()">Save
                                    Question</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Set Active Questions Modal -->
                <div class="modal fade" id="setActiveModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Select Active Questions</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-3"><strong>Select exactly 2 questions to be active in the registration
                                        form:</strong></p>
                                <div id="activeQuestionsCheckboxes">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                                <small class="text-muted d-block mt-3">Selected questions will appear in the
                                    registration form. You must select exactly 2 questions.</small>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="saveActiveQuestions()">Apply
                                    Changes</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View Question Modal -->
                <div class="modal fade" id="viewQuestionModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">View Question</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Question ID:</strong></label>
                                    <p id="viewQuestionId">-</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><strong>Question Text:</strong></label>
                                    <p id="viewQuestionText" style="word-wrap: break-word;">-</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><strong>Status:</strong></label>
                                    <p id="viewQuestionStatus">-</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><strong>Created Date:</strong></label>
                                    <p id="viewQuestionDate">-</p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Event Proposals Section -->
                <div id="event-proposals" class="d-none active-section">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2 class="h4 mb-0">Event Proposals</h2>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newEventModal">
                                    Create New Proposal
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Event Name</th>
                                            <th>Proposed By</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="proposalsTableBody">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Loading proposals...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Event Management Section -->
                <div id="events" class="d-none active-section admin-section">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2 class="h4 mb-0">Event Management</h2>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#newEventModal">
                                    <i class="bi bi-plus-lg"></i> Create Event Proposal
                                </button>
                            </div>

                            <!-- Event Filters -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path
                                                    d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.2.215.34.607.34 1.055a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z" />
                                            </svg>
                                        </span>
                                        <input type="text" class="form-control" id="eventSearchInput"
                                            placeholder="Search by event name..." onkeyup="filterEvents()">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" id="eventStatusFilter" onchange="filterEvents()">
                                        <option value="">All Status</option>
                                        <option value="Scheduled">Scheduled</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Moved">Moved</option>
                                        <option value="Postponed">Postponed</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Upcoming Events Table -->
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Event Name</th>
                                            <th>Date & Time</th>
                                            <th>Location</th>
                                            <th>Capacity</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="eventsTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Event Attendance Management Section -->
                <div id="event-attendance" class="d-none active-section admin-section">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="h4 mb-4">Event Attendance Management</h2>

                            <!-- Event Selection and QR Button -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="attendanceEventSelect" class="form-label">Select Ongoing Event</label>
                                    <select id="attendanceEventSelect" class="form-select"
                                        onchange="loadEventAttendanceMembers()">
                                        <option value="">-- Choose an Event --</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-success w-100 d-none" id="showQRButton" onclick="showOrganizerQRCode()">
                                        <i class="bi bi-qr-code"></i> Show Attendance QR
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="btn-group w-100" role="group" id="attendanceFilterGroup">
                                        <button type="button" class="btn btn-outline-primary active" onclick="setAttendanceFilter('all')" data-filter="all">All</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="setAttendanceFilter('members')" data-filter="members">Members</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="setAttendanceFilter('non-members')" data-filter="non-members">Non-Members</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Attendance Stats -->
                            <div class="row mb-4" id="attendanceStatsRow" style="display: none;">
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="card-title text-muted">Total Registered</h6>
                                            <h2 class="display-6 mb-0" id="totalRegisteredCard">-</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="card-title text-muted">Checked In</h6>
                                            <h2 class="display-6 mb-0" id="totalCheckedInCard">-</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="card-title text-muted">Attendance Rate</h6>
                                            <h2 class="display-6 mb-0" id="attendanceRateCard">-</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Members Attendance List -->
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Type</th>
                                            <th>Attendance Status</th>
                                            <th>Check-in Time</th>
                                        </tr>
                                    </thead>
                                    <tbody id="attendanceMembersTableBody">
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                Select an event to view registered attendees
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feedback Management Section -->
                <div id="feedback-management" class="d-none active-section admin-section">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="h4 mb-4">Feedback Management</h2>

                            <!-- Event Selection -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <label for="feedbackEventSelect" class="form-label">Select Event</label>
                                    <select id="feedbackEventSelect" class="form-select"
                                        onchange="loadFeedbackMembers()">
                                        <option value="">-- Choose an Event --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="btn-group w-100" role="group" id="feedbackFilterGroup">
                                        <button type="button" class="btn btn-outline-primary active" onclick="setFeedbackFilter('all')" data-filter="all">All</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="setFeedbackFilter('members')" data-filter="members">Members</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="setFeedbackFilter('non-members')" data-filter="non-members">Non-Members</button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="alert alert-info mt-3" id="feedbackStats" style="display: none;">
                                        <strong id="feedbackStatsText"></strong>
                                        <div id="feedbackAverageRating" class="mt-2" style="display: none;">
                                            <strong>Average Rating:</strong>
                                            <span id="averageRatingStars" class="ms-2 text-warning"></span>
                                            <span id="averageRatingValue" class="ms-2 text-dark"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Members with Feedback Status -->
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Type</th>
                                            <th>Attendance Time</th>
                                            <th>Feedback Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="feedbackMembersTableBody">
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                Select an event to view attendees
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Management Section -->
                <div id="content" class="d-none active-section">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="h4 mb-4">Content Management</h2>
                            <ul class="nav nav-tabs mb-4">
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#hero-content">Landing Page</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#initiatives-content">Initiatives</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#announcements-content">Announcements
                                        & Newsletters</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#about-us-content">About Us</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#socials-content">Social Links</a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <!-- Hero Section Content -->
                                <div id="hero-content" class="tab-pane fade">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h3 class="h5 mb-0">Manage Landing Page</h3>
                                        <button class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#newHeroModal" onclick="openAddHeroModal()">
                                            Add New Hero Section
                                        </button>
                                    </div>

                                    <!-- Hero Sections List -->
                                    <h4 class="h6 mb-3">Manage Landing Page</h4>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Description</th>
                                                    <th>Created Date</th>
                                                    <th>Status</th>
                                                    <th>Active</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="heroTableBody">
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">Loading hero
                                                        sections...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Manage Member Benefits -->
                                    <hr class="my-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h3 class="h5 mb-0">Manage Member Benefits</h3>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#memberBenefitModal" onclick="openAddMemberBenefitModal()">
                                            Add Benefit
                                        </button>
                                    </div>

                                    <div class="table-responsive mb-4">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Description</th>
                                                    <th>Active</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="memberBenefitsTableBody">
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">Loading member
                                                        benefits...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    </hr>

                                </div>

                                <!-- Initiatives Content -->
                                <div id="initiatives-content" class="tab-pane fade">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h3 class="h5 mb-0">Manage Initiatives</h3>
                                        <button class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#newInitiativeModal" onclick="openAddInitiativeModal()">
                                            Add New Initiative
                                        </button>
                                    </div>

                                    <!-- Initiatives List -->
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Category</th>
                                                    <th>Status</th>
                                                    <th>Last Updated</th>
                                                    <th>Highlight</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="initiativesTableBody">
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">Loading
                                                        initiatives...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Announcements Content -->
                                <div id="announcements-content" class="tab-pane fade">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h3 class="h5 mb-0">Manage Announcements & Newsletters</h3>
                                        <button class="btn btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#newNewsletterModal" onclick="openAddNewsletterModal()">
                                            New Newsletter
                                        </button>
                                    </div>

                                    <!-- Announcements List -->
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h4 class="h6 mb-3">Recent Announcements (from Approved Proposals)</h4>
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Title</th>
                                                            <th>Description</th>
                                                            <th>Submitted By</th>
                                                            <th>Status</th>
                                                            <th>Priority</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="announcementsTableBody">
                                                        <tr>
                                                            <td colspan="6" class="text-center text-muted">Loading
                                                                announcements...</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Newsletters List -->
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="h6 mb-3">Recent Newsletters</h4>
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Title</th>
                                                            <th>Content Preview</th>
                                                            <th>Published</th>
                                                            <th>Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="newslettersTableBody">
                                                        <tr>
                                                            <td colspan="5" class="text-center text-muted">Loading
                                                                newsletters...</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- About Us Content -->
                                <div id="about-us-content" class="tab-pane fade">
                                    <h3 class="h5 mb-4">Manage About Us Content</h3>

                                    <!-- Nested tabs for About Us subsections -->
                                    <ul class="nav nav-pills mb-4" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" data-bs-toggle="pill"
                                                data-bs-target="#mission-content" type="button" role="tab">
                                                Mission
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" data-bs-toggle="pill"
                                                data-bs-target="#vision-content" type="button" role="tab">
                                                Vision
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" data-bs-toggle="pill"
                                                data-bs-target="#values-content" type="button" role="tab">
                                                Values
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <!-- Mission Section -->
                                        <div id="mission-content" class="tab-pane fade show active" role="tabpanel">
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <h4 class="h6 mb-0">Mission</h4>
                                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#editMissionModal" onclick="openEditMissionModal()">
                                                    <i class="bi bi-pencil"></i> Edit Mission
                                                </button>
                                            </div>
                                            <div class="card">
                                                <div class="card-body">
                                                    <div id="missionContent">
                                                        <div class="text-center mb-3">
                                                            <h3 id="missionTitle" class="h4 mb-2">Our Mission</h3>
                                                            <p id="missionSubtitle" class="lead text-muted">Loading...
                                                            </p>
                                                        </div>
                                                        <ul id="missionBullets" class="list-unstyled">
                                                            <li class="text-muted mb-2">Loading mission content...</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Vision Section -->
                                        <div id="vision-content" class="tab-pane fade" role="tabpanel">
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <h4 class="h6 mb-0">Vision</h4>
                                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#editVisionModal" onclick="openEditVisionModal()">
                                                    <i class="bi bi-pencil"></i> Edit Vision
                                                </button>
                                            </div>
                                            <div class="card">
                                                <div class="card-body">
                                                    <div id="visionContent">
                                                        <div class="text-center mb-3">
                                                            <h3 id="visionTitle" class="h4 mb-2">Our Vision</h3>
                                                            <p id="visionSubtitle" class="lead text-muted">Loading...
                                                            </p>
                                                        </div>
                                                        <ul id="visionBullets" class="list-unstyled">
                                                            <li class="text-muted mb-2">Loading vision content...</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Values Section -->
                                        <div id="values-content" class="tab-pane fade" role="tabpanel">
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <h4 class="h6 mb-0">Values</h4>
                                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#newValueModal" onclick="openAddValueModal()">
                                                    <i class="bi bi-plus-circle"></i> Add Value
                                                </button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Title</th>
                                                            <th>Description</th>
                                                            <th>Icon</th>
                                                            <th>Order</th>
                                                            <th>Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="valuesTableBody">
                                                        <tr>
                                                            <td colspan="6" class="text-center text-muted">Loading
                                                                values...</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!-- Social Links Content -->
                                <div id="socials-content" class="tab-pane fade">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h3 class="h5 mb-0">Manage Social Media Links</h3>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Facebook URL</label>
                                            <input id="socialFacebook" class="form-control"
                                                placeholder="https://www.facebook.com/...">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Instagram URL</label>
                                            <input id="socialInstagram" class="form-control"
                                                placeholder="https://www.instagram.com/...">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">LinkedIn URL</label>
                                            <input id="socialLinkedIn" class="form-control"
                                                placeholder="https://www.linkedin.com/...">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Contact Email</label>
                                            <input id="socialEmail" class="form-control" placeholder="contact@your.org">
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <button class="btn btn-primary" onclick="saveSocialLinksAdmin()">Save Social
                                            Links</button>
                                        <button class="btn btn-secondary"
                                            onclick="loadSocialLinksAdmin()">Reload</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Reports Section for Admins -->
                <div id="reports" class="d-none active-section admin-section">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2 class="h4 mb-0">Reports</h2>
                                <div class="d-flex gap-2">
                                    <div>
                                        <label class="form-label small mb-1">Date Range:</label>
                                        <select id="dateRangeSelect" class="form-select form-select-sm"
                                            style="width: 180px;">
                                            <option value="30">Last 30 Days</option>
                                            <option value="90">Last Quarter</option>
                                            <option value="365">Last Year</option>
                                            <option value="custom">Custom Range</option>
                                        </select>
                                    </div>
                                    <div id="customDateRange" class="d-none"
                                        style="display: flex; gap: 10px; align-items: flex-end;">
                                        <div>
                                            <label class="form-label small mb-1">From:</label>
                                            <input type="date" id="reportStartDate"
                                                class="form-control form-control-sm">
                                        </div>
                                        <div>
                                            <label class="form-label small mb-1">To:</label>
                                            <input type="date" id="reportEndDate" class="form-control form-control-sm">
                                        </div>
                                        <button class="btn btn-sm btn-primary" onclick="loadReports()">Filter</button>
                                    </div>
                                    <button class="btn btn-primary" onclick="exportReport('pdf')">
                                        <i class="bi bi-file-pdf"></i> Export PDF
                                    </button>
                                </div>
                            </div>

                            <!-- Summary Cards - Responsive Grid -->
                            <div class="row g-2 g-md-4 mb-4">
                                <div class="col-6 col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted">Total Events</h6>
                                            <h2 class="display-6 mb-0" id="totalEventsCard">-</h2>
                                            <small id="eventsChangeCard">-</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted">Total Registrations</h6>
                                            <h2 class="display-6 mb-0" id="totalRegistrationsCard">-</h2>
                                            <small id="registrationsChangeCard">-</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted">Attendance Rate</h6>
                                            <h2 class="display-6 mb-0" id="avgAttendanceRateCard">-</h2>
                                            <small class="text-info">System Average</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted">Avg. Rating</h6>
                                            <h2 class="display-6 mb-0" id="avgFeedbackRatingCard">-</h2>
                                            <small id="feedbackCountCard">-</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted">Active Members</h6>
                                            <h2 class="display-6 mb-0" id="totalActiveMembersCard">-</h2>
                                            <small class="text-muted">Registered Members</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted">Non-Members</h6>
                                            <h2 class="display-6 mb-0" id="totalNonMembersCard">-</h2>
                                            <small class="text-info">In period</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted">Total Attendees</h6>
                                            <h2 class="display-6 mb-0" id="totalAttendeesCard">-</h2>
                                            <small class="text-muted">Members + Non-Members</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted">Initiatives</h6>
                                            <h2 class="display-6 mb-0" id="totalInitiativesCard">-</h2>
                                            <small class="text-muted">In period</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Charts Row -->
                            <div class="row g-3 g-md-4 mb-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Event Participation Trends</h5>
                                            <div id="trendsChartContainer" class="text-center text-muted py-5">
                                                <small>Loading trends data...</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Detailed Reports Tabs -->
                            <div class="card">
                                <div class="card-body">
                                    <ul class="nav nav-tabs mb-3">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-bs-toggle="tab"
                                                href="#events-report">Events</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#members-report">Members</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab"
                                                href="#initiatives-report">Initiatives</a>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <!-- Events Report -->
                                        <div id="events-report" class="tab-pane active">
                                            <div class="table-responsive">
                                                <table class="table table-hover table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Event Name</th>
                                                            <th>Date</th>
                                                            <th>Venue</th>
                                                            <th>Members</th>
                                                            <th>Non-Members</th>
                                                            <th>Total</th>
                                                            <th>Attended</th>
                                                            <th>Rate</th>
                                                            <th>Avg Rating</th>
                                                            <th>Feedback</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="eventsReportBody">
                                                        <tr>
                                                            <td colspan="10" class="text-center text-muted">Loading
                                                                events data...</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Members Report -->
                                        <div id="members-report" class="tab-pane fade">
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Member Type</th>
                                                            <th>Total Count</th>
                                                            <th>Active</th>
                                                            <th>Inactive</th>
                                                            <th>Avg. Participation</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="membersReportBody">
                                                        <tr>
                                                            <td colspan="5" class="text-center text-muted">Loading
                                                                members data...</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Initiatives Report -->
                                        <div id="initiatives-report" class="tab-pane fade">
                                            <div class="table-responsive">
                                                <table class="table table-hover table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Initiative Name</th>
                                                            <th>Category</th>
                                                            <th>Status</th>
                                                            <th>Publish Date</th>
                                                            <th>Description</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="initiativesReportBody">
                                                        <tr>
                                                            <td colspan="5" class="text-center text-muted">Loading
                                                                initiatives data...</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Settings Section -->
                <div id="admin-settings" class="d-none active-section admin-section">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="h4 mb-4 text-blue fw-bold">Admin Settings</h2>

                            <div class="p-4 bg-white rounded shadow-sm mb-4">
                                <h5 class="fw-bold text-blue mb-4">System Settings</h5>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="fw-bold text-blue mb-3">Member Registration</h6>

                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="enableRegistration"
                                                    checked>
                                                <label class="form-check-label" for="enableRegistration">
                                                    Enable Member Registration
                                                </label>
                                                <small class="d-block text-muted mt-1">When disabled, new
                                                    applications cannot be submitted</small>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="autoApprove">
                                                <label class="form-check-label" for="autoApprove">
                                                    Auto-approve New Members
                                                </label>
                                                <small class="d-block text-muted mt-1">When enabled, new
                                                    applicants
                                                    are automatically approved and added to members</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Maximum Auto-Approved
                                                    Members</label>
                                                <input type="number" class="form-control" id="maxMembers" value="1000"
                                                    min="1" placeholder="1000">
                                                <small class="d-block text-muted mt-1">Maximum members
                                                    allowed when
                                                    auto-approve is enabled</small>
                                            </div>
                                            <div class="alert alert-info alert-sm" role="alert">
                                                <i class="bi bi-info-circle"></i>
                                                <strong>Status:</strong> <span id="regStatus">Loading...</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="fw-bold text-blue mb-3">Event Settings</h6>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox"
                                                    id="enableEventProposals" checked>
                                                <label class="form-check-label" for="enableEventProposals">
                                                    Allow Event Proposals
                                                </label>
                                            </div>
                                            <label class="form-label fw-semibold">Event Capacity Limit</label>
                                            <input type="number" class="form-control mb-3" value="200"
                                                placeholder="200">

                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="autoEventReminders"
                                                    checked>
                                                <label class="form-check-label" for="autoEventReminders">
                                                    Automatic Event Reminders
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="fw-bold text-blue mb-3">System Backup</h6>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Database Size</label>
                                                <p class="form-control-plaintext"><strong
                                                        id="databaseSize">Loading...</strong></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Total Tables</label>
                                                <p class="form-control-plaintext"><strong
                                                        id="tableCount">Loading...</strong></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Backups Available</label>
                                                <p class="form-control-plaintext"><strong
                                                        id="backupCount">Loading...</strong></p>
                                            </div>

                                            <div class="d-flex gap-2 mb-3">
                                                <button class="btn btn-primary btn-sm" onclick="createSystemBackup()">
                                                    <i class="bi bi-cloud-arrow-down"></i> Backup Now
                                                </button>
                                                <button class="btn btn-secondary btn-sm" onclick="loadBackupsList()">
                                                    <i class="bi bi-arrow-clockwise"></i> Refresh List
                                                </button>
                                            </div>
                                            <div id="backupsList" class="list-group"
                                                style="max-height: 250px; overflow-y: auto;">
                                                <p class="text-muted small">Loading backups...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-white rounded shadow-sm mb-4">
                                <h5 class="fw-bold text-blue mb-4">Categories Management</h5>
                                <div class="mb-3">
                                    <button class="btn btn-primary btn-sm" onclick="openAddCategoryModal()">
                                        <i class="bi bi-plus-circle"></i> Add New Category
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40px;"><input type="checkbox" id="selectAllCategories"
                                                        onchange="toggleSelectAllCategories(this)"></th>
                                                <th>CategoryID</th>
                                                <th>Type</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="categoriesTableBody">
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Loading categories...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="p-4 bg-white rounded shadow-sm mb-4">
                                <h5 class="fw-bold text-blue mb-4"></h5>System Maintenance</h5>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="card-title fw-semibold text-blue mb-3">Cache Management</h6>
                                            <p class="card-text small text-muted">Clear temporary files and
                                                cached
                                                data to free up space.</p>
                                            <button class="btn btn-warning btn-sm mt-2" onclick="clearSystemCache()">
                                                <i class="bi bi-trash"></i> Clear System Cache
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="card-title fw-semibold text-blue mb-3">System Reset</h6>
                                            <p class="card-text small text-muted">Reset all settings to
                                                default.
                                                Database data is NOT deleted.</p>
                                            <button class="btn btn-danger btn-sm mt-2" onclick="confirmSystemReset()">
                                                <i class="bi bi-exclamation-triangle"></i> Reset System
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Member Dashboard Content -->
                <div id="member-dashboard" class="d-none active-section member-section">
                    <h2 class="h4 mb-4">Dashboard</h2>
                    <!-- Stats Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="card shadow-sm border-0 rounded-3 metric-card" id="totalActivitiesCard"
                                style="cursor: pointer; transition: all 0.2s;">
                                <div class="card-body text-center">
                                    <div class="mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#02075D"
                                            class="bi bi-calendar-check" viewBox="0 0 16 16">
                                            <path
                                                d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z" />
                                            <path
                                                d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z" />
                                        </svg>
                                    </div>
                                    <h5 class="fw-bold" style="color: #777777;">Total Activities</h5>
                                    <p class="display-5 fw-bold mb-0" id="statTotalActivities" style="color: #333333;">0
                                    </p>
                                    <small class="fw-semibold" style="color: #666666;">All time record</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card shadow-sm border-0 rounded-3 metric-card" id="upcomingActivitiesCard"
                                style="cursor: pointer; transition: all 0.2s;">
                                <div class="card-body text-center">
                                    <div class="mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#02075D"
                                            class="bi bi-calendar-event" viewBox="0 0 16 16">
                                            <path
                                                d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z" />
                                        </svg>
                                    </div>
                                    <h5 class="fw-bold" style="color: #777777;">Upcoming Activities</h5>
                                    <p class="display-5 fw-bold mb-0" id="statUpcomingActivities"
                                        style="color: #333333;">0</p>
                                    <small class="fw-semibold" style="color: #666666;">Next 30 days</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card shadow-sm border-0 rounded-3 metric-card" id="attendedActivitiesCard"
                                style="cursor: pointer; transition: all 0.2s;">
                                <div class="card-body text-center">
                                    <div class="mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#02075D"
                                            class="bi bi-person-check" viewBox="0 0 16 16">
                                            <path
                                                d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm1.679-4.493-1.335 2.226a.75.75 0 0 1-1.174.144l-.774-.773a.5.5 0 0 1 .708-.708l.547.548 1.17-1.951a.5.5 0 1 1 .858.514ZM11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                                            <path
                                                d="M8.256 14a4.474 4.474 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10c.26 0 .507.009.74.025.226-.341.496-.65.804-.918C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4s1 1 1 1h5.256Z" />
                                        </svg>
                                    </div>
                                    <h5 class="fw-bold" style="color: #777777;">Activities Attended</h5>
                                    <p class="display-5 fw-bold mb-0" id="statAttendedActivities"
                                        style="color: #333333;">0</p>
                                    <small class="fw-semibold" style="color: #666666;">Successfully joined</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Activities -->
                    <div class="card">
                        <div class="card-body">
                            <h3 class="h5 mb-4">Imminent Activities</h3>
                            <div class="list-group" id="dashboardEventsList">
                                <div class="text-center text-muted py-4">
                                    <p>Loading events...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Member Profile Content -->
                <div id="profile" class="d-none active-section member-section">
                    <h2 class="text-center fw-bold mb-4" style="color:#035996;">Member Profile</h2>

                    <div class="row justify-content-center g-4">

                        <div class="col-lg-6">
                            <div class="card shadow-sm border-0 profile-card">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h3 class="fw-bold mb-0" style="color:#035996;">Personal Information</h3>
                                        <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2"
                                            onclick="initiateProfileEdit()">
                                            <i class="bi bi-pencil"></i> Edit Profile
                                        </button>
                                    </div>

                                    <div class="text-center mb-4">
                                        <img id="displayProfileImage" src="assets/image/default-avatar.png"
                                            alt="Profile Photo" class="rounded-circle shadow-sm"
                                            style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #f8f9fa;">
                                    </div>

                                    <div class="mb-3">
                                        <label class="fw-semibold text-secondary small">Full Name:</label>
                                        <p class="mb-2 fw-bold" id="displayFullName">Loading...</p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="fw-semibold text-secondary small">Email:</label>
                                        <p class="mb-2" id="displayEmail">Loading...</p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="fw-semibold text-secondary small">Phone Number:</label>
                                        <p class="mb-2" id="displayPhone">Loading...</p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="fw-semibold text-secondary small">Role:</label>
                                        <p class="mb-2" id="displayRole">Loading...</p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="fw-semibold text-secondary small">Member Since:</label>
                                        <p class="mb-2" id="displayMemberSince">Loading...</p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="fw-semibold text-secondary small d-block">Membership
                                            Status:</label>
                                        <span class="badge bg-success px-3 py-2" id="displayStatus">Active</span>
                                    </div>

                                    <hr class="my-4">

                                    <h3 class="fw-bold mb-3 h5" style="color:#035996;">Change Password</h3>
                                    <form id="profilePasswordForm">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Current Password</label>
                                            <input type="password" class="form-control form-control-sm"
                                                id="currentPassword" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">New Password</label>
                                            <input type="password" class="form-control form-control-sm" id="newPassword"
                                                required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Confirm New Password</label>
                                            <input type="password" class="form-control form-control-sm"
                                                id="confirmPassword" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm w-100">Update
                                            Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="card shadow-sm border-0 profile-card">
                                <div class="card-body p-4">
                                    <h3 class="text-center fw-bold mb-4" style="color:#035996;">Member ID Preview</h3>

                                    <div class="id-card mx-auto shadow" id="idPreview"
                                        style="max-width: 350px; overflow: hidden; position: relative; font-family: sans-serif;">

                                        <div class="d-flex align-items-center justify-content-center h-100">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading Preview...</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center mt-4">
                                        <button class="btn btn-success px-4 fw-bold shadow-sm" onclick="downloadID()">
                                            <i class="bi bi-download me-2"></i> Download Organization ID
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- My Activities Content -->
                <div id="activities" class="d-none active-section member-section">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="h4 mb-4">My Activities</h2>
                            <ul class="nav nav-tabs mb-4" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" id="upcoming-tab" href="#upcoming" data-bs-toggle="tab"
                                        role="tab" aria-controls="upcoming" aria-selected="true">Upcoming</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="registered-tab" href="#registered" data-bs-toggle="tab"
                                        role="tab" aria-controls="registered" aria-selected="false">Registered</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="completed-tab" href="#completed" data-bs-toggle="tab"
                                        role="tab" aria-controls="completed" aria-selected="false">Completed</a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="upcoming">
                                    <div id="upcomingEventsList" class="list-group">
                                        <div class="text-center text-muted py-4">
                                            <p>Loading events...</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="registered">
                                    <div id="registeredEventsList" class="list-group">
                                        <div class="text-center text-muted py-4">
                                            <p>No registered events yet.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="completed">
                                    <div id="completedEventsList" class="list-group">
                                        <div class="text-center text-muted py-4">
                                            <p>No completed events yet.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity History Content -->
                <div id="history" class="d-none active-section member-section">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="h4 mb-4">Activity History</h2>
                            <div class="list-group" id="activityHistoryList">
                                <div class="text-center text-muted py-4">
                                    <p>Loading history...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Content -->
                <div id="settings" class="d-none active-section member-section">
                    <div class="card shadow-sm p-4" style="background:#f0f6ff; border-radius:12px;">
                        <h2 class="text-center fw-bold mb-4" style="color:#0b3d91;">Settings</h2>

                        <div class="p-4 mb-4"
                            style="background:white; border-radius:12px; border-left:6px solid #0b3d91;">
                            <h4 class="fw-bold mb-3" style="color:#0b3d91;">Notification Settings</h4>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="emailNotif" checked>
                                <label class="form-check-label" for="emailNotif">
                                    Email notifications for new activities
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="reminderNotif" checked>
                                <label class="form-check-label" for="reminderNotif">
                                    Activity reminders (24 hours before)
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="newsletterNotif" checked>
                                <label class="form-check-label" for="newsletterNotif">
                                    Newsletter subscription
                                </label>
                            </div>
                        </div>

                        <div class="p-4 mb-4"
                            style="background:white; border-radius:12px; border-left:6px solid #0b3d91;">
                            <h4 class="fw-bold mb-3" style="color:#0b3d91;">Privacy Settings</h4>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Show my profile to other members</span>
                                <input type="checkbox" class="form-check-input" id="profileVisibility" checked>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Show my activity history</span>
                                <input type="checkbox" class="form-check-input" id="activityVisibility" checked>
                            </div>
                        </div>

                        <div class="text-center">
                            <h4 class="fw-bold mb-3" style="color:#0b3d91;">Account Actions</h4>
                            <button class="btn btn-danger px-4" onclick="deactivateAccount()">Deactivate
                                Account</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Edit Profile Modal -->
        <div class="modal fade" id="editProfileModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editProfileForm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="editFirstName" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="editLastName" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="editEmail" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="editPhone"
                                        placeholder="+63 9XX XXX XXXX">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Profile Photo</label>
                                    <input type="file" class="form-control" id="editPhoto" accept="image/*">
                                    <div class="form-text">Maximum file size: 2MB. Supported formats: JPG, PNG</div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveProfileChanges()">Save
                            Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add this modal for password change -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Admin Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="adminPasswordForm">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" required>
                            <div class="form-text">Must be at least 8 characters with numbers and special
                                characters
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateAdminPassword()">Update
                        Password</button>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Scanner Modal for Admin Attendance -->
    <div class="modal fade" id="qrScannerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-qr-code"></i> Event Attendance QR Code</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="closeOrganizerQR()"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="text-muted mb-3">Ask members to scan this QR code with their phone to mark attendance</p>
                    <div id="organizerQRCode" class="d-flex justify-content-center mb-3"></div>
                    <div class="alert alert-info">
                        <small><i class="bi bi-info-circle"></i> Members will scan this QR code to automatically mark their attendance</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeOrganizerQR()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Copy all modals from member-dashboard -->
    <div class="modal fade" id="serialModal" tabindex="-1">
        <!-- ... copy serial number modal content ... -->
    </div>

    <!-- Add Attendance Modal -->
    <div class="modal fade" id="attendanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopQRScanner()"></button>
                </div>
                <div class="modal-body">
                    <p>Please scan the QR code or enter your serial number for:</p>
                    <p class="fw-bold" id="modalActivityTitle"></p>
                    <p class="text-muted small" id="modalActivityDate"></p>
                    
                    <!-- QR Scanner -->
                    <div id="qr-reader" style="width: 100%; height: 300px; margin-bottom: 15px;"></div>
                    
                    <div class="mb-3">
                        <label for="serialNumber" class="form-label">Serial Number</label>
                        <input type="text" class="form-control" id="serialNumber" placeholder="e.g., RPH-123456-ABCD"
                            required>
                        <div class="form-text">Scan the QR code or enter the serial number you received when registering</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="stopQRScanner()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="verifyAttendance()">Verify
                        Attendance</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Feedback Modal -->
    <div class="modal fade" id="viewFeedbackModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Feedback Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Event:</strong> <span id="feedbackEventName"></span></p>
                            <p><strong>Date:</strong> <span id="feedbackEventDate"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Member:</strong> <span id="feedbackMemberName"></span></p>
                            <p><strong>Email:</strong> <span id="feedbackMemberEmail"></span></p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>Rating</strong></label>
                        <div id="feedbackRating">
                            <!-- Stars will be displayed here -->
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Overall Experience</strong></label>
                            <p id="feedbackOverallExperience" class="mb-0"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Knowledge Gained</strong></label>
                            <p id="feedbackKnowledgeGained" class="mb-0"></p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>Comments</strong></label>
                        <div id="feedbackComments" class="p-3 bg-light rounded"></div>
                    </div>

                    <div class="mb-3">
                        <p><strong>Submitted:</strong> <span id="feedbackSubmissionDate"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Proposal Details Modal -->
    <div class="modal fade" id="viewProposalModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Event Proposal Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-4">
                        <h6 class="border-bottom pb-2">Basic Information</h6>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Event Title</label>
                                <input type="text" id="propEventName" class="form-control" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Proposed Date</label>
                                <input type="text" id="propDate" class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Time</label>
                                <input type="text" id="propStartTime" class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Time</label>
                                <input type="text" id="propEndTime" class="form-control" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Venue</label>
                                <input type="text" id="propLocation" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="border-bottom pb-2">Event Details</h6>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea id="propDescription" class="form-control" rows="3" readonly></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Target Participants</label>
                                <input type="text" id="propAttendees" class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Event Type</label>
                                <input type="text" id="propEventType" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="border-bottom pb-2">Resource Requirements</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Budget Estimate (₱)</label>
                                <input type="text" id="propBudget" class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Staff Required</label>
                                <input type="text" id="propStaff" class="form-control" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Equipment Needed</label>
                                <textarea id="propEquipment" class="form-control" rows="2" readonly></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="border-bottom pb-2">Expected Outcomes</h6>
                        <div class="mb-3">
                            <label class="form-label">Objectives</label>
                            <textarea id="propObjectives" class="form-control" rows="3" readonly></textarea>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="border-bottom pb-2">Additional Information</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Proposed By</label>
                                <input type="text" id="propProposer" class="form-control" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Partners/Sponsors</label>
                                <input type="text" id="propPartners" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <div class="alert" id="propStatusAlert">
                            <strong id="propStatus">Pending Review</strong>
                            <br>
                            <small id="propCreatedDate">Submitted on: -</small>
                        </div>
                    </div>

                    <div id="reviewInfo" class="d-none mb-3">
                        <label class="form-label">Review Information</label>
                        <div class="alert alert-info">
                            <strong>Reviewed by:</strong> <span id="propReviewer">-</span>
                            <br>
                            <small id="propReviewDate">Reviewed on: -</small>
                        </div>
                    </div>

                    <div id="passdateWarning" class="d-none mb-3">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> <strong>Warning:</strong> The proposed date has
                            passed. Please update the date before approval.
                        </div>
                        <label class="form-label">New Proposed Date</label>
                        <input type="date" id="newProposalDate" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning" id="updateProposalDateBtn" style="display: none;"
                        onclick="updateProposalDate()">Update Date</button>
                    <button type="button" class="btn btn-danger" id="deleteProposalBtn" style="display: none;"
                        onclick="deleteProposal(currentProposalId)">Delete Proposal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create New Event Proposal Modal -->
    <div class="modal fade" id="newEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Event Proposal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="eventProposalForm">
                        <!-- Basic Information -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Basic Information</h6>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Event Title</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Proposed Date</label>
                                    <input type="date" name="proposedDate" class="form-control" required>
                                    <small class="text-muted">Date must be in the future (after today)</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="startTime" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="endTime" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Venue</label>
                                    <input type="text" name="venue" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <!-- Event Details -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Event Details</h6>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" required
                                    placeholder="Provide a detailed description of the event..."></textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Target Participants</label>
                                    <input type="number" name="targetParticipants" class="form-control" required
                                        placeholder="Number of participants">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Event Type</label>
                                    <select name="eventType" id="eventTypeSelect" class="form-select" required>
                                        <option value="">Select event category</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Resource Requirements -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Resource Requirements</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Budget Estimate (₱)</label>
                                    <input type="number" name="budgetEstimate" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Staff Required</label>
                                    <input type="number" name="staffRequired" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Equipment Needed</label>
                                    <textarea name="equipmentNeeded" class="form-control" rows="2" required
                                        placeholder="List all required equipment..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Expected Outcomes -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Expected Outcomes</h6>
                            <div class="mb-3">
                                <label class="form-label">Objectives</label>
                                <textarea name="objectives" class="form-control" rows="3" required
                                    placeholder="List the main objectives of the event..."></textarea>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Additional Information</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Partners/Sponsors</label>
                                    <input type="text" name="partnersSponsor" class="form-control"
                                        placeholder="List potential partners or sponsors (optional)">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitProposal()">Submit Proposal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Event Modal (moved outside the form) -->
    <div class="modal fade" id="viewEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="eventForm">
                        <!-- All the event form fields go here -->
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveEventBtn" onclick="saveEventChanges()">Save
                        Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Management Modal (View & Edit) -->
    <div class="modal fade" id="editEventManagementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="eventManagementForm">
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Event Information</h6>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Event Name</label>
                                    <input type="text" id="emEventName" class="form-control" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <div id="emEventStatus" class="form-control"
                                        style="background-color: #f8f9fa; padding: 10px;">Ongoing</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Date & Time (Editable)</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Event Date</label>
                                    <input type="date" id="emEventDate" class="form-control" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" id="emEventStartTime" class="form-control" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">End Time</label>
                                    <input type="time" id="emEventEndTime" class="form-control" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Location & Capacity (Editable)</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Location</label>
                                    <input type="text" id="emEventLocation" class="form-control" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Capacity</label>
                                    <input type="number" id="emEventCapacity" class="form-control" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Staff Required</label>
                                    <input type="number" id="emEventStaffRequired" class="form-control" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Event Details (Read-Only)</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Event Type</label>
                                    <input type="text" id="emEventType" class="form-control" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Budget</label>
                                    <input type="text" id="emEventBudget" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Description</label>
                                <textarea id="emEventDescription" class="form-control" rows="3" readonly></textarea>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Equipment Needed</label>
                                <textarea id="emEventEquipment" class="form-control" rows="2" readonly></textarea>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Objectives</label>
                                <textarea id="emEventObjectives" class="form-control" rows="2" readonly></textarea>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Additional Information (Read-Only)</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Partners/Sponsors</label>
                                    <input type="text" id="emEventPartners" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning" id="emEditEventBtn" onclick="toggleEmEditMode()">Edit
                        Event</button>
                    <button type="button" class="btn btn-primary" id="emSaveEventBtn" onclick="saveEmEventChanges()"
                        style="display: none;">Save Changes</button>
                    <button type="button" class="btn btn-secondary" id="emCancelEditBtn" onclick="toggleEmEditMode()"
                        style="display: none;">Cancel</button>
                    <button type="button" class="btn btn-danger" id="emDeleteEventBtn" onclick="deleteEventFromModal()"
                        style="display: none;">Delete Event</button>
                </div>
            </div>
        </div>
    </div>

    <!-- New Hero Section Modal -->
    <div class="modal fade" id="newHeroModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="heroModalTitle">Add New Hero Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="heroForm">
                        <input type="hidden" id="heroId">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" id="heroTitle" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea id="heroDescription" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="heroSetAsActive">
                            <label class="form-check-label" for="heroSetAsActive">
                                Set as Active Hero Section
                                <small class="text-muted d-block">(Only 1 hero section can be active at a time)</small>
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveHero()">Save Hero Section</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="memberBenefitModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="memberBenefitModalTitle">Add Benefit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="memberBenefitForm">
                        <input type="hidden" id="benefitId" value="">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" id="benefitTitle" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea id="benefitDescription" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="benefitIsActive">
                            <label class="form-check-label">Set as active</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveMemberBenefit()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Icon Picker Modal for Member Benefits -->
    <div class="modal fade" id="benefitIconPickerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Icon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" id="benefitIconSearch" class="form-control" placeholder="Search icons...">
                    </div>
                    <div id="benefitIconGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(50px, 1fr)); gap: 8px; max-height: 400px; overflow-y: auto;">
                        <!-- Icons will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Initiative Modal -->
    <div class="modal fade" id="newInitiativeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="initiativeModalTitle">Add New Initiative</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="initiativeForm">
                        <input type="hidden" id="initiativeId">
                        <input type="hidden" id="initiativeImagePaths" value="[]">
                        <input type="hidden" id="initiativeExistingImages" value="[]">
                        <div class="mb-3">
                            <label class="form-label">Initiative Title</label>
                            <input type="text" id="initiativeTitle" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select id="initiativeCategory" class="form-select" required>
                                <option value="">-- Select Category --</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea id="initiativeDescription" class="form-control" rows="4" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Images (Multiple images supported)</label>
                            <input type="file" id="initiativeImageInput" class="form-control" accept="image/*" multiple>
                            <small class="text-muted d-block mt-2">Supported formats: JPG, PNG, GIF, WebP (Max 5MB
                                each)</small>
                        </div>

                        <div id="initiativeImagePreview" class="row mb-3"></div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="pinToHighlights">
                            <label class="form-check-label" for="pinToHighlights">
                                Pin to Highlights Carousel
                                <small class="text-muted">(Maximum 3 initiatives can be highlighted)</small>
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveInitiative()">Save Initiative</button>
                </div>
            </div>
        </div>
    </div>

    <!-- New Newsletter Modal -->
    <div class="modal fade" id="newNewsletterModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newsletterModalTitle">New Newsletter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newsletterForm">
                        <input type="hidden" id="newsletterId">
                        <input type="hidden" id="newsletterImagePaths" value="[]">
                        <input type="hidden" id="newsletterExistingImages" value="[]">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" id="newsletterTitle" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea id="newsletterContent" class="form-control" rows="6" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Images (Optional - Multiple images supported)</label>
                            <input type="file" id="newsletterImageInput" class="form-control" accept="image/*" multiple>
                            <small class="text-muted d-block mt-2">Supported formats: JPG, PNG, GIF, WebP (Max 5MB
                                each)</small>
                        </div>

                        <div id="newsletterImagePreview" class="row mb-3"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveNewsletter()">Publish</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Mission Modal -->
    <div class="modal fade" id="editMissionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Mission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="missionForm">
                        <input type="hidden" id="missionId">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" id="missionTitleInput" class="form-control"
                                placeholder="e.g., Our Mission" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subtitle</label>
                            <input type="text" id="missionSubtitleInput" class="form-control"
                                placeholder="Brief description or tagline" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mission Points</label>
                            <div id="missionBulletsContainer">
                                <!-- Bullet points will be dynamically added here -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                                onclick="addMissionBullet()">
                                <i class="bi bi-plus-circle"></i> Add Point
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveMission()">Save Mission</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Value Modal -->
    <div class="modal fade" id="newValueModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newValueModalLabel">Add Value</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="valueForm">
                        <input type="hidden" id="valueId" />
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" id="valueTitle" class="form-control" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea id="valueDescription" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Icon (Bootstrap Icons)</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text" id="valueIconPreview">
                                    <i class="bi-square"></i>
                                </span>
                                <input type="text" id="valueIcon" class="form-control" placeholder="Selected icon class"
                                    readonly />
                            </div>
                            <input type="text" id="valueIconSearch" class="form-control"
                                placeholder="Search icons (e.g. heart, star, settings)..." />
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-block">Available Icons</label>
                            <div id="valueIconGrid"
                                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 10px; max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 4px;">
                                <!-- Icons will be loaded here -->
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Display Order</label>
                                <input type="number" id="valueOrder" class="form-control" value="1" min="1" />
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="valueActive" checked />
                                    <label class="form-check-label" for="valueActive">Active</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveValue()">Save Value</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="editVisionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Vision</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="visionForm">
                        <input type="hidden" id="visionId">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" id="visionTitleInput" class="form-control" placeholder="e.g., Our Vision"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subtitle</label>
                            <input type="text" id="visionSubtitleInput" class="form-control"
                                placeholder="Brief description or tagline" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vision Points</label>
                            <div id="visionBulletsContainer">
                                <!-- Bullet points will be dynamically added here -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                                onclick="addVisionBullet()">
                                <i class="bi bi-plus-circle"></i> Add Point
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveVision()">Save Vision</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Event Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="feedbackForm">
                        <!-- Overall Rating -->
                        <div class="mb-3">
                            <label class="form-label">Overall Rating</label>
                            <div class="rating">
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="rating" value="1" id="rating1">
                                    <label class="btn btn-outline-warning" for="rating1">1</label>
                                    <input type="radio" class="btn-check" name="rating" value="2" id="rating2">
                                    <label class="btn btn-outline-warning" for="rating2">2</label>
                                    <input type="radio" class="btn-check" name="rating" value="3" id="rating3">
                                    <label class="btn btn-outline-warning" for="rating3">3</label>
                                    <input type="radio" class="btn-check" name="rating" value="4" id="rating4">
                                    <label class="btn btn-outline-warning" for="rating4">4</label>
                                    <input type="radio" class="btn-check" name="rating" value="5" id="rating5">
                                    <label class="btn btn-outline-warning" for="rating5">5</label>
                                </div>
                            </div>
                        </div>

                        <!-- Impact Assessment -->
                        <div class="mb-3">
                            <label class="form-label">How would you rate the impact of this event?</label>
                            <select class="form-select" required>
                                <option value="">Select impact level</option>
                                <option value="5">Very High Impact</option>
                                <option value="4">High Impact</option>
                                <option value="3">Moderate Impact</option>
                                <option value="2">Low Impact</option>
                                <option value="1">Very Low Impact</option>
                            </select>
                        </div>

                        <!-- Knowledge Gained -->
                        <div class="mb-3">
                            <label class="form-label">Knowledge/Skills Gained</label>
                            <select class="form-select" required>
                                <option value="">Select level</option>
                                <option value="5">Excellent</option>
                                <option value="4">Very Good</option>
                                <option value="3">Good</option>
                                <option value="2">Fair</option>
                                <option value="1">Poor</option>
                            </select>
                        </div>

                        <!-- Comments -->
                        <div class="mb-3">
                            <label class="form-label">Additional Comments</label>
                            <textarea class="form-control" rows="3" placeholder="Share your thoughts..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitFeedback()">Submit Feedback</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Management Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="categoryForm">
                        <input type="hidden" id="categoryId" value="">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <input type="text" class="form-control" id="categoryName" placeholder="e.g., Education"
                                required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveCategory()">Save Category</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newTeamMemberModal" tabindex="-1" aria-hidden="true" style="display: none;">
        <!-- This modal has been removed and team management is now automatically displayed in about.html -->
    </div>

        <footer class="site-footer mt-auto">
            <div class="footer-top py-5">
                <div class="container">
                    <div class="row align-items-start gy-4 justify-content-between">
                        <div class="col-12 col-md-5 d-flex align-items-center gap-3">
                            <a class="footer-logo-wrapper d-flex align-items-center justify-content-center"
                                href="index.html">
                                <img src="assets/image/reboot-logo.png" alt="Reboot PH" class="img-fluid">
                            </a>
                            <div class="text-white small">
                                <h4 class="h6 fw-bold mb-2">Reboot PH</h4>
                                <p class="mb-1">2804, Discovery Centre, 25 ADB Ave, Ortigas Center</p>
                                <p class="mb-0">Pasig, Philippines</p>
                            </div>
                        </div>

                        <div class="col-12 col-md-4 text-white small text-md-end">
                            <h4 class="h6 fw-bold mb-3">Contact Info</h4>
                            <p class="mb-1">rebootphinstitute@gmail.com</p>
                            <p class="mb-3">info@reboot-philippines.org</p>
                            <div class="d-flex justify-content-start justify-content-md-end gap-3">
                                <a id="footerFacebookLink" href="https://www.facebook.com/rebootphilippines"
                                    class="footer-social-icon" aria-label="Facebook" target="_blank"
                                    rel="noopener noreferrer">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                                            fill="currentColor" />
                                    </svg>
                                </a>
                                <a id="footerInstagramLink" href="https://www.instagram.com/rebootphinstitute/"
                                    class="footer-social-icon" aria-label="Instagram" target="_blank"
                                    rel="noopener noreferrer">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"
                                            fill="currentColor" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom py-3">
                <div class="container text-center text-white-50 small">
                    &copy; 2024 Reboot PH. All rights reserved.
                </div>
            </div>
        </footer>

        <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/member-dashboard-shared.js"></script>
        <script>
            (async function () {
                try {
                    const res = await fetch('api/manage-system-settings.php');
                    const data = await res.json();
                    if (!data.success) return;
                    const s = data.settings || {};
                    const email = s.social_email || 'rebootphinstitute@gmail.com';
                    const fb = s.social_facebook || 'https://www.facebook.com/rebootphilippines';
                    const ig = s.social_instagram || 'https://www.instagram.com/rebootphinstitute/';
                    const li = s.social_linkedin || 'https://www.linkedin.com/company/reboot-philippines/';
                    const emailEl = document.getElementById('footerEmailLink');
                    const fbEl = document.getElementById('footerFacebookLink');
                    const igEl = document.getElementById('footerInstagramLink');
                    const liEl = document.getElementById('footerLinkedInLink');
                    if (emailEl) emailEl.href = 'mailto:' + email;
                    if (fbEl) fbEl.href = fb;
                    if (igEl) igEl.href = ig;
                    if (liEl) liEl.href = li;
                } catch (e) { console.error('social links load error', e); }
            })();
        </script>
        <script>

            // Get current user's role from PHP session
            window.currentUserRole = '<?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : ''; ?>';
            window.isAdminView = true;
            window.adminId = <?php echo isset($_SESSION['memberID']) ? json_encode($_SESSION['memberID']) : 'null'; ?>; // Admin's own member ID
            window.currentMemberId = null; // Will be set when viewing member profiles
            console.log('Initialized: isAdminView=' + window.isAdminView + ', adminId=' + window.adminId + ', currentUserRole=' + window.currentUserRole);

            // Navigate to members management section
            function navigateToMembers() {
                // Click the members link in the sidebar
                const membersLink = document.querySelector('a[href="#members"]');
                if (membersLink) {
                    membersLink.click();
                } else {
                    alert('Members management section not found');
                }
            }

            let currentProposalId = null;

            document.addEventListener('DOMContentLoaded', function () {
                // Navigation functionality
                const navLinks = document.querySelectorAll('.list-group-item');
                const sections = document.querySelectorAll('.active-section');

                navLinks.forEach(link => {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        navLinks.forEach(l => l.classList.remove('active'));
                        this.classList.add('active');

                        sections.forEach(section => section.classList.add('d-none'));
                        const targetId = this.getAttribute('href').substring(1);
                        document.getElementById(targetId)?.classList.remove('d-none');
                    });
                });

                // Role change handler - only for member role dropdowns
                document.addEventListener('change', function (e) {
                    // Only trigger on role-specific selects (those with data-role-select attribute)
                    if (e.target.hasAttribute('data-role-select')) {
                        if (confirm('Are you sure you want to change this member\'s role?')) {
                            // Handle role change
                            alert('Role updated successfully');
                        } else {
                            // Reset to previous value
                            e.target.value = e.target.defaultValue;
                        }
                    }
                });
            });

            // ===== ADMIN SETTINGS MANAGEMENT =====

            // Load system settings
            async function loadSystemSettings() {
                try {
                    const response = await fetch('api/manage-system-settings.php', {
                        credentials: 'include'
                    });
                    const data = await response.json();
                    console.log('Settings loaded:', data); // Debug log

                    if (data.success && data.settings) {
                        const settings = data.settings;

                        // Update UI elements with loaded settings
                        const enableRegCheckbox = document.getElementById('enableRegistration');
                        const autoApproveCheckbox = document.getElementById('autoApprove');
                        const maxMembersInput = document.getElementById('maxMembers');

                        console.log('Enable Reg Checkbox:', enableRegCheckbox, 'Value:', settings.enable_member_registration);
                        console.log('Auto Approve Checkbox:', autoApproveCheckbox, 'Value:', settings.auto_approve_members);

                        if (enableRegCheckbox) {
                            enableRegCheckbox.checked = settings.enable_member_registration == true || settings.enable_member_registration == '1' || settings.enable_member_registration == 'true';
                        }

                        if (autoApproveCheckbox) {
                            autoApproveCheckbox.checked = settings.auto_approve_members == true || settings.auto_approve_members == '1' || settings.auto_approve_members == 'true';
                        }

                        if (maxMembersInput && settings.max_auto_approved_members !== undefined) {
                            maxMembersInput.value = settings.max_auto_approved_members;
                        }
                    } else {
                        console.error('Settings response error:', data.message);
                    }
                } catch (error) {
                    console.error('Error loading system settings:', error);
                }
            }

            // Save individual setting
            async function saveSetting(settingKey, settingValue, dataType = 'string') {
                try {
                    const response = await fetch('api/manage-system-settings.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'include',
                        body: JSON.stringify({
                            action: 'update',
                            settingKey: settingKey,
                            settingValue: settingValue,
                            dataType: dataType
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        console.log(`Setting ${settingKey} saved successfully`);
                        return true;
                    } else {
                        console.error('Error saving setting:', result.message);
                        alert('Error saving setting: ' + result.message);
                        return false;
                    }
                } catch (error) {
                    console.error('Error saving setting:', error);
                    alert('Error saving setting');
                    return false;
                }
            }

            // Handle enable registration toggle
            async function handleEnableRegistrationChange(checkbox) {
                const value = checkbox.checked ? '1' : '0';
                await saveSetting('enable_member_registration', value, 'boolean');
            }

            // Handle auto-approve toggle
            async function handleAutoApproveChange(checkbox) {
                const value = checkbox.checked ? '1' : '0';
                await saveSetting('auto_approve_members', value, 'boolean');
            }

            // Handle max members change
            async function handleMaxMembersChange(input) {
                const value = input.value.trim();

                if (!value || isNaN(value) || parseInt(value) < 1) {
                    alert('Please enter a valid number greater than 0');
                    input.focus();
                    return;
                }

                await saveSetting('max_auto_approved_members', value, 'integer');
            }

            // Initialize settings listeners on page load
            document.addEventListener('DOMContentLoaded', function () {
                // Load current settings
                loadSystemSettings();

                // Add event listeners to settings controls
                const enableRegCheckbox = document.getElementById('enableRegistration');
                const autoApproveCheckbox = document.getElementById('autoApprove');
                const maxMembersInput = document.getElementById('maxMembers');

                if (enableRegCheckbox) {
                    enableRegCheckbox.addEventListener('change', function (e) {
                        handleEnableRegistrationChange(e.target);
                    });
                }

                if (autoApproveCheckbox) {
                    autoApproveCheckbox.addEventListener('change', function (e) {
                        handleAutoApproveChange(e.target);
                    });
                }

                if (maxMembersInput) {
                    maxMembersInput.addEventListener('change', function (e) {
                        handleMaxMembersChange(e.target);
                    });
                }
            });

            function generateSerialNumber() {
                const prefix = 'RPH';
                const timestamp = new Date().getTime().toString().slice(-6);
                const random = Math.random().toString(36).substring(2, 6).toUpperCase();
                return `${prefix}-${timestamp}-${random}`;
            }

            function copySerialNumber(serialNumber) {
                navigator.clipboard.writeText(serialNumber).then(() => {
                    const copyBtn = document.querySelector('#serialModal .btn-primary');
                    copyBtn.textContent = 'Copied!';
                    setTimeout(() => {
                        copyBtn.textContent = 'Copy Serial Number';
                    }, 2000);
                });
            }

            function downloadQR() {
                const canvas = document.querySelector("#qrcode canvas");
                const image = canvas.toDataURL("image/png");
                const link = document.createElement('a');
                link.download = `reboot-ph-${Date.now()}.png`;
                link.href = image;
                link.click();
            }

            function showAttendanceForm(activityTitle, activityDate) {
                const modal = new bootstrap.Modal(document.getElementById('attendanceModal'));
                document.getElementById('modalActivityTitle').textContent = activityTitle;
                document.getElementById('modalActivityDate').textContent = new Date(activityDate).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                document.getElementById('serialNumber').value = '';
                modal.show();
                
                // Start QR scanner after modal is shown
                setTimeout(() => {
                    startQRScannerForAttendance();
                }, 500);
            }

            // Open attendance modal with QR scanner
            function openAttendanceModal(eventId, eventTitle, eventDate, startTime) {
                // Set modal content
                document.getElementById('modalActivityTitle').textContent = eventTitle;
                document.getElementById('modalActivityDate').textContent = `${new Date(eventDate).toLocaleDateString()} at ${startTime}`;
                
                // Store event ID for verification
                window.currentEventId = eventId;
                
                // Clear previous serial number input
                document.getElementById('serialNumber').value = '';
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('attendanceModal'));
                modal.show();
                
                // Start QR code scanner after modal is shown
                setTimeout(() => {
                    startQRScannerForAttendance(eventId);
                }, 500);
            }

            // Start QR code scanner for attendance
            function startQRScannerForAttendance(eventId) {
                try {
                    // Check if scanner is already running
                    if (window.html5QrcodeScanner) {
                        window.html5QrcodeScanner.clear();
                    }
                    
                    const scannerElement = document.getElementById('qr-reader');
                    if (!scannerElement) {
                        console.warn('QR reader element not found, creating it');
                        const modalBody = document.querySelector('#attendanceModal .modal-body');
                        if (modalBody) {
                            const readerDiv = document.createElement('div');
                            readerDiv.id = 'qr-reader';
                            readerDiv.style.width = '100%';
                            readerDiv.style.height = '300px';
                            readerDiv.style.marginBottom = '10px';
                            modalBody.insertBefore(readerDiv, modalBody.firstChild);
                        }
                    }
                    
                    window.html5QrcodeScanner = new Html5QrcodeScanner(
                        'qr-reader',
                        { fps: 10, qrbox: { width: 250, height: 250 } },
                        false
                    );
                    
                    window.html5QrcodeScanner.render(
                        (decodedText) => {
                            // QR code scanned successfully - stop scanner immediately
                            if (window.html5QrcodeScanner) {
                                window.html5QrcodeScanner.clear();
                                window.html5QrcodeScanner = null;
                            }
                            document.getElementById('serialNumber').value = decodedText;
                            console.log('QR Code Scanned:', decodedText);
                            // Auto-verify attendance after scanning
                            setTimeout(() => {
                                verifyAttendance();
                            }, 500);
                        },
                        (error) => {
                            // Ignore errors during scanning
                            console.debug('QR scan error:', error);
                        }
                    );
                } catch (error) {
                    console.error('Error starting QR scanner:', error);
                    alert('Could not start QR code scanner. You can enter the serial number manually instead.');
                }
            }

            // Stop QR code scanner
            function stopQRScanner() {
                try {
                    if (window.html5QrcodeScanner) {
                        window.html5QrcodeScanner.clear();
                        window.html5QrcodeScanner = null;
                    }
                } catch (error) {
                    console.error('Error stopping QR scanner:', error);
                }
            }

            // Verify attendance
            async function verifyAttendance() {
                let serialNumber = document.getElementById('serialNumber').value.trim();
                const memberId = window.currentMemberId;

                if (!serialNumber) {
                    alert('Please scan a QR code or enter event details');
                    return;
                }

                if (!memberId) {
                    alert('Error: Member ID not found. Please select a member first.');
                    return;
                }

                try {
                    // Parse event QR code (JSON format)
                    let eventId;
                    let qrData;
                    try {
                        qrData = JSON.parse(serialNumber);
                        eventId = qrData.eventId;
                    } catch (e) {
                        // If not JSON, show error
                        const modalBody = document.querySelector('#attendanceModal .modal-body');
                        const qrReader = document.getElementById('qr-reader');
                        if (qrReader) {
                            qrReader.style.display = 'none';
                        }
                        const verifyBtn = document.querySelector('#attendanceModal .modal-footer .btn-primary');
                        if (verifyBtn) {
                            verifyBtn.style.display = 'none';
                        }
                        
                        modalBody.innerHTML = `
                        <div class="text-center mb-4">
                            <div class="display-1 text-danger">
                                <i class="bi bi-x-circle"></i>
                            </div>
                        </div>
                        <div class="alert alert-danger">
                            <h6 class="alert-heading">Invalid QR Code</h6>
                            <p class="mb-0">Please scan a valid event QR code.</p>
                        </div>`;
                        
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('attendanceModal'));
                            if (modal) {
                                modal.hide();
                            }
                        }, 3000);
                        return;
                    }

                    if (!eventId) {
                        alert('Error: Event ID not found in QR code.');
                        return;
                    }

                    console.log('Attendance verification:', { eventId, memberId, scannedQR: qrData });

                    // Look up the registration for the selected member for this event
                    const lookupResponse = await fetch('api/manage-attendance.php?action=getEventAttendance', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `eventId=${encodeURIComponent(eventId)}&memberId=${encodeURIComponent(memberId)}`
                    });

                    const lookupData = await lookupResponse.json();
                    console.log('Event registration lookup:', { eventId, memberId, response: lookupData });

                    if (!lookupData.success || !lookupData.data || lookupData.data.length === 0) {
                        // Show error message - member not registered
                        const modalBody = document.querySelector('#attendanceModal .modal-body');
                        const qrReader = document.getElementById('qr-reader');
                        if (qrReader) {
                            qrReader.style.display = 'none';
                        }
                        const verifyBtn = document.querySelector('#attendanceModal .modal-footer .btn-primary');
                        if (verifyBtn) {
                            verifyBtn.style.display = 'none';
                        }
                        
                        modalBody.innerHTML = `
                        <div class="text-center mb-4">
                            <div class="display-1 text-danger">
                                <i class="bi bi-x-circle"></i>
                            </div>
                        </div>
                        <div class="alert alert-danger">
                            <h6 class="alert-heading">Not Registered</h6>
                            <p class="mb-0">You are not registered for this event.</p>
                        </div>`;
                        
                        // Close modal after 3 seconds
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('attendanceModal'));
                            if (modal) {
                                modal.hide();
                            }
                        }, 3000);
                        return;
                    }

                    // Get registration details
                    const registration = lookupData.data[0];
                    const registrationId = registration.RegistrationID;

                    // Check if already attended
                    if (registration.AttendanceID) {
                        // Show already attended message
                        const modalBody = document.querySelector('#attendanceModal .modal-body');
                        const qrReader = document.getElementById('qr-reader');
                        if (qrReader) {
                            qrReader.style.display = 'none';
                        }
                        const verifyBtn = document.querySelector('#attendanceModal .modal-footer .btn-primary');
                        if (verifyBtn) {
                            verifyBtn.style.display = 'none';
                        }
                        
                        modalBody.innerHTML = `
                        <div class="text-center mb-4">
                            <div class="display-1 text-warning">
                                <i class="bi bi-exclamation-circle-fill"></i>
                            </div>
                        </div>
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">Already Attended</h6>
                            <p class="mb-0">You have already checked in at ${new Date(registration.AttendanceTime).toLocaleTimeString()}</p>
                        </div>`;
                        
                        // Close modal after 3 seconds
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('attendanceModal'));
                            if (modal) {
                                modal.hide();
                            }
                        }, 3000);
                        return;
                    }

                    // Record the attendance
                    const response = await fetch('api/manage-attendance.php?action=recordAttendance', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `registrationId=${encodeURIComponent(registrationId)}&memberId=${encodeURIComponent(memberId)}&eventId=${encodeURIComponent(eventId)}&scanType=QR`
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Show success message
                        const serialInput = document.getElementById('serialNumber');
                        serialInput.value = '';
                        
                        // Hide the scanner and button, show success message
                        const qrReader = document.getElementById('qr-reader');
                        if (qrReader) {
                            qrReader.style.display = 'none';
                        }
                        const verifyBtn = document.querySelector('#attendanceModal .modal-footer .btn-primary');
                        if (verifyBtn) {
                            verifyBtn.style.display = 'none';
                        }
                        
                        // Update modal body with success message
                        const modalBody = document.querySelector('#attendanceModal .modal-body');
                        modalBody.innerHTML = `
                        <div class="text-center mb-4">
                            <div class="display-1 text-success">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </div>
                        <div class="alert alert-success">
                            <h6 class="alert-heading">Attendance Marked Successfully!</h6>
                            <hr>
                            <p class="mb-0">Checked in at ${new Date().toLocaleTimeString()}</p>
                        </div>`;
                        
                        // Close modal after 2 seconds
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('attendanceModal'));
                            if (modal) {
                                modal.hide();
                            }
                            // Reload registered events to update the UI
                            loadRegisteredEventsTab();
                        }, 2000);
                    } else {
                        // Show error message
                        const modalBody = document.querySelector('#attendanceModal .modal-body');
                        const qrReader = document.getElementById('qr-reader');
                        if (qrReader) {
                            qrReader.style.display = 'none';
                        }
                        const verifyBtn = document.querySelector('#attendanceModal .modal-footer .btn-primary');
                        if (verifyBtn) {
                            verifyBtn.style.display = 'none';
                        }
                        
                        modalBody.innerHTML = `
                        <div class="text-center mb-4">
                            <div class="display-1 text-danger">
                                <i class="bi bi-x-circle"></i>
                            </div>
                        </div>
                        <div class="alert alert-danger">
                            <h6 class="alert-heading">Error Recording Attendance</h6>
                            <p class="mb-0">${data.message || 'Failed to mark attendance. Please try again.'}</p>
                        </div>`;
                        
                        // Close modal after 3 seconds
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('attendanceModal'));
                            if (modal) {
                                modal.hide();
                            }
                        }, 3000);
                    }
                } catch (error) {
                    console.error('Error verifying attendance:', error);
                    
                    // Show error message
                    const modalBody = document.querySelector('#attendanceModal .modal-body');
                    const qrReader = document.getElementById('qr-reader');
                    if (qrReader) {
                        qrReader.style.display = 'none';
                    }
                    const verifyBtn = document.querySelector('#attendanceModal .modal-footer .btn-primary');
                    if (verifyBtn) {
                        verifyBtn.style.display = 'none';
                    }
                    
                    modalBody.innerHTML = `
                    <div class="text-center mb-4">
                        <div class="display-1 text-danger">
                            <i class="bi bi-x-circle"></i>
                        </div>
                    </div>
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">Connection Error</h6>
                        <p class="mb-0">${error.message}</p>
                    </div>`;
                    
                    // Close modal after 3 seconds
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('attendanceModal'));
                        if (modal) {
                            modal.hide();
                        }
                    }, 3000);
                }
            }

            function downloadID() {
                const idElement = document.getElementById('idPreview');
                html2canvas(idElement).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const link = document.createElement('a');
                    link.download = 'reboot-ph-member-id.png';
                    link.href = imgData;
                    link.click();
                });
            }

            function switchView(view) {
                if (view === 'admin') {
                    window.isAdminView = true;
                    document.body.classList.add('admin-view');
                } else {
                    window.isAdminView = false;
                    document.body.classList.remove('admin-view');
                    // Load member profile when switching to member view
                    loadMemberProfile();
                }
                // Update buttons
                const buttons = document.querySelectorAll('.btn-group .btn');
                buttons.forEach(btn => btn.classList.remove('active'));
                event.target.classList.add('active');

                // Update navigation
                document.getElementById('adminNav').classList.toggle('d-none', view !== 'admin');
                document.getElementById('memberNav').classList.toggle('d-none', view !== 'member');

                // Update content sections
                document.querySelectorAll('.admin-section').forEach(section => {
                    section.classList.toggle('d-none', view !== 'admin');
                });
                document.querySelectorAll('.member-section').forEach(section => {
                    section.classList.toggle('d-none', view !== 'member');
                });

                // Reset active states
                if (view === 'admin') {
                    document.querySelector('#adminNav .list-group-item').click();
                } else {
                    document.querySelector('#memberNav .list-group-item').click();
                }
            }

            // Navigation handler for Admin View
            document.addEventListener('DOMContentLoaded', function () {
                const adminNavLinks = document.querySelectorAll('#adminNav .list-group-item');

                adminNavLinks.forEach(link => {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();

                        adminNavLinks.forEach(l => l.classList.remove('active'));
                        this.classList.add('active');

                        document.querySelectorAll('.admin-section').forEach(section => {
                            section.classList.add('d-none');
                        });

                        const targetId = this.getAttribute('href').substring(1);
                        const targetSection = document.getElementById(targetId);

                        if (targetSection) {
                            targetSection.classList.remove('d-none');
                        }
                    });
                });
            });

            // Member View Navigation Handler
            document.addEventListener('DOMContentLoaded', function () {
                const memberNavLinks = document.querySelectorAll('#memberNav .list-group-item');
                memberNavLinks.forEach(link => {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();

                        memberNavLinks.forEach(l => l.classList.remove('active'));
                        this.classList.add('active');

                        document.querySelectorAll('.member-section').forEach(section => {
                            section.classList.add('d-none');
                        });

                        const targetId = this.getAttribute('href').substring(1);
                        const targetSection = document.getElementById(targetId);

                        if (targetSection) {
                            targetSection.classList.remove('d-none');

                            if (targetId === 'activities') {
                                loadMemberProfile().then(() => {
                                    setTimeout(() => {
                                        const memberId = window.currentMemberId;
                                        loadUpcomingEventsTab(memberId);
                                        loadRegisteredEventsTab(memberId);
                                        loadCompletedEventsTab(memberId);
                                    }, 5);
                                });
                            } else if (targetId === 'history') {
                                loadMemberProfile().then(() => {
                                    const memberId = window.currentMemberId;
                                    loadActivityHistory(memberId);
                                });
                            } else if (targetId === 'member-dashboard') {
                                // Load dashboard data for member view
                                setTimeout(() => {
                                    // Load stats and events together
                                    loadDashboardStats();
                                    loadMemberDashboardEventsOptimized();
                                }, 100);
                            } else if (targetId === 'profile') {
                                // Load full profile for Member View
                                if (window.currentMemberId) {
                                    loadProfileDetails(window.currentMemberId);
                                } else {
                                    console.warn('currentMemberId not set, attempting to fetch from session');
                                    // Fallback: load from API first
                                    loadMemberProfile().then(() => {
                                        if (window.currentMemberId) {
                                            loadProfileDetails(window.currentMemberId);
                                        }
                                    });
                                }
                            }
                        }
                    });
                });
            });

            // Handle My Activities tab switching
            document.addEventListener('DOMContentLoaded', function () {
                const upcomingTab = document.getElementById('upcoming-tab');
                const registeredTab = document.getElementById('registered-tab');
                const completedTab = document.getElementById('completed-tab');

                if (upcomingTab) {
                    upcomingTab.addEventListener('shown.bs.tab', function () {
                        loadUpcomingEventsTab();
                    });
                }

                if (registeredTab) {
                    registeredTab.addEventListener('shown.bs.tab', function () {
                        loadRegisteredEventsTab();
                    });
                }

                if (completedTab) {
                    completedTab.addEventListener('shown.bs.tab', function () {
                        loadCompletedEventsTab();
                    });
                }
            });

            function registerForEvent(eventName, eventId) {
                const serialNumber = generateSerialNumber();

                const modalHtml = `
            <div class="modal fade" id="registrationModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Registration Successful</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <div class="display-1 text-success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                            </div>
                            <p>You have successfully registered for:</p>
                            <p class="fw-bold">${eventName}</p>
                            <div class="alert alert-info">
                                <p class="mb-1">Your Registration Number:</p>
                                <h4 class="text-center mb-0">${serialNumber}</h4>
                            </div>
                            <div id="qrcode" class="text-center mt-3"></div>
                            <p class="text-muted small mt-3">Please keep this registration number and QR code. You will need them to verify your attendance at the event.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="copySerialNumber('${serialNumber}')">Copy Number</button>
                            <button type="button" class="btn btn-success" onclick="downloadQR()">Download QR</button>
                        </div>
                    </div>
                </div>
            </div>`;

                // Remove existing modal if any
                const existingModal = document.getElementById('registrationModal');
                if (existingModal) {
                    existingModal.remove();
                }

                // Add new modal to document
                document.body.insertAdjacentHTML('beforeend', modalHtml);

                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('registrationModal'));
                modal.show();

                // Generate QR code
                new QRCode(document.getElementById("qrcode"), {
                    text: JSON.stringify({
                        eventId: eventId,
                        serialNumber: serialNumber,
                        eventName: eventName,
                        registeredAt: new Date().toISOString()
                    }),
                    width: 128,
                    height: 128
                });

                // Disable the register button
                event.target.disabled = true;
                event.target.textContent = 'Registered';
                event.target.classList.remove('btn-outline-primary');
                event.target.classList.add('btn-secondary');
            }

            function viewApplication(applicationId) {
                const modal = new bootstrap.Modal(document.getElementById('applicationModal'));
                // Here you would typically fetch the application data using the applicationId
                // and populate the modal fields
                modal.show();
            }

            function viewProposal(proposalId) {
                const modal = new bootstrap.Modal(document.getElementById('viewProposalModal'));
                modal.show();
            }

            // Attendance Management Scripts
            function showAttendanceCheckingForm(eventId, eventName) {
                // Store the current event ID
                document.getElementById('attendanceCheckingForm').dataset.eventId = eventId;

                // Update the event name in the form
                document.getElementById('selectedEventName').textContent = eventName;

                // Show the form
                document.getElementById('attendanceCheckingForm').classList.remove('d-none');

                // Clear any previous input
                document.getElementById('serialNumberInput').value = '';

                // Clear QR scanner if it was active
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.clear();
                    document.getElementById('qr-scanner').classList.add('d-none');
                }

                // Scroll to the form
                document.getElementById('attendanceCheckingForm').scrollIntoView({ behavior: 'smooth' });
            }

            function hideAttendanceCheckingForm() {
                // Hide the form
                document.getElementById('attendanceCheckingForm').classList.add('d-none');

                // Clear QR scanner if it was active
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.clear();
                    document.getElementById('qr-scanner').classList.add('d-none');
                }
            }

            // Update the checkAttendance function
            function checkAttendance(serialNumber = null) {
                const serial = serialNumber || document.getElementById('serialNumberInput').value;
                const eventId = document.getElementById('attendanceCheckingForm').dataset.eventId;

                if (!serial) {
                    alert('Please enter a serial number');
                    return;
                }

                // Mock data with event-specific checks
                const mockData = {
                    'RPH-123456-ABCD': {
                        name: 'John Doe',
                        memberId: 'RPH-2024-0001',
                        eventId: 'ENV-001',
                        eventName: 'Environmental Awareness Workshop',
                        registrationTime: '2025-09-15T08:45:00'
                    }
                };

                const modal = new bootstrap.Modal(document.getElementById('attendanceCheckModal'));
                const resultDiv = document.getElementById('attendanceResult');
                const confirmBtn = document.getElementById('confirmAttendance');

                if (mockData[serial] && mockData[serial].eventId === eventId) {
                    const data = mockData[serial];
                    resultDiv.innerHTML = `
                    <div class="text-center mb-4">
                        <div class="display-1 text-success">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                    </div>
                    <div class="alert alert-success">
                        <h6 class="alert-heading">Member Found!</h6>
                        <hr>
                        <p class="mb-0"><strong>Name:</strong> ${data.name}</p>
                        <p class="mb-0"><strong>Member ID:</strong> ${data.memberId}</p>
                        <p class="mb-0"><strong>Event:</strong> ${data.eventName}</p>
                        <p class="mb-0"><strong>Registration Time:</strong> ${new Date(data.registrationTime).toLocaleString()}</p>
                    </div>`;
                    confirmBtn.classList.remove('d-none');
                } else {
                    resultDiv.innerHTML = `
                    <div class="text-center mb-4">
                        <div class="display-1 text-danger">
                            <i class="bi bi-x-circle"></i>
                        </div>
                    </div>
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">Invalid Registration</h6>
                        <p class="mb-0">The serial number provided is either invalid or not registered for this event.</p>
                    </div>`;
                    confirmBtn.classList.add('d-none');
                }

                modal.show();
            }

            function checkAttendance(serialNumber = null) {
                const serial = serialNumber || document.getElementById('serialNumberInput').value;
                if (!serial) {
                    alert('Please enter a serial number');
                    return;
                }

                // Simulate checking against a database
                const mockData = {
                    'RPH-123456-ABCD': {
                        name: 'John Doe',
                        memberId: 'RPH-2024-0001',
                        eventName: 'Environmental Awareness Workshop',
                        registrationTime: '2025-09-15T08:45:00'
                    }
                };

                const modal = new bootstrap.Modal(document.getElementById('attendanceCheckModal'));
                const resultDiv = document.getElementById('attendanceResult');
                const confirmBtn = document.getElementById('confirmAttendance');

                if (mockData[serial]) {
                    const data = mockData[serial];
                    resultDiv.innerHTML = `
                    <div class="text-center mb-4">
                        <div class="display-1 text-success">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                    </div>
                    <div class="alert alert-success">
                        <h6 class="alert-heading">Member Found!</h6>
                        <hr>
                        <p class="mb-0"><strong>Name:</strong> ${data.name}</p>
                        <p class="mb-0"><strong>Member ID:</strong> ${data.memberId}</p>
                        <p class="mb-0"><strong>Event:</strong> ${data.eventName}</p>
                        <p class="mb-0"><strong>Registration Time:</strong> ${new Date(data.registrationTime).toLocaleString()}</p>
                    </div>`;
                    confirmBtn.classList.remove('d-none');
                } else {
                    resultDiv.innerHTML = `
                    <div class="text-center mb-4">
                        <div class="display-1 text-danger">
                            <i class="bi bi-x-circle"></i>
                        </div>
                    </div>
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">Invalid Serial Number</h6>
                        <p class="mb-0">The serial number provided was not found in the registration list.</p>
                    </div>`;
                    confirmBtn.classList.add('d-none');
                }

                modal.show();
            }

            function openAttendanceCheck(eventId) {
                document.getElementById('serialNumberInput').value = '';
                document.getElementById('serialNumberInput').focus();
            }
            function toggleHighlight(checkbox, initiativeTitle) {
                if (checkbox.checked) {
                    // Count current highlights
                    const highlightedCount = document.querySelectorAll('input[type="checkbox"]:checked').length;
                    if (highlightedCount > 3) {
                        alert('Maximum of 3 initiatives can be highlighted at a time.');
                        checkbox.checked = false;
                        return;
                    }
                    // Show success message
                    alert(`"${initiativeTitle}" pinned to highlights.`);
                } else {
                    // Show unpin message
                    alert(`"${initiativeTitle}" removed from highlights.`);
                }
            }
            // Add to your existing JavaScript
            function requestFeedback(eventId, eventName, attendanceId) {
                // Show feedback modal
                const modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
                document.getElementById('feedbackForm').dataset.eventId = eventId;
                if (attendanceId) {
                    document.getElementById('feedbackForm').dataset.attendanceId = attendanceId;
                }
                modal.show();
            }

            function submitFeedback() {
                const form = document.getElementById('feedbackForm');
                const eventId = form.dataset.eventId;
                const attendanceId = form.dataset.attendanceId;

                // Get ratings
                const overallExpInput = form.querySelector('input[name="rating"]:checked');
                const impactSelect = form.querySelectorAll('select')[0];
                const knowledgeSelect = form.querySelectorAll('select')[1];
                const commentsInput = form.querySelector('textarea');

                // Validate
                if (!overallExpInput) {
                    alert('Please select a rating');
                    return;
                }

                if (!impactSelect.value) {
                    alert('Please select impact level');
                    return;
                }

                if (!knowledgeSelect.value) {
                    alert('Please select knowledge gained');
                    return;
                }

                // Prepare form data
                const formData = {
                    eventId: parseInt(eventId),
                    attendanceId: attendanceId ? parseInt(attendanceId) : null,
                    overallExperience: mapRatingToExperience(overallExpInput.value),
                    impact: parseInt(impactSelect.value),
                    knowledge: parseInt(knowledgeSelect.value),
                    comments: commentsInput.value.trim(),
                    isAnonymous: 0
                };

                console.log('Submitting feedback:', formData);

                // Send to API
                fetch('api/manage-feedback.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('Thank you for your feedback!');
                        bootstrap.Modal.getInstance(document.getElementById('feedbackModal')).hide();
                        // Reset form
                        form.reset();
                        // Reload the page to show updated feedback status
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } else {
                        alert('Error submitting feedback: ' + (result.message || 'Unknown error'));
                        console.error('API error:', result);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error submitting feedback: ' + error.message);
                });
            }

            // Helper function to map numeric rating to experience text
            function mapRatingToExperience(rating) {
                const ratingMap = {
                    '5': 'Excellent',
                    '4': 'Good',
                    '3': 'Average',
                    '2': 'Fair',
                    '1': 'Poor'
                };
                return ratingMap[rating] || 'Average';
            }

            // Function to calculate average ratings
            function calculateAverages(feedbackData) {
                const totals = {
                    rating: 0,
                    impact: 0,
                    knowledge: 0,
                    count: 0
                };

                feedbackData.forEach(feedback => {
                    totals.rating += parseInt(feedback.rating);
                    totals.impact += parseInt(feedback.impact);
                    totals.knowledge += parseInt(feedback.knowledge);
                    totals.count++;
                });

                return {
                    avgRating: (totals.rating / totals.count).toFixed(1),
                    impactScore: ((totals.rating + totals.impact + totals.knowledge) / (3 * totals.count)).toFixed(1)
                };
            }

            // Add to your existing script section
            function clearSystemCache() {
                if (confirm('Are you sure you want to clear the system cache?')) {
                    // Simulate cache clearing
                    setTimeout(() => {
                        alert('System cache cleared successfully');
                    }, 1000);
                }
            }

            function confirmSystemReset() {
                if (confirm('WARNING: This will reset all system settings to default. This action cannot be undone. Continue?')) {
                    // Simulate system reset
                    setTimeout(() => {
                        alert('System reset completed');
                    }, 1500);
                }
            }

            function updateAdminPassword() {
                const form = document.getElementById('adminPasswordForm');
                const passwords = form.querySelectorAll('input[type="password"]');

                if (passwords[1].value !== passwords[2].value) {
                    alert('New passwords do not match');
                    return;
                }

                if (passwords[1].value.length < 8) {
                    alert('Password must be at least 8 characters long');
                    return;
                }

                // Simulate password change
                alert('Password updated successfully');
                bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
                form.reset();
            }

            // Add event listeners for settings changes
            document.addEventListener('DOMContentLoaded', function () {
                const settingsToggles = document.querySelectorAll('.form-check-input');
                settingsToggles.forEach(toggle => {
                    toggle.addEventListener('change', function () {
                        const settingName = this.id;
                        const isEnabled = this.checked;
                        console.log(`${settingName} changed to ${isEnabled}`);
                        // Here you would typically save the setting to your backend
                    });
                });
            });


            // ===== QUESTIONNAIRE MANAGEMENT FUNCTIONS =====

            // Load all questionnaires
            async function loadQuestionnaires() {
                try {
                    const response = await fetch('api/get-questionnaires.php');
                    const data = await response.json();

                    if (data.success) {
                        populateQuestionnairesTable(data.questionnaires);
                        updateQuestionnairesStats(data.questionnaires);
                        displayActiveQuestionnaires(data.questionnaires);
                    } else {
                        console.error('Error loading questionnaires:', data.message);
                    }
                } catch (error) {
                    console.error('Error fetching questionnaires:', error);
                }
            }

            // Populate questionnaires table
            function populateQuestionnairesTable(questionnaires) {
                const tbody = document.getElementById('questionnairesTableBody');

                if (!questionnaires || questionnaires.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No questions available. Create one to get started.</td></tr>';
                    return;
                }

                tbody.innerHTML = questionnaires.map(q => `
                <tr>
                    <td><strong>Q${q.QuestionID}</strong></td>
                    <td>${q.QuestionText.substring(0, 60)}${q.QuestionText.length > 60 ? '...' : ''}</td>
                    <td>
                        <span class="badge ${q.IsActive ? 'bg-success' : 'bg-secondary'}">
                            ${q.IsActive ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>${new Date(q.CreatedDate).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewQuestion(${q.QuestionID})">View</button>
                        <button class="btn btn-sm btn-outline-warning" onclick="editQuestionnaire(${q.QuestionID})">Edit</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteQuestionnaire(${q.QuestionID})">Delete</button>
                    </td>
                </tr>
            `).join('');
            }

            // Update questionnaire statistics
            function updateQuestionnairesStats(questionnaires) {
                const totalCount = questionnaires.length;
                const activeCount = questionnaires.filter(q => q.IsActive).length;

                document.getElementById('totalQuestionsCount').textContent = totalCount;
                document.getElementById('activeQuestionsCount').textContent = activeCount;
            }

            // Display active questionnaires
            function displayActiveQuestionnaires(questionnaires) {
                const activeQuestionnaires = questionnaires.filter(q => q.IsActive);
                const displayDiv = document.getElementById('activeQuestionnairesDisplay');

                if (activeQuestionnaires.length === 0) {
                    displayDiv.innerHTML = '<p class="text-warning mb-0"><i class="bi bi-exclamation-circle"></i> No active questions set. Applicants will not see any assessment questions.</p>';
                    return;
                }

                displayDiv.innerHTML = activeQuestionnaires.map((q, index) => `
                <div class="mb-2 pb-2 border-bottom">
                    <p class="mb-1"><strong>Question ${index + 1}:</strong></p>
                    <p class="mb-0 text-muted">${q.QuestionText}</p>
                </div>
            `).join('');

                if (activeQuestionnaires.length < 2) {
                    displayDiv.innerHTML += '<p class="text-warning mt-2 mb-0"><i class="bi bi-info-circle"></i> Only ' + activeQuestionnaires.length + ' active question(s). Recommended: 2</p>';
                }
            }

            // Open add questionnaire modal
            function openAddQuestionnaireModal() {
                document.getElementById('questionnaireModalTitle').textContent = 'Add New Question';
                document.getElementById('questionnaireForm').reset();
                document.getElementById('questionIdInput').value = '';
                document.getElementById('setAsActive').checked = false;
            }

            // Edit questionnaire
            async function editQuestionnaire(questionId) {
                try {
                    const response = await fetch(`api/get-questionnaires.php?id=${questionId}`);
                    const data = await response.json();

                    if (data.success && data.questionnaires.length > 0) {
                        const q = data.questionnaires[0];
                        document.getElementById('questionnaireModalTitle').textContent = 'Edit Question';
                        document.getElementById('questionText').value = q.QuestionText;
                        document.getElementById('setAsActive').checked = q.IsActive == 1;
                        document.getElementById('questionIdInput').value = q.QuestionID;

                        const modal = new bootstrap.Modal(document.getElementById('addQuestionnaireModal'));
                        modal.show();
                    } else {
                        alert('Error loading question details');
                    }
                } catch (error) {
                    console.error('Error editing questionnaire:', error);
                    alert('Error loading question');
                }
            }

            // Save questionnaire
            async function saveQuestionnaire() {
                const questionText = document.getElementById('questionText').value.trim();
                const isActive = document.getElementById('setAsActive').checked;
                const questionId = document.getElementById('questionIdInput').value;

                if (!questionText) {
                    alert('Please enter a question');
                    return;
                }

                try {
                    const response = await fetch('api/manage-questionnaire.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: questionId ? 'update' : 'add',
                            questionId: questionId || null,
                            questionText: questionText,
                            isActive: isActive
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        bootstrap.Modal.getInstance(document.getElementById('addQuestionnaireModal')).hide();
                        loadQuestionnaires();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error saving questionnaire:', error);
                    alert('Error saving question');
                }
            }

            // View question details
            async function viewQuestion(questionId) {
                try {
                    const response = await fetch(`api/get-questionnaires.php?id=${questionId}`);
                    const data = await response.json();

                    if (data.success && data.questionnaires.length > 0) {
                        const q = data.questionnaires[0];
                        document.getElementById('viewQuestionId').textContent = 'Q' + q.QuestionID;
                        document.getElementById('viewQuestionText').textContent = q.QuestionText;
                        document.getElementById('viewQuestionStatus').innerHTML = `<span class="badge ${q.IsActive ? 'bg-success' : 'bg-secondary'}">${q.IsActive ? 'Active' : 'Inactive'}</span>`;
                        document.getElementById('viewQuestionDate').textContent = new Date(q.CreatedDate).toLocaleString();

                        const modal = new bootstrap.Modal(document.getElementById('viewQuestionModal'));
                        modal.show();
                    } else {
                        alert('Error loading question');
                    }
                } catch (error) {
                    console.error('Error viewing question:', error);
                    alert('Error loading question');
                }
            }

            // Delete questionnaire
            async function deleteQuestionnaire(questionId) {
                if (!confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-questionnaire.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            questionId: questionId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        loadQuestionnaires();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error deleting questionnaire:', error);
                    alert('Error deleting question');
                }
            }

            // Load active questions for selection modal
            async function loadActiveQuestionsModal() {
                try {
                    const response = await fetch('api/get-questionnaires.php');
                    const data = await response.json();

                    if (data.success) {
                        const checkboxesDiv = document.getElementById('activeQuestionsCheckboxes');
                        checkboxesDiv.innerHTML = data.questionnaires.map(q => `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="question${q.QuestionID}" value="${q.QuestionID}" ${q.IsActive ? 'checked' : ''} onchange="handleActiveCheckbox(this)">
                            <label class="form-check-label" for="question${q.QuestionID}">
                                Q${q.QuestionID}: ${q.QuestionText.substring(0, 70)}${q.QuestionText.length > 70 ? '...' : ''}
                            </label>
                        </div>
                    `).join('');
                    } else {
                        console.error('Error loading questions:', data.message);
                    }
                } catch (error) {
                    console.error('Error loading questions:', error);
                }
            }

            // Handle active checkbox changes - enforce max 2 selections
            function handleActiveCheckbox(checkbox) {
                const checkedBoxes = document.querySelectorAll('#activeQuestionsCheckboxes .form-check-input:checked');

                if (checkedBoxes.length > 2) {
                    alert('You can only select a maximum of 2 active questions');
                    checkbox.checked = false;
                }
            }

            // Save active questions selection
            async function saveActiveQuestions() {
                const checkedBoxes = document.querySelectorAll('#activeQuestionsCheckboxes .form-check-input:checked');

                if (checkedBoxes.length !== 2) {
                    alert('Please select exactly 2 active questions');
                    return;
                }

                const activeQuestionIds = Array.from(checkedBoxes).map(cb => cb.value);

                try {
                    const response = await fetch('api/manage-questionnaire.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'setActive',
                            activeQuestionIds: activeQuestionIds
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        bootstrap.Modal.getInstance(document.getElementById('setActiveModal')).hide();
                        loadQuestionnaires();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error saving active questions:', error);
                    alert('Error saving active questions');
                }
            }

            // Initialize questionnaire management
            document.addEventListener('DOMContentLoaded', function () {
                loadQuestionnaires();

                // Reload when questionnaire section is clicked
                const questionnaireLink = document.querySelector('a[href="#questionnaire"]');
                if (questionnaireLink) {
                    questionnaireLink.addEventListener('click', function () {
                        setTimeout(() => loadQuestionnaires(), 100);
                    });
                }
            });

            // ===== METRIC CARDS CLICK HANDLERS =====
            document.addEventListener('DOMContentLoaded', function () {
                // Total Members card - navigate to Members Management
                const totalMembersCard = document.getElementById('totalMembersCard');
                if (totalMembersCard) {
                    totalMembersCard.addEventListener('click', function () {
                        const membersLink = document.querySelector('a[href="#members"]');
                        if (membersLink) {
                            membersLink.click();
                        }
                    });
                }

                // Active Initiatives card - navigate to Content Management > Initiatives
                const activeInitiativesCard = document.getElementById('activeInitiativesCard');
                if (activeInitiativesCard) {
                    activeInitiativesCard.addEventListener('click', function () {
                        const contentMgmtLink = document.querySelector('a[href="#content"]');
                        if (contentMgmtLink) {
                            contentMgmtLink.click();
                            // Switch to initiatives tab after opening content management
                            setTimeout(function () {
                                const initiativesTab = document.querySelector('a[href="#initiatives-content"]');
                                if (initiativesTab) {
                                    initiativesTab.click();
                                }
                            }, 100);
                        }
                    });
                }

                // Pending Applications card - navigate to Member Application Management
                const pendingApplicationsCard = document.getElementById('pendingApplicationsCard');
                if (pendingApplicationsCard) {
                    pendingApplicationsCard.addEventListener('click', function () {
                        const applicationsLink = document.querySelector('a[href="#applications"]');
                        if (applicationsLink) {
                            applicationsLink.click();
                        }
                    });
                }

                // Event Proposals card - navigate to Event Proposals
                const eventProposalsCard = document.getElementById('eventProposalsCard');
                if (eventProposalsCard) {
                    eventProposalsCard.addEventListener('click', function () {
                        const proposalsLink = document.querySelector('a[href="#event-proposals"]');
                        if (proposalsLink) {
                            proposalsLink.click();
                        }
                    });
                }

                // View All Applications button
                const viewAllApplicationsBtn = document.getElementById('viewAllApplicationsBtn');
                if (viewAllApplicationsBtn) {
                    viewAllApplicationsBtn.addEventListener('click', function () {
                        const applicationsLink = document.querySelector('a[href="#applications"]');
                        if (applicationsLink) {
                            applicationsLink.click();
                        }
                    });
                }

                // View All Events button
                const viewAllEventsBtn = document.getElementById('viewAllEventsBtn');
                if (viewAllEventsBtn) {
                    viewAllEventsBtn.addEventListener('click', function () {
                        const eventMgmtLink = document.querySelector('a[href="#events"]');
                        if (eventMgmtLink) {
                            eventMgmtLink.click();
                        }
                    });
                }
            });

            // ===== MEMBERS MANAGEMENT FUNCTIONS =====

            // Load all members
            async function loadMembers() {
                try {
                    // Reset filters
                    document.getElementById('memberSearchInput').value = '';
                    document.getElementById('memberRoleFilter').value = '';
                    document.getElementById('memberStatusFilter').value = '';

                    const response = await fetch('api/get-members.php');
                    const data = await response.json();

                    if (data.success) {
                        populateMembersTable(data.members, data.currentMemberId);
                        updateMemberCount(data.members.length);
                        // Profile should only load when clicking on a member, not on initialization
                    } else {
                        console.error('Error loading members:', data.message);
                        document.getElementById('membersTableBody').innerHTML =
                            '<tr><td colspan="7" class="text-center text-danger">Error loading members</td></tr>';
                    }
                } catch (error) {
                    console.error('Error fetching members:', error);
                    document.getElementById('membersTableBody').innerHTML =
                        '<tr><td colspan="7" class="text-center text-danger">Error loading members</td></tr>';
                }
            }

            // Populate members table
            function populateMembersTable(members, currentMemberId) {
                const tbody = document.getElementById('membersTableBody');

                if (!members || members.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No members found</td></tr>';
                    return;
                }

                // Sort members by MemberID in ascending order
                const sortedMembers = members.sort((a, b) => {
                    return parseInt(a.MemberID) - parseInt(b.MemberID);
                });

                // Store original members data for filtering
                window.allMembers = sortedMembers;

                tbody.innerHTML = sortedMembers.map((member, index) => {
                    const isCurrentUser = member.MemberID == currentMemberId;
                    const roleDisabled = isCurrentUser ? 'disabled' : '';
                    const roleTitle = isCurrentUser ? 'You cannot change your own role' : 'Change role';

                    return `
                    <tr ${isCurrentUser ? 'class="table-active"' : ''} class="member-row" data-member-id="${member.MemberID}" data-member-name="${member.FName} ${member.LName}" data-member-email="${member.ApplicantEmail}" data-member-role="${member.Role}" data-member-status="${member.isActive}">
                        <td>
                            <strong>#${member.MemberID}</strong>
                        </td>
                        <td>
                            <strong>${member.FName} ${member.LName}</strong>
                            ${isCurrentUser ? '<span class="badge bg-info ms-2">You</span>' : ''}
                        </td>
                        <td>${member.ApplicantEmail}</td>
                        <td>
                            ${isCurrentUser ? (
                            '<span class="text-muted small">Cannot change own role</span>'
                        ) : (window.currentUserRole !== 'Executive Director' && window.currentUserRole !== 'Admin') ? (
                            '<span class="text-muted small">' + member.Role + '</span>'
                        ) : (
                            `<select class="form-select" data-role-select title="Change member role" onchange="updateMemberRole(${member.MemberID}, this.value)">
                                    <option value="Member" ${member.Role === 'Member' ? 'selected' : ''}>Member</option>
                                    <option value="Member Staff" ${member.Role === 'Member Staff' ? 'selected' : ''}>Member Staff</option>
                                    <option value="Executive Director" ${member.Role === 'Executive Director' ? 'selected' : ''}>Executive Director</option>
                                    <option value="Program Officer" ${member.Role === 'Program Officer' ? 'selected' : ''}>Program Officer</option>
                                    <option value="Regional Convenor" ${member.Role === 'Regional Convenor' ? 'selected' : ''}>Regional Convenor</option>
                                    <option value="Local Coordinator" ${member.Role === 'Local Coordinator' ? 'selected' : ''}>Local Coordinator</option>
                                    <option value="Finance Officer" ${member.Role === 'Finance Officer' ? 'selected' : ''}>Finance Officer</option>
                                    <option value="Meal Officer" ${member.Role === 'Meal Officer' ? 'selected' : ''}>Meal Officer</option>
                                    ${member.Role === 'Admin' ? '<option value="Admin" selected>Admin</option>' : ''}
                                </select>`
                        )}
                        </td>
                        <td>
                            <span class="badge ${member.isActive ? 'bg-success' : 'bg-danger'}">
                                ${member.isActive ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td>${new Date(member.JoinDate).toLocaleDateString()}</td>
                        <td>
                            ${!isCurrentUser ? (member.isActive ?
                            `<button class="btn btn-sm btn-outline-danger" onclick="deactivateMember(${member.MemberID}, '${member.FName} ${member.LName}')">Deactivate</button>` :
                            `<button class="btn btn-sm btn-outline-success" onclick="activateMember(${member.MemberID}, '${member.FName} ${member.LName}')">Activate</button>`
                        ) : '<span class="text-muted small">Admin only</span>'}
                        </td>
                    </tr>
                `;
                }).join('');
            }

            // Filter members based on search, role, and status
            function filterMembers() {
                const searchInput = document.getElementById('memberSearchInput').value.toLowerCase();
                const roleFilter = document.getElementById('memberRoleFilter').value;
                const statusFilter = document.getElementById('memberStatusFilter').value;

                const rows = document.querySelectorAll('.member-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const memberName = row.getAttribute('data-member-name').toLowerCase();
                    const memberEmail = row.getAttribute('data-member-email').toLowerCase();
                    const memberRole = row.getAttribute('data-member-role');
                    const memberStatus = row.getAttribute('data-member-status');

                    // Check search criteria
                    const matchesSearch = memberName.includes(searchInput) || memberEmail.includes(searchInput);

                    // Check role filter
                    const matchesRole = !roleFilter || memberRole === roleFilter;

                    // Check status filter
                    const matchesStatus = !statusFilter || (statusFilter === '1' ? memberStatus === '1' : memberStatus === '0');

                    // Show or hide row based on all criteria
                    if (matchesSearch && matchesRole && matchesStatus) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Show "no results" message if no members match filters
                if (visibleCount === 0) {
                    const tbody = document.getElementById('membersTableBody');
                    const messageRow = tbody.querySelector('.no-results-row');
                    if (!messageRow) {
                        tbody.innerHTML += '<tr class="no-results-row"><td colspan="7" class="text-center text-muted">No members match the selected filters</td></tr>';
                    }
                } else {
                    const messageRow = document.querySelector('.no-results-row');
                    if (messageRow) {
                        messageRow.remove();
                    }
                }

                // Update member count to show visible members
                document.getElementById('memberCount').textContent = visibleCount;
            }

            // Update member count
            function updateMemberCount(count) {
                document.getElementById('memberCount').textContent = count;
            }

            // Update member role
            async function updateMemberRole(memberId, newRole) {
                // Only Executive Director and Admin can change roles
                if (window.currentUserRole !== 'Executive Director' && window.currentUserRole !== 'Admin') {
                    alert('You do not have authority to change member roles. Only Executive Director and Admin can change roles.');
                    loadMembers();
                    return;
                }

                // Prevent assigning Admin role through UI (Admin can only be set in database)
                if (newRole === 'Admin') {
                    alert('Admin role cannot be assigned through the interface. Admin role can only be set directly in the database.');
                    loadMembers();
                    return;
                }

                try {
                    const response = await fetch('api/update-member.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'updateRole',
                            memberId: memberId,
                            role: newRole
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        loadMembers();
                    } else {
                        alert('Error: ' + data.message);
                        loadMembers();
                    }
                } catch (error) {
                    console.error('Error updating member role:', error);
                    alert('Error updating member role');
                    loadMembers();
                }
            }

            // View member details
            function viewMemberDetails(memberId, name, email, gender, birthDate, profileImage) {
                currentViewedMemberId = memberId; // Store the currently viewed member
                // Parse the full name into first and last name
                const nameParts = name.split(' ');
                const firstName = nameParts[0];
                const lastName = nameParts.slice(1).join(' ');

                // Populate the display fields in the profile section
                document.getElementById('displayFullName').value = name;
                document.getElementById('displayEmail').value = email;
                document.getElementById('displayPhone').value = '';
                document.getElementById('displayRole').value = document.querySelector(`[data-member-id="${memberId}"]`)?.dataset.memberRole || 'Member';
                document.getElementById('displayMemberSince').value = new Date().toLocaleDateString();
                document.getElementById('displayStatus').value = document.querySelector(`[data-member-id="${memberId}"]`)?.dataset.memberStatus === '1' ? 'Active' : 'Inactive';

                // Update ID preview name
                document.getElementById('idPreviewName').textContent = name;

                // Display profile image - FIX: Check if image exists and is not null
                const profileImageEl = document.getElementById('displayProfileImage');
                const idPreviewPhotoDiv = document.querySelector('#idPreview .rounded-circle');

                console.log('Profile Image Value:', profileImage); // Debug

                if (profileImage && profileImage !== 'null' && profileImage !== '' && profileImage !== undefined) {
                    profileImageEl.src = profileImage;
                    console.log('Setting image to:', profileImage); // Debug

                    if (idPreviewPhotoDiv) {
                        idPreviewPhotoDiv.innerHTML = `<img src="${profileImage}" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                    }
                } else {
                    // No image - show placeholder
                    profileImageEl.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"%3E%3Crect width="200" height="200" fill="%23e9ecef"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="14" fill="%23999"%3ENo Photo%3C/text%3E%3C/svg%3E';

                    if (idPreviewPhotoDiv) {
                        idPreviewPhotoDiv.innerHTML = `<div class="h-100 d-flex align-items-center justify-content-center text-muted">2x2 Photo</div>`;
                    }
                }

                // Switch to profile tab
                const profileSection = document.getElementById('profile');
                document.querySelectorAll('.member-section').forEach(section => {
                    section.classList.add('d-none');
                });
                profileSection?.classList.remove('d-none');
            }

            // Deactivate member
            async function deactivateMember(memberId, memberName) {
                if (!confirm(`Are you sure you want to deactivate ${memberName}?`)) {
                    return;
                }

                try {
                    const response = await fetch('api/update-member.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'deactivateMember',
                            memberId: memberId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        loadMembers();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error deactivating member:', error);
                    alert('Error deactivating member');
                }
            }

            // Activate member
            async function activateMember(memberId, memberName) {
                if (!confirm(`Are you sure you want to activate ${memberName}?`)) {
                    return;
                }

                try {
                    const response = await fetch('api/update-member.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'activateMember',
                            memberId: memberId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        loadMembers();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error activating member:', error);
                    alert('Error activating member');
                }
            }

            // Initialize members management
            document.addEventListener('DOMContentLoaded', function () {
                loadMembers();

                // Reload when members section is clicked
                const membersLink = document.querySelector('a[href="#members"]');
                if (membersLink) {
                    membersLink.addEventListener('click', function () {
                        setTimeout(() => loadMembers(), 100);
                    });
                }
            });

            // ===== APPLICATIONS MANAGEMENT FUNCTIONS =====

            let currentApplicationId = null;
            let currentViewedMemberId = null;
            let currentViewedMemberName = null;

            // Load all applications (pending, approved, and rejected)
            async function loadApplications() {
                try {
                    const response = await fetch('api/get-applications.php?status=all');
                    const data = await response.json();

                    if (data.success) {
                        populateApplicationsTable(data.applications);
                        updateApplicationCount(data.applications ? data.applications.length : 0);
                    } else {
                        console.error('Error loading applications:', data.message);
                        document.getElementById('applicationsTableBody').innerHTML =
                            '<tr><td colspan="6" class="text-center text-danger">Error loading applications</td></tr>';
                    }
                } catch (error) {
                    console.error('Error fetching applications:', error);
                    document.getElementById('applicationsTableBody').innerHTML =
                        '<tr><td colspan="6" class="text-center text-danger">Error loading applications</td></tr>';
                }
            }

            // Populate applications table with all applicants and their status
            function populateApplicationsTable(applications) {
                const tbody = document.getElementById('applicationsTableBody');

                if (!applications || applications.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No applications found</td></tr>';
                    return;
                }

                // Store all applications for filtering
                window.allApplications = applications;

                tbody.innerHTML = applications.map(app => {
                    let statusBadge = '';
                    let statusValue = app.ApplicationStatus;

                    if (statusValue == 0) {
                        statusBadge = '<span class="badge bg-warning">Under Review</span>';
                    } else if (statusValue == 1) {
                        statusBadge = '<span class="badge bg-success">Approved</span>';
                    } else if (statusValue == 2) {
                        statusBadge = '<span class="badge bg-danger">Rejected</span>';
                    }

                    const reviewDate = app.ReviewDate ? new Date(app.ReviewDate).toLocaleDateString() : 'N/A';

                    return `
                    <tr class="application-row" data-app-id="${app.ApplicationID}" data-app-name="${app.FName} ${app.LName}" data-app-email="${app.ApplicantEmail}" data-app-status="${app.ApplicationStatus}">
                        <td>
                            <strong>${app.FName} ${app.LName}</strong>
                        </td>
                        <td>${app.ApplicantEmail}</td>
                        <td>${new Date(app.SubmissionDate).toLocaleDateString()}</td>
                        <td>
                            ${statusBadge}
                        </td>
                        <td>${reviewDate}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewApplication(${app.ApplicationID})">
                                View
                            </button>
                            ${(app.ApplicationStatus == 0 && window.currentUserRole === 'Executive Director') ? `
                                <button class="btn btn-sm btn-outline-success" onclick="approveApplicationByBtn(${app.ApplicationID}, '${app.FName} ${app.LName}')">
                                    Approve
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="rejectApplicationByBtn(${app.ApplicationID}, '${app.FName} ${app.LName}')">
                                    Reject
                                </button>
                            ` : ''}
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteApplicationRecord(${app.ApplicationID}, '${app.FName} ${app.LName}')">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                `;
                }).join('');
            }

            // Filter applications based on search and status
            function filterApplications() {
                const searchInput = document.getElementById('applicationSearchInput').value.toLowerCase();
                const statusFilter = document.getElementById('applicationStatusFilter').value;

                const rows = document.querySelectorAll('.application-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const appName = row.getAttribute('data-app-name').toLowerCase();
                    const appEmail = row.getAttribute('data-app-email').toLowerCase();
                    const appStatus = row.getAttribute('data-app-status');

                    // Check search criteria
                    const matchesSearch = appName.includes(searchInput) || appEmail.includes(searchInput);

                    // Check status filter
                    const matchesStatus = !statusFilter || appStatus === statusFilter;

                    // Show or hide row based on all criteria
                    if (matchesSearch && matchesStatus) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Show "no results" message if no applications match filters
                if (visibleCount === 0) {
                    const tbody = document.getElementById('applicationsTableBody');
                    const messageRow = tbody.querySelector('.no-results-row');
                    if (!messageRow) {
                        tbody.innerHTML += '<tr class="no-results-row"><td colspan="6" class="text-center text-muted">No applications match the selected filters</td></tr>';
                    }
                } else {
                    const messageRow = document.querySelector('.no-results-row');
                    if (messageRow) {
                        messageRow.remove();
                    }
                }

                // Update application count to show visible applications
                document.getElementById('pendingApplicationCount').textContent = visibleCount;
            }

            // Update application count
            function updateApplicationCount(count) {
                document.getElementById('pendingApplicationCount').textContent = count;
            }

            document.addEventListener('DOMContentLoaded', function () {
                loadApplications();

                // Reload when applications section is clicked
                const applicationsLink = document.querySelector('a[href="#applications"]');
                if (applicationsLink) {
                    applicationsLink.addEventListener('click', function () {
                        setTimeout(() => loadApplications(), 100);
                    });
                }
            });

            // Approve application directly from table
            async function approveApplicationByBtn(applicationId, applicantName) {
                // Only Executive Director can approve members
                if (window.currentUserRole !== 'Executive Director') {
                    alert('You do not have authority to approve members. Only Executive Director can approve members.');
                    return;
                }
                currentApplicationId = applicationId;
                if (!confirm(`Are you sure you want to approve ${applicantName}'s application?`)) {
                    return;
                }
                await approveApplication();
            }

            // Reject application directly from table
            async function rejectApplicationByBtn(applicationId, applicantName) {
                currentApplicationId = applicationId;
                if (!confirm(`Are you sure you want to reject ${applicantName}'s application?`)) {
                    return;
                }
                await rejectApplication();
            }

            // View application details
            async function viewApplication(applicationId) {
                try {
                    const response = await fetch(`api/get-application-details.php?id=${applicationId}`);
                    const data = await response.json();

                    if (data.success) {
                        const app = data.application;
                        currentApplicationId = applicationId;

                        // Populate application details
                        document.getElementById('appFName').textContent = app.FName || '-';
                        document.getElementById('appLName').textContent = app.LName || '-';
                        document.getElementById('appMName').textContent = app.MName || '-';
                        document.getElementById('appExtName').textContent = app.ExtensionName || '-';
                        document.getElementById('appGender').textContent = app.Gender || '-';
                        document.getElementById('appBirthDate').textContent = new Date(app.BirthDate).toLocaleDateString();
                        document.getElementById('appEmail').textContent = app.ApplicantEmail;
                        document.getElementById('appSubmissionDate').textContent = new Date(app.SubmissionDate).toLocaleString();

                        // Populate answers - get them properly from the database
                        const answersContainer = document.getElementById('appAnswersContainer');
                        if (data.answers && data.answers.length > 0) {
                            answersContainer.innerHTML = data.answers.map((answer, index) => `
                            <div class="mb-3 p-3 bg-light rounded">
                                <p class="small text-muted mb-2"><strong>Answer ${index + 1}:</strong></p>
                                <p class="mb-0">${answer.AnswerText || 'No answer provided'}</p>
                            </div>
                        `).join('');
                        } else {
                            answersContainer.innerHTML = '<p class="text-muted">No answers provided</p>';
                        }

                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('applicationModal'));
                        modal.show();
                    } else {
                        alert('Error loading application details');
                    }
                } catch (error) {
                    console.error('Error viewing application:', error);
                    alert('Error loading application');
                }
            }

            // Approve application
            async function approveApplication() {
                if (!currentApplicationId) return;

                // Admin and Executive Director can approve members
                const rolesCanApprove = ['Admin', 'Executive Director'];
                if (!rolesCanApprove.includes(window.currentUserRole)) {
                    alert('You do not have authority to approve members. Only Admin and Executive Director can approve members.');
                    return;
                }

                // Find and disable all approve buttons to prevent double-click
                const modalApproveBtn = document.getElementById('approveBtn');
                const tableApproveBtns = document.querySelectorAll(`[onclick*="approveApplicationByBtn"][onclick*="${currentApplicationId}"]`);

                if (modalApproveBtn) {
                    modalApproveBtn.disabled = true;
                    modalApproveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Approving...';
                }
                tableApproveBtns.forEach(btn => {
                    btn.disabled = true;
                });

                try {
                    const response = await fetch('api/manage-application.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'approve',
                            applicationId: currentApplicationId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        let alertMessage = data.message;

                        // If we have temporary password and email may have failed, show it to admin
                        if (data.temporaryPassword && !data.emailSent) {
                            alertMessage += `\n\n⚠️ Email may not have been sent.\n\nTemporary Password: ${data.temporaryPassword}\nApplicant Email: ${data.applicantEmail}\n\nPlease communicate these credentials to the applicant through another method.`;
                        } else if (data.temporaryPassword) {
                            alertMessage += `\n\nTemporary Password: ${data.temporaryPassword}`;
                        }

                        alert(alertMessage);
                        const modalEl = document.getElementById('applicationModal');
                        if (modalEl && bootstrap.Modal.getInstance(modalEl)) {
                            bootstrap.Modal.getInstance(modalEl).hide();
                        }
                        loadApplications();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error approving application:', error);
                    alert('Error approving application');
                } finally {
                    // Re-enable the buttons
                    if (modalApproveBtn) {
                        modalApproveBtn.disabled = false;
                        modalApproveBtn.innerHTML = 'Approve';
                    }
                    tableApproveBtns.forEach(btn => {
                        btn.disabled = false;
                    });
                }
            }

            // Reject application
            async function rejectApplication() {
                if (!currentApplicationId) return;

                if (!confirm('Are you sure you want to reject this application?')) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-application.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'reject',
                            applicationId: currentApplicationId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        bootstrap.Modal.getInstance(document.getElementById('applicationModal')).hide();
                        loadApplications();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error rejecting application:', error);
                    alert('Error rejecting application');
                }
            }

            // Delete application record and all related data
            async function deleteApplicationRecord(applicationId, applicantName) {
                if (!confirm(`Are you sure you want to permanently delete the application for "${applicantName}"? This will also remove any associated member records and cannot be undone.`)) {
                    return;
                }

                try {
                    const response = await fetch('api/delete-application.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            applicationId: applicationId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Application deleted successfully');
                        loadApplications();
                        // Close modal if open
                        const modal = document.getElementById('applicationModal');
                        if (modal) {
                            const bsModal = bootstrap.Modal.getInstance(modal);
                            if (bsModal) bsModal.hide();
                        }
                    } else {
                        alert('Error deleting application: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error deleting application:', error);
                    alert('Error rejecting application');
                }
            }

            // ===== EVENT PROPOSALS MANAGEMENT FUNCTIONS =====

            // Load all event proposals
            async function loadEventProposals() {
                try {
                    const response = await fetch('api/get-event-proposals.php');

                    // Check if response is ok
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const text = await response.text();

                    // Check if response is empty
                    if (!text || text.trim().length === 0) {
                        throw new Error('Empty response from server');
                    }

                    // Try to parse JSON
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (parseError) {
                        console.error('JSON Parse Error. Raw response:', text.substring(0, 500));
                        throw new Error('Invalid JSON response: ' + parseError.message);
                    }

                    if (data.success) {
                        populateProposalsTable(data.proposals);
                        updateProposalCount(data.count);
                    } else {
                        console.error('Error loading proposals:', data.message);
                        document.getElementById('proposalsTableBody').innerHTML =
                            '<tr><td colspan="5" class="text-center text-danger">Error: ' + (data.message || 'Unknown error') + '</td></tr>';
                    }
                } catch (error) {
                    console.error('Error fetching proposals:', error);
                    document.getElementById('proposalsTableBody').innerHTML =
                        '<tr><td colspan="5" class="text-center text-danger">Error: ' + error.message + '</td></tr>';
                }
            }

            // Populate proposals table
            function populateProposalsTable(proposals) {
                const tbody = document.getElementById('proposalsTableBody');

                if (!proposals || proposals.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No proposals found</td></tr>';
                    return;
                }

                tbody.innerHTML = proposals.map(prop => {
                    const statusBadge = {
                        'Pending': 'bg-warning',
                        'Approved': 'bg-success',
                        'Rejected': 'bg-danger',
                        'Postponed': 'bg-danger'
                    }[prop.Status] || 'bg-secondary';

                    const proposerName = `${prop.FName || '-'} ${prop.LName || '-'}`;
                    const proposedDate = prop.ProposedDate ? new Date(prop.ProposedDate).toLocaleDateString() : '-';

                    return `
                    <tr data-proposal-status="${prop.Status}" data-proposal-id="${prop.ProposalID}">
                        <td>${prop.Title || '-'}</td>
                        <td>${proposerName}</td>
                        <td>${proposedDate}</td>
                        <td><span class="badge ${statusBadge}">${prop.Status}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="viewEventProposal(${prop.ProposalID})">View</button>
                                ${prop.Status === 'Pending' ? `
                                    <button class="btn btn-outline-info" onclick="editEventProposal(${prop.ProposalID})">Edit</button>
                                    <button class="btn btn-outline-success" onclick="approveProposalFromTable(${prop.ProposalID})">Approve</button>
                                    <button class="btn btn-outline-danger" onclick="rejectProposalFromTable(${prop.ProposalID})">Reject</button>
                                ` : ''}

                                ${prop.Status === 'Rejected' || prop.Status === 'Postponed' ? `
                                    <button class="btn btn-outline-danger" onclick="deleteProposalFromTable(${prop.ProposalID}, '${prop.Title.replace(/'/g, "\\'")}', '${prop.Status}')">Delete</button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
                }).join('');
            }

            // Update proposal count
            function updateProposalCount(count) {
                const elem = document.getElementById('eventProposalsCount');
                if (elem) elem.textContent = count;
            }

            // View proposal details
            async function viewEventProposal(proposalId) {
                try {
                    const response = await fetch(`api/get-event-proposal-details.php?id=${proposalId}`);
                    const data = await response.json();

                    if (data.success) {
                        const prop = data.proposal;

                        // Set all field values
                        document.getElementById('propEventName').value = prop.Title || '-';
                        document.getElementById('propDescription').value = prop.Description || '-';
                        document.getElementById('propDate').value = prop.ProposedDate ? new Date(prop.ProposedDate).toLocaleDateString() : '-';
                        document.getElementById('propStartTime').value = prop.StartTime || '-';
                        document.getElementById('propEndTime').value = prop.EndTime || '-';
                        document.getElementById('propLocation').value = prop.Venue || '-';
                        document.getElementById('propAttendees').value = prop.TargetParticipants || '0';
                        document.getElementById('propEventType').value = prop.EventType || '-';
                        document.getElementById('propBudget').value = prop.BudgetEstimate || '0';
                        document.getElementById('propStaff').value = prop.StaffRequired || '0';
                        document.getElementById('propEquipment').value = prop.EquipmentNeeded || '-';
                        document.getElementById('propObjectives').value = prop.Objectives || '-';
                        document.getElementById('propProposer').value = `${prop.FName || '-'} ${prop.LName || '-'}`;
                        document.getElementById('propPartners').value = prop.PartnersSponsor || '-';

                        // Set status
                        const statusAlert = document.getElementById('propStatusAlert');
                        statusAlert.className = 'alert';
                        if (prop.Status === 'Approved') {
                            statusAlert.classList.add('alert-success');
                        } else if (prop.Status === 'Rejected') {
                            statusAlert.classList.add('alert-danger');
                        } else {
                            statusAlert.classList.add('alert-warning');
                        }

                        document.getElementById('propStatus').textContent = `${prop.Status}`;
                        document.getElementById('propCreatedDate').textContent =
                            prop.SubmissionDate ? `Submitted on: ${new Date(prop.SubmissionDate).toLocaleString()}` : 'Submitted on: -';

                        // Show review info if reviewed
                        const reviewInfo = document.getElementById('reviewInfo');
                        if (prop.ReviewDate && prop.ReviewDate !== '0000-00-00 00:00:00' && prop.ReviewByAdminID) {
                            reviewInfo.classList.remove('d-none');
                            document.getElementById('propReviewer').textContent = `${prop.ReviewerFirstName || '-'} ${prop.ReviewerLastName || '-'}`;
                            document.getElementById('propReviewDate').textContent =
                                `Reviewed on: ${new Date(prop.ReviewDate).toLocaleString()}`;
                        } else {
                            reviewInfo.classList.add('d-none');
                        }

                        // Check if proposed date has passed and proposal is not approved
                        const proposedDate = new Date(prop.ProposedDate);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        const passdateWarning = document.getElementById('passdateWarning');
                        const updateDateBtn = document.getElementById('updateProposalDateBtn');
                        const deleteBtn = document.getElementById('deleteProposalBtn');

                        if (proposedDate < today && prop.Status === 'Pending Review') {
                            passdateWarning.classList.remove('d-none');
                            updateDateBtn.style.display = 'block';
                            document.getElementById('newProposalDate').value = prop.ProposedDate;
                            document.getElementById('newProposalDate').dataset.proposalId = proposalId;
                        } else {
                            passdateWarning.classList.add('d-none');
                            updateDateBtn.style.display = 'none';
                        }

                        // Show delete button only for pending review proposals
                        if (deleteBtn) {
                            if (prop.Status === 'Pending Review') {
                                deleteBtn.style.display = 'block';
                                deleteBtn.dataset.proposalTitle = prop.Title;
                            } else {
                                deleteBtn.style.display = 'none';
                            }
                        }

                        // Set proposal ID for approve/reject buttons
                        currentProposalId = proposalId;

                        const modal = new bootstrap.Modal(document.getElementById('viewProposalModal'));
                        modal.show();
                    } else {
                        alert('Error loading proposal details: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error viewing proposal:', error);
                    alert('Error loading proposal');
                }
            }

            // Edit proposal - populate form with existing data
            async function editEventProposal(proposalId) {
                try {
                    const response = await fetch(`api/get-event-proposal-details.php?id=${proposalId}`);
                    const data = await response.json();

                    if (data.success) {
                        const prop = data.proposal;

                        // Get the form element
                        const form = document.getElementById('eventProposalForm');
                        if (!form) {
                            alert('Form not found in page');
                            return;
                        }

                        // Reset form first
                        form.reset();

                        // Helper to safely set field value
                        const setField = (name, value) => {
                            try {
                                const input = form.querySelector(`input[name="${name}"], textarea[name="${name}"], select[name="${name}"]`);
                                if (input) {
                                    input.value = value || '';
                                }
                            } catch (e) {
                                console.log(`Could not set field ${name}`);
                            }
                        };

                        // Populate form fields
                        setField('title', prop.Title || '');
                        setField('proposedDate', prop.ProposedDate || '');
                        setField('startTime', prop.StartTime || '');
                        setField('endTime', prop.EndTime || '');
                        setField('venue', prop.Venue || '');
                        setField('description', prop.Description || '');
                        setField('targetParticipants', prop.TargetParticipants || '');
                        setField('eventType', prop.EventType || '');
                        setField('budgetEstimate', prop.BudgetEstimate || '');
                        setField('staffRequired', prop.StaffRequired || '');
                        setField('equipmentNeeded', prop.EquipmentNeeded || '');
                        setField('objectives', prop.Objectives || '');
                        setField('partnersSponsor', prop.PartnersSponsor || '');

                        // Store proposal ID for submission
                        form.dataset.proposalId = proposalId;
                        form.dataset.isEditing = 'true';

                        // Update modal
                        const modal = document.getElementById('newEventModal');
                        if (modal) {
                            const titleElem = modal.querySelector('.modal-title');
                            const submitBtn = modal.querySelector('.modal-footer .btn-primary');

                            if (titleElem) titleElem.textContent = 'Edit Event Proposal';
                            if (submitBtn) submitBtn.textContent = 'Update Proposal';

                            const modalInstance = new bootstrap.Modal(modal);
                            modalInstance.show();
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error loading proposal details');
                }
            }
            // Reset form state when modal is hidden
            document.getElementById('newEventModal').addEventListener('hidden.bs.modal', function () {
                const form = document.getElementById('eventProposalForm');
                form.reset();
                delete form.dataset.proposalId;
                delete form.dataset.isEditing;

                // Reset modal title and button
                this.querySelector('.modal-title').textContent = 'Create New Event Proposal';
                this.querySelector('.btn-primary').textContent = 'Submit Proposal';
            });



            // Updated submitProposal with field-by-field validation prompts
            async function submitProposal() {
                const form = document.getElementById('eventProposalForm');

                // Define required fields with user-friendly labels
                const requiredFields = [
                    { name: 'title', label: 'Event Title' },
                    { name: 'proposedDate', label: 'Proposed Date' },
                    { name: 'startTime', label: 'Start Time' },
                    { name: 'endTime', label: 'End Time' },
                    { name: 'venue', label: 'Venue' },
                    { name: 'description', label: 'Description' },
                    { name: 'targetParticipants', label: 'Target Participants' },
                    { name: 'eventType', label: 'Event Type' },
                    { name: 'budgetEstimate', label: 'Budget Estimate' },
                    { name: 'staffRequired', label: 'Staff Required' },
                    { name: 'equipmentNeeded', label: 'Equipment Needed' },
                    { name: 'objectives', label: 'Objectives' }
                ];

                // Check for empty required fields
                const emptyFields = [];
                requiredFields.forEach(field => {
                    const input = form.querySelector(`[name="${field.name}"]`);
                    if (input) {
                        const value = input.value?.trim();
                        if (!value) {
                            emptyFields.push(field.label);
                            // Highlight the field visually
                            input.classList.add('is-invalid');
                            input.style.borderColor = '#dc3545';
                        } else {
                            // Remove highlight if field is filled
                            input.classList.remove('is-invalid');
                            input.style.borderColor = '';
                        }
                    }
                });

                // If there are empty fields, show specific prompt
                if (emptyFields.length > 0) {
                    const fieldList = emptyFields.map((field, i) => `${i + 1}. ${field}`).join('\n');
                    alert(`Please fill in the following required fields:\n\n${fieldList}`);
                    return;
                }

                // Additional validation for specific fields
                const startTime = form.querySelector('[name="startTime"]').value;
                const endTime = form.querySelector('[name="endTime"]').value;
                if (startTime >= endTime) {
                    alert('End time must be after start time');
                    form.querySelector('[name="endTime"]').classList.add('is-invalid');
                    form.querySelector('[name="endTime"]').style.borderColor = '#dc3545';
                    return;
                }

                const staffRequired = parseInt(form.querySelector('[name="staffRequired"]').value);
                if (staffRequired < 0) {
                    alert('Staff required must be a valid number (0 or more)');
                    return;
                }

                const targetParticipants = parseInt(form.querySelector('[name="targetParticipants"]').value);
                if (targetParticipants <= 0) {
                    alert('Target participants must be greater than 0');
                    form.querySelector('[name="targetParticipants"]').classList.add('is-invalid');
                    form.querySelector('[name="targetParticipants"]').style.borderColor = '#dc3545';
                    return;
                }

                const budgetEstimate = parseInt(form.querySelector('[name="budgetEstimate"]').value);
                if (budgetEstimate < 0) {
                    alert('Budget estimate must be a valid amount');
                    return;
                }

                // Validate proposed date is in the future (not today or past)
                const proposedDateInput = form.querySelector('[name="proposedDate"]').value;
                const proposedDate = new Date(proposedDateInput);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                proposedDate.setHours(0, 0, 0, 0);

                if (proposedDate <= today) {
                    alert('Proposed date must be in the future. Please select a date after today.');
                    form.querySelector('[name="proposedDate"]').classList.add('is-invalid');
                    form.querySelector('[name="proposedDate"]').style.borderColor = '#dc3545';
                    return;
                } else {
                    form.querySelector('[name="proposedDate"]').classList.remove('is-invalid');
                    form.querySelector('[name="proposedDate"]').style.borderColor = '';
                }

                const formData = new FormData(form);
                const isEditing = form.dataset.isEditing === 'true';
                const proposalId = isEditing ? parseInt(form.dataset.proposalId) : null;

                const data = {
                    action: isEditing ? 'update' : 'create',
                    ...(isEditing && proposalId && { proposalId: proposalId }),
                    title: formData.get('title'),
                    description: formData.get('description'),
                    proposedDate: formData.get('proposedDate'),
                    startTime: formData.get('startTime'),
                    endTime: formData.get('endTime'),
                    venue: formData.get('venue'),
                    targetParticipants: formData.get('targetParticipants'),
                    eventType: formData.get('eventType'),
                    budgetEstimate: formData.get('budgetEstimate'),
                    staffRequired: formData.get('staffRequired'),
                    equipmentNeeded: formData.get('equipmentNeeded'),
                    objectives: formData.get('objectives'),
                    partnersSponsor: formData.get('partnersSponsor')
                };

                try {
                    const response = await fetch('api/manage-event-proposal.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'include',
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert(isEditing ? 'Proposal updated successfully!' : 'Event proposal submitted successfully!');
                        form.reset();
                        // Reset all field styles
                        requiredFields.forEach(field => {
                            const input = form.querySelector(`[name="${field.name}"]`);
                            if (input) {
                                input.classList.remove('is-invalid');
                                input.style.borderColor = '';
                            }
                        });
                        delete form.dataset.proposalId;
                        delete form.dataset.isEditing;

                        // Reset modal title and button
                        const modal = document.getElementById('newEventModal');
                        modal.querySelector('.modal-title').textContent = 'Create New Event Proposal';
                        modal.querySelector('.btn-primary').textContent = 'Submit Proposal';

                        bootstrap.Modal.getInstance(modal).hide();
                        loadEventProposals();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error submitting proposal:', error);
                    alert('Error submitting proposal');
                }
            }

            // Fix for approveProposalFromTable (line 4569)
            async function approveProposalFromTable(proposalId) {
                // Check if user has authority to approve event proposals
                const rolesCanApproveProposals = ['Executive Director', 'Program Officer', 'Regional Convenor', 'Local Coordinator'];
                if (!rolesCanApproveProposals.includes(window.currentUserRole)) {
                    alert('You do not have authority to approve event proposals. Only Executive Director, Program Officer, Regional Convenor, and Local Coordinator can approve proposals.');
                    return;
                }

                try {
                    const approveResponse = await fetch('api/manage-event-proposal.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({
                            action: 'approve',
                            proposalId: proposalId
                        })
                    });

                    const approveData = await approveResponse.json();

                    // CHECK FOR ERRORS
                    if (!approveData.success) {
                        if (approveData.message.includes('Security Alert')) {
                            alert(approveData.message);
                        } else if (approveData.message.includes('already')) {
                            alert('Event already exists for this proposal.\n\nThe proposal has been updated to Approved status.');
                            loadEventProposals();
                            if (typeof loadEvents === 'function') loadEvents();
                        } else {
                            alert('Error: ' + approveData.message);
                        }
                        return;
                    }

                    // Proposal approved - now create the event
                    if (!confirm('Proposal validated. Do you want to proceed with creating the event?')) {
                        loadEventProposals();
                        return;
                    }

                    const eventResponse = await fetch('api/manage-event.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({
                            action: 'createFromProposal',
                            proposalId: proposalId
                        })
                    });

                    const eventData = await eventResponse.json();

                    if (eventData.success) {
                        alert('Proposal approved and event created successfully!');
                        loadEventProposals();
                        if (typeof loadEvents === 'function') loadEvents();

                        // Close modal
                        const modal = document.getElementById('viewProposalModal');
                        if (modal) {
                            const modalInstance = bootstrap.Modal.getInstance(modal);
                            if (modalInstance) modalInstance.hide();
                        }
                    } else {
                        // Handle case where event already exists
                        if (eventData.message.includes('already')) {
                            alert('Event already exists for this proposal.\n\nThe proposal has been approved.\n\nPlease check the Events section.');
                            loadEventProposals();
                            if (typeof loadEvents === 'function') loadEvents();
                        } else {
                            alert('Event creation error: ' + eventData.message);
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error processing request: ' + error.message);
                }
            }

            // Reject proposal 
            async function rejectProposalFromTable(proposalId) {
                try {
                    const response = await fetch('api/manage-event-proposal.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'reject',
                            proposalId: proposalId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Proposal rejected successfully!');
                        loadEventProposals();

                        const modal = document.getElementById('viewProposalModal');
                        if (modal) {
                            const modalInstance = bootstrap.Modal.getInstance(modal);
                            if (modalInstance) modalInstance.hide();
                        }
                    } else {
                        alert(data.message);
                    }
                } catch (error) {
                    console.error('Error rejecting proposal:', error);
                    alert('Error rejecting proposal');
                }
            }

            // Postpone approved proposal
            async function postponeProposal(proposalId) {
                if (!confirm('Are you sure you want to postpone this approved event proposal? All registered members will be removed.')) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-event-proposal.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'postpone',
                            proposalId: proposalId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Event proposal postponed successfully! All registered members have been removed.');
                        
                        // Update table row - find and update the row for this proposal
                        const tableRow = document.querySelector(`tr[data-proposal-id="${proposalId}"]`);
                        if (tableRow) {
                            // Update the status badge
                            const badgeCell = tableRow.querySelector('td:nth-child(4)');
                            if (badgeCell) {
                                badgeCell.innerHTML = '<span class="badge bg-danger">Postponed</span>';
                            }
                            
                            // Update the action buttons
                            const actionCell = tableRow.querySelector('td:nth-child(5)');
                            if (actionCell) {
                                const proposalTitle = tableRow.querySelector('td:first-child').textContent;
                                actionCell.innerHTML = `
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="viewEventProposal(${proposalId})">View</button>
                                        <button class="btn btn-outline-danger" onclick="deleteProposalFromTable(${proposalId}, '${proposalTitle}', 'Postponed')">Delete</button>
                                    </div>
                                `;
                            }
                            
                            // Update data attribute
                            tableRow.setAttribute('data-proposal-status', 'Postponed');
                        }
                        
                        // Do NOT reload proposals - just update the table row
                        // loadEventProposals();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error postponing proposal');
                }
            }

            // Filter events based on search and status
            function filterEvents() {
                const searchInput = document.getElementById('eventSearchInput').value.toLowerCase();
                const statusFilter = document.getElementById('eventStatusFilter').value;

                const rows = document.querySelectorAll('#eventsTableBody tr');
                let visibleCount = 0;

                rows.forEach(row => {
                    const eventName = row.querySelector('td:first-child')?.textContent.toLowerCase() || '';
                    const eventStatus = row.getAttribute('data-event-status') || '';

                    // Check search criteria
                    const matchesSearch = eventName.includes(searchInput);

                    // Check status filter
                    const matchesStatus = !statusFilter || eventStatus === statusFilter;

                    // Show or hide row based on all criteria
                    if (matchesSearch && matchesStatus) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Show "no results" message if no events match filters
                if (visibleCount === 0) {
                    const tbody = document.getElementById('eventsTableBody');
                    const messageRow = tbody.querySelector('.no-results-row');
                    if (!messageRow) {
                        tbody.innerHTML += '<tr class="no-results-row"><td colspan="6" class="text-center text-muted">No events match the selected filters</td></tr>';
                    }
                } else {
                    const messageRow = document.querySelector('.no-results-row');
                    if (messageRow) {
                        messageRow.remove();
                    }
                }
            }

            // Initialize event proposals management
            document.addEventListener('DOMContentLoaded', function () {
                loadEventProposals();
                populateEventTypeDropdown();

                // Reload when event proposals section is clicked
                const proposalsLink = document.querySelector('a[href="#event-proposals"]');
                if (proposalsLink) {
                    proposalsLink.addEventListener('click', function () {
                        setTimeout(() => loadEventProposals(), 100);
                    });
                }

                // Also populate event type when modal opens and set min date
                const newEventModal = document.getElementById('newEventModal');
                if (newEventModal) {
                    newEventModal.addEventListener('show.bs.modal', function () {
                        populateEventTypeDropdown();

                        // Set minimum date to tomorrow (proposal date must be in the future)
                        const dateInput = document.querySelector('[name="proposedDate"]');
                        if (dateInput) {
                            const tomorrow = new Date();
                            tomorrow.setDate(tomorrow.getDate() + 1);
                            const minDate = tomorrow.toISOString().split('T')[0];
                            dateInput.setAttribute('min', minDate);
                        }
                    });
                }
            });

            // ===== EVENT MANAGEMENT FUNCTIONS =====

            // Load all events
            async function loadEvents() {
                try {
                    const response = await fetch('api/get-events.php');
                    const data = await response.json();

                    if (data.success) {
                        populateEventsTable(data.events);
                    } else {
                        console.error('Error loading events:', data.message);
                        document.getElementById('eventsTableBody').innerHTML =
                            '<tr><td colspan="6" class="text-center text-danger">Error loading events</td></tr>';
                    }
                } catch (error) {
                    console.error('Error fetching events:', error);
                    document.getElementById('eventsTableBody').innerHTML =
                        '<tr><td colspan="6" class="text-center text-danger">Error loading events</td></tr>';
                }
            }

            function populateEventsTable(events) {
                const tbody = document.getElementById('eventsTableBody');

                if (!events || events.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No events scheduled</td></tr>';
                    return;
                }

                // Store all events for filtering
                window.allEvents = events;

                tbody.innerHTML = events.map(event => {
                    const eventDate = new Date(event.ProposedDate);
                    const dateStr = eventDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                    const timeStr = event.StartTime && event.EndTime ? `${event.StartTime} - ${event.EndTime}` : '-';

                    // Determine status - use event.status (lowercase) - this is what the API returns
                    let statusBadge = 'bg-secondary';
                    let statusDisplay = event.status || 'Unknown';

                    switch (event.status) {
                        case 'Scheduled':
                            statusBadge = 'bg-info'; // Blue
                            break;
                        case 'Completed':
                            statusBadge = 'bg-success'; // Green
                            break;
                        case 'Moved':
                            statusBadge = 'bg-warning'; // Yellow
                            break;
                        case 'Postponed':
                            statusBadge = 'bg-danger'; // Red
                            break;
                        case 'Ongoing':
                            statusBadge = 'bg-primary'; // Dark blue
                            break;
                        case 'Near':
                            statusBadge = 'bg-warning'; // Yellow
                            break;
                        default:
                            statusBadge = 'bg-secondary';
                    }

                    return `
                    <tr data-event-status="${event.status}" data-event-id="${event.EventID}">
                        <td>${event.Title || '-'}</td>
                        <td>
                            <div>${dateStr}</div>
                            <small class="text-muted">${timeStr}</small>
                        </td>
                        <td>${event.Venue || '-'}</td>
                        <td>${event.Capacity || '-'}</td>
                        <td><span class="badge ${statusBadge}">${statusDisplay}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="viewEmEvent(${event.EventID})">View</button>
                                ${event.status !== 'Postponed' && event.status !== 'Completed' ? `<button class="btn btn-outline-warning" onclick="postponeEvent(${event.EventID})">Postpone</button>` : ''}
                                ${event.status === 'Postponed' ? `<button class="btn btn-outline-danger" onclick="deleteEvent(${event.EventID}, '${event.Title}', '${event.status}')">Delete</button>` : ''}
                            </div>
                        </td>
                    </tr>
                `;
                }).join('');
            }

            // View event management details
            async function viewEmEvent(eventId) {
                try {
                    const response = await fetch(`api/get-event-details.php?id=${eventId}`);
                    const data = await response.json();

                    if (data.success) {
                        const event = data.event;

                        // Safe field setter
                        const setField = (id, value) => {
                            const elem = document.getElementById(id);
                            if (elem) {
                                if (elem.tagName === 'DIV') {
                                    elem.textContent = value || '-';
                                } else if (elem.tagName === 'TEXTAREA') {
                                    elem.textContent = value || '-';
                                } else {
                                    elem.value = value || '';
                                }
                            }
                        };

                        // Read-only fields
                        setField('emEventName', event.Title);
                        setField('emEventType', event.EventType);
                        setField('emEventDescription', event.Description);
                        setField('emEventBudget', event.BudgetEstimate);
                        setField('emEventEquipment', event.EquipmentNeeded);
                        setField('emEventObjectives', event.Objectives);
                        setField('emEventPartners', event.PartnersSponsor);
                        setField('emEventStatus', event.Status);

                        // Editable fields
                        setField('emEventDate', event.ProposedDate);
                        setField('emEventStartTime', event.StartTime);
                        setField('emEventEndTime', event.EndTime);
                        setField('emEventLocation', event.Venue);
                        setField('emEventCapacity', event.TargetParticipants);
                        setField('emEventStaffRequired', event.StaffRequired);

                        // Store event data
                        const form = document.getElementById('eventManagementForm');
                        if (form) {
                            form.dataset.eventId = eventId;
                            form.dataset.proposalId = event.ProposalID;
                            form.dataset.originalStatus = event.Status;
                        }

                        // Show delete button only if event status is Postponed
                        const deleteBtn = document.getElementById('emDeleteEventBtn');
                        if (deleteBtn) {
                            if (event.Status === 'Postponed') {
                                deleteBtn.style.display = 'block';
                                deleteBtn.dataset.eventTitle = event.Title;
                            } else {
                                deleteBtn.style.display = 'none';
                            }
                        }

                        // Reset to view mode
                        setEmEditMode(false);

                        const modal = new bootstrap.Modal(document.getElementById('editEventManagementModal'));
                        modal.show();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error loading event details');
                }
            }

            // Toggle edit mode for event management
            function toggleEmEditMode() {
                const form = document.getElementById('eventManagementForm');
                const isEditing = form.dataset.isEditing === 'true';
                setEmEditMode(!isEditing);
            }

            // Set edit mode
            function setEmEditMode(isEditing) {
                const form = document.getElementById('eventManagementForm');
                const editBtn = document.getElementById('emEditEventBtn');
                const saveBtn = document.getElementById('emSaveEventBtn');
                const cancelBtn = document.getElementById('emCancelEditBtn');

                const readOnlyFields = [
                    'emEventName', 'emEventType', 'emEventDescription', 'emEventBudget',
                    'emEventEquipment', 'emEventObjectives',
                    'emEventPartners', 'emEventStatus'
                ];

                const editableFields = [
                    'emEventDate', 'emEventStartTime', 'emEventEndTime',
                    'emEventLocation', 'emEventCapacity'
                ];

                // Set read-only fields always readonly
                readOnlyFields.forEach(id => {
                    const field = document.getElementById(id);
                    if (field) {
                        field.setAttribute('readonly', 'readonly');
                        if (field.classList) field.classList.add('bg-light');
                    }
                });

                // Toggle editable fields
                editableFields.forEach(id => {
                    const field = document.getElementById(id);
                    if (field) {
                        if (isEditing) {
                            field.removeAttribute('readonly');
                            field.classList.remove('bg-light');
                        } else {
                            field.setAttribute('readonly', 'readonly');
                            field.classList.add('bg-light');
                        }
                    }
                });

                // Toggle buttons
                if (isEditing) {
                    editBtn.style.display = 'none';
                    saveBtn.style.display = 'inline-block';
                    cancelBtn.style.display = 'inline-block';
                } else {
                    editBtn.style.display = 'inline-block';
                    saveBtn.style.display = 'none';
                    cancelBtn.style.display = 'none';
                }

                // Hide edit button for postponed events
                if (form.dataset.originalStatus === 'Postponed') {
                    editBtn.style.display = 'none';
                }

                form.dataset.isEditing = isEditing;
            }

            // Save event management changes
            async function saveEmEventChanges() {
                const form = document.getElementById('eventManagementForm');
                const eventId = form.dataset.eventId;

                if (!eventId) {
                    alert('Event ID not found');
                    return;
                }

                const eventDate = document.getElementById('emEventDate').value;
                const startTime = document.getElementById('emEventStartTime').value;
                const endTime = document.getElementById('emEventEndTime').value;
                const location = document.getElementById('emEventLocation').value;
                const capacity = document.getElementById('emEventCapacity').value;

                if (!eventDate || !startTime || !endTime || !location || !capacity) {
                    alert('Please fill in all required editable fields');
                    return;
                }

                if (endTime <= startTime) {
                    alert('End time must be after start time');
                    return;
                }

                try {
                    const eventDateTime = new Date(`${eventDate}T${startTime}`);
                    eventDateTime.setHours(eventDateTime.getHours() - 12);
                    const registrationDeadline = eventDateTime.toISOString().split('T')[0];

                    const response = await fetch('api/manage-event.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'update',
                            eventId: eventId,
                            eventDate: eventDate,
                            startTime: startTime,
                            endTime: endTime,
                            location: location,
                            capacity: capacity,
                            registrationDeadline: registrationDeadline
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Event updated successfully!');
                        bootstrap.Modal.getInstance(document.getElementById('editEventManagementModal')).hide();
                        loadEvents();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error updating event');
                }
            }

            // Update proposal date if it has passed
            async function updateProposalDate() {
                const newDate = document.getElementById('newProposalDate').value;
                const proposalId = document.getElementById('newProposalDate').dataset.proposalId;

                if (!newDate) {
                    alert('Please select a new date');
                    return;
                }

                const selectedDate = new Date(newDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (selectedDate < today) {
                    alert('Please select a date that has not passed');
                    return;
                }

                try {
                    const response = await fetch('api/manage-event-proposal.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'updateDate',
                            proposalId: parseInt(proposalId),
                            newDate: newDate
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Proposal date updated successfully!');
                        bootstrap.Modal.getInstance(document.getElementById('viewProposalModal')).hide();
                        loadEventProposals();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error updating proposal date');
                }
            }

            // Postpone event
            async function postponeEvent(eventId) {
                if (!confirm('Are you sure you want to postpone this event? All registered members will be removed.')) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-event.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'postpone',
                            eventId: eventId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Event postponed successfully! All registered members have been removed. Delete button is now available.');
                        
                        // Update the status field in the modal
                        const emEventStatus = document.getElementById('emEventStatus');
                        if (emEventStatus) {
                            emEventStatus.textContent = 'Postponed';
                        }
                        
                        // Update the form dataset
                        const form = document.getElementById('eventManagementForm');
                        if (form) {
                            form.dataset.originalStatus = 'Postponed';
                        }
                        
                        // Show delete button
                        const deleteBtn = document.getElementById('emDeleteEventBtn');
                        if (deleteBtn) {
                            deleteBtn.style.display = 'block';
                        }
                        
                        // Update table row - find and update the row for this event
                        const tableRow = document.querySelector(`tr[data-event-id="${eventId}"]`);
                        if (tableRow) {
                            // Update the status badge
                            const badgeCell = tableRow.querySelector('td:nth-child(5)');
                            if (badgeCell) {
                                badgeCell.innerHTML = '<span class="badge bg-danger">Postponed</span>';
                            }
                            
                            // Update the action buttons
                            const actionCell = tableRow.querySelector('td:nth-child(6)');
                            if (actionCell) {
                                const eventTitle = tableRow.querySelector('td:first-child').textContent;
                                actionCell.innerHTML = `
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="viewEmEvent(${eventId})">View</button>
                                        <button class="btn btn-outline-danger" onclick="deleteEvent(${eventId}, '${eventTitle}', 'Postponed')">Delete</button>
                                    </div>
                                `;
                            }
                            
                            // Update data attribute
                            tableRow.setAttribute('data-event-status', 'Postponed');
                        }
                        
                        // Close modal first if open
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editEventManagementModal'));
                        if (modal) modal.hide();
                        // Do NOT reload events - just update the table row
                        // setTimeout(() => loadEvents(), 300);
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error postponing event');
                }
            }

            // Delete event from management modal
            async function deleteEventFromModal() {
                const form = document.getElementById('eventManagementForm');
                if (!form) {
                    alert('Error: Form not found');
                    return;
                }

                const eventId = parseInt(form.dataset.eventId);
                if (!eventId || isNaN(eventId)) {
                    alert('Error: Event ID not found');
                    return;
                }

                // Get the status from the form dataset which is more reliable
                const eventStatus = form.dataset.originalStatus || document.getElementById('emEventStatus').textContent.trim();
                if (eventStatus !== 'Postponed') {
                    alert('Events can only be deleted if they are Postponed. Current status: ' + eventStatus + '.\\n\\nPlease postpone the event first.');
                    return;
                }

                const eventTitle = document.getElementById('emEventName').value;

                if (!confirm(`Are you sure you want to delete the event "${eventTitle}"? This action cannot be undone.`)) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-event.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'delete',
                            eventId: eventId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Event deleted successfully!');
                        bootstrap.Modal.getInstance(document.getElementById('editEventManagementModal')).hide();
                        loadEvents();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error deleting event');
                }
            }

            // Delete proposal
            async function deleteProposal(proposalId) {
                if (!proposalId) {
                    alert('Error: Proposal ID not found');
                    return;
                }

                const proposalStatus = document.getElementById('propStatus').textContent;
                if (proposalStatus !== 'Rejected' && proposalStatus !== 'Postponed') {
                    alert('Proposals can only be deleted if they are Rejected or Postponed. Current status: ' + proposalStatus);
                    return;
                }

                const proposalTitle = document.getElementById('propEventName').value;

                if (!confirm(`Are you sure you want to delete the proposal "${proposalTitle}"? This action cannot be undone.`)) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-event-proposal.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'delete',
                            proposalId: parseInt(proposalId)
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Proposal deleted successfully!');
                        bootstrap.Modal.getInstance(document.getElementById('viewProposalModal')).hide();
                        loadEventProposals();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error deleting proposal');
                }
            }

            // Delete proposal from table
            async function deleteProposalFromTable(proposalId, proposalTitle, proposalStatus) {
                // Check if proposal is Rejected or Postponed
                if (proposalStatus !== 'Rejected' && proposalStatus !== 'Postponed') {
                    alert('Proposals can only be deleted if they are Rejected or Postponed. Current status: ' + proposalStatus);
                    return;
                }

                if (!confirm(`Are you sure you want to delete the proposal "${proposalTitle}"? This action cannot be undone.`)) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-event-proposal.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'delete',
                            proposalId: parseInt(proposalId)
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Proposal deleted successfully!');
                        loadEventProposals();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error deleting proposal');
                }
            }

            // Delete event from events table
            async function deleteEvent(eventId, eventTitle, eventStatus) {
                // Check if event is Postponed
                if (eventStatus !== 'Postponed') {
                    alert('Events can only be deleted if they are Postponed. Current status: ' + eventStatus + '.\\n\\nPlease postpone the event first using the Postpone button.');
                    return;
                }

                if (!confirm(`Are you sure you want to delete the event "${eventTitle}"? This action cannot be undone.`)) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-event.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'delete',
                            eventId: eventId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Event deleted successfully!');
                        loadEvents();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error deleting event');
                }
            }

            // ===== INITIATIVES MANAGEMENT FUNCTIONS =====

            // Load all initiatives
            async function loadInitiatives() {
                try {
                    const response = await fetch('api/get-initiatives.php');
                    const data = await response.json();

                    if (data.success) {
                        populateInitiativesTable(data.initiatives);
                    } else {
                        console.error('Error:', data.message);
                        document.getElementById('initiativesTableBody').innerHTML =
                            '<tr><td colspan="6" class="text-center text-danger">Error loading initiatives</td></tr>';
                    }
                } catch (error) {
                    console.error('Error fetching initiatives:', error);
                    document.getElementById('initiativesTableBody').innerHTML =
                        '<tr><td colspan="6" class="text-center text-danger">Error loading initiatives</td></tr>';
                }
            }

            // Populate initiatives table
            function populateInitiativesTable(initiatives) {
                const tbody = document.getElementById('initiativesTableBody');

                if (!initiatives || initiatives.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No initiatives found. Create one to get started.</td></tr>';
                    return;
                }

                tbody.innerHTML = initiatives.map(initiative => {
                    const publishDate = new Date(initiative.PublishDate).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                    const lastModified = initiative.LastModifiedDate ?
                        new Date(initiative.LastModifiedDate).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        }) : publishDate;

                    const categoryBadge = {
                        'education': 'bg-info',
                        'environment': 'bg-success',
                        'community': 'bg-primary'
                    }[initiative.Category] || 'bg-secondary';

                    return `
                    <tr>
                        <td><strong>${escapeHtml(initiative.Title)}</strong></td>
                        <td>
                            <span class="badge bg-light text-dark">${escapeHtml(initiative.Category)}</span>
                        </td>
                        <td><span class="badge bg-success">Active</span></td>
                        <td>${lastModified}</td>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                    id="highlight_${initiative.InitiativeID}"
                                    ${initiative.isHighlighted ? 'checked' : ''}
                                    onchange="toggleInitiativeHighlight(${initiative.InitiativeID}, this.checked, '${escapeHtml(initiative.Title)}')">
                                <label class="form-check-label" for="highlight_${initiative.InitiativeID}">
                                    Pin to Highlights
                                </label>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editInitiative(${initiative.InitiativeID})">Edit</button>
                                <button class="btn btn-outline-danger" onclick="deleteInitiative(${initiative.InitiativeID}, '${escapeHtml(initiative.Title)}')">Remove</button>
                            </div>
                        </td>
                    </tr>
                `;
                }).join('');
            }

            // ===== CATEGORIES MANAGEMENT =====

            // Populate event type dropdown with categories
            async function populateEventTypeDropdown() {
                try {
                    const response = await fetch('api/get-categories.php');
                    const data = await response.json();

                    if (data.success && data.categories) {
                        const eventTypeSelect = document.getElementById('eventTypeSelect');
                        if (eventTypeSelect) {
                            // Clear existing options except the default one
                            eventTypeSelect.innerHTML = '<option value="">Select event type</option>';

                            // Add category options
                            data.categories.forEach(category => {
                                const option = document.createElement('option');
                                option.value = category.Type;
                                option.textContent = category.Type;
                                eventTypeSelect.appendChild(option);
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error loading event types:', error);
                }
            }

            // Load all categories
            async function loadCategories() {
                try {
                    // Verify the dropdown element exists
                    const categorySelect = document.getElementById('initiativeCategory');
                    if (!categorySelect) {
                        console.error('Category dropdown element not found!');
                        return;
                    }

                    const response = await fetch('api/get-categories.php');
                    const data = await response.json();

                    console.log('Category API response:', data);

                    if (data.success && data.categories && data.categories.length > 0) {
                        // Clear existing options
                        categorySelect.innerHTML = '<option value="">-- Select Category --</option>';

                        // Add each category as an option (use CategoryID as value, Type as display text)
                        data.categories.forEach(category => {
                            const option = document.createElement('option');
                            option.value = category.CategoryID;
                            option.textContent = category.Type;
                            categorySelect.appendChild(option);
                        });
                        console.log('Categories loaded successfully:', data.categories.length, 'categories');
                    } else {
                        console.warn('No categories returned from API', data);
                        categorySelect.innerHTML = '<option value="">-- No Categories Available --</option>';
                    }
                } catch (error) {
                    console.error('Error loading categories:', error);
                    const categorySelect = document.getElementById('initiativeCategory');
                    if (categorySelect) {
                        categorySelect.innerHTML = '<option value="">-- Error Loading Categories --</option>';
                    }
                }
            }

            // Load categories for admin settings table
            async function loadCategoriesAdmin() {
                try {
                    const response = await fetch('api/get-categories.php');
                    const data = await response.json();

                    if (data.success) {
                        populateCategoriesTable(data.categories || []);
                    } else {
                        alert('Error loading categories: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error loading categories:', error);
                    alert('Error loading categories');
                }
            }

            // Populate categories table
            function populateCategoriesTable(categories) {
                const tbody = document.getElementById('categoriesTableBody');

                if (!categories || categories.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No categories found</td></tr>';
                    return;
                }

                tbody.innerHTML = categories.map(category => `
                    <tr>
                        <td><input type="checkbox" class="category-checkbox" value="${category.CategoryID}"></td>
                        <td><strong>${category.CategoryID}</strong></td>
                        <td>${category.Type || category.CategoryName || '-'}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editCategory(${category.CategoryID}, '${category.CategoryName}', '${category.Type}')">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(${category.CategoryID}, '${category.CategoryName}')">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                `).join('');
            }

            // Open add category modal
            function openAddCategoryModal() {
                document.getElementById('categoryId').value = '';
                document.getElementById('categoryName').value = '';
                document.getElementById('categoryModalTitle').textContent = 'Add Category';
                const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
                modal.show();
            }

            // Toggle select all categories
            function toggleSelectAllCategories(checkbox) {
                const allCheckboxes = document.querySelectorAll('.category-checkbox');
                allCheckboxes.forEach(cb => cb.checked = checkbox.checked);
            }

            // Edit category
            function editCategory(categoryId, categoryName, categoryType) {
                document.getElementById('categoryId').value = categoryId;
                document.getElementById('categoryName').value = categoryType || categoryName;
                document.getElementById('categoryModalTitle').textContent = 'Edit Category';
                const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
                modal.show();
            }

            // Save category (create or update)
            async function saveCategory() {
                const categoryId = document.getElementById('categoryId').value.trim();
                const categoryName = document.getElementById('categoryName').value.trim();

                if (!categoryName) {
                    alert('Type is required');
                    return;
                }

                const action = categoryId ? 'update' : 'create';
                const payload = { action, categoryName };
                if (categoryId) payload.categoryId = parseInt(categoryId);

                try {
                    const response = await fetch('api/manage-category.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
                        loadCategoriesAdmin();
                        loadCategories();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error saving category:', error);
                    alert('Error saving category');
                }
            }

            // Delete category
            async function deleteCategory(categoryId, categoryName) {
                if (!confirm(`Are you sure you want to delete the category "${categoryName}"?`)) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-category.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'delete',
                            categoryId: parseInt(categoryId)
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        loadCategoriesAdmin();
                        loadCategories();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error deleting category:', error);
                    alert('Error deleting category');
                }
            }

            // Initialize categories management
            document.addEventListener('DOMContentLoaded', function () {
                // Load categories when admin-settings is accessed
                const adminSettingsLink = document.querySelector('a[href="#admin-settings"]');
                if (adminSettingsLink) {
                    adminSettingsLink.addEventListener('click', function () {
                        loadCategoriesAdmin();
                    });
                }
            });

            // ===== IMAGE UPLOAD HANDLERS =====

            // Handle initiative image uploads
            document.addEventListener('DOMContentLoaded', function () {
                const initiativeImageInput = document.getElementById('initiativeImageInput');
                if (initiativeImageInput) {
                    initiativeImageInput.addEventListener('change', function (e) {
                        handleInitiativeImageUpload(e.target.files);
                    });
                }

                const newsletterImageInput = document.getElementById('newsletterImageInput');
                if (newsletterImageInput) {
                    newsletterImageInput.addEventListener('change', function (e) {
                        handleNewsletterImageUpload(e.target.files);
                    });
                }

                // Load categories when page loads
                loadCategories();
            });

            // Upload initiative images
            async function handleInitiativeImageUpload(files) {
                const preview = document.getElementById('initiativeImagePreview');
                const pathsInput = document.getElementById('initiativeImagePaths');
                let uploadedPaths = pathsInput.value ? JSON.parse(pathsInput.value) : [];

                for (let file of files) {
                    if (!file.type.startsWith('image/')) {
                        alert('Please select valid image files');
                        continue;
                    }

                    if (file.size > 5 * 1024 * 1024) {
                        alert(`File ${file.name} exceeds 5MB limit`);
                        continue;
                    }

                    try {
                        const formData = new FormData();
                        formData.append('image', file);

                        const response = await fetch('api/upload-image.php', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            uploadedPaths.push({
                                path: result.imagePath,
                                name: result.fileName
                            });
                            addImagePreview(preview, result.imagePath, result.fileName, 'initiative', uploadedPaths);
                        } else {
                            alert('Upload failed: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Upload error:', error);
                        alert('Error uploading image');
                    }
                }

                pathsInput.value = JSON.stringify(uploadedPaths);
            }

            // Upload newsletter images
            async function handleNewsletterImageUpload(files) {
                const preview = document.getElementById('newsletterImagePreview');
                const pathsInput = document.getElementById('newsletterImagePaths');
                let uploadedPaths = pathsInput.value ? JSON.parse(pathsInput.value) : [];

                for (let file of files) {
                    if (!file.type.startsWith('image/')) {
                        alert('Please select valid image files');
                        continue;
                    }

                    if (file.size > 5 * 1024 * 1024) {
                        alert(`File ${file.name} exceeds 5MB limit`);
                        continue;
                    }

                    try {
                        const formData = new FormData();
                        formData.append('image', file);

                        const response = await fetch('api/upload-image.php', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            uploadedPaths.push({
                                path: result.imagePath,
                                name: result.fileName
                            });
                            addImagePreview(preview, result.imagePath, result.fileName, 'newsletter', uploadedPaths);
                        } else {
                            alert('Upload failed: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Upload error:', error);
                        alert('Error uploading image');
                    }
                }

                pathsInput.value = JSON.stringify(uploadedPaths);
            }

            // Add image preview card
            function addImagePreview(previewContainer, imagePath, fileName, type, allPaths) {
                const cardHtml = `
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm position-relative">
                        <img src="${imagePath}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Preview">
                        <div class="card-body p-2">
                            <small class="text-muted d-block text-truncate" title="${fileName}">${fileName}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" 
                                onclick="removeImagePreview(this, '${type}', '${imagePath}')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;

                previewContainer.insertAdjacentHTML('beforeend', cardHtml);
            }

            // Remove image from preview and paths
            function removeImagePreview(button, type, imagePath) {
                button.closest('.col-md-6').remove();

                const pathsInput = type === 'initiative'
                    ? document.getElementById('initiativeImagePaths')
                    : document.getElementById('newsletterImagePaths');

                let paths = pathsInput.value ? JSON.parse(pathsInput.value) : [];
                paths = paths.filter(p => p.path !== imagePath);
                pathsInput.value = JSON.stringify(paths);
            }

            // Remove existing image from preview and track for deletion
            function removeExistingImagePreview(button, type, imagePath) {
                button.closest('.col-md-6').remove();

                const existingInput = type === 'initiative'
                    ? document.getElementById('initiativeExistingImages')
                    : document.getElementById('newsletterExistingImages');

                let existingImages = existingInput.value ? JSON.parse(existingInput.value) : [];
                existingImages = existingImages.filter(img => img !== imagePath);
                existingInput.value = JSON.stringify(existingImages);
            }

            // Open add initiative modal
            async function openAddInitiativeModal() {
                console.log('Opening Add Initiative Modal');
                document.getElementById('initiativeModalTitle').textContent = 'Add New Initiative';
                document.getElementById('initiativeForm').reset();
                document.getElementById('initiativeId').value = '';
                document.getElementById('initiativeImagePaths').value = '[]';
                document.getElementById('initiativeExistingImages').value = '[]';
                document.getElementById('initiativeImagePreview').innerHTML = '';
                document.getElementById('initiativeImageInput').value = '';

                // Load categories first - ensure it completes before showing modal
                console.log('Loading categories...');
                await loadCategories();
                
                // Verify dropdown was populated
                const categorySelect = document.getElementById('initiativeCategory');
                console.log('Category dropdown options count:', categorySelect.options.length);
                
                // Show modal after categories are loaded
                console.log('Showing modal...');
                const modal = new bootstrap.Modal(document.getElementById('newInitiativeModal'));
                modal.show();
            }

            // Edit initiative
            async function editInitiative(initiativeId) {
                try {
                    console.log('Opening Edit Initiative Modal for ID:', initiativeId);
                    
                    // Load categories first and wait for it to complete
                    console.log('Loading categories...');
                    await loadCategories();

                    const response = await fetch(`api/get-initiatives.php?id=${initiativeId}`);
                    const data = await response.json();

                    console.log('Initiative data:', data);

                    if (data.success && data.initiatives && data.initiatives.length > 0) {
                        const initiative = data.initiatives[0];

                        document.getElementById('initiativeModalTitle').textContent = 'Edit Initiative';
                        document.getElementById('initiativeId').value = initiative.InitiativeID;
                        document.getElementById('initiativeTitle').value = initiative.Title;

                        // Set category - use CategoryID to match with dropdown values
                        const categorySelect = document.getElementById('initiativeCategory');
                        console.log('Category dropdown found:', !!categorySelect);
                        console.log('Initiative CategoryID:', initiative.CategoryID);
                        console.log('Available options:', categorySelect.options.length);
                        
                        if (categorySelect && initiative.CategoryID) {
                            // Find the option with matching CategoryID and select it
                            let found = false;
                            for (let i = 0; i < categorySelect.options.length; i++) {
                                if (parseInt(categorySelect.options[i].value) === parseInt(initiative.CategoryID)) {
                                    categorySelect.selectedIndex = i;
                                    categorySelect.value = initiative.CategoryID;
                                    found = true;
                                    console.log('Category selected at index:', i, 'Value:', categorySelect.value);
                                    break;
                                }
                            }
                            if (!found) {
                                console.warn('Category ID', initiative.CategoryID, 'not found in dropdown options');
                            }
                        }

                        document.getElementById('initiativeDescription').value = initiative.Description;
                        document.getElementById('pinToHighlights').checked = initiative.isHighlighted == 1;

                        // Clear previous images
                        document.getElementById('initiativeImagePreview').innerHTML = '';
                        document.getElementById('initiativeImageInput').value = '';

                        // Load existing images from database
                        const existingImages = initiative.images || [];
                        const imagePathsData = existingImages.map(path => ({
                            path: path,
                            name: path.split('/').pop(),
                            isExisting: true
                        }));

                        // Store existing images separately
                        document.getElementById('initiativeImagePaths').value = JSON.stringify(imagePathsData.filter(img => !img.isExisting));
                        document.getElementById('initiativeExistingImages').value = JSON.stringify(existingImages);

                        // Display existing images in preview
                        const preview = document.getElementById('initiativeImagePreview');
                        imagePathsData.forEach(img => {
                            const cardHtml = `
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border-0 shadow-sm position-relative">
                                    <img src="${img.path}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Preview">
                                    <div class="card-body p-2">
                                        <small class="text-muted d-block text-truncate" title="${img.name}">${img.name}</small>
                                        <small class="badge bg-info">Existing</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" 
                                            onclick="removeExistingImagePreview(this, 'initiative', '${img.path}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        `;
                            preview.insertAdjacentHTML('beforeend', cardHtml);
                        });

                        const modal = new bootstrap.Modal(document.getElementById('newInitiativeModal'));
                        modal.show();
                    } else {
                        alert('Error loading initiative');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error loading initiative: ' + error.message);
                }
            }
            

            // Save initiative (create or update)
            async function saveInitiative() {
                const initiativeId = document.getElementById('initiativeId').value;
                const title = document.getElementById('initiativeTitle').value.trim();
                const categoryIdStr = document.getElementById('initiativeCategory').value;
                const description = document.getElementById('initiativeDescription').value.trim();
                const isHighlighted = document.getElementById('pinToHighlights').checked ? 1 : 0;
                const imagePathsInput = document.getElementById('initiativeImagePaths').value;
                const imagePaths = imagePathsInput ? JSON.parse(imagePathsInput).filter(p => !p.isExisting).map(p => p.path) : [];
                const existingImages = document.getElementById('initiativeExistingImages').value ? JSON.parse(document.getElementById('initiativeExistingImages').value) : [];

                // Detailed validation
                if (!title) {
                    alert('Please enter an initiative title');
                    document.getElementById('initiativeTitle').focus();
                    return;
                }

                if (!categoryIdStr || categoryIdStr === '') {
                    alert('Please select a category from the dropdown');
                    document.getElementById('initiativeCategory').focus();
                    return;
                }

                const categoryId = parseInt(categoryIdStr);
                if (isNaN(categoryId) || categoryId <= 0) {
                    alert('Invalid category selected');
                    return;
                }

                if (!description) {
                    alert('Please enter a description');
                    document.getElementById('initiativeDescription').focus();
                    return;
                }

                // On create, require at least one image. On update, allow if either existing or new images exist
                if (!initiativeId && imagePaths.length === 0) {
                    alert('Please upload at least one image');
                    return;
                }

                if (initiativeId && imagePaths.length === 0 && existingImages.length === 0) {
                    alert('Please upload at least one image');
                    return;
                }

                try {
                    const action = initiativeId ? 'update' : 'create';
                    const payload = {
                        action: action,
                        title: title,
                        categoryId: categoryId,
                        description: description,
                        isHighlighted: isHighlighted,
                        imagePaths: imagePaths
                    };

                    if (initiativeId) {
                        payload.initiativeId = parseInt(initiativeId);
                        payload.existingImages = existingImages; // Send which images to keep
                    }

                    console.log('Sending payload:', payload); // Debug

                    const response = await fetch('api/manage-initiative.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert(result.message);
                        // Properly close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('newInitiativeModal'));
                        if (modal) {
                            modal.hide();
                        }
                        loadInitiatives();
                    } else {
                        alert('Error: ' + result.message);
                        console.error('API Error:', result);
                    }
                } catch (error) {
                    console.error('Error saving initiative:', error);
                    alert('Error saving initiative: ' + error.message);
                } finally {
                    // Ensure no modal backdrop is left behind
                    document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                }
            }

            // Delete initiative
            async function deleteInitiative(initiativeId, initiativeTitle) {
                if (!confirm(`Are you sure you want to delete "${initiativeTitle}"?`)) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-initiative.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            initiativeId: initiativeId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        loadInitiatives();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error deleting initiative:', error);
                    alert('Error deleting initiative');
                }
            }

            // Toggle initiative highlight
            async function toggleInitiativeHighlight(initiativeId, isHighlighted, title) {
                // Get current form data
                const categoryId = document.getElementById('initiativeCategory')?.value;
                const description = document.getElementById('initiativeDescription')?.value;

                // If we can't get current data from form, fetch it
                let finalCategoryId = categoryId;
                let finalDescription = description;

                if (!finalCategoryId || !finalDescription) {
                    try {
                        const response = await fetch(`api/get-initiatives.php?id=${initiativeId}`);
                        const data = await response.json();

                        if (data.success && data.initiatives && data.initiatives.length > 0) {
                            const initiative = data.initiatives[0];
                            finalCategoryId = initiative.CategoryID;
                            finalDescription = initiative.Description;
                        } else {
                            alert('Error loading initiative data');
                            return;
                        }
                    } catch (error) {
                        console.error('Error fetching initiative:', error);
                        alert('Error loading initiative');
                        return;
                    }
                }

                try {
                    const response = await fetch('api/manage-initiative.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'update',
                            initiativeId: parseInt(initiativeId),
                            title: title,
                            categoryId: parseInt(finalCategoryId),
                            description: finalDescription,
                            isHighlighted: isHighlighted ? 1 : 0,
                            imagePaths: [] // Keep existing images
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        const message = isHighlighted ?
                            `"${title}" pinned to highlights.` :
                            `"${title}" removed from highlights.`;
                        alert(message);
                        loadInitiatives(); // Reload to refresh the list
                    } else {
                        alert('Error: ' + data.message);
                        loadInitiatives(); // Reload to revert checkbox state
                    }
                } catch (error) {
                    console.error('Error toggling highlight:', error);
                    alert('Error updating highlight status');
                    loadInitiatives();
                }
            }

            // Helper function to escape HTML
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                // Convert to string in case text is null, undefined, or a number
                const str = String(text || '');
                return str.replace(/[&<>"']/g, m => map[m]);
            }

            // Initialize initiatives management
            document.addEventListener('DOMContentLoaded', function () {
                loadInitiatives();

                // Reload when content management section is clicked
                const contentLink = document.querySelector('a[href="#content"]');
                if (contentLink) {
                    contentLink.addEventListener('click', function () {
                        setTimeout(() => loadInitiatives(), 100);
                    });
                }

                // Cleanup modal backdrop when initiative modal is hidden
                const initiativeModal = document.getElementById('newInitiativeModal');
                if (initiativeModal) {
                    initiativeModal.addEventListener('hidden.bs.modal', function () {
                        // Remove any leftover backdrops
                        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                        // Restore body scroll if it was disabled
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    });
                }
            });

            // ===== ANNOUNCEMENTS MANAGEMENT FUNCTIONS =====

            // Load all announcements (from announcement table with proposal data)
            async function loadAnnouncements() {
                try {
                    const response = await fetch('api/get-announcements.php');
                    const data = await response.json();

                    if (data.success) {
                        populateAnnouncementsTable(data.announcements);
                    } else {
                        console.error('Error:', data.message);
                        document.getElementById('announcementsTableBody').innerHTML =
                            '<tr><td colspan="6" class="text-center text-danger">Error loading announcements</td></tr>';
                    }
                } catch (error) {
                    console.error('Error fetching announcements:', error);
                    document.getElementById('announcementsTableBody').innerHTML =
                        '<tr><td colspan="6" class="text-center text-danger">Error loading announcements</td></tr>';
                }
            }

            // Populate announcements table with proposal data
            function populateAnnouncementsTable(announcements) {
                const tbody = document.getElementById('announcementsTableBody');

                if (!announcements || announcements.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No announcements found. Create one from approved proposals.</td></tr>';
                    return;
                }

                tbody.innerHTML = announcements.map(announcement => {
                    const descriptionPreview = announcement.Description
                        ? announcement.Description.substring(0, 50) + (announcement.Description.length > 50 ? '...' : '')
                        : 'N/A';
                    const submittedBy = announcement.FName && announcement.LName
                        ? `${announcement.FName} ${announcement.LName}`
                        : 'Unknown';

                    return `
                    <tr>
                        <td><strong>${escapeHtml(announcement.Title)}</strong></td>
                        <td>${escapeHtml(descriptionPreview)}</td>
                        <td>${escapeHtml(submittedBy)}</td>
                        <td><span class="badge bg-info">${escapeHtml(announcement.Status)}</span></td>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" ${announcement.IsPriority == 1 ? 'checked' : ''} 
                                    onchange="toggleAnnouncementPriority(${announcement.AnnouncementID}, this.checked)">
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-danger" onclick="removeAnnouncement(${announcement.AnnouncementID}, '${escapeHtml(announcement.Title)}')">Remove</button>
                            </div>
                        </td>
                    </tr>
                `;
                }).join('');
            }

            // Toggle announcement priority
            async function toggleAnnouncementPriority(announcementId, isPriority) {
                try {
                    const response = await fetch('api/manage-announcement.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'update',
                            announcementId: announcementId,
                            IsPriority: isPriority ? 1 : 0
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        console.log('Priority updated successfully');
                    } else {
                        alert('Error: ' + result.message);
                        loadAnnouncements(); // Reload to reset checkbox
                    }
                } catch (error) {
                    console.error('Error toggling priority:', error);
                    alert('Error updating priority');
                    loadAnnouncements();
                }
            }

            // Remove announcement
            async function removeAnnouncement(announcementId, title) {
                if (!confirm(`Are you sure you want to remove "${title}" from announcements?`)) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-announcement.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            announcementId: announcementId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Announcement removed successfully');
                        loadAnnouncements();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error removing announcement:', error);
                    alert('Error removing announcement');
                }
            }

            // ===== NEWSLETTERS MANAGEMENT FUNCTIONS =====

            // Load all newsletters
            async function loadNewsletters() {
                try {
                    const response = await fetch('api/get-newsletters.php');
                    const data = await response.json();

                    if (data.success) {
                        populateNewslettersTable(data.newsletters);
                    } else {
                        console.error('Error:', data.message);
                        document.getElementById('newslettersTableBody').innerHTML =
                            '<tr><td colspan="5" class="text-center text-danger">Error loading newsletters</td></tr>';
                    }
                } catch (error) {
                    console.error('Error fetching newsletters:', error);
                    document.getElementById('newslettersTableBody').innerHTML =
                        '<tr><td colspan="5" class="text-center text-danger">Error loading newsletters</td></tr>';
                }
            }

            // Populate newsletters table
            function populateNewslettersTable(newsletters) {
                const tbody = document.getElementById('newslettersTableBody');

                if (!newsletters || newsletters.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No newsletters found. Create one to get started.</td></tr>';
                    return;
                }

                tbody.innerHTML = newsletters.map(newsletter => {
                    const publishDate = new Date(newsletter.PublishDate).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                    const contentPreview = newsletter.Content.substring(0, 50) + (newsletter.Content.length > 50 ? '...' : '');

                    return `
                    <tr>
                        <td><strong>${escapeHtml(newsletter.Title)}</strong></td>
                        <td>${escapeHtml(contentPreview)}</td>
                        <td>${publishDate}</td>
                        <td><span class="badge bg-success">Published</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editNewsletter(${newsletter.NewsletterID})">Edit</button>
                                <button class="btn btn-outline-danger" onclick="deleteNewsletter(${newsletter.NewsletterID}, '${escapeHtml(newsletter.Title)}')">Remove</button>
                            </div>
                        </td>
                    </tr>
                `;
                }).join('');
            }

            // Open add newsletter modal
            function openAddNewsletterModal() {
                // Clear previous state
                document.body.classList.remove('modal-open');
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());

                document.getElementById('newsletterModalTitle').textContent = 'New Newsletter';
                document.getElementById('newsletterForm').reset();
                document.getElementById('newsletterId').value = '';
                document.getElementById('newsletterImagePaths').value = '[]';
                document.getElementById('newsletterExistingImages').value = '[]';
                document.getElementById('newsletterImagePreview').innerHTML = '';
                document.getElementById('newsletterImageInput').value = '';

                // Create fresh modal instance
                const modalElement = document.getElementById('newNewsletterModal');
                const modal = new bootstrap.Modal(modalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
                modal.show();
            }

            // Edit newsletter
            async function editNewsletter(newsletterId) {
                try {
                    const response = await fetch(`api/get-newsletters.php?id=${newsletterId}`);
                    const data = await response.json();

                    if (data.success && data.newsletters && data.newsletters.length > 0) {
                        const newsletter = data.newsletters[0];

                        document.getElementById('newsletterModalTitle').textContent = 'Edit Newsletter';
                        document.getElementById('newsletterId').value = newsletter.NewsletterID;
                        document.getElementById('newsletterTitle').value = newsletter.Title;
                        document.getElementById('newsletterContent').value = newsletter.Content;

                        // Clear previous images
                        document.getElementById('newsletterImagePreview').innerHTML = '';
                        document.getElementById('newsletterImageInput').value = '';

                        // Load existing images from database
                        const existingImages = newsletter.images || [];
                        const imagePathsData = existingImages.map(path => ({
                            path: path,
                            name: path.split('/').pop(),
                            isExisting: true
                        }));

                        // Store existing images separately
                        document.getElementById('newsletterImagePaths').value = JSON.stringify(imagePathsData.filter(img => !img.isExisting));
                        document.getElementById('newsletterExistingImages').value = JSON.stringify(existingImages);

                        // Display existing images in preview
                        const preview = document.getElementById('newsletterImagePreview');
                        imagePathsData.forEach(img => {
                            const cardHtml = `
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border-0 shadow-sm position-relative">
                                    <img src="${img.path}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Preview">
                                    <div class="card-body p-2">
                                        <small class="text-muted d-block text-truncate" title="${img.name}">${img.name}</small>
                                        <small class="badge bg-info">Existing</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" 
                                            onclick="removeExistingImagePreview(this, 'newsletter', '${img.path}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        `;
                            preview.insertAdjacentHTML('beforeend', cardHtml);
                        });

                        const modal = new bootstrap.Modal(document.getElementById('newNewsletterModal'));
                        modal.show();
                    } else {
                        alert('Error loading newsletter');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error loading newsletter: ' + error.message);
                }
            }

            // Initialize newsletter management
            document.addEventListener('DOMContentLoaded', function () {
                loadNewsletters();

                // Prevent modal from staying disabled/greyed out
                const newNewsletterModal = document.getElementById('newNewsletterModal');
                if (newNewsletterModal) {
                    // Re-enable modal backdrop and content when hidden
                    newNewsletterModal.addEventListener('hidden.bs.modal', function () {
                        // Ensure modal is fully reset
                        document.body.classList.remove('modal-open');
                        const backdrops = document.querySelectorAll('.modal-backdrop');
                        backdrops.forEach(backdrop => backdrop.remove());

                        // Reset form state
                        document.getElementById('newsletterForm').reset();
                        document.getElementById('newsletterId').value = '';
                        document.getElementById('newsletterImagePaths').value = '[]';
                        document.getElementById('newsletterImagePreview').innerHTML = '';
                        document.getElementById('newsletterImageInput').value = '';
                    });
                }

                // Reload when newsletter section is clicked
                const newsletterLink = document.querySelector('a[href="#newsletter"]');
                if (newsletterLink) {
                    newsletterLink.addEventListener('click', function () {
                        loadNewsletters();
                    });
                }
            });

            // Save newsletter (create or update)
            async function saveNewsletter() {
                const newsletterId = document.getElementById('newsletterId').value;
                const title = document.getElementById('newsletterTitle').value.trim();
                const content = document.getElementById('newsletterContent').value.trim();
                const imagePathsInput = document.getElementById('newsletterImagePaths').value;
                const imagePaths = imagePathsInput ? JSON.parse(imagePathsInput).filter(p => !p.isExisting).map(p => p.path) : [];
                const existingImages = document.getElementById('newsletterExistingImages').value ? JSON.parse(document.getElementById('newsletterExistingImages').value) : [];

                // Validation
                if (!title) {
                    alert('Please enter a newsletter title');
                    document.getElementById('newsletterTitle').focus();
                    return;
                }

                if (!content) {
                    alert('Please enter content');
                    document.getElementById('newsletterContent').focus();
                    return;
                }

                // Images are optional for newsletters

                try {
                    const action = newsletterId ? 'update' : 'create';
                    const payload = {
                        action: action,
                        title: title,
                        content: content,
                        imagePaths: imagePaths
                    };

                    if (newsletterId) {
                        payload.newsletterId = parseInt(newsletterId);
                        payload.existingImages = existingImages; // Send which images to keep
                    }

                    console.log('Sending payload:', payload); // Debug

                    const response = await fetch('api/manage-newsletter.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert(result.message);
                        bootstrap.Modal.getInstance(document.getElementById('newNewsletterModal')).hide();
                        loadNewsletters();
                    } else {
                        alert('Error: ' + result.message);
                        console.error('API Error:', result);
                    }
                } catch (error) {
                    console.error('Error saving newsletter:', error);
                    alert('Error saving newsletter: ' + error.message);
                }
            }

            // Delete newsletter
            async function deleteNewsletter(newsletterId, title) {
                if (!confirm(`Are you sure you want to delete "${title}"?`)) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-newsletter.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            newsletterId: newsletterId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        loadNewsletters();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error deleting newsletter:', error);
                    alert('Error deleting newsletter');
                }
            }

            // Initialize announcements and newsletters
            document.addEventListener('DOMContentLoaded', function () {
                loadAnnouncements();
                loadNewsletters();

                // Reload when content management section is clicked
                const contentLink = document.querySelector('a[href="#content"]');
                if (contentLink) {
                    contentLink.addEventListener('click', function () {
                        setTimeout(() => {
                            loadAnnouncements();
                            loadNewsletters();
                            loadValues();
                            initializeIconPicker();
                            // load social links admin pane when content tab opens
                            if (typeof loadSocialLinksAdmin === 'function') loadSocialLinksAdmin();
                        }, 100);
                    });
                }
            });

            // ===== HERO SECTION MANAGEMENT FUNCTIONS =====

            // Load all hero sections
            async function loadHeroSections() {
                try {
                    const response = await fetch('api/get-hero-section.php');
                    const data = await response.json();

                    if (data.success) {
                        populateHeroTable(data.heroSections);
                    } else {
                        console.error('Error:', data.message);
                        document.getElementById('heroTableBody').innerHTML =
                            '<tr><td colspan="6" class="text-center text-danger">Error loading hero sections</td></tr>';
                    }
                } catch (error) {
                    console.error('Error fetching hero sections:', error);
                    document.getElementById('heroTableBody').innerHTML =
                        '<tr><td colspan="6" class="text-center text-danger">Error loading hero sections</td></tr>';
                }
            }

            // ===== MEMBER BENEFITS MANAGEMENT =====
            async function loadMemberBenefitsAdmin() {
                const tbody = document.getElementById('memberBenefitsTableBody');
                if (!tbody) return;

                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Loading member benefits...</td></tr>';

                try {
                    const res = await fetch('api/get-member-benefits.php');
                    const data = await res.json();

                    if (!data.success || !Array.isArray(data.benefits) || data.benefits.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No member benefits found.</td></tr>';
                        return;
                    }

                    tbody.innerHTML = data.benefits.map(b => {
                        const id = b.BenefitsID;
                        const title = escapeHtml(String(b.Title || ''));
                        const rawDesc = String(b.Description || '');
                        const descPreview = escapeHtml(rawDesc.length > 120 ? rawDesc.substring(0, 120) + '...' : rawDesc);
                        const activeBadge = (b.isActive == 1) ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
                        const titleArg = JSON.stringify(String(b.Title || ''));

                        return `
                            <tr>
                                <td>${title}</td>
                                <td>${descPreview}</td>
                                <td class="text-center">${activeBadge}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary me-1" onclick="editMemberBenefit(${id})"><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteMemberBenefit(${id}, ${titleArg})"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } catch (err) {
                    console.error('Error loading member benefits:', err);
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading member benefits</td></tr>';
                }
            }

            function openAddMemberBenefitModal() {
                document.getElementById('memberBenefitModalTitle').textContent = 'Add Benefit';
                document.getElementById('memberBenefitForm').reset();
                document.getElementById('benefitId').value = '';
                document.getElementById('benefitIsActive').checked = false;
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('memberBenefitModal'));
                modal.show();
            }

            async function editMemberBenefit(id) {
                try {
                    const res = await fetch(`api/get-member-benefits.php`);
                    const data = await res.json();
                    const b = (data.benefits || []).find(x => parseInt(x.BenefitsID) === parseInt(id));
                    if (!b) { alert('Benefit not found'); return; }

                    document.getElementById('memberBenefitModalTitle').textContent = 'Edit Benefit';
                    document.getElementById('benefitId').value = b.BenefitsID;
                    document.getElementById('benefitTitle').value = b.Title || '';
                    document.getElementById('benefitDescription').value = b.Description || '';
                    document.getElementById('benefitIsActive').checked = (b.isActive == 1);
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('memberBenefitModal'));
                    modal.show();
                } catch (e) { console.error(e); alert('Error loading benefit'); }
            }

            async function saveMemberBenefit() {
                const id = document.getElementById('benefitId').value.trim();
                const title = document.getElementById('benefitTitle').value.trim();
                const description = document.getElementById('benefitDescription').value.trim();
                const isActive = document.getElementById('benefitIsActive').checked ? 1 : 0;

                if (!title) { alert('Title required'); return; }

                const action = id ? 'update' : 'create';
                const payload = { action, title, description, isActive };
                if (id) payload.id = parseInt(id);

                try {
                    const res = await fetch('api/manage-member-benefits.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert(data.message);
                        // Hide the modal
                        const modalEl = document.getElementById('memberBenefitModal');
                        const modalInst = bootstrap.Modal.getInstance(modalEl);
                        if (modalInst) modalInst.hide();
                        // Reload table
                        if (typeof loadMemberBenefitsAdmin === 'function') loadMemberBenefitsAdmin();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Error saving benefit');
                }
            }

            async function deleteMemberBenefit(id, title) {
                if (!confirm(`Delete "${title}"?`)) return;
                try {
                    const res = await fetch('api/manage-member-benefits.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({ action: 'delete', id: parseInt(id) })
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert(data.message);
                        loadMemberBenefitsAdmin();
                    } else alert('Error: ' + data.message);
                } catch (e) { console.error(e); alert('Error deleting'); }
            }

            async function setActiveMemberBenefit(id, title) {
                try {
                    const res = await fetch('api/manage-member-benefits.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({ action: 'setActive', id: parseInt(id) })
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert(`"${title}" set as active`);
                        loadMemberBenefitsAdmin();
                    } else alert('Error: ' + data.message);
                } catch (e) { console.error(e); alert('Error setting active'); }
            }

            // Icon picker for member benefits
            function openBenefitIconPicker() {
                new bootstrap.Modal(document.getElementById('benefitIconPickerModal')).show();
            }

            function loadBenefitIconGrid(icons) {
                const container = document.getElementById('benefitIconGrid');
                if (!container) return;

                container.innerHTML = icons.map(iconClass => `
                    <div class="icon-item" data-icon="bi-${iconClass}" style="display: flex; align-items: center; justify-content: center; padding: 8px; cursor: pointer; border: 1px solid #e0e0e0; border-radius: 4px; transition: all 0.2s; background: white;" title="bi-${iconClass}">
                        <i class="bi bi-${iconClass}" style="font-size: 20px;"></i>
                    </div>
                `).join('');

                // Add click and hover effects
                document.querySelectorAll('#benefitIconGrid .icon-item').forEach(item => {
                    item.addEventListener('click', (e) => {
                        const iconClass = item.getAttribute('data-icon');
                        selectBenefitIcon(iconClass);
                    });

                    item.addEventListener('mouseover', () => {
                        item.style.backgroundColor = '#f0f7ff';
                        item.style.borderColor = '#035996';
                    });

                    item.addEventListener('mouseout', () => {
                        item.style.backgroundColor = 'white';
                        item.style.borderColor = '#e0e0e0';
                    });
                });
            }

            function selectBenefitIcon(iconClass) {
                // Update the input field
                document.getElementById('benefitIconClass').value = iconClass;
                
                // Update the preview
                document.getElementById('benefitIconPreview').innerHTML = `<i class="bi ${iconClass}"></i>`;
                
                // Close the icon picker modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('benefitIconPickerModal'));
                if (modal) modal.hide();
                
                console.log('Icon selected:', iconClass);
            }

            // Initialize icon picker for member benefits
            document.addEventListener('DOMContentLoaded', function () {
                // Initialize when icon picker modal is first shown
                const benefitIconModal = document.getElementById('benefitIconPickerModal');
                if (benefitIconModal) {
                    benefitIconModal.addEventListener('show.bs.modal', async function () {
                        // Check if icons are already loaded
                        if (document.getElementById('benefitIconGrid').innerHTML.trim() === '') {
                            try {
                                // Reuse the same icon list from values or load it
                                if (_bootstrapIcons && _bootstrapIcons.length > 0) {
                                    const popularKeywords = [
                                        'people', 'handshake', 'heart', 'star', 'lightbulb', 'target', 'book',
                                        'shield', 'chart', 'check', 'award', 'link', 'network', 'globe',
                                        'growth', 'trust', 'unity', 'vision', 'team', 'collaborate', 'gift',
                                        'medical', 'building', 'briefcase', 'graduation', 'bicycle'
                                    ];
                                    const popularIcons = _bootstrapIcons.filter(icon =>
                                        popularKeywords.some(keyword => icon.includes(keyword))
                                    ).slice(0, 100);
                                    loadBenefitIconGrid(popularIcons);

                                    // Set up search for benefit icon picker
                                    const searchInput = document.getElementById('benefitIconSearch');
                                    if (searchInput) {
                                        searchInput.addEventListener('input', function () {
                                            const query = this.value.toLowerCase();
                                            if (query.length === 0) {
                                                loadBenefitIconGrid(popularIcons);
                                            } else {
                                                const filtered = _bootstrapIcons.filter(icon => icon.includes(query));
                                                loadBenefitIconGrid(filtered.slice(0, 200));
                                            }
                                        });
                                    }
                                } else {
                                    console.log('Bootstrap icons not yet loaded, will load on initialization');
                                }
                            } catch (error) {
                                console.error('Error initializing benefit icon picker:', error);
                            }
                        }
                    });
                }
            });

            // Ensure loadMemberBenefitsAdmin() is invoked when Content Management tab is opened.
            // Call it on page load as well:
            document.addEventListener('DOMContentLoaded', function () {
                // Load member benefits when hero content tab is clicked
                const heroContentTab = document.querySelector('a[href="#hero-content"]');
                if (heroContentTab) {
                    heroContentTab.addEventListener('click', function () {
                        setTimeout(loadMemberBenefitsAdmin, 150);
                    });
                }
                
                // Also load when content link is clicked
                const contentLink = document.querySelector('a[href="#content"]');
                if (contentLink) {
                    contentLink.addEventListener('click', function () {
                        setTimeout(loadMemberBenefitsAdmin, 150);
                    });
                }
                
                // Initialize member benefits modal and ensure backdrop cleanup
                const memberBenefitModal = document.getElementById('memberBenefitModal');
                if (memberBenefitModal) {
                    memberBenefitModal.addEventListener('hidden.bs.modal', function () {
                        // Ensure backdrop is removed
                        const backdrops = document.querySelectorAll('.modal-backdrop');
                        backdrops.forEach(backdrop => backdrop.remove());
                        // Remove modal-open class from body
                        document.body.classList.remove('modal-open');
                    });
                }
                
                // Load benefits immediately on page load
                loadMemberBenefitsAdmin();
            });

            // Populate hero sections table
            function populateHeroTable(heroSections) {
                const tbody = document.getElementById('heroTableBody');

                if (!heroSections || heroSections.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hero sections found. Create one to get started.</td></tr>';
                    return;
                }

                tbody.innerHTML = heroSections.map(hero => {
                    const createdDate = new Date(hero.PublishDate).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                    const descPreview = hero.description.substring(0, 50) + (hero.description.length > 50 ? '...' : '');

                    return `
                    <tr>
                        <td><strong>${escapeHtml(hero.Title)}</strong></td>
                        <td>${escapeHtml(descPreview)}</td>
                        <td>${createdDate}</td>
                        <td><span class="badge bg-success">Active</span></td>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" 
                                    name="activeHero" 
                                    id="hero_${hero.heroID}"
                                    value="${hero.heroID}"
                                    ${hero.isActive ? 'checked' : ''}
                                    onchange="setActiveHero(${hero.heroID}, '${escapeHtml(hero.Title)}')">
                                <label class="form-check-label" for="hero_${hero.heroID}">
                                    Set Active
                                </label>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editHero(${hero.heroID})">Edit</button>
                                <button class="btn btn-outline-danger" onclick="deleteHero(${hero.heroID}, '${escapeHtml(hero.Title)}')">Delete</button>
                            </div>
                        </td>
                    </tr>
                `;
                }).join('');
            }

            // Open add hero modal
            function openAddHeroModal() {
                document.getElementById('heroModalTitle').textContent = 'Add New Hero Section';
                document.getElementById('heroForm').reset();
                document.getElementById('heroId').value = '';
                document.getElementById('heroSetAsActive').checked = false;
            }

            // Edit hero section
            async function editHero(heroId) {
                try {
                    const response = await fetch(`api/get-hero-section.php?id=${heroId}`);
                    const data = await response.json();

                    if (data.success && data.heroSections && data.heroSections.length > 0) {
                        const hero = data.heroSections[0];

                        document.getElementById('heroModalTitle').textContent = 'Edit Hero Section';
                        document.getElementById('heroId').value = hero.heroID;
                        document.getElementById('heroTitle').value = hero.Title;
                        document.getElementById('heroDescription').value = hero.description;
                        document.getElementById('heroSetAsActive').checked = hero.isActive;

                        const modal = new bootstrap.Modal(document.getElementById('newHeroModal'));
                        modal.show();
                    } else {
                        alert('Error loading hero section');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error loading hero section');
                }
            }

            // Save hero section (create or update)
            async function saveHero() {
                const heroId = document.getElementById('heroId').value.trim();
                const title = document.getElementById('heroTitle').value.trim();
                const description = document.getElementById('heroDescription').value.trim();
                const isActive = document.getElementById('heroSetAsActive').checked ? 1 : 0;

                if (!title || !description) {
                    alert('Please fill in all required fields');
                    return;
                }

                try {
                    const isUpdate = heroId && heroId !== '';
                    const action = isUpdate ? 'update' : 'create';
                    const payload = {
                        action: action,
                        title: title,
                        description: description,
                        isActive: isActive
                    };

                    // Only add heroId if updating and it's a valid number
                    if (isUpdate) {
                        payload.heroId = parseInt(heroId);
                    }

                    console.log('Sending payload:', payload);

                    const response = await fetch('api/manage-hero-section.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert(result.message);
                        bootstrap.Modal.getInstance(document.getElementById('newHeroModal')).hide();
                        loadHeroSections();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error saving hero section:', error);
                    alert('Error saving hero section: ' + error.message);
                }
            }

            // Delete hero section
            async function deleteHero(heroId, heroTitle) {
                if (!confirm(`Are you sure you want to delete "${heroTitle}"?`)) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-hero-section.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            heroId: heroId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        loadHeroSections();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error deleting hero section:', error);
                    alert('Error deleting hero section');
                }
            }

            // Set active hero section
            async function setActiveHero(heroId, heroTitle) {
                try {
                    const response = await fetch('api/manage-hero-section.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'setActive',
                            heroId: heroId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(`"${heroTitle}" is now the active hero section.`);
                        loadHeroSections();
                    } else {
                        alert('Error: ' + data.message);
                        loadHeroSections();
                    }
                } catch (error) {
                    console.error('Error setting active hero:', error);
                    alert('Error setting active hero section');
                    loadHeroSections();
                }
            }

            // ========== SOCIAL LINKS MANAGEMENT (Admin) ==========

            async function loadSocialLinksAdmin() {
                try {
                    const res = await fetch('api/manage-system-settings.php');
                    const data = await res.json();
                    if (!data.success) return;
                    const s = data.settings || {};

                    document.getElementById('socialFacebook').value = s.social_facebook || '';
                    document.getElementById('socialInstagram').value = s.social_instagram || '';
                    document.getElementById('socialLinkedIn').value = s.social_linkedin || '';
                    document.getElementById('socialEmail').value = s.social_email || '';
                } catch (e) {
                    console.error('Error loading social settings:', e);
                    alert('Error loading social links');
                }
            }

            async function saveSocialLinksAdmin() {
                const facebook = document.getElementById('socialFacebook').value.trim();
                const instagram = document.getElementById('socialInstagram').value.trim();
                const linkedin = document.getElementById('socialLinkedIn').value.trim();
                const email = document.getElementById('socialEmail').value.trim();

                const updates = [
                    { key: 'social_facebook', value: facebook },
                    { key: 'social_instagram', value: instagram },
                    { key: 'social_linkedin', value: linkedin },
                    { key: 'social_email', value: email }
                ];

                try {
                    for (const u of updates) {
                        await fetch('api/manage-system-settings.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify({ action: 'update', settingKey: u.key, settingValue: u.value, dataType: 'string' })
                        });
                    }
                    alert('Social links updated');
                    // refresh values in admin and front-end if necessary
                    loadSocialLinksAdmin();
                } catch (e) {
                    console.error('Error saving social links:', e);
                    alert('Error saving social links');
                }
            }

            // ========== MISSION & VISION MANAGEMENT ==========

            // Add bullet point to mission
            function addMissionBullet(value = '') {
                const container = document.getElementById('missionBulletsContainer');
                const bulletIndex = container.children.length;
                const bulletHtml = `
                <div class="input-group mb-2" data-bullet-index="${bulletIndex}">
                    <span class="input-group-text">●</span>
                    <textarea class="form-control" rows="2" placeholder="Enter mission point" required>${value}</textarea>
                    <button type="button" class="btn btn-outline-danger" onclick="removeMissionBullet(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
                container.insertAdjacentHTML('beforeend', bulletHtml);
            }

            // Remove bullet point from mission
            function removeMissionBullet(button) {
                button.closest('.input-group').remove();
            }

            // Add bullet point to vision
            function addVisionBullet(value = '') {
                const container = document.getElementById('visionBulletsContainer');
                const bulletIndex = container.children.length;
                const bulletHtml = `
                <div class="input-group mb-2" data-bullet-index="${bulletIndex}">
                    <span class="input-group-text">●</span>
                    <textarea class="form-control" rows="2" placeholder="Enter vision point" required>${value}</textarea>
                    <button type="button" class="btn btn-outline-danger" onclick="removeVisionBullet(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
                container.insertAdjacentHTML('beforeend', bulletHtml);
            }

            // Remove bullet point from vision
            function removeVisionBullet(button) {
                button.closest('.input-group').remove();
            }

            // Open edit mission modal
            async function openEditMissionModal() {
                try {
                    const response = await fetch('api/manage-about-us.php?type=mission');
                    const data = await response.json();

                    if (data.success && data.content) {
                        const mission = data.content;
                        document.getElementById('missionId').value = mission.id || '';
                        document.getElementById('missionTitleInput').value = mission.title || 'Our Mission';
                        document.getElementById('missionSubtitleInput').value = mission.subtitle || '';

                        // Clear and populate bullets
                        const container = document.getElementById('missionBulletsContainer');
                        container.innerHTML = '';
                        if (mission.bullets && mission.bullets.length > 0) {
                            mission.bullets.forEach(bullet => {
                                addMissionBullet(bullet);
                            });
                        } else {
                            addMissionBullet();
                        }
                    } else {
                        // Initialize empty form
                        document.getElementById('missionId').value = '';
                        document.getElementById('missionTitleInput').value = 'Our Mission';
                        document.getElementById('missionSubtitleInput').value = '';
                        document.getElementById('missionBulletsContainer').innerHTML = '';
                        addMissionBullet();
                    }
                } catch (error) {
                    console.error('Error loading mission:', error);
                    // Initialize empty form on error
                    document.getElementById('missionId').value = '';
                    document.getElementById('missionTitleInput').value = 'Our Mission';
                    document.getElementById('missionSubtitleInput').value = '';
                    document.getElementById('missionBulletsContainer').innerHTML = '';
                    addMissionBullet();
                }
            }

            // Open edit vision modal
            async function openEditVisionModal() {
                try {
                    const response = await fetch('api/manage-about-us.php?type=vision');
                    const data = await response.json();

                    if (data.success && data.content) {
                        const vision = data.content;
                        document.getElementById('visionId').value = vision.id || '';
                        document.getElementById('visionTitleInput').value = vision.title || 'Our Vision';
                        document.getElementById('visionSubtitleInput').value = vision.subtitle || '';

                        // Clear and populate bullets
                        const container = document.getElementById('visionBulletsContainer');
                        container.innerHTML = '';
                        if (vision.bullets && vision.bullets.length > 0) {
                            vision.bullets.forEach(bullet => {
                                addVisionBullet(bullet);
                            });
                        } else {
                            addVisionBullet();
                        }
                    } else {
                        // Initialize empty form
                        document.getElementById('visionId').value = '';
                        document.getElementById('visionTitleInput').value = 'Our Vision';
                        document.getElementById('visionSubtitleInput').value = '';
                        document.getElementById('visionBulletsContainer').innerHTML = '';
                        addVisionBullet();
                    }
                } catch (error) {
                    console.error('Error loading vision:', error);
                    // Initialize empty form on error
                    document.getElementById('visionId').value = '';
                    document.getElementById('visionTitleInput').value = 'Our Vision';
                    document.getElementById('visionSubtitleInput').value = '';
                    document.getElementById('visionBulletsContainer').innerHTML = '';
                    addVisionBullet();
                }
            }

            // Save mission
            async function saveMission() {
                const missionId = document.getElementById('missionId').value.trim();
                const title = document.getElementById('missionTitleInput').value.trim();
                const subtitle = document.getElementById('missionSubtitleInput').value.trim();

                // Collect bullet points
                const bullets = [];
                const bulletInputs = document.querySelectorAll('#missionBulletsContainer .form-control');
                bulletInputs.forEach(input => {
                    const value = input.value.trim();
                    if (value) {
                        bullets.push(value);
                    }
                });

                if (!title || !subtitle || bullets.length === 0) {
                    alert('Please fill in all required fields and add at least one mission point');
                    return;
                }

                try {
                    const payload = {
                        action: missionId ? 'update' : 'create',
                        type: 'mission',
                        title: title,
                        subtitle: subtitle,
                        bullets: bullets
                    };

                    if (missionId) {
                        payload.id = missionId;
                    }

                    const response = await fetch('api/manage-about-us.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert(result.message || 'Mission saved successfully');
                        bootstrap.Modal.getInstance(document.getElementById('editMissionModal')).hide();
                        loadMission();
                    } else {
                        alert('Error: ' + (result.message || 'Failed to save mission'));
                    }
                } catch (error) {
                    console.error('Error saving mission:', error);
                    alert('Error saving mission: ' + error.message);
                }
            }

            // Save vision
            async function saveVision() {
                const visionId = document.getElementById('visionId').value.trim();
                const title = document.getElementById('visionTitleInput').value.trim();
                const subtitle = document.getElementById('visionSubtitleInput').value.trim();

                // Collect bullet points
                const bullets = [];
                const bulletInputs = document.querySelectorAll('#visionBulletsContainer .form-control');
                bulletInputs.forEach(input => {
                    const value = input.value.trim();
                    if (value) {
                        bullets.push(value);
                    }
                });

                if (!title || !subtitle || bullets.length === 0) {
                    alert('Please fill in all required fields and add at least one vision point');
                    return;
                }

                try {
                    const payload = {
                        action: visionId ? 'update' : 'create',
                        type: 'vision',
                        title: title,
                        subtitle: subtitle,
                        bullets: bullets
                    };

                    if (visionId) {
                        payload.id = visionId;
                    }

                    const response = await fetch('api/manage-about-us.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert(result.message || 'Vision saved successfully');
                        bootstrap.Modal.getInstance(document.getElementById('editVisionModal')).hide();
                        loadVision();
                    } else {
                        alert('Error: ' + (result.message || 'Failed to save vision'));
                    }
                } catch (error) {
                    console.error('Error saving vision:', error);
                    alert('Error saving vision: ' + error.message);
                }
            }

            // ----- Core Values (Values) Management -----
            function openAddValueModal() {
                // Close icon picker if it's open
                const iconModal = document.getElementById('iconPickerModal');
                if (iconModal && iconModal.classList.contains('show')) {
                    const bsIconModal = bootstrap.Modal.getInstance(iconModal);
                    if (bsIconModal) bsIconModal.hide();
                }

                // Reset form
                document.getElementById('valueForm').reset();
                document.getElementById('valueId').value = '';
                document.getElementById('valueIcon').value = '';
                document.getElementById('valueIconPreview').innerHTML = '<i class="bi bi-square"></i>';
                document.getElementById('newValueModalLabel').textContent = 'Add Value';
                document.getElementById('valueActive').checked = true; // Default to active
                new bootstrap.Modal(document.getElementById('newValueModal')).show();
            }

            function editValue(id) {
                const v = _valuesCache.find(x => Number(x.value_id) === Number(id));
                if (!v) return alert('Value not found');
                document.getElementById('valueId').value = v.value_id;
                document.getElementById('valueTitle').value = v.title || '';
                document.getElementById('valueDescription').value = v.description || '';
                document.getElementById('valueIcon').value = v.icon_class || '';
                const iconClass = v.icon_class || 'bi-square';
                document.getElementById('valueIconPreview').innerHTML = `<i class="bi ${iconClass}"></i>`;
                document.getElementById('valueOrder').value = v.display_order || 1;
                document.getElementById('valueActive').checked = (v.is_active == 1 || v.is_active === '1');
                document.getElementById('newValueModalLabel').textContent = 'Edit Value';
                new bootstrap.Modal(document.getElementById('newValueModal')).show();
            }

            async function deleteValue(id) {
                if (!confirm('Delete this value?')) return;
                try {
                    const res = await fetch('api/manage-about-us.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', type: 'values', value_id: id })
                    });
                    const j = await res.json();
                    if (j.success) {
                        alert(j.message || 'Deleted');
                        loadValues();
                    } else alert('Error: ' + (j.message || 'Failed'));
                } catch (e) { console.error(e); alert('Request failed'); }
            }

            let _valuesCache = [];
            let _bootstrapIcons = [];

            // Initialize icon picker - load from official Bootstrap Icons JSON  
            async function initializeIconPicker() {
                try {
                    // Fetch the icons from the local JSON file
                    const response = await fetch('assets/bootstrap-icons-1.11.3/font/bootstrap-icons.json');
                    if (!response.ok) throw new Error('Failed to load icons');

                    const allIcons = await response.json();
                    _bootstrapIcons = Object.keys(allIcons).map(key => key.replace('bi-', ''));

                    // Initialize with popular organizational icons
                    const popularKeywords = [
                        'people', 'handshake', 'heart', 'star', 'lightbulb', 'target', 'book',
                        'shield', 'chart', 'check', 'award', 'link', 'network', 'globe',
                        'growth', 'trust', 'unity', 'vision', 'team', 'collaborate'
                    ];

                    const popularIcons = _bootstrapIcons.filter(icon =>
                        popularKeywords.some(keyword => icon.includes(keyword))
                    ).slice(0, 100);

                    loadValueIconGrid(popularIcons);

                    // Set up search functionality for embedded icon picker
                    const searchInput = document.getElementById('valueIconSearch');
                    if (searchInput) {
                        searchInput.addEventListener('input', function () {
                            const query = this.value.toLowerCase();
                            if (query.length === 0) {
                                loadValueIconGrid(popularIcons);
                            } else {
                                const filtered = _bootstrapIcons.filter(icon => icon.includes(query));
                                loadValueIconGrid(filtered.slice(0, 200));
                            }
                        });
                    }
                } catch (error) {
                    console.error('Error initializing icon picker:', error);
                }
            }

            function loadValueIconGrid(icons) {
                const container = document.getElementById('valueIconGrid');
                if (!container) return;

                container.innerHTML = icons.map(iconClass => `
                    <div class="icon-item" data-icon="bi-${iconClass}" style="display: flex; align-items: center; justify-content: center; padding: 8px; cursor: pointer; border: 1px solid #e0e0e0; border-radius: 4px; transition: all 0.2s; background: white;" title="bi-${iconClass}">
                        <i class="bi bi-${iconClass}" style="font-size: 24px;"></i>
                    </div>
                `).join('');

                // Add click and hover effects
                document.querySelectorAll('#valueIconGrid .icon-item').forEach(item => {
                    item.addEventListener('click', (e) => {
                        const iconClass = item.getAttribute('data-icon');
                        selectIcon(iconClass);
                    });

                    item.addEventListener('mouseover', () => {
                        item.style.backgroundColor = '#f0f7ff';
                        item.style.borderColor = '#035996';
                    });

                    item.addEventListener('mouseout', () => {
                        item.style.backgroundColor = 'white';
                        item.style.borderColor = '#e0e0e0';
                    });
                });
            }

            function loadIconGrid(icons) {
                const container = document.getElementById('iconGridContainer');
                if (!container) return;

                container.innerHTML = '';

                if (icons.length === 0) {
                    container.innerHTML = '<p class="text-muted text-center" style="grid-column: 1/-1;">No icons found</p>';
                    return;
                }

                icons.forEach(iconClass => {
                    const div = document.createElement('div');
                    div.style.cssText = 'text-align: center; padding: 10px; border-radius: 6px; cursor: pointer; transition: 0.2s; border: 1px solid #ddd;';

                    // Create icon element with error handling
                    const iconHtml = `<i class="bi ${iconClass}" style="font-size: 1.8rem;"></i>`;
                    div.innerHTML = iconHtml;
                    div.title = iconClass;

                    div.addEventListener('mouseenter', () => {
                        div.style.backgroundColor = '#e7f1fb';
                        div.style.borderColor = '#035996';
                    });

                    div.addEventListener('mouseleave', () => {
                        div.style.backgroundColor = '';
                        div.style.borderColor = '#ddd';
                    });

                    div.addEventListener('click', () => {
                        selectIcon(iconClass);
                    });

                    container.appendChild(div);
                });
            }

            async function loadValues() {
                try {
                    const res = await fetch('api/manage-about-us.php?type=values');
                    const j = await res.json();
                    const tbody = document.getElementById('valuesTableBody');
                    if (!j.success || !Array.isArray(j.values) || j.values.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No values found.</td></tr>';
                        _valuesCache = [];
                        return;
                    }
                    _valuesCache = j.values;
                    tbody.innerHTML = '';
                    j.values.forEach(v => {
                        const status = (v.is_active == 1 || v.is_active === '1') ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
                        const iconHtml = v.icon_class ? `<i class="${escapeHtml(v.icon_class)}"></i> ${escapeHtml(v.icon_class)}` : '';
                        tbody.innerHTML += `\
                            <tr>\
                                <td>${escapeHtml(v.title || '')}</td>\
                                <td style="max-width:320px;">${escapeHtml(v.description || '')}</td>\
                                <td>${iconHtml}</td>\
                                <td>${escapeHtml(v.display_order || '')}</td>\
                                <td>${status}</td>\
                                <td>\
                                    <button class="btn btn-sm btn-primary me-1" onclick="editValue(${v.value_id})"><i class="bi bi-pencil"></i></button>\
                                    <button class="btn btn-sm btn-danger" onclick="deleteValue(${v.value_id})"><i class="bi bi-trash"></i></button>\
                                </td>\
                            </tr>`;
                    });
                } catch (e) { console.error('loadValues error', e); }
            }

            async function saveValue() {
                const id = document.getElementById('valueId').value;
                const title = document.getElementById('valueTitle').value.trim();
                const description = document.getElementById('valueDescription').value.trim();
                const icon = document.getElementById('valueIcon').value.trim();
                const order = Number(document.getElementById('valueOrder').value || 1);
                const isActive = document.getElementById('valueActive').checked ? 1 : 0;

                if (!title) {
                    alert('Title is required');
                    return;
                }

                const payload = {
                    action: id ? 'update' : 'create',
                    type: 'values',
                    title: title,
                    description: description,
                    icon_class: icon,
                    display_order: order,
                    is_active: isActive
                };
                if (id) payload.value_id = id;

                try {
                    const res = await fetch('api/manage-about-us.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const j = await res.json();
                    if (j.success) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('newValueModal'));
                        if (modal) modal.hide();
                        alert(j.message || 'Saved successfully');
                        loadValues();
                    } else {
                        alert('Error: ' + (j.message || 'Failed to save'));
                    }
                } catch (e) {
                    console.error('Save error:', e);
                    alert('Request failed: ' + e.message);
                }
            }

            function selectIcon(iconClass) {
                // Update the input field with the selected icon class
                const iconInput = document.getElementById('valueIcon');
                if (iconInput) {
                    iconInput.value = iconClass;
                }

                // Update the preview with the selected icon
                const preview = document.getElementById('valueIconPreview');
                if (preview) {
                    preview.innerHTML = `<i class="bi ${iconClass}"></i>`;
                }

                console.log('Icon selected:', iconClass);
            }

            // expose global functions used by inline handlers
            window.openAddValueModal = openAddValueModal;
            window.editValue = editValue;
            window.deleteValue = deleteValue;
            document.addEventListener('DOMContentLoaded', function () {
                loadValues();
                initializeIconPicker();
            });

            // Load and display mission
            async function loadMission() {
                try {
                    const response = await fetch('api/manage-about-us.php?type=mission');
                    const data = await response.json();

                    if (data.success && data.content) {
                        const mission = data.content;
                        document.getElementById('missionTitle').textContent = mission.title || 'Our Mission';
                        document.getElementById('missionSubtitle').textContent = mission.subtitle || '';

                        const bulletsContainer = document.getElementById('missionBullets');
                        if (mission.bullets && mission.bullets.length > 0) {
                            bulletsContainer.innerHTML = mission.bullets.map(bullet =>
                                `<li class="mb-2"><span class="me-2">●</span>${escapeHtml(bullet)}</li>`
                            ).join('');
                        } else {
                            bulletsContainer.innerHTML = '<li class="text-muted">No mission points defined yet.</li>';
                        }
                    } else {
                        document.getElementById('missionTitle').textContent = 'Our Mission';
                        document.getElementById('missionSubtitle').textContent = 'No mission defined yet.';
                        document.getElementById('missionBullets').innerHTML = '<li class="text-muted">Click "Edit Mission" to add content.</li>';
                    }
                } catch (error) {
                    console.error('Error loading mission:', error);
                    document.getElementById('missionTitle').textContent = 'Our Mission';
                    document.getElementById('missionSubtitle').textContent = 'Error loading mission content.';
                    document.getElementById('missionBullets').innerHTML = '<li class="text-danger">Error loading content.</li>';
                }
            }

            // Load and display vision
            async function loadVision() {
                try {
                    const response = await fetch('api/manage-about-us.php?type=vision');
                    const data = await response.json();

                    if (data.success && data.content) {
                        const vision = data.content;
                        document.getElementById('visionTitle').textContent = vision.title || 'Our Vision';
                        document.getElementById('visionSubtitle').textContent = vision.subtitle || '';

                        const bulletsContainer = document.getElementById('visionBullets');
                        if (vision.bullets && vision.bullets.length > 0) {
                            bulletsContainer.innerHTML = vision.bullets.map(bullet =>
                                `<li class="mb-2"><span class="me-2">●</span>${escapeHtml(bullet)}</li>`
                            ).join('');
                        } else {
                            bulletsContainer.innerHTML = '<li class="text-muted">No vision points defined yet.</li>';
                        }
                    } else {
                        document.getElementById('visionTitle').textContent = 'Our Vision';
                        document.getElementById('visionSubtitle').textContent = 'No vision defined yet.';
                        document.getElementById('visionBullets').innerHTML = '<li class="text-muted">Click "Edit Vision" to add content.</li>';
                    }
                } catch (error) {
                    console.error('Error loading vision:', error);
                    document.getElementById('visionTitle').textContent = 'Our Vision';
                    document.getElementById('visionSubtitle').textContent = 'Error loading vision content.';
                    document.getElementById('visionBullets').innerHTML = '<li class="text-danger">Error loading content.</li>';
                }
            }

            // Initialize hero section management
            document.addEventListener('DOMContentLoaded', function () {
                loadHeroSections();

                // Reload when content management section is clicked
                const contentLink = document.querySelector('a[href="#content"]');
                if (contentLink) {
                    contentLink.addEventListener('click', function () {
                        setTimeout(() => loadHeroSections(), 100);
                    });
                }
            });

            // Initialize Mission and Vision
            document.addEventListener('DOMContentLoaded', function () {
                // Load mission and vision on page load if About Us tab is active
                loadMission();
                loadVision();
                loadValues();

                // Load when About Us tab is clicked
                const aboutUsTab = document.querySelector('button[data-bs-target="#about-us-content"]');
                if (aboutUsTab) {
                    aboutUsTab.addEventListener('shown.bs.tab', function () {
                        loadMission();
                        loadVision();
                        loadValues();
                    });
                }

                // Load when Mission/Vision/Values pills are clicked
                const missionPill = document.querySelector('button[data-bs-target="#mission-content"]');
                const visionPill = document.querySelector('button[data-bs-target="#vision-content"]');
                const valuesPill = document.querySelector('button[data-bs-target="#values-content"]');

                if (missionPill) {
                    missionPill.addEventListener('shown.bs.tab', function () {
                        loadMission();
                    });
                }

                if (visionPill) {
                    visionPill.addEventListener('shown.bs.tab', function () {
                        loadVision();
                    });
                }

                if (valuesPill) {
                    valuesPill.addEventListener('shown.bs.tab', function () {
                        loadValues();
                    });
                }
            });

            // Initialize events
            document.addEventListener('DOMContentLoaded', function () {
                loadEvents();

                const eventsLink = document.querySelector('a[href="#events"]');
                if (eventsLink) {
                    eventsLink.addEventListener('click', function () {
                        setTimeout(() => loadEvents(), 100);
                    });
                }
            });

            // ===== DASHBOARD MANAGEMENT FUNCTIONS =====

            // Load dashboard statistics
            async function loadDashboardStats() {
                try {
                    // Load members count
                    const membersResponse = await fetch('api/get-members.php');
                    const membersData = await membersResponse.json();
                    if (membersData.success) {
                        const memberCount = membersData.members ? membersData.members.length : 0;
                        document.getElementById('dashboardTotalMembers').textContent = memberCount;
                        document.getElementById('dashboardMembersSubtext').textContent = memberCount + ' active members';
                    }

                    // Load initiatives count
                    const initiativesResponse = await fetch('api/get-initiatives.php');
                    const initiativesData = await initiativesResponse.json();
                    if (initiativesData.success) {
                        const initiativeCount = initiativesData.initiatives ? initiativesData.initiatives.length : 0;
                        document.getElementById('dashboardActiveInitiatives').textContent = initiativeCount;
                        document.getElementById('dashboardInitiativesSubtext').textContent = initiativeCount + ' active initiatives';
                    }

                    // Load pending applications count
                    const applicationsResponse = await fetch('api/get-applications.php');
                    const applicationsData = await applicationsResponse.json();
                    if (applicationsData.success) {
                        const pendingCount = applicationsData.count || 0;
                        document.getElementById('dashboardPendingApplications').textContent = pendingCount;

                        // Populate recent applications
                        const recentAppsList = document.getElementById('recentApplicationsList');
                        if (applicationsData.applications && applicationsData.applications.length > 0) {
                            const recentApps = applicationsData.applications.slice(0, 3);
                            recentAppsList.innerHTML = recentApps.map(app => `
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">${app.FName} ${app.LName}</h6>
                                    <small>${new Date(app.SubmissionDate).toLocaleDateString()}</small>
                                </div>
                                <p class="mb-1">Applied for membership</p>
                            </a>
                        `).join('');
                        } else {
                            recentAppsList.innerHTML = '<p class="text-muted">No pending applications</p>';
                        }
                    }

                    // Load event proposals count
                    const proposalsResponse = await fetch('api/get-event-proposals.php');
                    const proposalsData = await proposalsResponse.json();
                    if (proposalsData.success) {
                        const proposalCount = proposalsData.count || 0;
                        const pendingProposals = proposalsData.proposals ?
                            proposalsData.proposals.filter(p => p.Status === 'Pending').length : 0;

                        document.getElementById('dashboardEventProposals').textContent = proposalCount;
                        document.getElementById('dashboardProposalsSubtext').textContent =
                            pendingProposals + ' awaiting approval';
                    }

                    // Load upcoming events for admin dashboard main view (show nearest events only)
                    // This uses dashboardEventsList which is populated by loadUpcomingEvents() from member-dashboard-shared.js
                    // The loadUpcomingEvents() function already handles showing nearest events, so we don't need to duplicate it here

                } catch (error) {
                    console.error('Error loading dashboard stats:', error);
                }
            }

            // ===== SYSTEM BACKUP & MAINTENANCE FUNCTIONS =====

            // Load system statistics
            async function loadSystemStats() {
                try {
                    const response = await fetch('api/manage-system-maintenance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'include',
                        body: JSON.stringify({
                            action: 'getSystemStats'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        updateSystemStatsDisplay(data.stats);
                    }
                } catch (error) {
                    console.error('Error loading system stats:', error);
                }
            }

            // Update system stats display
            function updateSystemStatsDisplay(stats) {
                // Update stats if elements exist
                const dbSizeElem = document.getElementById('databaseSize');
                const tableCountElem = document.getElementById('tableCount');
                const backupCountElem = document.getElementById('backupCount');
                const phpVersionElem = document.getElementById('phpVersion');

                if (dbSizeElem) dbSizeElem.textContent = stats.databaseSize;
                if (tableCountElem) tableCountElem.textContent = stats.tableCount;
                if (backupCountElem) backupCountElem.textContent = stats.backupCount;
                if (phpVersionElem) phpVersionElem.textContent = stats.phpVersion;
            }

            // Create system backup
            async function createSystemBackup() {
                const button = event.target;
                const originalText = button.textContent;

                button.disabled = true;
                button.textContent = 'Creating Backup...';

                try {
                    const response = await fetch('api/manage-system-maintenance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'backup'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(`Backup created successfully!\nFile: ${data.fileName}\nSize: ${data.fileSize}`);
                        loadBackupsList();
                        loadSystemStats();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error creating backup:', error);
                    alert('Error creating backup');
                } finally {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            }

            // Load backups list
            async function loadBackupsList() {
                try {
                    const response = await fetch('api/manage-system-maintenance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'listBackups'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        displayBackupsList(data.backups);
                    }
                } catch (error) {
                    console.error('Error loading backups:', error);
                }
            }

            // Display backups list
            function displayBackupsList(backups) {
                const container = document.getElementById('backupsList');

                if (!container) return;

                if (backups.length === 0) {
                    container.innerHTML = '<p class="text-muted">No backups found</p>';
                    return;
                }

                container.innerHTML = backups.map(backup => `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${backup.name}</h6>
                        <small class="text-muted">
                            Created: ${backup.created} | Size: ${backup.size}
                        </small>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="downloadBackup('${backup.name}')">
                            <i class="bi bi-download"></i> Download
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteBackup('${backup.name}')">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            `).join('');
            }

            // Download backup
            function downloadBackup(fileName) {
                const backupDir = 'backups/';
                const link = document.createElement('a');
                link.href = backupDir + fileName;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            // Delete backup
            async function deleteBackup(fileName) {
                if (!confirm(`Are you sure you want to delete "${fileName}"?`)) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-system-maintenance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'deleteBackup',
                            fileName: fileName
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Backup deleted successfully');
                        loadBackupsList();
                        loadSystemStats();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error deleting backup:', error);
                    alert('Error deleting backup');
                }
            }

            // Clear system cache
            async function clearSystemCache() {
                if (!confirm('Are you sure you want to clear the system cache?')) {
                    return;
                }

                try {
                    const response = await fetch('api/manage-system-maintenance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'clearCache'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        loadSystemStats();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error clearing cache:', error);
                    alert('Error clearing cache');
                }
            }

            // Confirm system reset
            function confirmSystemReset() {
                const modal = document.createElement('div');
                modal.innerHTML = `
                <div class="modal fade" id="resetConfirmModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content border-danger">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> System Reset</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>WARNING: This action cannot be undone!</strong></p>
                                <p>Resetting the system will:</p>
                                <ul>
                                    <li>Clear all cached data</li>
                                    <li>Reset all system settings to default</li>
                                    <li>Remove temporary files</li>
                                </ul>
                                <p><strong>Database data will NOT be deleted.</strong></p>
                                <p>Are you absolutely sure you want to proceed?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" onclick="performSystemReset()">Yes, Reset System</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

                document.body.appendChild(modal);
                const resetModal = new bootstrap.Modal(document.getElementById('resetConfirmModal'));
                resetModal.show();

                // Clean up modal after hiding
                document.getElementById('resetConfirmModal').addEventListener('hidden.bs.modal', function () {
                    modal.remove();
                });
            }

            // Perform system reset
            async function performSystemReset() {
                try {
                    const response = await fetch('api/manage-system-maintenance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'clearCache'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('System reset completed successfully');
                        bootstrap.Modal.getInstance(document.getElementById('resetConfirmModal')).hide();
                        loadSystemStats();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error resetting system:', error);
                    alert('Error resetting system');
                }
            }

            // Initialize system maintenance on page load
            document.addEventListener('DOMContentLoaded', function () {
                loadSystemStats();
                loadBackupsList();

                // Add event listeners to backup buttons
                const backupNowBtn = Array.from(document.querySelectorAll('button')).find(btn => btn.textContent.includes('Backup Now'));
                if (backupNowBtn) {
                    backupNowBtn.onclick = createSystemBackup;
                }

                // Reload when admin settings section is clicked
                const settingsLink = document.querySelector('a[href="#admin-settings"]');
                if (settingsLink) {
                    settingsLink.addEventListener('click', function () {
                        setTimeout(() => {
                            loadSystemSettings();
                            loadSystemStats();
                            loadBackupsList();
                        }, 100);
                    });
                }
            });

            // ===== REPORTS MANAGEMENT FUNCTIONS =====

            // Load all reports
            async function loadReports() {
                const dateRange = document.getElementById('dateRangeSelect').value;
                let startDate, endDate;

                if (dateRange === 'custom') {
                    startDate = document.getElementById('reportStartDate').value;
                    endDate = document.getElementById('reportEndDate').value;

                    if (!startDate || !endDate) {
                        alert('Please select both start and end dates');
                        return;
                    }
                } else {
                    endDate = new Date().toISOString().split('T')[0];
                    startDate = new Date();
                    startDate.setDate(startDate.getDate() - parseInt(dateRange));
                    startDate = startDate.toISOString().split('T')[0];
                }

                try {
                    // Load summary
                    const summaryResponse = await fetch(`api/get-reports.php?type=summary&startDate=${startDate}&endDate=${endDate}`, {
                        credentials: 'include'
                    });
                    const summaryData = await summaryResponse.json();
                    if (summaryData.success) {
                        updateSummaryCards(summaryData.summary);
                    }

                    // Load events report
                    const eventsResponse = await fetch(`api/get-reports.php?type=events&startDate=${startDate}&endDate=${endDate}`, {
                        credentials: 'include'
                    });
                    const eventsData = await eventsResponse.json();
                    if (eventsData.success) {
                        populateEventsReport(eventsData.data);
                    }

                    // Load members report
                    const membersResponse = await fetch(`api/get-reports.php?type=members&startDate=${startDate}&endDate=${endDate}`, {
                        credentials: 'include'
                    });
                    const membersData = await membersResponse.json();
                    if (membersData.success) {
                        populateMembersReport(membersData.data);
                    }

                    // Load initiatives report
                    const initiativesResponse = await fetch(`api/get-reports.php?type=initiatives&startDate=${startDate}&endDate=${endDate}`, {
                        credentials: 'include'
                    });
                    const initiativesData = await initiativesResponse.json();
                    console.log('Initiatives API Response:', initiativesData); // Debug log
                    if (initiativesData.success) {
                        populateInitiativesReportTable(initiativesData.data);
                    } else {
                        console.error('Initiatives API Error:', initiativesData.message);
                        document.getElementById('initiativesReportBody').innerHTML = `<tr><td colspan="4" class="text-center text-danger">Error: ${initiativesData.message || 'Failed to load initiatives'}</td></tr>`;
                    }

                    // Load trends
                    const trendsResponse = await fetch(`api/get-reports.php?type=trends&startDate=${startDate}&endDate=${endDate}`, {
                        credentials: 'include'
                    });
                    const trendsData = await trendsResponse.json();
                    if (trendsData.success) {
                        displayTrendsChart(trendsData.data);
                    }
                } catch (error) {
                    console.error('Error loading reports:', error);
                    alert('Error loading reports. Please try again.');
                }
            }

            // Update summary cards
            function updateSummaryCards(summary) {
                const formatChange = (value) => {
                    let symbol, color;
                    if (value > 0) {
                        symbol = '↑';
                        color = 'success';
                    } else if (value < 0) {
                        symbol = '↓';
                        color = 'danger';
                    } else {
                        symbol = '→';
                        color = 'muted';
                    }
                    return `<small class="text-${color}">${symbol} ${Math.abs(value)}% vs last period</small>`;
                };

                document.getElementById('totalEventsCard').textContent = summary.totalEvents;
                document.getElementById('eventsChangeCard').innerHTML = formatChange(summary.eventsChange);

                document.getElementById('totalRegistrationsCard').textContent = summary.totalRegistrations;
                document.getElementById('registrationsChangeCard').innerHTML = formatChange(summary.registrationsChange);

                document.getElementById('avgAttendanceRateCard').textContent = summary.avgAttendanceRate + '%';
                
                document.getElementById('totalAttendeesCard').textContent = summary.totalAttendees;

                document.getElementById('avgFeedbackRatingCard').textContent = summary.avgFeedbackRating.toFixed(2);
                document.getElementById('feedbackCountCard').innerHTML = `<small class="text-muted">${summary.totalFeedback} responses</small>`;

                document.getElementById('totalActiveMembersCard').textContent = summary.totalActiveMembers;
                
                document.getElementById('totalNonMembersCard').textContent = summary.totalNonMembers;
                
                document.getElementById('totalInitiativesCard').textContent = summary.totalInitiatives;
            }

            // Populate events report table
            function populateEventsReport(events) {
                const tbody = document.getElementById('eventsReportBody');

                if (!events || events.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">No events in selected period</td></tr>';
                    return;
                }

                tbody.innerHTML = events.map(event => `
                <tr>
                    <td><strong>${escapeHtml(event.eventName)}</strong></td>
                    <td>${event.eventDate}</td>
                    <td><small>${escapeHtml(event.Venue || '-')}</small></td>
                    <td><span class="badge bg-primary">${event.memberRegistrations || 0}</span></td>
                    <td><span class="badge bg-info">${event.nonMemberRegistrations || 0}</span></td>
                    <td><strong>${event.registered}</strong></td>
                    <td><strong>${event.attended}</strong></td>
                    <td>
                        <span class="badge ${event.attendanceRate >= 80 ? 'bg-success' : event.attendanceRate >= 60 ? 'bg-warning' : 'bg-danger'}">
                            ${event.attendanceRate}%
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-warning">${event.avgRating}/5.0</span>
                    </td>
                    <td>${event.feedbackCount}</td>
                </tr>
            `).join('');
            }

            // Populate members report table
            function populateMembersReport(members) {
                const tbody = document.getElementById('membersReportBody');

                if (!members || members.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No member data available</td></tr>';
                    return;
                }

                tbody.innerHTML = members.map(member => `
                <tr>
                    <td><strong>${member.memberType}</strong></td>
                    <td>${member.totalCount}</td>
                    <td><span class="badge bg-success">${member.active}</span></td>
                    <td><span class="badge bg-secondary">${member.inactive}</span></td>
                    <td>${member.avgParticipation}%</td>
                </tr>
            `).join('');
            }

            // Populate initiatives report table
            function populateInitiativesReportTable(initiatives) {
                const tbody = document.getElementById('initiativesReportBody');

                if (!initiatives || initiatives.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No initiatives found</td></tr>';
                    return;
                }

                tbody.innerHTML = initiatives.map(initiative => `
                <tr>
                    <td><strong>${escapeHtml(initiative.Title || '-')}</strong></td>
                    <td>${initiative.category || '-'}</td>
                    <td>
                        <span class="badge ${initiative.status === 'Featured' ? 'bg-warning' : 'bg-secondary'}">
                            ${initiative.status}
                        </span>
                    </td>
                    <td>${initiative.publishDate}</td>
                    <td><small>${escapeHtml(initiative.Description || '').substring(0, 50)}${escapeHtml(initiative.Description || '').length > 50 ? '...' : ''}</small></td>
                </tr>
            `).join('');
            }

            // Display trends chart as line graph
            function displayTrendsChart(trends) {
                const container = document.getElementById('trendsChartContainer');

                if (!trends || trends.length === 0) {
                    container.innerHTML = '<small class="text-muted">No trend data available</small>';
                    return;
                }

                // Detect mobile/tablet screen size
                const isMobile = window.innerWidth < 768;
                const isTablet = window.innerWidth < 992;

                // Calculate max values for scaling
                const maxAttendees = Math.max(...trends.map(t => parseInt(t.attendees) || 0), 1);
                const maxEvents = Math.max(...trends.map(t => parseInt(t.events) || 0), 1);

                // Responsive sizing
                let width, height, padding, fontSize, pointRadius;
                if (isMobile) {
                    width = Math.max(300, trends.length * 60);
                    height = 220;
                    padding = 30;
                    fontSize = 9;
                    pointRadius = 3;
                } else if (isTablet) {
                    width = Math.max(500, trends.length * 90);
                    height = 260;
                    padding = 35;
                    fontSize = 10;
                    pointRadius = 3.5;
                } else {
                    width = Math.max(800, trends.length * 120);
                    height = 300;
                    padding = 40;
                    fontSize = 11;
                    pointRadius = 4;
                }

                const graphWidth = width - (padding * 2);
                const graphHeight = height - (padding * 2);
                const pointSpacing = graphWidth / (trends.length - 1 || 1);

                // Create SVG with responsive sizing
                let svg = `<svg width="100%" height="${height}" viewBox="0 0 ${width} ${height}" preserveAspectRatio="xMidYMid meet" style="border: 1px solid #ddd; border-radius: 4px; display: block; margin: 0 auto;">`;
                
                // Y-axis label
                svg += `<text x="12" y="18" font-size="${fontSize}" fill="#666">Attendees</text>`;
                
                // Y-axis
                svg += `<line x1="${padding}" y1="${padding}" x2="${padding}" y2="${height - padding}" stroke="#999" stroke-width="1"/>`;
                
                // X-axis
                svg += `<line x1="${padding}" y1="${height - padding}" x2="${width - padding}" y2="${height - padding}" stroke="#999" stroke-width="1"/>`;

                // Y-axis scale lines and labels
                for (let i = 0; i <= 5; i++) {
                    const y = padding + (graphHeight / 5) * i;
                    const value = Math.floor(maxAttendees * (5 - i) / 5);
                    svg += `<line x1="${padding - 5}" y1="${y}" x2="${padding}" y2="${y}" stroke="#999" stroke-width="1"/>`;
                    svg += `<text x="2" y="${y + 3}" font-size="${fontSize - 1}" fill="#666">${value}</text>`;
                }

                // Plot lines and points
                let linePath = `M ${padding} ${height - padding - (parseInt(trends[0].attendees || 0) / maxAttendees * graphHeight)}`;
                
                trends.forEach((trend, index) => {
                    const x = padding + (index * pointSpacing);
                    const attendeeValue = parseInt(trend.attendees) || 0;
                    const y = height - padding - (attendeeValue / maxAttendees * graphHeight);
                    
                    if (index > 0) {
                        linePath += ` L ${x} ${y}`;
                    }
                });

                // Draw line
                svg += `<polyline points="${linePath.replace(/^M /, '').replace(/ L /g, ',')}" fill="none" stroke="#007bff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`;

                // Plot points and labels
                trends.forEach((trend, index) => {
                    const x = padding + (index * pointSpacing);
                    const attendeeValue = parseInt(trend.attendees) || 0;
                    const y = height - padding - (attendeeValue / maxAttendees * graphHeight);
                    
                    // Point
                    svg += `<circle cx="${x}" cy="${y}" r="${pointRadius}" fill="#007bff" stroke="white" stroke-width="1"/>`;
                    
                    // X-axis label (specific date with day of month)
                    const dateLabel = trend.dateFormatted || trend.monthName || trend.month;
                    const labelY = height - padding + (isMobile ? 16 : 20);
                    svg += `<text x="${x}" y="${labelY}" font-size="${fontSize}" fill="#666" text-anchor="middle">${dateLabel}</text>`;
                });

                svg += `</svg>`;

                // Create responsive data table below graph
                let tableHtml = '<div style="margin-top: 20px;" class="trends-table-wrapper">';
                tableHtml += isMobile ? 
                    // Mobile: Card-like view for each trend
                    '<div class="trends-mobile-cards">' + 
                    trends.map(trend => {
                        const dateDisplay = trend.dateFormatted || trend.monthName || trend.month;
                        const memberNonMember = `${trend.memberAttendees || 0} / ${trend.nonMemberAttendees || 0}`;
                        return `<div class="card mb-2" style="border: 1px solid #dee2e6;">
                            <div class="card-body p-2">
                                <div class="row g-2">
                                    <div class="col-6"><small class="text-muted">Date</small><div><strong>${dateDisplay}</strong></div></div>
                                    <div class="col-6"><small class="text-muted">Events</small><div><strong>${trend.events || 0}</strong></div></div>
                                    <div class="col-6"><small class="text-muted">Registrations</small><div><strong>${trend.registrations || 0}</strong></div></div>
                                    <div class="col-6"><small class="text-muted">Attendees</small><div><strong>${trend.attendees || 0}</strong></div></div>
                                    <div class="col-6"><small class="text-muted">M / NM</small><div><strong>${memberNonMember}</strong></div></div>
                                    <div class="col-6"><small class="text-muted">Attendance</small><div><strong>${trend.attendanceRate || 0}%</strong></div></div>
                                    <div class="col-12"><small class="text-muted">Avg Rating</small><div><strong>${parseFloat(trend.avgFeedbackRating || 0).toFixed(2)}/5.0</strong></div></div>
                                </div>
                            </div>
                        </div>`;
                    }).join('') +
                    '</div>'
                    : 
                    // Desktop: Traditional table view
                    '<div style="overflow-x: auto;"><table class="table table-sm table-bordered"><thead><tr><th>Date</th><th>Events</th><th>Registrations</th><th>Attendees</th><th>Member / Non-Member</th><th>Attendance Rate</th><th>Avg Rating</th></tr></thead><tbody>' +
                    trends.map(trend => {
                        const dateDisplay = trend.dateFormatted || trend.monthName || trend.month;
                        const memberNonMember = `${trend.memberAttendees || 0} / ${trend.nonMemberAttendees || 0}`;
                        return `<tr>
                            <td><strong>${dateDisplay}</strong></td>
                            <td>${trend.events || 0}</td>
                            <td>${trend.registrations || 0}</td>
                            <td>${trend.attendees || 0}</td>
                            <td>${memberNonMember}</td>
                            <td>${trend.attendanceRate || 0}%</td>
                            <td>${parseFloat(trend.avgFeedbackRating || 0).toFixed(2)}/5.0</td>
                        </tr>`;
                    }).join('') +
                    '</tbody></table></div>';
                tableHtml += '</div>';

                container.innerHTML = svg + tableHtml;
            }

            // Export reports
            async function exportReport(format) {
                const dateRange = document.getElementById('dateRangeSelect').value;
                const activeTab = document.querySelector('.nav-link.active');
                const reportType = activeTab.getAttribute('href').replace('#', '').replace('-report', '');

                let startDate, endDate;

                if (dateRange === 'custom') {
                    startDate = document.getElementById('reportStartDate').value;
                    endDate = document.getElementById('reportEndDate').value;

                    if (!startDate || !endDate) {
                        alert('Please select both start and end dates');
                        return;
                    }
                } else {
                    endDate = new Date().toISOString().split('T')[0];
                    startDate = new Date();
                    startDate.setDate(startDate.getDate() - parseInt(dateRange));
                    startDate = startDate.toISOString().split('T')[0];
                }

                try {
                    // Show loading indicator
                    const btn = event.target.closest('.btn');
                    const originalText = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating PDF...';

                    const url = `api/export-reports.php?format=${format}&type=${reportType}&startDate=${startDate}&endDate=${endDate}`;
                    window.open(url, '_blank');

                    // Reset button after 2 seconds
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }, 2000);
                } catch (error) {
                    console.error('Error exporting report:', error);
                    alert('Error exporting report');
                }
            }

            // Initialize reports
            document.addEventListener('DOMContentLoaded', function () {
                // Load reports when section is clicked
                const reportsLink = document.querySelector('a[href="#reports"]');
                if (reportsLink) {
                    reportsLink.addEventListener('click', function () {
                        setTimeout(() => loadReports(), 100);
                    });
                }

                // Handle date range change
                const dateRangeSelect = document.getElementById('dateRangeSelect');
                if (dateRangeSelect) {
                    dateRangeSelect.addEventListener('change', function () {
                        const customRange = document.getElementById('customDateRange');
                        if (this.value === 'custom') {
                            customRange.classList.remove('d-none');
                            customRange.style.display = 'flex';
                        } else {
                            customRange.classList.add('d-none');
                            loadReports();
                        }
                    });
                }
            });

            // Load ongoing events for attendance management dropdown
            async function loadOngoingEvents() {
                // Store the current event details
                const form = document.getElementById('attendanceCheckingForm');
                form.dataset.eventId = eventId;
                form.dataset.eventSerialNumber = eventSerialNumber;
                form.dataset.step = '1'; // Step 1: Verify event, Step 2: Verify member

                // Update the event name in the form
                document.getElementById('selectedEventName').textContent = eventName;

                // Show the form and set to step 1
                form.classList.remove('d-none');
                document.getElementById('eventSerialInput').value = '';
                document.getElementById('memberSerialInput').value = '';

                // Show step 1, hide step 2
                document.getElementById('step1-event-verification').classList.remove('d-none');
                document.getElementById('step2-member-verification').classList.add('d-none');

                // Clear QR scanner if it was active
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.clear();
                    document.getElementById('qr-scanner').classList.add('d-none');
                }

                // Scroll to the form
                form.scrollIntoView({ behavior: 'smooth' });
            }

            function hideAttendanceCheckingForm() {
                document.getElementById('attendanceCheckingForm').classList.add('d-none');

                if (html5QrcodeScanner) {
                    html5QrcodeScanner.clear();
                    document.getElementById('qr-scanner').classList.add('d-none');
                }
            }

            // Step 1: Verify event serial number
            async function verifyEventSerial(serialNumber = null) {
                const eventSerial = serialNumber || document.getElementById('eventSerialInput').value.trim();
                const eventId = document.getElementById('attendanceCheckingForm').dataset.eventId;

                if (!eventSerial) {
                    alert('Please enter the event serial number');
                    return;
                }

                try {
                    const response = await fetch('api/manage-attendance.php?action=verifyRegistration', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `eventSerialNumber=${encodeURIComponent(eventSerial)}&eventId=${encodeURIComponent(eventId)}`
                    });

                    const data = await response.json();

                    if (data.valid && data.eventVerified) {
                        // Move to step 2
                        document.getElementById('attendanceCheckingForm').dataset.step = '2';
                        document.getElementById('step1-event-verification').classList.add('d-none');
                        document.getElementById('step2-member-verification').classList.remove('d-none');
                        document.getElementById('memberSerialInput').value = '';
                        document.getElementById('memberSerialInput').focus();
                        alert('Event verified! Now scan or enter member serial number.');
                    } else {
                        alert('Invalid event serial number. Please try again.');
                    }
                } catch (error) {
                    console.error('Error verifying event serial:', error);
                    alert('Error verifying event serial');
                }
            }

            // Step 2: Verify member and check attendance
            async function verifyMemberSerial(serialNumber = null) {
                const memberSerial = serialNumber || document.getElementById('memberSerialInput').value.trim();
                const eventId = document.getElementById('attendanceCheckingForm').dataset.eventId;
                const eventSerialNumber = document.getElementById('attendanceCheckingForm').dataset.eventSerialNumber;

                if (!memberSerial) {
                    alert('Please enter member serial number');
                    return;
                }

                try {
                    const response = await fetch('api/manage-attendance.php?action=verifyRegistration', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `eventSerialNumber=${encodeURIComponent(eventSerialNumber)}&memberSerialNumber=${encodeURIComponent(memberSerial)}&eventId=${encodeURIComponent(eventId)}`
                    });

                    const data = await response.json();

                    if (data.valid) {
                        const member = data.data;
                        
                        // If member hasn't attended yet, automatically record attendance
                        if (!member.hasAttended) {
                            // Auto-record attendance without showing modal
                            await recordAttendanceForMember(member.registrationId, member.memberId, eventId);
                        } else {
                            // If already attended, show modal with info
                            const modal = new bootstrap.Modal(document.getElementById('attendanceCheckModal'));
                            const resultDiv = document.getElementById('attendanceResult');
                            const confirmBtn = document.getElementById('confirmAttendance');
                            
                            resultDiv.innerHTML = `
                            <div class="text-center mb-4">
                                <div class="display-1 text-warning">
                                    <i class="bi bi-exclamation-circle-fill"></i>
                                </div>
                            </div>
                            <div class="alert alert-warning">
                                <h6 class="alert-heading">Already Attended</h6>
                                <hr>
                                <p class="mb-0"><strong>Name:</strong> ${member.name}</p>
                                <p class="mb-0"><strong>First Check-in:</strong> ${new Date(member.attendanceTime).toLocaleString()}</p>
                            </div>`;
                            
                            confirmBtn.classList.add('d-none');
                            modal.show();
                            
                            // Close after 3 seconds
                            setTimeout(() => {
                                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('attendanceCheckModal'));
                                if (modalInstance) {
                                    modalInstance.hide();
                                }
                            }, 3000);
                        }
                    } else {
                        // Show error modal
                        const modal = new bootstrap.Modal(document.getElementById('attendanceCheckModal'));
                        const resultDiv = document.getElementById('attendanceResult');
                        const confirmBtn = document.getElementById('confirmAttendance');
                        
                        resultDiv.innerHTML = `
                        <div class="text-center mb-4">
                            <div class="display-1 text-danger">
                                <i class="bi bi-x-circle"></i>
                            </div>
                        </div>
                        <div class="alert alert-danger">
                            <h6 class="alert-heading">Invalid Registration</h6>
                            <p class="mb-0">${data.message}</p>
                        </div>`;
                        confirmBtn.classList.add('d-none');
                        modal.show();
                        
                        // Close after 3 seconds
                        setTimeout(() => {
                            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('attendanceCheckModal'));
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                        }, 3000);
                    }
                    
                    document.getElementById('memberSerialInput').value = '';

                } catch (error) {
                    console.error('Error verifying member serial:', error);
                    alert('Error verifying member serial');
                }
            }

            // Record attendance for member
            async function recordAttendanceForMember(registrationId, memberId, eventId) {
                try {
                    const response = await fetch('api/manage-attendance.php?action=recordAttendance', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `registrationId=${encodeURIComponent(registrationId)}&memberId=${encodeURIComponent(memberId)}&eventId=${encodeURIComponent(eventId)}&scanType=QR`
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Show success modal
                        const resultDiv = document.getElementById('attendanceResult');
                        resultDiv.innerHTML = `
                        <div class="text-center mb-4">
                            <div class="display-1 text-success">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </div>
                        <div class="alert alert-success">
                            <h6 class="alert-heading">Attendance Recorded!</h6>
                            <hr>
                            <p class="mb-0"><strong>${data.memberName}</strong> checked in at ${new Date(data.attendanceTime).toLocaleTimeString()}</p>
                        </div>`;

                        document.getElementById('confirmAttendance').classList.add('d-none');

                        // Show the modal
                        const modal = new bootstrap.Modal(document.getElementById('attendanceCheckModal'));
                        modal.show();

                        // Close modal and refresh members table after 2 seconds
                        setTimeout(() => {
                            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('attendanceCheckModal'));
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                            // Refresh the members table to show updated attendance
                            loadEventAttendanceMembers();
                            
                            // Reset the form to allow next scan
                            document.getElementById('attendanceCheckingForm').dataset.step = '1';
                            document.getElementById('step1-event-verification').classList.remove('d-none');
                            document.getElementById('step2-member-verification').classList.add('d-none');
                            document.getElementById('eventSerialInput').value = '';
                            document.getElementById('memberSerialInput').value = '';
                        }, 2000);
                    } else {
                        alert('Error recording attendance: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error recording attendance:', error);
                    alert('Error recording attendance');
                }
            }

            // View event attendance details
            async function viewEventAttendanceDetails(eventId) {
                try {
                    const response = await fetch(`api/manage-attendance.php?action=getEventDetails&eventId=${eventId}`);
                    const data = await response.json();

                    if (data.success) {
                        const event = data.event;
                        const stats = data.stats;

                        let html = `
                        <h5>${event.EventName}</h5>
                        <hr>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">Registered</h6>
                                        <p class="card-text display-6">${stats.registeredCount}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">Attended</h6>
                                        <p class="card-text display-6">${stats.attendedCount}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">Attendance Rate</h6>
                                        <p class="card-text display-6">${stats.attendanceRate}%</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">No-show</h6>
                                        <p class="card-text display-6">${stats.registeredCount - stats.attendedCount}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h6 class="mt-4">Attendance Details:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Member Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Check-in Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.attendanceList.map(member => `
                                        <tr>
                                            <td>${member.FName} ${member.LName}</td>
                                            <td><small>${member.ApplicantEmail}</small></td>
                                            <td><span class="badge ${member.Status === 'Attended' ? 'bg-success' : 'bg-warning'}">${member.Status}</span></td>
                                            <td><small>${member.AttendanceTime ? new Date(member.AttendanceTime).toLocaleTimeString() : '-'}</small></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;

                        // Create modal if it doesn't exist
                        let modal = document.getElementById('eventAttendanceDetailsModal');
                        if (!modal) {
                            const modalHtml = `
                            <div class="modal fade" id="eventAttendanceDetailsModal" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Event Attendance Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body" id="attendanceDetailsContent">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                            document.body.insertAdjacentHTML('beforeend', modalHtml);
                            modal = document.getElementById('eventAttendanceDetailsModal');
                        }

                        document.getElementById('attendanceDetailsContent').innerHTML = html;
                        new bootstrap.Modal(modal).show();
                    } else {
                        alert('Error loading event details: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error loading event details:', error);
                    alert('Error loading event details');
                }
            }

            // Toggle scanner for member verification
            let html5QrcodeScanner = null;

            function toggleScannerMember() {
                const scannerDiv = document.getElementById('qr-scanner');
                if (scannerDiv.classList.contains('d-none')) {
                    scannerDiv.classList.remove('d-none');
                    html5QrcodeScanner = new Html5QrcodeScanner(
                        "reader", { fps: 10, qrbox: 250 });
                    html5QrcodeScanner.render(onScanSuccess);
                } else {
                    scannerDiv.classList.add('d-none');
                    if (html5QrcodeScanner) {
                        html5QrcodeScanner.clear();
                        html5QrcodeScanner = null;
                    }
                }
            }

            function onScanSuccess(decodedText) {
                try {
                    const step = document.getElementById('attendanceCheckingForm').dataset.step;

                    if (step === '1') {
                        // Scanning event serial
                        verifyEventSerial(decodedText);
                    } else if (step === '2') {
                        // Stop the scanner immediately when member QR is detected
                        if (html5QrcodeScanner) {
                            html5QrcodeScanner.clear();
                            html5QrcodeScanner = null;
                        }
                        document.getElementById('qr-scanner').classList.add('d-none');
                        
                        // Scanning member serial - automatically mark attendance
                        verifyMemberSerial(decodedText);
                    }
                } catch (e) {
                    console.error('QR decode error:', e);
                    alert('Invalid QR Code format');
                }
            }

            // Initialize event attendance when section is clicked
            document.addEventListener('DOMContentLoaded', function () {
                const attendanceLink = document.querySelector('a[href="#event-attendance"]');
                if (attendanceLink) {
                    attendanceLink.addEventListener('click', function () {
                        setTimeout(() => loadOngoingEvents(), 100);
                    });
                }
            });

            // ===== FEEDBACK MANAGEMENT FUNCTIONS =====

            // Load completed events for feedback selection
            async function loadFeedbackEvents() {
                try {
                    const response = await fetch('api/get-admin-feedback.php?action=events');
                    const result = await response.json();

                    if (result.success) {
                        const select = document.getElementById('feedbackEventSelect');
                        select.innerHTML = '<option value="">-- Choose an Event --</option>';

                        result.events.forEach(event => {
                            const option = document.createElement('option');
                            option.value = event.EventID;
                            option.textContent = `${event.Title} (${new Date(event.ProposedDate).toLocaleDateString()})`;
                            select.appendChild(option);
                        });
                    } else {
                        alert('Error loading events');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Failed to load events');
                }
            }

            // Calculate average rating for an event
            async function calculateEventAverageRating(eventId) {
                try {
                    const response = await fetch(`api/get-admin-feedback.php?action=average-rating&eventId=${eventId}`);
                    const result = await response.json();

                    if (result.success && result.averageRating !== null) {
                        const avgRating = Math.round(result.averageRating * 10) / 10;
                        const fullStars = Math.floor(avgRating);
                        const hasHalfStar = avgRating % 1 >= 0.5;
                        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

                        let starsHtml = '★'.repeat(fullStars);
                        if (hasHalfStar) starsHtml += '⋆';
                        starsHtml += '☆'.repeat(emptyStars);

                        document.getElementById('averageRatingStars').innerHTML = starsHtml;
                        document.getElementById('averageRatingValue').textContent = `${avgRating}/5 (${result.totalRatings} ratings)`;
                        document.getElementById('feedbackAverageRating').style.display = 'block';
                    } else {
                        document.getElementById('feedbackAverageRating').style.display = 'none';
                    }
                } catch (error) {
                    console.error('Error calculating average rating:', error);
                }
            }

            // Load members and feedback status for selected event
            async function loadFeedbackMembers() {
                const eventId = document.getElementById('feedbackEventSelect').value;
                const tbody = document.getElementById('feedbackMembersTableBody');
                const filter = document.querySelector('#feedbackFilterGroup .btn.active')?.dataset.filter || 'all';

                if (!eventId) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Select an event to view attendees</td></tr>';
                    document.getElementById('feedbackStats').style.display = 'none';
                    return;
                }

                try {
                    const response = await fetch(`api/get-admin-feedback.php?action=members&eventId=${eventId}&filter=${filter}`);
                    const result = await response.json();

                    if (result.success) {
                        if (result.count === 0) {
                            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No attendees with feedback data for this event</td></tr>';
                            document.getElementById('feedbackStats').style.display = 'none';
                            return;
                        }

                        // Update stats
                        const statsText = `Total Attendees: ${result.count} | Feedback Submitted: ${result.feedbackCount}`;
                        document.getElementById('feedbackStatsText').textContent = statsText;
                        document.getElementById('feedbackStats').style.display = 'block';

                        // Load and display average rating
                        await calculateEventAverageRating(eventId);

                        // Populate table
                        tbody.innerHTML = result.members.map((member, index) => {
                            const feedbackBadge = member.HasFeedback
                                ? '<span class="badge bg-success">Submitted</span>'
                                : '<span class="badge bg-warning text-dark">Pending</span>';

                            const actions = member.HasFeedback
                                ? `<button class="btn btn-sm btn-info" onclick="viewMemberFeedback(${member.FeedbackID}, '${eventId}')">View Feedback</button>`
                                : `<button class="btn btn-sm btn-primary" onclick="requestFeedback(${eventId}, '', ${member.AttendanceID})">Provide Feedback</button>`;

                            const attendanceDate = new Date(member.AttendanceTime).toLocaleString();
                            const userType = member.UserType || 'Member';

                            return `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${member.FName} ${member.LName}</td>
                                <td>${member.Email}</td>
                                <td><span class="badge bg-info">${userType}</span></td>
                                <td>${attendanceDate}</td>
                                <td>${feedbackBadge}</td>
                                <td>${actions}</td>
                            </tr>
                        `;
                        }).join('');
                    } else {
                        alert('Error loading attendees: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Failed to load attendees');
                }
            }

            function setFeedbackFilter(filter) {
                // Update button states
                document.querySelectorAll('#feedbackFilterGroup .btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector(`#feedbackFilterGroup [data-filter="${filter}"]`).classList.add('active');
                
                // Reload data with new filter
                loadFeedbackMembers();
            }

            // View feedback for a member
            async function viewMemberFeedback(feedbackId, eventId) {
                try {
                    const response = await fetch(`api/get-admin-feedback.php?action=view-feedback&feedbackId=${feedbackId}`);
                    const result = await response.json();

                    if (result.success && result.feedback) {
                        const feedback = result.feedback;

                        // Populate modal
                        document.getElementById('feedbackEventName').textContent = feedback.EventTitle;
                        document.getElementById('feedbackEventDate').textContent = new Date(feedback.ProposedDate).toLocaleDateString();
                        document.getElementById('feedbackMemberName').textContent = feedback.IsAnonymous ? 'Anonymous Member' : `${feedback.FName} ${feedback.LName}`;
                        document.getElementById('feedbackMemberEmail').textContent = feedback.IsAnonymous ? 'N/A' : feedback.Email;

                        // Display rating as stars
                        const starsHtml = `<div class="text-warning">
                        ${'★'.repeat(feedback.Rating)}${'☆'.repeat(5 - feedback.Rating)}
                        <span class="ms-2 text-dark">${feedback.Rating}/5</span>
                    </div>`;
                        document.getElementById('feedbackRating').innerHTML = starsHtml;

                        // Display Overall Experience
                        if (document.getElementById('feedbackOverallExperience')) {
                            document.getElementById('feedbackOverallExperience').textContent = feedback.OverallExperience || 'N/A';
                        }

                        // Display Knowledge Gained
                        if (document.getElementById('feedbackKnowledgeGained')) {
                            document.getElementById('feedbackKnowledgeGained').textContent = feedback.KnowledgeGained || 'N/A';
                        }

                        document.getElementById('feedbackComments').textContent = feedback.Comments || 'No comments provided';
                        document.getElementById('feedbackSubmissionDate').textContent = new Date(feedback.SubmissionDate).toLocaleString();

                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('viewFeedbackModal'));
                        modal.show();
                    } else {
                        alert('Error loading feedback');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Failed to load feedback');
                }
            }

            // Initialize feedback management
            document.addEventListener('DOMContentLoaded', function () {
                loadFeedbackEvents();

                // Reload when feedback management section is clicked
                const feedbackLink = document.querySelector('a[href="#feedback-management"]');
                if (feedbackLink) {
                    feedbackLink.addEventListener('click', function () {
                        loadFeedbackEvents();
                    });
                }
            });

            // ===== PROFILE MANAGEMENT FUNCTIONS =====

            // Load FULL profile details for Member View Profile tab ONLY
            async function loadProfileDetails(memberId) {
                try {
                    // Ensure we have a valid member ID
                    if (!memberId) {
                        console.error('No member ID provided to loadProfileDetails');
                        return false;
                    }

                    const response = await fetch(`api/get-members.php`);
                    const data = await response.json();

                    if (data.success && data.members) {
                        // Find the specific member by ID
                        const member = data.members.find(m => parseInt(m.MemberID) === parseInt(memberId));

                        if (member) {
                            // Store current member ID for use throughout the page
                            window.currentMemberId = member.MemberID;
                            window.currentMemberEmail = member.ApplicantEmail;
                            window.currentMemberName = member.FName + ' ' + member.LName;

                            // Populate the display fields in the profile section
                            const displayFullNameEl = document.getElementById('displayFullName');
                            const displayEmailEl = document.getElementById('displayEmail');
                            const displayPhoneEl = document.getElementById('displayPhone');
                            const displayRoleEl = document.getElementById('displayRole');
                            const displayMemberSinceEl = document.getElementById('displayMemberSince');
                            const displayStatusEl = document.getElementById('displayStatus');

                            if (displayFullNameEl) displayFullNameEl.textContent = window.currentMemberName;
                            if (displayEmailEl) displayEmailEl.textContent = member.ApplicantEmail;
                            if (displayPhoneEl) displayPhoneEl.textContent = member.Phone || 'N/A';
                            if (displayRoleEl) displayRoleEl.textContent = member.Role || 'Member';

                            const joinDate = new Date(member.JoinDate);
                            if (displayMemberSinceEl) displayMemberSinceEl.textContent = joinDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
                            if (displayStatusEl) displayStatusEl.textContent = member.isActive ? 'Active' : 'Inactive';

                            // Display profile image
                            const profileImageEl = document.getElementById('displayProfileImage');
                            const idPreviewPhotoDiv = document.querySelector('#idPreview .rounded-circle');

                            if (profileImageEl) {
                                if (member.ProfileImage && member.ProfileImage !== 'null' && member.ProfileImage !== '') {
                                    profileImageEl.src = member.ProfileImage;
                                    if (idPreviewPhotoDiv) {
                                        idPreviewPhotoDiv.innerHTML = `<img src="${member.ProfileImage}" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                                    }
                                } else {
                                    profileImageEl.src = 'assets/image/default-avatar.png';
                                    if (idPreviewPhotoDiv) {
                                        idPreviewPhotoDiv.innerHTML = `<div class="h-100 d-flex align-items-center justify-content-center text-muted">2x2 Photo</div>`;
                                    }
                                }
                            }

                            // Update ID preview - FULL PROFILE DETAILS
                            const idPreviewNameEl = document.getElementById('idPreviewName');
                            const idPreviewRoleEl = document.getElementById('idPreviewRole');
                            const idPreviewIDEl = document.getElementById('idPreviewID');
                            const idPreviewMemberSinceEl = document.getElementById('idPreviewMemberSince');
                            const idPreviewStatusEl = document.getElementById('idPreviewValidUntil');
                            const qrContainerEl = document.getElementById('idPreviewQR');

                            if (idPreviewNameEl) idPreviewNameEl.textContent = window.currentMemberName;
                            if (idPreviewRoleEl) idPreviewRoleEl.textContent = member.Role || 'Member';
                            if (idPreviewIDEl) idPreviewIDEl.textContent = `ID: RPH-${member.MemberID.toString().padStart(7, '0')}`;
                            if (idPreviewMemberSinceEl) idPreviewMemberSinceEl.textContent = `Member Since: ${joinDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })}`;
                            if (idPreviewStatusEl) idPreviewStatusEl.textContent = `Status: ${member.isActive ? 'Active' : 'Inactive'}`;

                            // Generate QR code for ID preview
                            if (qrContainerEl) {
                                qrContainerEl.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=RPH-${member.MemberID.toString().padStart(7, '0')}" alt="Member QR Code" class="img-fluid" style="width: 100px;">`;
                            }

                            return true;
                        } else {
                            console.error('Member not found with ID:', memberId);
                            return false;
                        }
                    } else {
                        console.error('Error loading members:', data.message);
                        return false;
                    }
                } catch (error) {
                    console.error('Error loading profile details:', error);
                    return false;
                }
            }

            // View FULL member profile - only called from Member View Profile tab
            function viewMemberProfileTab(memberId) {
                if (!memberId) {
                    console.error('No member ID provided');
                    return;
                }

                loadProfileDetails(memberId).then(success => {
                    if (success) {
                        // Switch to profile tab
                        const profileSection = document.getElementById('profile');
                        document.querySelectorAll('.member-section').forEach(section => {
                            section.classList.add('d-none');
                        });
                        if (profileSection) profileSection.classList.remove('d-none');
                    } else {
                        alert('Failed to load member profile details');
                    }
                }).catch(error => {
                    console.error('Error in viewMemberProfileTab:', error);
                    alert('Error loading profile');
                });
            }

            // View SIMPLE member details - only for Members Management table in Admin View
            function viewMemberDetails(memberId, name, email, gender, birthDate, profileImage) {
                currentViewedMemberId = memberId;

                // Only populate BASIC info for admin member management view
                // DO NOT populate full profile details or ID preview

                // Basic fields only
                const basicFullNameEl = document.getElementById('basicDisplayFullName');
                const basicEmailEl = document.getElementById('basicDisplayEmail');
                const basicPhoneEl = document.getElementById('basicDisplayPhone');
                const basicRoleEl = document.getElementById('basicDisplayRole');
                const basicStatusEl = document.getElementById('basicDisplayStatus');

                if (basicFullNameEl) basicFullNameEl.value = name;
                if (basicEmailEl) basicEmailEl.value = email;
                if (basicPhoneEl) basicPhoneEl.value = '';
                if (basicRoleEl) basicRoleEl.value = document.querySelector(`[data-member-id="${memberId}"]`)?.dataset.memberRole || 'Member';
                if (basicStatusEl) basicStatusEl.value = document.querySelector(`[data-member-id="${memberId}"]`)?.dataset.memberStatus === '1' ? 'Active' : 'Inactive';
            }

            // Handle profile password change
            document.addEventListener('DOMContentLoaded', function () {
                const profilePasswordForm = document.getElementById('profilePasswordForm');
                if (profilePasswordForm) {
                    profilePasswordForm.addEventListener('submit', async function (e) {
                        e.preventDefault();

                        const currentPassword = document.getElementById('currentPassword').value;
                        const newPassword = document.getElementById('newPassword').value;
                        const confirmPassword = document.getElementById('confirmPassword').value;

                        // Validation
                        if (!currentPassword || !newPassword || !confirmPassword) {
                            alert('Please fill in all password fields');
                            return;
                        }

                        if (newPassword.length < 8) {
                            alert('New password must be at least 8 characters long');
                            return;
                        }

                        if (newPassword !== confirmPassword) {
                            alert('New passwords do not match');
                            return;
                        }

                        try {
                            const response = await fetch('api/login.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    action: 'change-password',
                                    currentPassword: currentPassword,
                                    newPassword: newPassword
                                })
                            });

                            const result = await response.json();

                            if (result.success) {
                                alert('Password updated successfully!');
                                profilePasswordForm.reset();
                            } else {
                                alert('Error: ' + (result.message || 'Failed to update password'));
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('Failed to update password');
                        }
                    });
                }
            });

            // Initiate profile edit
            function initiateProfileEdit() {
                const fullName = document.getElementById('displayFullName')?.value || '';
                const email = document.getElementById('displayEmail')?.value || '';
                const phone = document.getElementById('displayPhone')?.value || '';

                // Validate that we have necessary data
                if (!fullName || !email) {
                    alert('Please refresh the page to load your profile information');
                    return;
                }

                // Parse first and last name
                const nameParts = fullName.split(' ');
                const firstName = nameParts[0] || '';
                const lastName = nameParts.slice(1).join(' ') || '';

                document.getElementById('editFirstName').value = firstName;
                document.getElementById('editLastName').value = lastName;
                document.getElementById('editEmail').value = email;
                document.getElementById('editPhone').value = phone;

                const modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
                modal.show();
            }

            // Save profile changes
            async function saveProfileChanges() {
                const firstName = document.getElementById('editFirstName').value.trim();
                const lastName = document.getElementById('editLastName').value.trim();
                const email = document.getElementById('editEmail').value.trim();
                const photoFile = document.getElementById('editPhoto').files[0];

                if (!firstName || !lastName || !email) {
                    alert('Please fill in all required fields');
                    return;
                }

                try {
                    let profileImagePath = null;

                    // Upload photo if provided
                    if (photoFile) {
                        const formData = new FormData();
                        formData.append('file', photoFile);
                        formData.append('type', 'profile');

                        const uploadResponse = await fetch('api/upload-image.php', {
                            method: 'POST',
                            body: formData
                        });

                        const uploadResult = await uploadResponse.json();

                        if (!uploadResult.success) {
                            alert('Error uploading photo: ' + (uploadResult.message || 'Unknown error'));
                            return;
                        }

                        profileImagePath = uploadResult.path;
                    }

                    // Get the logged-in member's ID from the session (stored when members are loaded)
                    const memberId = window.currentMemberId;

                    if (!memberId) {
                        alert('Session error: Could not determine logged-in user. Please reload the page.');
                        return;
                    }

                    const response = await fetch('api/update-member.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'updateProfile',
                            memberId: memberId,
                            firstName: firstName,
                            lastName: lastName,
                            email: email,
                            profileImage: profileImagePath
                        })
                    });

                    const result = await response.json();

                    if (!result.success) {
                        alert('Error: ' + (result.message || 'Failed to update profile'));
                        return;
                    }

                    // Update display fields immediately
                    const fullName = firstName + ' ' + lastName;
                    const displayFullNameEl = document.getElementById('displayFullName');
                    const displayEmailEl = document.getElementById('displayEmail');
                    const idPreviewNameEl = document.getElementById('idPreviewName');

                    if (displayFullNameEl) displayFullNameEl.value = fullName;
                    if (displayEmailEl) displayEmailEl.value = email;
                    if (idPreviewNameEl) idPreviewNameEl.textContent = fullName;

                    // Update profile image if uploaded
                    if (profileImagePath) {
                        const profileImageEl = document.getElementById('displayProfileImage');
                        if (profileImageEl) {
                            profileImageEl.src = profileImagePath;
                        }

                        // Update ID preview photo
                        const idPreviewPhotoDiv = document.querySelector('#idPreview .rounded-circle');
                        if (idPreviewPhotoDiv) {
                            idPreviewPhotoDiv.innerHTML = `<img src="${profileImagePath}" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                        }
                    }

                    // Close the modal
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
                    if (editModal) {
                        editModal.hide();
                    }

                    // Show success message
                    alert('Profile updated successfully!');

                    // Reload members table to reflect changes
                    await loadMembers();

                } catch (error) {
                    console.error('Error:', error);
                    alert('Failed to update profile: ' + error.message);
                }
            }

            // Download ID (use existing function or enhance it)
            function downloadID() {
                const idElement = document.getElementById('idPreview');
                if (!idElement) {
                    alert('ID Preview not found');
                    return;
                }

                // Ensure any external images (like the QR code) have the crossOrigin attribute
                const images = idElement.getElementsByTagName('img');
                for (let img of images) {
                    img.setAttribute('crossOrigin', 'anonymous');
                }

                html2canvas(idElement, {
                    backgroundColor: '#ffffff',
                    scale: 2,
                    useCORS: true,      // Allows capturing images from different domains
                    allowTaint: true,   // Allows images to "taint" the canvas
                    logging: false      // Keeps console clean
                }).then(canvas => {
                    const link = document.createElement('a');
                    link.href = canvas.toDataURL('image/png');
                    link.download = `Reboot-PH-Organization-ID-${Date.now()}.png`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }).catch(error => {
                    console.error('Error downloading ID:', error);
                    alert('Failed to download ID. Ensure the QR code has finished loading.');
                });
            }


            // ===== ACCOUNT MANAGEMENT FUNCTIONS =====

            // Deactivate account
            async function deactivateAccount() {
                if (!confirm('Are you sure you want to deactivate your account? This action will disable your access to the platform.')) {
                    return;
                }

                try {
                    const response = await fetch('api/update-member.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'deactivateAccount'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Your account has been deactivated. You will be redirected to the login page.');
                        window.location.href = 'login.html';
                    } else {
                        alert('Failed to deactivate account: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error deactivating account:', error);
                    alert('An error occurred while deactivating your account.');
                }
            }


            // ===== OPTIMIZED MEMBER DASHBOARD EVENTS LOADER =====
            // Dedicated function for loading events in member dashboard view (admin-dashboard.html)
            async function loadMemberDashboardEventsOptimized() {
                try {
                    console.log('loadMemberDashboardEventsOptimized() called');

                    // Find the member-dashboard section
                    const memberDashboardSection = document.getElementById('member-dashboard');
                    if (!memberDashboardSection) {
                        console.warn('member-dashboard section not found');
                        return;
                    }

                    // Find the container
                    const container = memberDashboardSection.querySelector('#dashboardEventsList');
                    if (!container) {
                        console.warn('dashboardEventsList container not found in member-dashboard');
                        return;
                    }

                    // Fetch upcoming events with credentials
                    const response = await fetch('api/get-member-events.php?type=upcoming', {
                        credentials: 'include'
                    });

                    if (!response.ok) {
                        console.error('API returned status:', response.status);
                        container.innerHTML = '<div class="text-center text-danger py-4">Error loading events. Please try again.</div>';
                        return;
                    }

                    const data = await response.json();
                    console.log('Member events API response:', data);

                    // Check if API call was successful
                    if (!data.success) {
                        console.error('API error:', data.message);
                        container.innerHTML = '<div class="text-center text-danger py-4">Error: ' + (data.message || 'Failed to load events') + '</div>';
                        return;
                    }

                    // Check if there are events
                    if (!data.events || data.events.length === 0) {
                        console.log('No upcoming events found');
                        container.innerHTML = '<div class="text-center text-muted py-4">No upcoming events available</div>';
                        return;
                    }

                    // Sort events by date
                    const sortedEvents = data.events.sort((a, b) => {
                        const dateA = new Date(a.ProposedDate + ' ' + (a.StartTime || '00:00:00'));
                        const dateB = new Date(b.ProposedDate + ' ' + (b.StartTime || '00:00:00'));
                        return dateA - dateB;
                    });

                    // Display only nearest 5 events (imminent activities)
                    const nearestEvents = sortedEvents.slice(0, 5);
                    console.log('Displaying ' + nearestEvents.length + ' nearest events');

                    // Build HTML for events
                    container.innerHTML = nearestEvents.map(event => {
                        const eventDate = new Date(event.ProposedDate);
                        const today = new Date();
                        const daysUntil = Math.ceil((eventDate - today) / (1000 * 60 * 60 * 24));

                        let timeBadge = '';
                        if (daysUntil < 0) {
                            timeBadge = '<span class="badge bg-secondary">Past</span>';
                        } else if (daysUntil === 0) {
                            timeBadge = '<span class="badge bg-danger">Today</span>';
                        } else if (daysUntil === 1) {
                            timeBadge = '<span class="badge bg-warning">Tomorrow</span>';
                        } else if (daysUntil <= 7) {
                            timeBadge = `<span class="badge bg-info">In ${daysUntil} days</span>`;
                        } else {
                            timeBadge = `<span class="badge bg-primary">In ${Math.ceil(daysUntil / 7)} weeks</span>`;
                        }

                        const spotsLeft = event.Capacity - event.RegisteredCount;
                        const capacityBadge = spotsLeft > 0
                            ? `<span class="badge bg-success">${spotsLeft} spots left</span>`
                            : `<span class="badge bg-danger">Full</span>`;

                        return `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                        <h6 class="mb-1">${escapeHtml(event.Title)}</h6>
                        ${timeBadge}
                    </div>
                    <p class="mb-2 text-muted small">
                        📅 ${eventDate.toLocaleDateString()} | ⏰ ${event.StartTime} - ${event.EndTime}<br>
                        📍 ${escapeHtml(event.Venue)}
                    </p>
                    <div class="d-flex gap-2 align-items-center justify-content-between flex-wrap">
                        <div class="d-flex gap-2">
                            ${capacityBadge}
                            <span class="badge bg-secondary">${event.RegisteredCount} registered</span>
                        </div>
                    </div>
                </div>
            `;
                    }).join('');

                    console.log('Successfully populated member dashboard with ' + nearestEvents.length + ' events');

                } catch (error) {
                    console.error('Error loading member dashboard events:', error);
                    const container = document.getElementById('member-dashboard')?.querySelector('#dashboardEventsList');
                    if (container) {
                        container.innerHTML = '<div class="text-center text-danger py-4">Error loading events: ' + error.message + '</div>';
                    }
                }
            }

            // Initialize dashboard when page loads
            document.addEventListener('DOMContentLoaded', function () {
                loadDashboardStats();
                // Load upcoming events for admin dashboard "Upcoming Events" card
                if (typeof loadUpcomingEvents === 'function') {
                    loadUpcomingEvents();
                }

                // Reload stats when dashboard section is clicked
                const dashboardLink = document.querySelector('a[href="#dashboard"]');
                if (dashboardLink) {
                    dashboardLink.addEventListener('click', function () {
                        setTimeout(() => {
                            loadDashboardStats();
                            // Reload upcoming events when dashboard is clicked
                            if (typeof loadUpcomingEvents === 'function') {
                                loadUpcomingEvents();
                            }
                        }, 100);
                    });
                }
            });

            // for quick cards dashboard member view
            document.addEventListener('DOMContentLoaded', function () {
                function navigateTo(selector) {
                    const link = document.querySelector(selector);
                    if (link) link.click();
                }

                // 1. Total Activities - navigate to My ACtivities section
                const totalActivitiesCard = document.getElementById('totalActivitiesCard');
                if (totalActivitiesCard) {
                    totalActivitiesCard.addEventListener('click', function () {
                        navigateTo('a[href="#activities"]');
                    });
                }

                // 2. Upcoming Activities - navigate to My Activities section
                const upcomingActivitiesCard = document.getElementById('upcomingActivitiesCard');
                if (upcomingActivitiesCard) {
                    upcomingActivitiesCard.addEventListener('click', function () {
                        navigateTo('a[href="#activities"]');
                    });
                }

                // 3. Activities Attended - navigate to Activity History section
                const attendedActivitiesCard = document.getElementById('attendedActivitiesCard');
                if (attendedActivitiesCard) {
                    attendedActivitiesCard.addEventListener('click', function () {
                        navigateTo('a[href="#history"]');
                    });
                }
            });

            // Helper function to calculate event status based on current time
            function calculateEventStatus(proposedDate, startTime, endTime) {
                try {
                    const now = new Date();
                    
                    // Parse date string (format: YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS)
                    const datePart = proposedDate.split('T')[0]; // Remove any time part if present
                    const [year, month, day] = datePart.split('-');
                    
                    // Parse start time (format: HH:MM:SS)
                    const [startHour, startMin, startSec] = (startTime || '00:00:00').split(':');
                    // Parse end time (format: HH:MM:SS)
                    const [endHour, endMin, endSec] = (endTime || '23:59:59').split(':');
                    
                    // Create date objects using local time
                    const eventStart = new Date(
                        parseInt(year),
                        parseInt(month) - 1,
                        parseInt(day),
                        parseInt(startHour),
                        parseInt(startMin),
                        parseInt(startSec || 0)
                    );
                    const eventEnd = new Date(
                        parseInt(year),
                        parseInt(month) - 1,
                        parseInt(day),
                        parseInt(endHour),
                        parseInt(endMin),
                        parseInt(endSec || 0)
                    );
                    
                    // If event end time has passed, mark as Completed
                    if (now > eventEnd) {
                        return 'Completed';
                    }
                    
                    // If event has started but not ended, mark as Ongoing
                    if (now >= eventStart && now <= eventEnd) {
                        return 'Ongoing';
                    }
                    
                    // Calculate time remaining until event starts (in milliseconds)
                    const timeUntilStart = eventStart - now;
                    const oneDay = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
                    
                    // If event is within 1 day (close to start time), mark as Near
                    if (timeUntilStart > 0 && timeUntilStart <= oneDay) {
                        return 'Near';
                    }
                    
                    // Event is scheduled (more than 1 day away)
                    return 'Scheduled';
                } catch (e) {
                    console.error('Error calculating event status:', e);
                    return 'Unknown';
                }
            }

            // Load ongoing events for attendance management
            async function loadOngoingEvents() {
                try {
                    console.log('Loading ongoing events...');
                    const select = document.getElementById('attendanceEventSelect');
                    if (!select) {
                        console.warn('attendanceEventSelect element not found - attendance section may not be visible');
                        return;
                    }
                    
                    const response = await fetch('api/manage-attendance.php?action=getEvents', {
                        method: 'GET',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    console.log('Events API response:', data);

                    if (data.success && Array.isArray(data.events)) {
                        const currentValue = select.value;

                        // Clear existing options except first
                        select.innerHTML = '<option value="">-- Choose an Event --</option>';

                        // Add event options with calculated status
                        if (data.events.length === 0) {
                            console.log('No events found in database');
                        } else {
                            data.events.forEach(event => {
                                const option = document.createElement('option');
                                option.value = event.EventID;
                                const eventName = event.EventName || 'Untitled Event';
                                const eventDate = event.ProposedDate || 'No date';
                                // Calculate dynamic status based on current time
                                const dynamicStatus = calculateEventStatus(event.ProposedDate, event.StartTime, event.EndTime);
                                option.textContent = `${eventName} (${eventDate}) - ${dynamicStatus}`;
                                select.appendChild(option);
                            });
                            console.log(`Loaded ${data.events.length} events`);
                        }

                        // Restore previous selection if still available
                        if (currentValue && select.querySelector(`option[value="${currentValue}"]`)) {
                            select.value = currentValue;
                        }
                    } else {
                        console.error('API returned unexpected response:', data);
                    }
                } catch (error) {
                    console.error('Error loading ongoing events:', error);
                    alert('Error loading events: ' + error.message);
                }
            }

            // Load members for selected event
            async function loadEventAttendanceMembers() {
                try {
                    const eventId = document.getElementById('attendanceEventSelect').value;
                    const showQRBtn = document.getElementById('showQRButton');
                    const tbody = document.getElementById('attendanceMembersTableBody');
                    const statsRow = document.getElementById('attendanceStatsRow');
                    const filter = document.querySelector('#attendanceFilterGroup .btn.active')?.dataset.filter || 'all';

                    // Reset if no event selected
                    if (!eventId) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Select an event to view registered attendees
                                </td>
                            </tr>
                        `;
                        statsRow.style.display = 'none';
                        showQRBtn.classList.add('d-none');
                        console.log('No event selected');
                        return;
                    }

                    console.log('Loading attendees for event ID:', eventId, 'Filter:', filter);
                    
                    // Fetch attendees for the selected event
                    const response = await fetch(`api/manage-attendance.php?action=getEventAttendance&eventId=${eventId}&filter=${filter}`, {
                        method: 'GET',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    console.log('Raw API response:', data);

                    if (!data.success) {
                        throw new Error(data.message || 'API returned failure');
                    }

                    // Validate attendees data
                    if (!data.attendees || !Array.isArray(data.attendees)) {
                        throw new Error('Invalid attendees data received from API');
                    }

                    // Update statistics
                    const totalReg = parseInt(data.stats?.totalRegistered) || 0;
                    const totalAtt = parseInt(data.stats?.totalAttended) || 0;
                    const rate = totalReg > 0 ? Math.round((totalAtt / totalReg) * 100) : 0;
                    
                    document.getElementById('totalRegisteredCard').textContent = totalReg;
                    document.getElementById('totalCheckedInCard').textContent = totalAtt;
                    document.getElementById('attendanceRateCard').textContent = rate + '%';
                    statsRow.style.display = '';

                    // Show QR button only after event is selected
                    showQRBtn.classList.remove('d-none');
                    showQRBtn.dataset.eventId = eventId;

                    // Populate members table
                    if (data.attendees.length > 0) {
                        const rows = data.attendees.map((attendee, index) => {
                            const isCheckedIn = attendee.AttendanceID ? true : false;
                            const statusBadge = isCheckedIn 
                                ? '<span class="badge bg-success">Checked In</span>' 
                                : '<span class="badge bg-warning">Pending</span>';
                            const checkInTime = attendee.AttendanceTime 
                                ? new Date(attendee.AttendanceTime).toLocaleString() 
                                : '-';
                            const userType = attendee.UserType || 'Member';
                            
                            return `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${attendee.FName} ${attendee.LName}</td>
                                    <td>${attendee.ApplicantEmail}</td>
                                    <td><span class="badge bg-info">${userType}</span></td>
                                    <td>${statusBadge}</td>
                                    <td>${checkInTime}</td>
                                </tr>
                            `;
                        }).join('');
                        
                        tbody.innerHTML = rows;
                        console.log(`Successfully loaded ${data.attendees.length} attendees`);
                    } else {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No registered attendees for this event yet
                                </td>
                            </tr>
                        `;
                    }
                } catch (error) {
                    console.error('Error loading event attendance:', error);
                    document.getElementById('attendanceMembersTableBody').innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center text-danger py-4">
                                <strong>Error:</strong> ${error.message}
                            </td>
                        </tr>
                    `;
                    document.getElementById('attendanceStatsRow').style.display = 'none';
                    document.getElementById('showQRButton').classList.add('d-none');
                }
            }

            function setAttendanceFilter(filter) {
                // Update button states
                document.querySelectorAll('#attendanceFilterGroup .btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector(`#attendanceFilterGroup [data-filter="${filter}"]`).classList.add('active');
                
                // Reload data with new filter
                loadEventAttendanceMembers();
            }

            // Initialize attendance management on page load
            document.addEventListener('DOMContentLoaded', function() {
                loadOngoingEvents();
                // Refresh ongoing events every 30 seconds
                setInterval(loadOngoingEvents, 30000);
            });

            // QR Scanner for admin attendance
            let qrScannerInstance = null;

            function showOrganizerQRCode() {
                try {
                    const eventId = document.getElementById('attendanceEventSelect').value;
                    const eventSelect = document.getElementById('attendanceEventSelect');
                    
                    if (!eventId) {
                        alert('Please select an event first');
                        return;
                    }
                    
                    const eventName = eventSelect.options[eventSelect.selectedIndex].text;
                    console.log('Generating QR code for event:', eventId, eventName);
                    
                    // Fetch the event details to get the serial number
                    fetchEventDetails(eventId).then(eventDetails => {
                        const serialNumber = eventDetails?.SerialNumber || 'EVT-' + eventId;
                        
                        // Generate QR code data with the event serial number
                        // Members will scan this to get the serial number for marking attendance
                        const qrData = JSON.stringify({
                            type: 'event_attendance',
                            eventId: eventId,
                            eventName: eventName,
                            serialNumber: serialNumber,
                            timestamp: new Date().toISOString()
                        });
                        
                        console.log('QR Data:', qrData);
                        
                        // Show modal with QR code
                        const modal = new bootstrap.Modal(document.getElementById('qrScannerModal'));
                        modal.show();
                        
                        // Generate QR code after modal is shown
                        setTimeout(() => {
                            try {
                                const qrContainer = document.getElementById('organizerQRCode');
                                if (!qrContainer) {
                                    console.error('QR container element not found');
                                    return;
                                }
                                
                                qrContainer.innerHTML = ''; // Clear previous QR
                                
                                // Check if QRCode library is loaded
                                if (typeof QRCode === 'undefined') {
                                    console.error('QRCode library not loaded');
                                    qrContainer.innerHTML = '<div class="alert alert-danger">QR Code library not loaded</div>';
                                    return;
                                }
                                
                                // Generate the QR code
                                new QRCode(qrContainer, {
                                    text: qrData,
                                    width: 300,
                                    height: 300,
                                    colorDark: '#1a7f0d',
                                    colorLight: '#ffffff',
                                    correctLevel: QRCode.CorrectLevel.H
                                });
                                
                                console.log('QR code generated successfully');
                            } catch (error) {
                                console.error('Error generating QR code:', error);
                                const qrContainer = document.getElementById('organizerQRCode');
                                if (qrContainer) {
                                    qrContainer.innerHTML = '<div class="alert alert-danger">Error generating QR code: ' + error.message + '</div>';
                                }
                            }
                        }, 300);
                    }).catch(error => {
                        console.error('Error fetching event details:', error);
                        alert('Error loading event details: ' + error.message);
                    });
                } catch (error) {
                    console.error('Error in showOrganizerQRCode:', error);
                    alert('Error generating QR code: ' + error.message);
                }
            }

            // Helper function to fetch event details including serial number
            async function fetchEventDetails(eventId) {
                try {
                    const response = await fetch(`api/manage-attendance.php?action=getEventDetails&eventId=${eventId}`, {
                        method: 'GET',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    
                    const data = await response.json();
                    if (data.success && data.event) {
                        return data.event;
                    } else {
                        throw new Error(data.message || 'Failed to fetch event details');
                    }
                } catch (error) {
                    console.error('Error fetching event details:', error);
                    // Return null and let caller handle it
                    return null;
                }
            }

            function closeOrganizerQR() {
                try {
                    const qrContainer = document.getElementById('organizerQRCode');
                    if (qrContainer) {
                        qrContainer.innerHTML = '';
                    }
                } catch (error) {
                    console.error('Error closing QR code:', error);
                }
            }

        </script>
</body>

</html>