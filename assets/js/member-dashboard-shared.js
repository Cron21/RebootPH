// ===== SHARED MEMBER DASHBOARD FUNCTIONS =====
// File: assets/js/member-dashboard-shared.js
// Used by both member-dashboard.html and admin-dashboard.html (member view)

// ===== UTILITY FUNCTIONS =====

// Check if an event is currently ongoing
function isEventOngoing(eventDate, startTime, endTime) {
    try {
        const now = new Date();
        const eventDateTime = new Date(eventDate);
        
        // Parse start and end times (format: HH:MM)
        const [startHour, startMin] = startTime.split(':').map(Number);
        const [endHour, endMin] = endTime.split(':').map(Number);
        
        const eventStart = new Date(eventDateTime);
        eventStart.setHours(startHour, startMin, 0, 0);
        
        const eventEnd = new Date(eventDateTime);
        eventEnd.setHours(endHour, endMin, 59, 999);
        
        return now >= eventStart && now <= eventEnd;
    } catch (e) {
        console.error('Error checking if event is ongoing:', e);
        return false;
    }
}

// Generate organizer QR code for attendance (used by admin)
function generateOrganizerQRCode(eventId) {
    const qrData = JSON.stringify({
        type: 'organizer',
        eventId: eventId,
        timestamp: new Date().toISOString(),
        organizerId: 'ORG-' + Date.now()
    });
    return qrData;
}

// ===== MEMBER PROFILE MANAGEMENT =====

async function loadMemberProfile() {
    try {
        // Check if we're in admin view - if so, skip member profile loading
        const isAdminView = document.body.classList.contains('admin-view') || window.isAdminView === true;

        if (isAdminView) {
            console.log('Admin view detected - skipping member profile load');
            // Just load the dashboard data without profile
            await Promise.all([
                loadDashboardStats(),
                loadUpcomingEvents()
            ]);
            return;
        }

        const response = await fetch('api/get-members.php');
        const data = await response.json();

        if (data.success && data.currentMemberId) {
            const currentMember = data.members.find(m => m.MemberID == data.currentMemberId);

            if (currentMember) {
                // Store member ID for use throughout the page
                window.currentMemberId = currentMember.MemberID;
                window.currentMemberEmail = currentMember.ApplicantEmail;
                window.currentMemberName = currentMember.FName + ' ' + currentMember.LName;

                // Update display fields
                const displayFullName = document.getElementById('displayFullName');
                const displayEmail = document.getElementById('displayEmail');
                const displayPhone = document.getElementById('displayPhone');
                const displayRole = document.getElementById('displayRole');
                const displayMemberSince = document.getElementById('displayMemberSince');
                const displayStatus = document.getElementById('displayStatus');
                const displayProfileImage = document.getElementById('displayProfileImage');

                if (displayFullName) displayFullName.value = window.currentMemberName;
                if (displayEmail) displayEmail.value = currentMember.ApplicantEmail;
                if (displayPhone) displayPhone.value = currentMember.Phone || '';
                if (displayRole) displayRole.value = currentMember.Role || 'Member';

                const joinDate = new Date(currentMember.JoinDate);
                if (displayMemberSince) displayMemberSince.value = joinDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
                if (displayStatus) displayStatus.value = currentMember.isActive ? 'Active' : 'Inactive';

                // Handle profile image
                if (displayProfileImage) {
                    if (currentMember.ProfileImage && currentMember.ProfileImage !== 'null' && currentMember.ProfileImage !== '' && currentMember.ProfileImage !== undefined) {
                        displayProfileImage.src = currentMember.ProfileImage;
                        displayProfileImage.style.objectFit = 'cover';
                    } else {
                        // Show placeholder
                        displayProfileImage.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"%3E%3Crect width="200" height="200" fill="%23e9ecef"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="14" fill="%23999"%3ENo Photo%3C/text%3E%3C/svg%3E';
                    }
                }

                // Update ID preview logic
                const idPreview = document.getElementById('idPreview');
                if (idPreview) {
                    // Clear only the loading content, keep responsive CSS classes
                    idPreview.innerHTML = '';

                    let photoHtml = '';
                    // Chine-check kung may valid na image URL ang member
                    if (currentMember.ProfileImage && currentMember.ProfileImage !== 'null' && currentMember.ProfileImage !== '') {
                        photoHtml = `<img src="${currentMember.ProfileImage}" alt="Profile" style="width: clamp(35px, 18%, 50px); height: clamp(35px, 18%, 50px); border-radius: 50%; object-fit: cover; border: 2px solid #035996; display: block; flex-shrink: 0;">`;
                    } else {
                        // HETO ANG PAGBABAGO: Default avatar ang lalabas kapag walang pic
                        photoHtml = `<img src="assets/image/default-avatar.png" alt="Default Profile" style="width: clamp(35px, 18%, 50px); height: clamp(35px, 18%, 50px); border-radius: 50%; object-fit: cover; border: 2px solid #035996; display: block; background: #f8f9fa; flex-shrink: 0;">`;
                    }

                    idPreview.innerHTML = `
                        <div style="background-color: #035996 !important; display: flex !important; flex-direction: row !important; align-items: center !important; justify-content: space-between !important; padding: clamp(6px, 3%, 12px) clamp(10px, 3%, 16px) !important; box-sizing: border-box !important; flex: 0 0 auto;">
                            <div style="background: white !important; border-radius: 50%; display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0; width: clamp(35px, 12%, 50px); height: clamp(35px, 12%, 50px); padding: clamp(3px, 2%, 4px);">
                                <img src="assets/image/reboot2-logo.png" style="width: 80%; height: auto;" alt="Logo">
                            </div>
                            <div style="text-align: right !important; color: white !important; flex: 1; padding-left: clamp(8px, 3%, 12px);">
                                <h4 style="margin: 0; font-weight: bold; font-size: clamp(10px, 2.5vw, 14px); color: white !important; line-height: 1.1;">Reboot Philippines</h4>
                                <p style="margin: clamp(1px, 0.5%, 3px) 0 0 0; font-size: clamp(6px, 1.2vw, 8px); opacity: 0.9; color: white !important; line-height: 1;">2804, Discovery Centre, Pasig City</p>
                            </div>
                        </div>

                        <div style="display: flex !important; flex-direction: row !important; align-items: flex-start !important; padding: clamp(8px, 2.5%, 12px) clamp(8px, 2.5%, 12px) !important; flex: 1; box-sizing: border-box !important; gap: clamp(6px, 2%, 10px); min-height: 0;">
                            <div style="flex: 0 0 auto; display: flex !important; justify-content: center !important; align-items: center !important;">
                                ${photoHtml}
                            </div>

                            <div style="flex: 1 !important; text-align: left !important; overflow: hidden;">
                                <div style="margin-bottom: clamp(2px, 1%, 4px);">
                                    <small style="color: #666; font-size: clamp(6px, 1.3vw, 8px); display: block; margin: 0;">Name:</small>
                                    <span style="font-weight: bold; font-size: clamp(10px, 2.2vw, 12px); color: #333 !important; display: block; line-height: 1;">${window.currentMemberName}</span>
                                </div>
                                <div style="margin-bottom: clamp(2px, 1%, 4px);">
                                    <small style="color: #666; font-size: clamp(6px, 1.3vw, 8px); display: block; margin: 0;">Role:</small>
                                    <span style="font-weight: bold; font-size: clamp(8px, 1.8vw, 10px); color: #035996 !important; display: block; line-height: 1;">${currentMember.Role || 'Member'}</span>
                                </div>
                                <div style="margin-bottom: 0;">
                                    <span style="font-weight: 800; color: #035996 !important; font-size: clamp(8px, 1.8vw, 10px); display: block; line-height: 1;">ID: RPH-${currentMember.MemberID.toString().padStart(7, '0')}</span>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex !important; flex-direction: row !important; align-items: flex-end !important; justify-content: space-between !important; padding: clamp(8px, 2.5%, 12px) clamp(8px, 2.5%, 12px) !important; box-sizing: border-box !important; gap: clamp(6px, 2%, 10px); flex: 0 0 auto; border-top: 1px solid #f0f0f0;">
                            <div style="font-size: clamp(7px, 1.4vw, 9px); color: #035996 !important; text-align: left !important;">
                                <p style="margin: 0 !important; line-height: 1.2 !important;">
                                    <strong style="display: block; font-size: clamp(6px, 1.2vw, 8px); margin: 0;">Member Since:</strong>
                                    <span style="font-weight: bold; font-size: clamp(7px, 1.3vw, 9px);">${joinDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })}</span>
                                </p>
                            </div>
                            <div id="idPreviewQR" style="background: white !important; padding: clamp(2px, 1%, 4px); border-radius: clamp(2px, 1%, 3px); border: 1px solid #eee; flex-shrink: 0;">
                                <img id="memberQRCode" 
                                    style="width: clamp(50px, 18%, 70px); height: auto; display: block !important;">
                            </div>
                        </div>
                    `;
                    
                    // Generate QR code URL after DOM is updated
                    setTimeout(() => {
                        const qrImg = document.getElementById('memberQRCode');
                        if (qrImg && currentMember.MemberID) {
                            const memberId = 'RPH-' + currentMember.MemberID.toString().padStart(7, '0');
                            const protocol = window.location.protocol;
                            const host = window.location.host;
                            const memberDetailsUrl = protocol + '//' + host + '/view-member.html?id=' + encodeURIComponent(memberId);
                            const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=85x85&data=' + encodeURIComponent(memberDetailsUrl);
                            qrImg.src = qrUrl;
                        }
                    }, 100);
                }
            }
        }

        // Always load dashboard stats and upcoming events
        await Promise.all([
            loadDashboardStats(),
            loadUpcomingEvents()
        ]);

    } catch (error) {
        console.error('Error loading member profile:', error);
        try {
            await Promise.all([
                loadDashboardStats(),
                loadUpcomingEvents()
            ]);
        } catch (e) {
            console.error('Error loading dashboard data:', e);
        }
    }
}

// Load dashboard statistics
// FIXED loadDashboardStats() - Lines 107-161
async function loadDashboardStats() {
    try {
        console.log('loadDashboardStats() called');

        let totalActivities = 0;
        let upcomingActivities = 0;
        let attendedCount = 0;

        let registeredData = { success: false, events: [] };
        let upcomingData = { success: false, events: [] };

        try {
            const registeredResponse = await fetch(`api/get-member-events.php?type=registered`);
            registeredData = await registeredResponse.json();
        } catch (error) {
            console.error('Error fetching registered events:', error);
        }

        try {
            const upcomingResponse = await fetch('api/get-member-events.php?type=upcoming');
            upcomingData = await upcomingResponse.json();
        } catch (error) {
            console.error('Error fetching upcoming events:', error);
        }

        if (registeredData.success || upcomingData.success) {
            totalActivities = registeredData.events ? registeredData.events.length : 0;
            upcomingActivities = upcomingData.success && upcomingData.events
                ? upcomingData.events.length
                : 0;
            attendedCount = registeredData.events
                ? registeredData.events.filter(e => e.AttendanceCount > 0).length
                : 0;
        }

        console.log('Dashboard stats calculated:', { totalActivities, upcomingActivities, attendedCount });

        // Try to find dashboard section - check BOTH member-dashboard and dashboard IDs
        let dashboardSection = document.getElementById('member-dashboard');
        if (!dashboardSection) {
            dashboardSection = document.getElementById('dashboard');
        }

        if (dashboardSection) {
            const allH2 = dashboardSection.querySelectorAll('.col-md-4 h2');

            if (allH2.length >= 3) {
                allH2[0].textContent = totalActivities;
                allH2[1].textContent = upcomingActivities;
                allH2[2].textContent = attendedCount;
                console.log('Updated dashboard stats successfully');
            } else {
                console.warn('Could not find 3 stat cards. Found h2 count:', allH2.length);
            }
        } else {
            console.warn('Dashboard section not found');
        }

    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

// Load upcoming events for the dashboard (Imminent Activities - shows nearest events only)
async function loadUpcomingEvents() {
    try {
        console.log('loadUpcomingEvents() called');

        // Load events regardless of visibility - they'll be displayed when the dashboard tab is clicked
        const response = await fetch('api/get-member-events.php?type=upcoming');
        const data = await response.json();

        console.log('API response:', data);

        if (data.success && data.events && data.events.length > 0) {
            console.log('Found ' + data.events.length + ' upcoming events');
            const sortedEvents = data.events.sort((a, b) => {
                const dateA = new Date(a.ProposedDate + ' ' + (a.StartTime || '00:00:00'));
                const dateB = new Date(b.ProposedDate + ' ' + (b.StartTime || '00:00:00'));
                return dateA - dateB;
            });
            populateUpcomingEventsList(sortedEvents);
        } else {
            console.log('No events found or API error');
            showNoEventsMessage();
        }
    } catch (error) {
        console.error('Error loading upcoming events:', error);
        showNoEventsMessage(true);
    }
}

// Helper function to show "no events" or error message
function showNoEventsMessage(isError = false) {
    console.log('showNoEventsMessage called, isError:', isError);

    // Try to find container - check both regular dashboard and member-dashboard
    let container = document.getElementById('dashboardEventsList');

    console.log('Looking for dashboardEventsList container...');
    console.log('Found by direct ID:', container ? 'YES' : 'NO');

    if (!container) {
        const memberDashboard = document.getElementById('member-dashboard');
        console.log('Found member-dashboard section:', memberDashboard ? 'YES' : 'NO');

        if (memberDashboard) {
            container = memberDashboard.querySelector('#dashboardEventsList');
            console.log('Found dashboardEventsList in member-dashboard:', container ? 'YES' : 'NO');
        }
    }

    if (!container) {
        const regularDashboard = document.getElementById('dashboard');
        console.log('Found dashboard section:', regularDashboard ? 'YES' : 'NO');

        if (regularDashboard) {
            container = regularDashboard.querySelector('#dashboardEventsList');
            console.log('Found dashboardEventsList in dashboard:', container ? 'YES' : 'NO');
        }
    }

    if (container) {
        if (isError) {
            container.innerHTML = '<div class="text-center text-danger py-4">Error loading events. Please try again.</div>';
        } else {
            container.innerHTML = '<div class="text-center text-muted py-4">No upcoming events available</div>';
        }
        console.log('Updated container HTML');
    } else {
        console.warn('dashboardEventsList container NOT FOUND - cannot update events');
    }
}

// Populate upcoming events list on dashboard (Imminent Activities - shows nearest events only)
function populateUpcomingEventsList(events) {
    console.log('populateUpcomingEventsList called with', events.length, 'events');

    // Determine which dashboard section is visible/active
    let container = null;
    const memberDashboard = document.getElementById('member-dashboard');
    const adminDashboard = document.getElementById('dashboard');

    // Check which one is currently visible (not d-none)
    if (memberDashboard && !memberDashboard.classList.contains('d-none')) {
        // Member view is active - use the container in member-dashboard
        container = memberDashboard.querySelector('#dashboardEventsList');
        console.log('Member view is active - using member-dashboard container');
    } else if (adminDashboard && !adminDashboard.classList.contains('d-none')) {
        // Admin view is active - use the container in dashboard
        container = adminDashboard.querySelector('#dashboardEventsList');
        console.log('Admin view is active - using dashboard container');
    } else {
        // Fallback: try to find by ID directly
        container = document.getElementById('dashboardEventsList');
        console.log('Using direct ID lookup');
    }

    console.log('Found dashboardEventsList container:', container ? 'YES' : 'NO');

    if (!container) {
        console.warn('dashboardEventsList container not found - cannot display events');
        return;
    }

    if (!events || events.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">No upcoming events available</div>';
        return;
    }

    // Show only the 5 nearest events for "Imminent Activities"
    const nearestEvents = events.slice(0, 5);

    container.innerHTML = nearestEvents.map(event => {
        const eventDate = new Date(event.ProposedDate);
        const today = new Date();
        const daysUntil = Math.ceil((eventDate - today) / (1000 * 60 * 60 * 24));

        let timeBadge = '';
        if (daysUntil < 0) timeBadge = '<span class="badge bg-secondary">Past</span>';
        else if (daysUntil === 0) timeBadge = '<span class="badge bg-danger">Today</span>';
        else if (daysUntil === 1) timeBadge = '<span class="badge bg-warning">Tomorrow</span>';
        else if (daysUntil <= 7) timeBadge = `<span class="badge bg-info">In ${daysUntil} days</span>`;
        else timeBadge = `<span class="badge bg-primary">In ${Math.ceil(daysUntil / 7)} weeks</span>`;

        const spotsLeft = event.Capacity - event.RegisteredCount;
        const capacityBadge = spotsLeft > 0
            ? `<span class="badge bg-success">${spotsLeft} spots left</span>`
            : `<span class="badge bg-danger">Full</span>`;

        // Check if we're in admin view - if so, don't show register button
        const isAdminView = document.body.classList.contains('admin-view') || window.isAdminView;

        // Show register button only if not in admin view
        let registerBtn = '';
        if (!isAdminView) {
            registerBtn = event.MemberRegistered === 1
                ? `<button class="btn btn-sm btn-success" disabled><i class="bi bi-check-circle"></i> Registered</button>`
                : `<button class="btn btn-sm btn-outline-primary" onclick="registerForEventDashboard(${event.EventID}, '${event.Title.replace(/'/g, "\\'")}', '${event.ProposedDate}', '${event.StartTime}', '${event.EndTime}', '${event.Venue}', '${event.SerialNumber}')">Register Now</button>`;
        }

        return `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                    <h6 class="mb-1">${event.Title}</h6>
                    ${timeBadge}
                </div>
                <p class="mb-2 text-muted small">
                    📅 ${eventDate.toLocaleDateString()} | ⏰ ${event.StartTime} - ${event.EndTime}<br>
                    📍 ${event.Venue}
                </p>
                <div class="d-flex gap-2 align-items-center justify-content-between flex-wrap">
                    <div class="d-flex gap-2">
                        ${capacityBadge}
                        <span class="badge bg-secondary">${event.RegisteredCount} registered</span>
                    </div>
                    ${registerBtn}
                </div>
            </div>
        `;
    }).join('');

    console.log('Successfully populated ' + nearestEvents.length + ' events in container');
}

// Register for event from dashboard
async function registerForEventDashboard(eventId, eventTitle, eventDate, startTime, endTime, venue, serialNumber) {
    // Guard: Check if currentMemberId is set and is valid
    if (!window.currentMemberId) {
        alert('Error: You must be logged in as a member to register for events.\n\nAdmins should not register for events in Member View.');
        console.error('currentMemberId is not set. Admin cannot register.');
        return;
    }

    // Additional check: Make sure we have a valid number
    if (isNaN(window.currentMemberId) || window.currentMemberId <= 0) {
        alert('Error: Invalid member ID. Please refresh the page and try again.');
        console.error('Invalid currentMemberId:', window.currentMemberId);
        return;
    }

    // Show registration type selection modal
    showRegistrationTypeModal(eventId, eventTitle);
}

// Register for event from activities tab
async function registerForEventTab(eventId, eventTitle, eventDate, startTime, endTime, venue) {
    // Show registration type selection modal
    showRegistrationTypeModal(eventId, eventTitle);
}

// Show modal to select registration type (Staff or Attendee)
function showRegistrationTypeModal(eventId, eventTitle) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'registrationTypeModal_' + eventId;
    modal.innerHTML = `
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Register for Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="mb-3">${eventTitle}</h6>
                    <p class="text-muted small mb-3">How would you like to register?</p>
                    <div class="btn-group-vertical w-100" role="group">
                        <button type="button" class="btn btn-outline-primary text-start p-3 mb-2" onclick="submitRegistration(${eventId}, 'Staff', '${eventTitle}')">
                            <div class="fw-bold">👔 Register as Staff</div>
                            <div class="small text-muted">You will be registered as event staff</div>
                        </button>
                        <button type="button" class="btn btn-outline-success text-start p-3" onclick="submitRegistration(${eventId}, 'Attendee', '${eventTitle}')">
                            <div class="fw-bold">👥 Register as Attendee</div>
                            <div class="small text-muted">You will be registered as a general participant</div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    // Remove modal from DOM when hidden
    modal.addEventListener('hidden.bs.modal', function () {
        modal.remove();
    });
}

// Submit registration with type
async function submitRegistration(eventId, registrationType, eventTitle) {
    try {
        const response = await fetch('api/manage-registration.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'register',
                memberId: window.currentMemberId,
                eventId: eventId,
                registrationType: registrationType
            })
        });

        const data = await response.json();

        if (data.success) {
            alert(`Successfully registered for ${eventTitle} as ${registrationType}!`);
            // Close the modal
            const modal = document.getElementById('registrationTypeModal_' + eventId);
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
            }
            // Reload both tabs
            loadUpcomingEventsTab();
            loadRegisteredEventsTab();
            loadDashboardStats();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error registering for event:', error);
        alert('Error registering for event');
    }
}

// Load registered events for My Activities tab
async function loadRegisteredEventsTab(memberId = null) {
    try {
        const url = memberId ? `api/get-member-events.php?type=registered&memberId=${memberId}` : 'api/get-member-events.php?type=registered';
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`API responded with status ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.events) {
            populateRegisteredEventsTab(data.events);
        } else {
            const container = document.getElementById('registeredEventsList');
            if (container) {
                container.innerHTML = '<div class="text-center text-muted py-4">No registered events yet</div>';
            }
        }
    } catch (error) {
        console.error('Error loading registered events:', error);
        const container = document.getElementById('registeredEventsList');
        if (container) {
            container.innerHTML = `<div class="text-center text-danger py-4">Error loading events: ${error.message}</div>`;
        }
    }
}

// Populate registered events tab
function populateRegisteredEventsTab(events) {
    const container = document.getElementById('registeredEventsList');
    if (!container) return;

    if (!events || events.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">You haven\'t registered for any events yet.</div>';
        return;
    }

    container.innerHTML = events.map(event => {
        const eventDate = new Date(event.ProposedDate);
        const today = new Date();
        const attended = event.AttendanceCount > 0;

        // Parse start and end times for proper comparison
        const [startHour, startMin, startSec] = event.StartTime.split(':').map(Number);
        const [endHour, endMin, endSec] = event.EndTime.split(':').map(Number);
        
        const eventStartTime = new Date(eventDate);
        eventStartTime.setHours(startHour, startMin, startSec || 0);
        
        const eventEndTime = new Date(eventDate);
        eventEndTime.setHours(endHour, endMin, endSec || 0);
        
        const now = new Date();
        const isOngoing = now >= eventStartTime && now <= eventEndTime;
        const isUpcoming = eventStartTime > now;

        let statusBadge = '';
        if (attended) statusBadge = '<span class="badge bg-success">✓ Attended</span>';
        else if (isOngoing) statusBadge = '<span class="badge bg-warning">Ongoing</span>';
        else if (isUpcoming) statusBadge = '<span class="badge bg-primary">Upcoming</span>';
        else statusBadge = '<span class="badge bg-secondary">Completed</span>';

        // Show cancel button if event is NOT ongoing AND NOT completed (and not attended)
        const isCompleted = now > eventEndTime;
        const canUnregister = !attended && !isOngoing && !isCompleted;
        const unregisterBtn = canUnregister
            ? `<button class="btn btn-sm btn-outline-danger" onclick="unregisterFromEvent(${event.EventID}, '${event.Title.replace(/'/g, "\\'")}')"">Cancel Registration</button>`
            : '';

        const regDate = new Date(event.RegistrationDate);
        
        // Display registration type badge
        const registrationType = event.RegistrationType || 'Attendee';
        const registrationTypeBadge = registrationType === 'Staff' 
            ? '<span class="badge bg-info">👔 Staff</span>'
            : '<span class="badge bg-secondary">👥 Attendee</span>';

        // Add attendance button for ongoing events
        const attendanceBtn = isOngoing && !attended
            ? `<button class="btn btn-sm btn-success" onclick="openAttendanceModal(${event.EventID}, '${event.Title.replace(/'/g, "\\'")}', '${event.ProposedDate}', '${event.StartTime}')"><i class="bi bi-qr-code"></i> Scan Attendance QR</button>`
            : '';

        return `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                    <h6 class="mb-1">${event.Title}</h6>
                    <div class="d-flex gap-2">
                        ${registrationTypeBadge}
                        ${statusBadge}
                    </div>
                </div>
                <p class="mb-3 text-muted small">
                    📅 ${eventDate.toLocaleDateString()} | ⏰ ${event.StartTime} - ${event.EndTime}<br>
                    📍 ${event.Venue}<br>
                    Registered on: ${regDate.toLocaleDateString()}
                </p>
                ${event.Description ? `<p class="mb-3 small">${event.Description}</p>` : ''}

                <div class="d-flex gap-2 flex-wrap">
                    ${attendanceBtn}
                    ${unregisterBtn}
                </div>
            </div>
        `;
    }).join('');
}

// Unregister from event
async function unregisterFromEvent(eventId, eventTitle) {
    if (!confirm(`Are you sure you want to cancel your registration for "${eventTitle}"?`)) {
        return;
    }

    try {
        const response = await fetch('api/manage-registration.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'unregister',
                memberId: window.currentMemberId,
                eventId: eventId
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Registration cancelled');
            loadRegisteredEventsTab();
            loadUpcomingEventsTab();
            loadDashboardStats();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error unregistering:', error);
        alert('Error cancelling registration');
    }
}

// Show QR scanner modal for member to scan organizer's attendance QR code
function showAttendanceCheckInModal(eventId, eventTitle, memberId, memberName) {
    try {
        // Create permission request modal HTML
        const modalHtml = `
            <div class="modal fade" id="attendancePermissionModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title"><i class="bi bi-camera-video"></i> Camera Permission Required</h5>
                        </div>
                        <div class="modal-body text-center py-4">
                            <p class="mb-3">To scan the attendance QR code, we need access to your camera.</p>
                            <p class="text-muted">You will be prompted to allow camera access.</p>
                            <div class="alert alert-info mt-3">
                                <small><i class="bi bi-lightbulb"></i> Make sure the admin is showing the attendance QR code before scanning</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="requestCameraAndStartScanning(${eventId}, ${memberId}, '${eventTitle.replace(/'/g, "\\'")}')">
                                <i class="bi bi-camera-video"></i> Allow Camera & Scan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove any existing modals
        const existingPermission = document.getElementById('attendancePermissionModal');
        if (existingPermission) existingPermission.remove();
        const existingScanner = document.getElementById('memberQRScannerModal');
        if (existingScanner) existingScanner.remove();
        
        // Add modal to document
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show permission modal
        const permissionModal = new bootstrap.Modal(document.getElementById('attendancePermissionModal'));
        permissionModal.show();
        
    } catch (error) {
        console.error('Error showing attendance permission modal:', error);
        alert('Error opening attendance check-in');
    }
}

// Request camera permission and start scanning
async function requestCameraAndStartScanning(eventId, memberId, eventTitle) {
    try {
        // Request camera permission
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        
        // Stop the stream - we just needed to request permission
        stream.getTracks().forEach(track => track.stop());
        
        // Close permission modal
        const permissionModal = bootstrap.Modal.getInstance(document.getElementById('attendancePermissionModal'));
        if (permissionModal) permissionModal.hide();
        
        // Show scanner modal
        showQRScannerModal(eventId, memberId, eventTitle);
        
    } catch (error) {
        if (error.name === 'NotAllowedError') {
            alert('Camera permission denied. Please enable camera access in your browser settings and try again.');
        } else {
            console.error('Error requesting camera permission:', error);
            alert('Error accessing camera: ' + error.message);
        }
    }
}

// Show QR scanner modal
function showQRScannerModal(eventId, memberId, eventTitle) {
    try {
        // Create scanner modal HTML
        const modalHtml = `
            <div class="modal fade" id="memberQRScannerModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title"><i class="bi bi-qr-code-scan"></i> Scan Event QR Code</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="closeAttendanceScanner()"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-3">Ask the event organizer to show their QR code and scan it below</p>
                            <div id="memberQRScannerContainer" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>
                            <div class="alert alert-success mt-3">
                                <small><i class="bi bi-check-circle"></i> Point your camera at the QR code displayed by the organizer</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeAttendanceScanner()">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove any existing scanner modal
        const existingScanner = document.getElementById('memberQRScannerModal');
        if (existingScanner) existingScanner.remove();
        
        // Add modal to document
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Store event info in modal for use in scanner callback
        const modal = document.getElementById('memberQRScannerModal');
        modal.dataset.eventId = eventId;
        modal.dataset.memberId = memberId;
        modal.dataset.eventTitle = eventTitle;
        
        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Start scanner after modal is shown
        setTimeout(() => startAttendanceScanner(eventId, memberId, eventTitle), 500);
        
    } catch (error) {
        console.error('Error showing scanner modal:', error);
        alert('Error opening QR scanner');
    }
}

// Member QR Scanner Functions
let memberAttendanceScannerInstance = null;

async function startAttendanceScanner(eventId, memberId, eventTitle) {
    const scannerElement = document.getElementById('memberQRScannerContainer');
    if (!scannerElement) return;

    try {
        const html5QrcodeScanner = new Html5Qrcode("memberQRScannerContainer");
        
        await html5QrcodeScanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            (decodedText) => onAttendanceQRScanSuccess(decodedText, eventId, memberId),
            (error) => onAttendanceQRScanFailure(error)
        );
        
        memberAttendanceScannerInstance = html5QrcodeScanner;
    } catch (err) {
        console.error('Error starting attendance scanner:', err);
        alert('Error starting QR scanner. Please ensure camera permissions are granted.');
    }
}

function onAttendanceQRScanSuccess(decodedText, eventId, memberId) {
    try {
        const qrData = JSON.parse(decodedText);
        
        // Check if it's an organizer QR code
        if (qrData.type === 'organizer' && qrData.eventId) {
            // Stop scanner
            if (memberAttendanceScannerInstance) {
                memberAttendanceScannerInstance.stop();
                memberAttendanceScannerInstance = null;
            }
            
            // Close scanner modal
            const scannerModal = bootstrap.Modal.getInstance(document.getElementById('memberQRScannerModal'));
            if (scannerModal) scannerModal.hide();
            
            // Submit attendance marking
            submitAttendanceCheckIn(eventId, memberId);
        }
    } catch (err) {
        console.error('Invalid QR data:', err);
        // Continue scanning
    }
}

function onAttendanceQRScanFailure(error) {
    // Silently ignore scan failures
}

function closeAttendanceScanner() {
    if (memberAttendanceScannerInstance) {
        memberAttendanceScannerInstance.stop();
        memberAttendanceScannerInstance = null;
    }
}

// Submit attendance confirmation after QR scan
async function submitAttendanceCheckIn(eventId, memberId) {
    try {
        const response = await fetch('api/manage-attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'markAttendance',
                eventId: eventId,
                memberId: memberId,
                checkInMethod: 'qr_scan'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✓ Attendance marked successfully!');
            loadRegisteredEventsTab();
            loadCompletedEventsTab();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error submitting attendance:', error);
        alert('Error marking attendance');
    }
}

// Load completed events for My Activities tab
async function loadCompletedEventsTab(memberId = null) {
    try {
        const url = memberId ? `api/get-member-events.php?type=completed&memberId=${memberId}` : 'api/get-member-events.php?type=completed';
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`API responded with status ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.events) {
            populateCompletedEventsTab(data.events);
        } else {
            const container = document.getElementById('completedEventsList');
            if (container) {
                container.innerHTML = '<div class="text-center text-muted py-4">No completed events yet</div>';
            }
        }
    } catch (error) {
        console.error('Error loading completed events:', error);
        const container = document.getElementById('completedEventsList');
        if (container) {
            container.innerHTML = `<div class="text-center text-danger py-4">Error loading events: ${error.message}</div>`;
        }
    }
}

// Populate completed events tab
function populateCompletedEventsTab(events) {
    const container = document.getElementById('completedEventsList');
    if (!container) return;

    if (!events || events.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">No completed events yet.</div>';
        return;
    }

    container.innerHTML = events.map(event => {
        const eventDate = new Date(event.ProposedDate);
        const attended = event.AttendanceID ? 'Yes' : 'No';
        const rating = event.Rating ? `${event.Rating}/5 ⭐` : 'Not rated';
        const hasFeedback = event.FeedbackID ? true : false;

        let attendedBadge = event.AttendanceID
            ? '<span class="badge bg-success">Attended</span>'
            : '<span class="badge bg-warning">Did not attend</span>';

        let feedbackButton = '';
        if (event.AttendanceID) {
            // Only show feedback button if member attended
            if (hasFeedback) {
                feedbackButton = `
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="openFeedbackForm(${event.EventID}, ${event.AttendanceID}, true)">
                        <i class="bi bi-pencil"></i> Update Feedback
                    </button>
                `;
            } else {
                feedbackButton = `
                    <button class="btn btn-sm btn-success mt-2" onclick="openFeedbackForm(${event.EventID}, ${event.AttendanceID}, false)">
                        <i class="bi bi-star"></i> Provide Feedback
                    </button>
                `;
            }
        }

        return `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                    <h6 class="mb-1">${event.Title}</h6>
                    ${attendedBadge}
                </div>
                <p class="mb-2 text-muted small">
                    📅 ${eventDate.toLocaleDateString()} | ⏰ ${event.StartTime} - ${event.EndTime}<br>
                    📍 ${event.Venue}<br>
                    Attended: ${attended} | Rating: ${rating}
                </p>
                ${event.Comments ? `<p class="mb-2 small"><strong>Comments:</strong> ${event.Comments}</p>` : ''}

                <div class="d-flex gap-2">
                    ${feedbackButton}
                </div>
            </div>
        `;
    }).join('');
}

// Open feedback form (redirect to feedback page or open modal)
function openFeedbackForm(eventId, attendanceId, hasFeedback) {
    // Redirect to feedback page with event pre-selected
    window.location.href = `feedback.html?eventId=${eventId}&attendanceId=${attendanceId}`;
}

// ===== ACTIVITY HISTORY PAGE =====

// Load activity history
async function loadActivityHistory(memberId = null) {
    try {
        const url = memberId ? `api/get-member-events.php?type=completed&memberId=${memberId}` : 'api/get-member-events.php?type=completed';
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`API responded with status ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.events) {
            populateActivityHistoryPage(data.events);
        }
    } catch (error) {
        console.error('Error loading activity history:', error);
    }
}

// Populate activity history page
function populateActivityHistoryPage(events) {
    const container = document.querySelector('#history .list-group');
    if (!container) return;

    if (!events || events.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">No completed events yet.</div>';
        return;
    }

    container.innerHTML = events.map(event => {
        const eventDate = new Date(event.ProposedDate);
        const attended = event.AttendanceID ? '✓' : '✗';
        const rating = event.Rating ? `${event.Rating}/5 ⭐` : 'Not rated';

        let statusBadge = event.AttendanceID
            ? '<span class="badge bg-success mb-2">Attended</span>'
            : '<span class="badge bg-secondary mb-2">Did not attend</span>';

        return `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${event.Title}</h6>
                        <p class="mb-1">${event.Description || ''}</p>
                        <small class="text-muted d-block">📅 ${eventDate.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' })} • ${event.StartTime} - ${event.EndTime}</small>
                        <small class="text-muted d-block">📍 ${event.Venue}</small>
                    </div>
                    <div class="text-end">
                        ${statusBadge}
                        <small class="text-muted d-block">Rating: ${rating}</small>
                        ${event.Comments ? `<small class="text-muted d-block">${event.Comments}</small>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// ===== UTILITY FUNCTIONS =====

// Function to generate random serial number
function generateSerialNumber() {
    const prefix = 'RPH';
    const timestamp = new Date().getTime().toString().slice(-6);
    const random = Math.random().toString(36).substring(2, 6).toUpperCase();
    return `${prefix}-${timestamp}-${random}`;
}

// Function to show attendance form
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
}

// Function to verify attendance
function verifyAttendance() {
    const serialNumber = document.getElementById('serialNumber').value.trim();

    if (!serialNumber) {
        alert('Please enter a serial number');
        return;
    }

    if (!serialNumber.match(/^RPH-\d{6}-[A-Z0-9]{4}$/)) {
        alert('Invalid serial number format. Please check and try again.');
        return;
    }

    alert('Attendance verified successfully!');
    const modal = bootstrap.Modal.getInstance(document.getElementById('attendanceModal'));
    modal.hide();
}

// Function to download ID
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

// Function to deactivate account
function deactivateAccount() {
    if (confirm('Are you sure you want to deactivate your account? This action cannot be undone.')) {
        alert('Please contact support to complete account deactivation.');
    }
}

// Initiate profile edit
function initiateProfileEdit() {
    const fullName = document.getElementById('displayFullName').value;
    const email = document.getElementById('displayEmail').value;
    const phone = document.getElementById('displayPhone').value;

    const nameParts = fullName.trim().split(' ');
    const firstName = nameParts[0];
    const lastName = nameParts.slice(1).join(' ') || '';

    document.getElementById('editFirstName').value = firstName;
    document.getElementById('editLastName').value = lastName;
    document.getElementById('editEmail').value = email;
    document.getElementById('editPhone').value = phone;
    document.getElementById('editPhoto').value = '';

    const modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
    modal.show();
}

// Save profile changes - FIXED VERSION WITH BETTER ERROR HANDLING
async function saveProfileChanges() {
    const firstName = document.getElementById('editFirstName').value.trim();
    const lastName = document.getElementById('editLastName').value.trim();
    const email = document.getElementById('editEmail').value.trim();
    const phone = document.getElementById('editPhone').value.trim();
    const photoInput = document.getElementById('editPhoto');

    // Validate required fields first
    if (!firstName || !lastName || !email) {
        alert('Please fill in all required fields (marked with *)');
        return;
    }

    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        alert('Please enter a valid email address');
        return;
    }

    // Check if member ID is available
    if (!window.currentMemberId) {
        alert('Error: Member ID not loaded. Please refresh the page.');
        console.error('window.currentMemberId is not set');
        return;
    }

    try {
        let profileImagePath = null;

        // Handle profile image upload if file is selected
        if (photoInput && photoInput.files && photoInput.files.length > 0) {
            const file = photoInput.files[0];

            console.log('Uploading file:', file.name, 'Size:', file.size, 'Type:', file.type);

            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Image size must be less than 2MB');
                return;
            }

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please upload a valid image file (JPG, PNG, GIF, or WebP)');
                return;
            }

            // Upload image first using FormData
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'profile');

            console.log('Sending file upload request to api/upload-image.php');

            const uploadResponse = await fetch('api/upload-image.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            console.log('Upload response status:', uploadResponse.status);

            if (!uploadResponse.ok) {
                const errorText = await uploadResponse.text();
                console.error('Upload failed:', errorText);
                throw new Error(`HTTP error! status: ${uploadResponse.status}`);
            }

            const uploadData = await uploadResponse.json();
            console.log('Upload response:', uploadData);

            if (uploadData.success) {
                profileImagePath = uploadData.imagePath;
                console.log('Image uploaded successfully:', profileImagePath);
            } else {
                alert('Error uploading image: ' + (uploadData.message || 'Unknown error'));
                console.error('Upload error:', uploadData);
                return;
            }
        }

        // Update profile with or without image
        const updatePayload = {
            action: 'updateProfile',
            memberId: window.currentMemberId,
            firstName: firstName,
            lastName: lastName,
            email: email,
            phone: phone,
            profileImage: profileImagePath
        };

        console.log('Sending profile update with payload:', updatePayload);

        const response = await fetch('api/update-member.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(updatePayload)
        });

        console.log('Update response status:', response.status);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Update failed:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Update response:', data);

        if (data.success) {
            // Update display fields
            document.getElementById('displayFullName').value = `${firstName} ${lastName}`;
            document.getElementById('displayEmail').value = email;
            document.getElementById('displayPhone').value = phone;

            // Update profile image if uploaded
            if (profileImagePath && document.getElementById('displayProfileImage')) {
                document.getElementById('displayProfileImage').src = profileImagePath;
            }

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
            if (modal) {
                modal.hide();
            }

            // Reload profile to refresh all data
            await loadMemberProfile();

            alert('Profile updated successfully!');
        } else {
            alert('Error updating profile: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving profile:', error);
        alert('An error occurred while saving your profile. Please try again.');
    }
}