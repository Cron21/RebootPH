<?php

require_once 'api/config.php'; 

// Check kung naka-login at kung member (including Member Staff)
$memberRoles = ['Member', 'Member Staff'];

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $memberRoles)) {
    header("Location: login.html?error=not_member");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - Reboot PH</title>
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/style.css">
    <script src="assets/vendor/qrcode.min.js"></script>
    <script src="assets/vendor/html2canvas.min.js"></script>
    <script src="assets/vendor/html5-qrcode.min.js"></script>
    
    <style>
        html {
            scroll-padding-top: 90px;
        }
    
        /* for navigation bar */
        .navbar-nav.gap-3 {
            gap: 20px;
        }
    
        .navbar-nav .list-group-item {
            color: #035996;
            background-color: transparent !important;
            border: none;
            font-weight: 500;
            border-radius: 6px;
            transition: color 0.2s ease-in-out;
        }
    
        .navbar-nav .list-group-item:hover {
            color: #023f75;
        }
    
        .navbar-nav .list-group-item.active {
            color: #4BB949 !important;
            background-color: transparent !important;
        }
    
    
        /* ====== DASHBOARD CARDS (3 SUMMARY CARDS) ====== */
        #dashboard .card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }
    
        #dashboard .card.bg-primary {
            background-color: #0A4FA3 !important;
        }
    
        #dashboard .card.bg-success {
            background-color: #2EAD4B !important;
        }
    
        #dashboard .card.bg-info {
            background-color: #F4D03F !important;
            color: #000 !important;
        }
    
        /* icon box upper‐right */
        #dashboard .card .bg-white {
            padding: 10px !important;
            border-radius: 12px !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
    
        /* title label */
        #dashboard .card-body h6 {
            font-size: 0.95rem;
            font-weight: 600;
            opacity: .9;
        }
    
        /* big number */
        #dashboard .card-body h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-top: 8px;
        }
    
        /* small description */
        #dashboard small {
            opacity: .9;
            font-size: .85rem;
        }
    
        /* ===== UPCOMING ACTIVITIES BOX ===== */
        #dashboard>.card {
            border-radius: 18px;
            background-color: #4BB9491A !important;
            border: none;
        }
    
        /* title */
        #dashboard h3.h5 {
            font-weight: 700;
            color: #0A4FA3;
        }
    
        /* ===== ACTIVITY LIST ITEM ===== */
        #dashboard .list-group-item {
            border: none !important;
            border-radius: 15px !important;
            background: #fff !important;
            padding: 24px;
            margin-bottom: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }
    
        #dashboard .list-group-item h6 {
            font-weight: 700;
        }
    
        #dashboard .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: .75rem;
        }
    
        /* register button redesign */
        #dashboard .list-group-item .btn-outline-primary {
            border: none !important;
            border-radius: 8px;
            background-color: #2EAD4B !important;
            color: #fff !important;
            padding: 6px 14px;
            font-size: .85rem;
        }
    
        #dashboard .list-group-item .btn-outline-primary:hover {
            opacity: .85;
        }
    
        /* for profile section */
        .profile-card {
            border-radius: 12px;
            background: #ffffff;
        }
    
        .id-card {
            background: #ffffff;
            border: 2px solid #035996;
            border-radius: 12px;
            max-width: 330px;
        }
    
        #profile label {
            color: #555;
        }
    
        #profile p {
            color: #333;
        }
    </style>

</head>

<body class="bg-light d-flex flex-column min-vh-100"
    style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;">

    <nav class="navbar navbar-expand-lg navbar-light fixed-top bg-light shadow-lg">
        <div class="container d-flex align-items-center justify-content-between">
            <a class="navbar-brand d-flex align-items-center gap-2 brand-logo" href="index.html">
                <img src="assets/image/reboot-logo.png" alt="Reboot PH logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-lg-auto header-nav mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="#dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#profile">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#activities">My Activities</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#history">Activity History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#settings">Settings</a>
                    </li>
                </ul>
                <div class="d-flex justify-content-center justify-content-lg-end align-items-center ms-auto">
                    <span class="text-black me-3 fw-bold">Welcome, <?php echo htmlspecialchars($_SESSION['firstName']. ' ' . $_SESSION['lastName']); ?>!</span>
                    <a href="login.html" class="btn btn-outline-success">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>



    <main class="container-fluid my-5 pt-5 flex-grow-1">
        <div class="row">


            <!-- Main Content -->
            <!-- <div class="col-md-9 col-lg-10"> -->
            <!-- Dashboard Content -->
            <div id="dashboard" class="active-section">
                <h2 class="h4 mb-4">Dashboard</h2>
                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title mb-0">Total Activities</h6>
                                        <h2 class="mt-2 mb-0">0</h2>
                                    </div>
                                    <div class="bg-white p-2 rounded">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="currentColor" class="bi bi-calendar-check text-primary"
                                            viewBox="0 0 16 16">
                                            <path
                                                d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z" />
                                            <path
                                                d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small>All time activities</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title mb-0">Upcoming Activities</h6>
                                        <h2 class="mt-2 mb-0">0</h2>
                                    </div>
                                    <div class="bg-white p-2 rounded">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="currentColor" class="bi bi-calendar-plus text-success"
                                            viewBox="0 0 16 16">
                                            <path
                                                d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7z" />
                                            <path
                                                d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small>Scheduled activities</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title mb-0">Activities Attended</h6>
                                        <h2 class="mt-2 mb-0">0</h2>
                                    </div>
                                    <div class="bg-white p-2 rounded">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="currentColor" class="bi bi-person-check text-info"
                                            viewBox="0 0 16 16">
                                            <path
                                                d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm1.679-4.493-1.335 2.226a.75.75 0 0 1-1.174.144l-.774-.773a.5.5 0 0 1 .708-.708l.547.548 1.17-1.951a.5.5 0 1 1 .858.514ZM11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                                            <path
                                                d="M8.256 14a4.474 4.474 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10c.26 0 .507.009.74.025.226-.341.496-.65.804-.918C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4s1 1 1 1h5.256Z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small>Completed activities</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Activities -->
                <div class="card">
                    <div class="card-body" id="dashboardContent">
                        <h3 class="h5 mb-4">Imminent Activities</h3>
                        <div class="list-group" id="dashboardEventsList">
                            <div class="text-center text-muted py-4">
                                <p>Loading events...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="profile" class="d-none active-section">
                <h2 class="text-center fw-bold mb-4" style="color:#035996;">Member Profile</h2>

                <div class="row justify-content-center g-4">

                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 profile-card h-100">
                            <div class="card-body p-4">

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h3 class="fw-bold mb-0" style="color:#035996;">Personal Information</h3>
                                    <button class="btn btn-outline-primary btn-sm" onclick="initiateProfileEdit()">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" class="bi bi-pencil me-1" viewBox="0 0 16 16">
                                            <path
                                                d="M12.146.292a.5.5 0 0 1 .708 0l3.854 3.854a.5.5 0 0 1 0 .708l-10.851 10.851a.5.5 0 0 1-.224.105l-2.5.5a.5.5 0 0 1-.609-.609l.5-2.5a.5.5 0 0 1 .105-.224l10.851-10.851zM11.6 1.697L3.5 9.8V12h2.2l8.1-8.1-1.6-1.6z" />
                                        </svg>
                                        Edit
                                    </button>
                                </div>

                                <div class="mb-4 text-center">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Crect width='200' height='200' fill='%23e9ecef'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='14' fill='%23999'%3ENo Photo%3C/text%3E%3C/svg%3E"
                                            id="displayProfileImage" alt="Profile Photo"
                                            class="rounded-circle shadow-sm"
                                            style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #035996;">
                                        <div class="mt-2">
                                            <p class="text-muted small mb-0">2x2 photo format recommended</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Full Name</label>
                                        <input type="text" class="form-control" id="displayFullName" value="" readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Email</label>
                                        <input type="email" class="form-control" id="displayEmail" value="" readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Phone Number</label>
                                        <input type="tel" class="form-control" id="displayPhone" value="" readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Role</label>
                                        <input type="text" class="form-control" id="displayRole" value="" readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Member Since</label>
                                        <input type="text" class="form-control" id="displayMemberSince" value=""
                                            readonly>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Membership Status</label>
                                        <input type="text" class="form-control fw-bold text-success" id="displayStatus"
                                            value="" readonly>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card shadow-sm border-0 profile-card h-100">
                            <div class="card-body p-4">

                                <h3 class="text-center fw-bold mb-4" style="color:#035996;">Member ID Preview</h3>

                                <div id="idPreview" class="id-card mx-auto p-3 shadow-sm"
                                    style="border: 1px solid #dee2e6; border-radius: 10px; background: #fff;">

                                    <div class="d-flex align-items-center mb-3 border-bottom pb-2">
                                        <img src="assets/image/reboot-logo.png" class="me-2" style="height:50px;"
                                            alt="Logo">
                                        <div>
                                            <h5 class="mb-0 fw-bold" style="color:#035996;">Reboot Philippines</h5>
                                            <small class="text-muted">Environmental Organization</small>
                                        </div>
                                    </div>

                                    <div class="text-center my-3">
                                        <div class="rounded-circle bg-light mx-auto mb-2 d-flex align-items-center justify-content-center"
                                            style="width: 110px; height: 110px; border: 2px dashed #ccc; overflow: hidden;">
                                            <span class="text-muted small">2x2 Photo</span>
                                        </div>
                                    </div>

                                    <div class="text-center mb-3">
                                        <h5 class="mb-1 fw-bold" id="idPreviewName">-</h5>
                                        <p class="mb-1 text-primary fw-semibold" id="idPreviewRole">-</p>
                                        <small class="text-muted d-block" id="idPreviewID">ID: -</small>
                                    </div>

                                    <div class="text-center mb-3 small">
                                        <p class="mb-0 text-muted" id="idPreviewMemberSince">Member Since: -</p>
                                        <p class="mb-0 text-muted" id="idPreviewValidUntil">Status: -</p>
                                    </div>

                                    <div class="text-center" id="idPreviewQR">
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <button class="btn btn-success px-4" onclick="downloadID()">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" class="bi bi-download me-2" viewBox="0 0 16 16">
                                            <path
                                                d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z" />
                                            <path
                                                d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z" />
                                        </svg>
                                        Download ID Format
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0 profile-card mt-2">
                            <div class="card-body p-4">
                                <h3 class="fw-bold mb-4" style="color:#035996;">Change Password</h3>

                                <form id="profilePasswordForm">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Current Password <span
                                                    class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="currentPassword" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">New Password <span
                                                    class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="newPassword" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Confirm Password <span
                                                    class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="confirmPassword" required>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-text text-muted">Password must be at least 8 characters
                                                long</div>
                                        </div>
                                        <div class="col-12 text-end">
                                            <button type="submit" class="btn btn-primary px-4">Update
                                                Password</button>
                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- My Activities Content -->
            <div id="activities" class="d-none active-section">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h4 mb-4">My Activities</h2>
                        <ul class="nav nav-tabs mb-4">
                            <li class="nav-item">
                                <a class="nav-link active" href="#upcoming" data-bs-toggle="tab">Upcoming</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#registered" data-bs-toggle="tab">Registered</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#completed" data-bs-toggle="tab">Completed</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <!-- Upcoming Events -->
                            <div class="tab-pane fade show active" id="upcoming">
                                <div id="upcomingEventsList" class="list-group">
                                    <div class="text-center text-muted py-4">
                                        <p>Loading events...</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Registered Events -->
                            <div class="tab-pane fade" id="registered">
                                <div id="registeredEventsList" class="list-group">
                                    <div class="text-center text-muted py-4">
                                        <p>No registered events yet.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Completed Events -->
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

            <!-- Event Registration Modal -->
            <div class="modal fade" id="eventRegistrationModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Register for Event</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="registrationEventDetails" class="mb-3">
                                <h6 id="regEventTitle" class="mb-2"></h6>
                                <small id="regEventDate" class="text-muted d-block mb-1"></small>
                                <small id="regEventVenue" class="text-muted d-block mb-2"></small>
                            </div>
                            <div id="registrationQRCode" class="text-center mb-3">
                                <img id="registrationQRImage" style="max-width: 200px;" />
                            </div>
                            <p class="text-muted small">Your registration has been saved. Use the QR code or serial
                                number for check-in on the event day.</p>
                            <div class="alert alert-info">
                                <strong>Serial Number:</strong> <span id="registrationSerialNumber"
                                    class="font-monospace"></span>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="downloadEventQR()">Download QR
                                Code</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- History Content -->
            <div id="history" class="d-none active-section">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Activity History</h2>
                        <div class="list-group">
                            <!-- Activity history will be loaded dynamically -->
                            <div class="text-center text-muted py-4">
                                <p>Loading activity history...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <div id="settings" class="d-none active-section">
                <div class="card shadow-sm p-4" style="background:#f0f6ff; border-radius:12px;">
                    <h2 class="text-center fw-bold mb-4" style="color:#0b3d91;">Settings</h2>

                    <div class="p-4 mb-4" style="background:white; border-radius:12px; border-left:6px solid #0b3d91;">
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

                    <div class="p-4 mb-4" style="background:white; border-radius:12px; border-left:6px solid #0b3d91;">
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
    </main>

    <!--footer-->
    <footer class="site-footer mt-auto">
        <div class="footer-top py-5">
            <div class="container">
                <div class="row align-items-start gy-4 justify-content-between">
                    <div class="col-12 col-md-4 d-flex align-items-center gap-3">
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
                    <div class="col-12 col-md-3 text-white small text-center">
                        <h4 class="h6 fw-bold mb-3">About Us</h4>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a href="about.html#mission-vision" class="footer-link">Our Story</a></li>
                            <li class="mb-2"><a href="about.html#team" class="footer-link">Team</a></li>
                        </ul>
                    </div>
                    <div class="col-12 col-md-4 text-white small text-md-end">
                        <h4 class="h6 fw-bold mb-3">Contact Info</h4>
                        <p class="mb-1">
                            <a id="footerEmailLink" href="mailto:rebootphinstitute@gmail.com"
                                class="text-white text-decoration-none">rebootphinstitute@gmail.com</a>
                        </p>
                        <p class="mb-3">
                            <a href="mailto:info@reboot-philippines.org"
                                class="text-white text-decoration-none">info@reboot-philippines.org</a>
                        </p>

                        <div class="d-flex justify-content-start justify-content-md-end gap-3">

                            <a id="footerFacebookLink" href="https://www.facebook.com/rebootphilippines" class="footer-social-icon"
                                aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                                        fill="currentColor" />
                                </svg>
                            </a>

                            <a id="footerInstagramLink"  href="https://www.instagram.com/rebootphinstitute/" class="footer-social-icon"
                                aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"
                                        fill="currentColor" />
                                </svg>
                            </a>

                            <a id="footerLinkedInLink" href="https://www.linkedin.com/company/reboot-philippines/" class="footer-social-icon"
                                aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"
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
    
    <script>
        (async function(){
            try{
                const res = await fetch('api/manage-system-settings.php');
                const data = await res.json();
                if(!data.success) return;
                const s = data.settings || {};
                const email = s.social_email || 'rebootphinstitute@gmail.com';
                const fb = s.social_facebook || 'https://www.facebook.com/rebootphilippines';
                const ig = s.social_instagram || 'https://www.instagram.com/rebootphinstitute/';
                const li = s.social_linkedin || 'https://www.linkedin.com/company/reboot-philippines/';
                const emailEl = document.getElementById('footerEmailLink');
                const fbEl = document.getElementById('footerFacebookLink');
                const igEl = document.getElementById('footerInstagramLink');
                const liEl = document.getElementById('footerLinkedInLink');
                if(emailEl) emailEl.href = 'mailto:' + email;
                if(fbEl) fbEl.href = fb;
                if(igEl) igEl.href = ig;
                if(liEl) liEl.href = li;
            } catch(e){ console.error('social links load error', e); }
        })();
    </script>

    <!-- Add Attendance Modal -->
    <div class="modal fade" id="attendanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Please enter your serial number for:</p>
                    <p class="fw-bold" id="modalActivityTitle"></p>
                    <p class="text-muted small" id="modalActivityDate"></p>
                    <div class="mb-3">
                        <label for="serialNumber" class="form-label">Serial Number</label>
                        <input type="text" class="form-control" id="serialNumber" placeholder="e.g., RPH-123456-ABCD"
                            required>
                        <div class="form-text">Enter the serial number you received when registering for this activity
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="verifyAttendance()">Verify
                        Attendance</button>
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
                                <input type="tel" class="form-control" id="editPhone" placeholder="+63 9XX XXX XXXX">
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
                    <button type="button" class="btn btn-primary" onclick="saveProfileChanges()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Registration Confirmation Modal -->
    <div class="modal fade" id="eventConfirmationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Confirm Event Registration</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Please review the event details before confirming your registration.
                    </div>

                    <div class="row g-3">
                        <!-- Event Title -->
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-1">Event Name</h6>
                                    <h4 class="mb-0" id="confirmEventTitle">-</h4>
                                </div>
                            </div>
                        </div>

                        <!-- Date & Time -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-1">
                                        <i class="bi bi-calendar-event me-2"></i>Date & Time
                                    </h6>
                                    <p class="mb-1" id="confirmEventDateTime">-</p>
                                    <small class="text-muted" id="confirmEventDuration">-</small>
                                </div>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-1">
                                        <i class="bi bi-geo-alt me-2"></i>Location
                                    </h6>
                                    <p class="mb-0" id="confirmEventVenue">-</p>
                                </div>
                            </div>
                        </div>

                        <!-- Capacity -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-1">
                                        <i class="bi bi-people me-2"></i>Available Spots
                                    </h6>
                                    <p class="mb-0" id="confirmEventCapacity">-</p>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-1">
                                        <i class="bi bi-info-circle me-2"></i>Details
                                    </h6>
                                    <p class="mb-0 small" id="confirmEventDescription"
                                        style="white-space: pre-wrap; word-break: break-word; max-height: 200px; overflow-y: auto;">
                                        -</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> Please ensure you can attend this event before confirming your
                        registration. You can cancel anytime from your registered events.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmRegistrationBtn"
                        onclick="confirmEventRegistration()">
                        <i class="bi bi-check-circle me-2"></i>Confirm Registration
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/member-dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOMContentLoaded event fired');
            loadMemberProfile();

            const navLinks = document.querySelectorAll('.header-nav .nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();

                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');

                    document.querySelectorAll('.active-section').forEach(section => {
                        section.classList.add('d-none');
                    });

                    const targetId = this.getAttribute('href').substring(1);
                    const targetSection = document.getElementById(targetId);

                    if (targetSection) {
                        targetSection.classList.remove('d-none');

                        if (targetId === 'activities') {
                            loadUpcomingEventsTab();
                            loadRegisteredEventsTab();
                            loadCompletedEventsTab();
                        } else if (targetId === 'history') {
                            loadActivityHistory();
                        } else if (targetId === 'profile') {
                            loadMemberProfile();
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>