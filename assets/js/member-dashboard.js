// ===== MEMBER DASHBOARD FUNCTIONS =====
// File: assets/js/member-dashboard.js
// Used ONLY by member-dashboard.html
// Contains all member profile, activities, and registration functions

// ===== UTILITY FUNCTIONS =====

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function loadMemberProfile() {
    try {
        const response = await fetch('api/get-members.php');
        const data = await response.json();

        if (data.success && data.currentMemberId) {
            const currentMember = data.members.find(m => m.MemberID == data.currentMemberId);

            if (currentMember) {
                // Store member ID for use throughout the page
                window.currentMemberId = currentMember.MemberID;
                window.currentMemberEmail = currentMember.ApplicantEmail;
                window.currentMemberName = currentMember.FName + ' ' + currentMember.LName;

                // Update display fields - with safety checks
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
                        displayProfileImage.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"%3E%3Crect width="200" height="200" fill="%23e9ecef"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="14" fill="%23999"%3ENo Photo%3C/text%3E%3C/svg%3E';
                    }
                }

                // Update ID preview
                const idPreview = document.getElementById('idPreview');
                if (idPreview) {
                    let photoHtml = '';
                    if (currentMember.ProfileImage && currentMember.ProfileImage !== 'null' && currentMember.ProfileImage !== '' && currentMember.ProfileImage !== undefined) {
                        photoHtml = `<img src="${currentMember.ProfileImage}" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                    } else {
                        photoHtml = `<div class="h-100 d-flex align-items-center justify-content-center text-muted">2x2 Photo</div>`;
                    }

                    idPreview.style.cssText = `
                        width: 100%;
                        height: auto;
                        max-width: 350px;
                        aspect-ratio: 3.5 / 4.5;
                        margin: 0 auto;
                    `;

                    idPreview.innerHTML = `
                        <div style="
                            width: 100%;
                            height: 100%;
                            background: linear-gradient(135deg, #0A4FA3 0%, #035996 100%);
                            color: white;
                            padding: 5%;
                            display: flex;
                            flex-direction: column;
                            font-family: 'Segoe UI', Roboto, sans-serif;
                            box-sizing: border-box;
                            border-radius: 12px;
                            gap: 3%;
                        ">
                            
                            <!-- Header -->
                            <div style="
                                display: flex;
                                align-items: center;
                                justify-content: space-between;
                                border-bottom: 1px solid rgba(255,255,255,0.3);
                                padding-bottom: 3%;
                                gap: 3%;
                                flex-shrink: 0;
                            ">
                                <img src="assets/image/reboot-logo.png" alt="Reboot Logo" style="
                                    height: 11%;
                                    width: 11%;
                                    border-radius: 50%;
                                    background: white;
                                    padding: 1%;
                                    flex-shrink: 0;
                                ">
                                <div style="
                                    text-align: right;
                                    font-size: 2.5%;
                                    line-height: 1.2;
                                    flex: 1;
                                ">
                                    <div style="font-weight: bold; font-size: 3.2%;">Reboot Philippines</div>
                                    <div style="opacity: 0.9; font-size: 2.3%;">2804, Discovery Centre, Pasig</div>
                                </div>
                            </div>

                            <!-- Main Content Row -->
                            <div style="
                                display: flex;
                                gap: 4%;
                                flex: 1;
                                align-items: center;
                                min-width: 0;
                            ">
                                
                                <!-- Left Side: Photo -->
                                <div style="
                                    flex-shrink: 0;
                                    display: flex;
                                    align-items: center;
                                ">
                                    <div style="
                                        width: 25%;
                                        aspect-ratio: 1;
                                        border-radius: 50%;
                                        background: white;
                                        border: 2px solid white;
                                        overflow: hidden;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        flex-shrink: 0;
                                    ">
                                        ${photoHtml}
                                    </div>
                                </div>

                                <!-- Center: Info -->
                                <div style="
                                    flex: 1;
                                    min-width: 0;
                                    display: flex;
                                    flex-direction: column;
                                    gap: 1%;
                                ">
                                    <div style="
                                        font-size: 3.5%;
                                        font-weight: bold;
                                        overflow: hidden;
                                        display: -webkit-box;
                                        -webkit-line-clamp: 2;
                                        -webkit-box-orient: vertical;
                                        line-height: 1.2;
                                    ">${window.currentMemberName}</div>
                                    <div style="
                                        font-size: 2.2%;
                                        opacity: 0.85;
                                    ">Role:</div>
                                    <div style="
                                        font-size: 3%;
                                        font-weight: 600;
                                        opacity: 0.95;
                                        overflow: hidden;
                                        display: -webkit-box;
                                        -webkit-line-clamp: 2;
                                        -webkit-box-orient: vertical;
                                        line-height: 1.2;
                                    ">${currentMember.Role || 'Member'}</div>
                                    <div style="
                                        font-size: 2.2%;
                                        opacity: 0.85;
                                    ">ID: RPH-${currentMember.MemberID.toString().padStart(7, '0')}</div>
                                </div>

                                <!-- Right Side: QR Code -->
                                <div style="
                                    flex-shrink: 0;
                                    display: flex;
                                    align-items: center;
                                ">
                                    <img id="memberDetailsQRCode" data-member-id="${currentMember.MemberID}" src="" alt="Member QR Code" 
                                         style="
                                            width: 22%;
                                            aspect-ratio: 1;
                                            background: white;
                                            padding: 1%;
                                            border-radius: 2%;
                                            flex-shrink: 0;
                                        ">
                                </div>

                            </div>

                            <!-- Footer -->
                            <div style="
                                border-top: 1px solid rgba(255,255,255,0.3);
                                padding-top: 2%;
                                font-size: 2.2%;
                                text-align: center;
                                opacity: 0.9;
                                line-height: 1.3;
                                flex-shrink: 0;
                            ">
                                <div>Member Since:</div>
                                <div style="font-weight: 600;">${joinDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</div>
                                <div>${currentMember.isActive ? '✓ Active' : 'Inactive'}</div>
                            </div>

                        </div>
                    `;
                    
                    // Generate QR code after DOM is fully updated
                    const generateMemberQR = () => {
                        const qrImg = document.getElementById('memberDetailsQRCode');
                        if (qrImg && currentMember.MemberID) {
                            const memberId = 'RPH-' + currentMember.MemberID.toString().padStart(7, '0');
                            const protocol = window.location.protocol;
                            const host = window.location.host;
                            const memberDetailsUrl = protocol + '//' + host + '/view-member.html?id=' + encodeURIComponent(memberId);
                            const timestamp = Math.random(); // Force unique requests
                            const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' + encodeURIComponent(memberDetailsUrl) + '&nocache=' + timestamp;
                            
                            // Set src directly
                            qrImg.src = qrUrl;
                            qrImg.setAttribute('src', qrUrl);
                            
                            console.log('Generated QR for:', memberId);
                            console.log('QR Target URL:', memberDetailsUrl);
                            console.log('QR API URL:', qrUrl);
                        } else {
                            console.warn('QR img not found or no member ID');
                        }
                    };
                    
                    // Try multiple times to ensure it works
                    setTimeout(generateMemberQR, 100);
                    setTimeout(generateMemberQR, 300);
                    setTimeout(generateMemberQR, 600);
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
async function loadDashboardStats() {
    try {
        console.log('loadDashboardStats() called');
        
        // Check if dashboard element exists first
        const dashboardSection = document.getElementById('dashboard');
        if (!dashboardSection) {
            console.warn('Dashboard section not found - skipping stats update');
            return;
        }
        
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

        const allH2 = dashboardSection.querySelectorAll('.col-md-4 h2');
        
        if (allH2.length >= 3) {
            allH2[0].textContent = totalActivities;
            allH2[1].textContent = upcomingActivities;
            allH2[2].textContent = attendedCount;
            console.log('Updated dashboard stats successfully');
        } else {
            console.warn('Could not find 3 stat cards. Found h2 count:', allH2.length);
        }
        
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

// Load upcoming events for the dashboard (Imminent Activities)
async function loadUpcomingEvents() {
    try {
        const dashboardSection = document.getElementById('dashboard');
        
        if (!dashboardSection) {
            console.warn('Dashboard section not found - skipping upcoming events load');
            return;
        }
        
        const isDashboardVisible = !dashboardSection.classList.contains('d-none') && 
                                   dashboardSection.classList.contains('active-section');
        
        if (!isDashboardVisible) {
            console.log('Dashboard not visible, skipping events load');
            return;
        }
        
        const response = await fetch('api/get-member-events.php?type=upcoming');
        const data = await response.json();

        if (data.success && data.events && data.events.length > 0) {
            const sortedEvents = data.events.sort((a, b) => {
                const dateA = new Date(a.ProposedDate + ' ' + (a.StartTime || '00:00:00'));
                const dateB = new Date(b.ProposedDate + ' ' + (b.StartTime || '00:00:00'));
                return dateA - dateB;
            });
            populateUpcomingEventsList(sortedEvents);
        } else {
            showNoEventsMessage();
        }
    } catch (error) {
        console.error('Error loading upcoming events:', error);
        const dashboardSection = document.getElementById('dashboard');
        if (dashboardSection) {
            showNoEventsMessage(true);
        }
    }
}

// Helper function to show "no events" or error message
function showNoEventsMessage(isError = false) {
    const container = document.getElementById('dashboardEventsList');
    if (!container) return;
    
    if (isError) {
        container.innerHTML = '<div class="text-center text-danger py-4">Error loading events. Please try again.</div>';
    } else {
        container.innerHTML = '<div class="text-center text-muted py-4">No upcoming events available</div>';
    }
}

// Populate upcoming events list on dashboard
function populateUpcomingEventsList(events) {
    const container = document.getElementById('dashboardEventsList');

    if (!container) {
        console.warn('dashboardEventsList container not found');
        return;
    }

    if (!events || events.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">No upcoming events available</div>';
        return;
    }

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

        const registerBtn = event.MemberRegistered === 1
            ? `<button class="btn btn-sm btn-success" disabled><i class="bi bi-check-circle"></i> Registered</button>`
            : `<button class="btn btn-sm btn-outline-primary register-btn" data-event-id="${event.EventID}">Register Now</button>`;

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
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-secondary view-event-btn" data-event-id="${event.EventID}" data-event-title="${escapeHtml(event.Title)}">View Details</button>
                        ${event.MemberRegistered === 1 ? `<button class="btn btn-sm btn-info scan-qr-btn" data-event-id="${event.EventID}" title="Scan QR Code for attendance"><i class="bi bi-qr-code"></i> Scan QR</button>` : ''}
                        ${registerBtn}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Show confirmation modal for event registration (Dashboard) - Now fetches data from database
async function showEventConfirmation(eventId) {
    try {
        console.log('showEventConfirmation called with eventId:', eventId);
        
        // Check if modal element exists
        const modalElement = document.getElementById('eventConfirmationModal');
        if (!modalElement) {
            console.error('Modal element not found with ID: eventConfirmationModal');
            alert('Error: Confirmation modal not found on page');
            return;
        }
        console.log('Modal element found:', modalElement);
        
        // Fetch event details from database
        console.log('Fetching event details from api/get-event-details.php?id=' + eventId);
        const response = await fetch(`api/get-event-details.php?id=${eventId}`);
        
        if (!response.ok) {
            console.error('API response not OK:', response.status, response.statusText);
            alert('Error: Could not load event details (HTTP ' + response.status + ')');
            return;
        }
        
        const data = await response.json();
        console.log('API response data:', data);

        if (!data.success || !data.event) {
            console.error('API response indicates failure or missing event:', data);
            alert('Error: Could not load event details - ' + (data.message || 'Unknown error'));
            return;
        }

        const event = data.event;
        console.log('Event data retrieved:', event);

        // Store event data in window for confirmation
        window.pendingEventRegistration = {
            eventId: event.EventID,
            eventTitle: event.Title,
            eventDate: event.ProposedDate,
            startTime: event.StartTime,
            endTime: event.EndTime,
            venue: event.Venue,
            serialNumber: '',
            capacity: event.Capacity || 0,
            registeredCount: event.RegisteredCount || 0,
            description: event.Description
        };

        // Populate modal with event details
        const titleEl = document.getElementById('confirmEventTitle');
        const dateTimeEl = document.getElementById('confirmEventDateTime');
        const durationEl = document.getElementById('confirmEventDuration');
        const venueEl = document.getElementById('confirmEventVenue');
        const capacityEl = document.getElementById('confirmEventCapacity');
        const descriptionEl = document.getElementById('confirmEventDescription');
        
        if (!titleEl || !dateTimeEl || !durationEl || !venueEl || !capacityEl || !descriptionEl) {
            console.error('One or more modal content elements not found');
            alert('Error: Modal structure incomplete');
            return;
        }
        
        titleEl.textContent = event.Title;
        
        const eventDateTime = new Date(event.ProposedDate);
        const formattedDate = eventDateTime.toLocaleDateString('en-US', { 
            weekday: 'long',
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        dateTimeEl.textContent = `${formattedDate} | ${event.StartTime} - ${event.EndTime}`;
        durationEl.textContent = `Duration: ${event.StartTime} to ${event.EndTime}`;
        
        venueEl.textContent = event.Venue || 'TBA';
        
        const spotsLeft = (event.Capacity || 0) - (event.RegisteredCount || 0);
        const capacityText = spotsLeft > 0 
            ? `${spotsLeft} spots available (${event.RegisteredCount || 0}/${event.Capacity || 0} registered)`
            : 'Event is Full';
        const capacityClass = spotsLeft > 0 ? 'text-success' : 'text-danger';
        capacityEl.innerHTML = `<span class="${capacityClass}"><strong>${capacityText}</strong></span>`;
        
        // Set description with proper text content (not HTML)
        const description = event.Description || 'No additional details available';
        descriptionEl.textContent = description;
        console.log('Description set. Length:', description.length, 'First 100 chars:', description.substring(0, 100));

        // Show modal
        console.log('Attempting to show modal...');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        console.log('Modal shown successfully');
    } catch (error) {
        console.error('Error in showEventConfirmation:', error);
        console.error('Error stack:', error.stack);
        alert('Error loading event details: ' + error.message);
    }
}

// Confirm the event registration after user clicks confirm
async function confirmEventRegistration() {
    const eventData = window.pendingEventRegistration;
    
    if (!eventData) {
        alert('Error: Event data not found');
        return;
    }

    try {
        const confirmBtn = document.getElementById('confirmRegistrationBtn');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registering...';

        const response = await fetch('api/manage-registration.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'register',
                memberId: window.currentMemberId,
                eventId: eventData.eventId
            })
        });

        const data = await response.json();

        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('eventConfirmationModal'));
            if (modal) {
                modal.hide();
            }

            // Show success message
            alert(`Successfully registered for "${eventData.eventTitle}"!`);
            
            // Refresh event lists
            loadUpcomingEvents();
            loadDashboardStats();
            loadUpcomingEventsTab();
            loadRegisteredEventsTab();

            // Clear pending registration
            window.pendingEventRegistration = null;
        } else {
            alert('Error: ' + (data.message || 'Registration failed'));
        }
    } catch (error) {
        console.error('Error registering for event:', error);
        alert('Error registering for event. Please try again.');
    } finally {
        // Re-enable button
        const confirmBtn = document.getElementById('confirmRegistrationBtn');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Confirm Registration';
    }
}

// Register for event from dashboard (now shows confirmation)
function registerForEventDashboard(eventId) {
    console.log('registerForEventDashboard called with eventId:', eventId);
    showEventConfirmation(eventId);
}

// Register for event from activities tab (now shows confirmation)
function registerForEventTab(eventId) {
    showEventConfirmation(eventId);
}

// ===== MY ACTIVITIES TAB =====

async function loadUpcomingEventsTab(memberId = null) {
    try {
        const container = document.getElementById('upcomingEventsList');
        if (!container) return;
        
        const url = memberId ? `api/get-member-events.php?type=upcoming&memberId=${memberId}` : 'api/get-member-events.php?type=upcoming';
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`API responded with status ${response.status}`);
        }
        
        const data = await response.json();

        if (data.success && data.events) {
            const sortedEvents = data.events.sort((a, b) => {
                const dateA = new Date(a.ProposedDate + ' ' + a.StartTime);
                const dateB = new Date(b.ProposedDate + ' ' + b.StartTime);
                return dateA - dateB;
            });
            populateUpcomingEventsTab(sortedEvents);
        } else {
            container.innerHTML = '<div class="text-center text-muted py-4">No upcoming events available</div>';
        }
    } catch (error) {
        console.error('Error loading upcoming events:', error);
        const container = document.getElementById('upcomingEventsList');
        if (container) {
            container.innerHTML = `<div class="text-center text-danger py-4">Error loading events: ${error.message}</div>`;
        }
    }
}

function populateUpcomingEventsTab(events) {
    const container = document.getElementById('upcomingEventsList');
    if (!container) return;

    // Filter for events the member has NOT registered for (MemberRegistered === 0)
    const unregisteredEvents = events.filter(event => event.MemberRegistered === 0);

    // 1. Handle the case where there are no upcoming events
    if (!unregisteredEvents || unregisteredEvents.length === 0) {
        // Clear the container and show a "No events" message, using a structure similar to the original placeholder
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <p>No upcoming events available for registration.</p>
            </div>
        `;
        return;
    }

    // 2. Generate the HTML for each event using the new Card/D-flex design
    container.innerHTML = unregisteredEvents.map(event => {
        const eventDate = new Date(event.ProposedDate);
        const today = new Date();
        // Calculate days until the event
        const daysUntil = Math.ceil((eventDate - today) / (1000 * 60 * 60 * 24));

        let timeBadgeClass = '';
        let timeBadgeText = '';

        if (daysUntil === 0) {
            timeBadgeText = 'Today';
            timeBadgeClass = 'text-danger'; // Design: Text red for today
        } else if (daysUntil === 1) {
            timeBadgeText = 'Tomorrow';
            timeBadgeClass = 'text-warning'; // Design: Text yellow for tomorrow
        } else if (daysUntil <= 7) {
            timeBadgeText = 'Next Week'; // Simplified for the Card design example: less than or equal to a week
            timeBadgeClass = 'text-primary';
        } else {
            timeBadgeText = `${Math.ceil(daysUntil / 7)} Weeks`; // Simplified: show weeks for longer periods
            timeBadgeClass = 'text-muted'; 
        }

        // --- Capacity and Registration Status Badge ---
        const spotsLeft = event.Capacity - event.RegisteredCount;
        let capacityBadgeClass = '';
        let capacityBadgeText = '';
        let registerButtonDisabled = '';
        
        if (spotsLeft <= 0) {
            capacityBadgeText = 'Event Full';
            capacityBadgeClass = 'bg-danger';
            registerButtonDisabled = 'disabled'; // Disable button if full
        } else if (spotsLeft <= 10) { // Example: Consider 10 spots left as 'Few Slots Left'
            capacityBadgeText = `${spotsLeft} Slots Left`;
            capacityBadgeClass = 'bg-warning text-dark';
        } else {
            capacityBadgeText = 'Registration Open';
            capacityBadgeClass = 'bg-success';
        }

        // Function to select an emoji based on a simple type (you might need a more complex mapping)
        const getIconEmoji = (title) => {
            if (title.toLowerCase().includes('clean') || title.toLowerCase().includes('beach')) return { emoji: '🌊', bg: 'bg-primary', text: 'text-primary' };
            if (title.toLowerCase().includes('forum') || title.toLowerCase().includes('talk')) return { emoji: '🎤', bg: 'bg-danger', text: 'text-danger' };
            if (title.toLowerCase().includes('workshop') || title.toLowerCase().includes('awareness')) return { emoji: '📢', bg: 'bg-success', text: 'text-success' };
            return { emoji: '🗓️', bg: 'bg-info', text: 'text-info' }; // Default
        };

        const icon = getIconEmoji(event.Title);

        return `
            <div class="card mb-3 shadow-sm">
                <div class="card-body d-flex">
                    <div class="me-3 d-flex align-items-start">
                        <span class="${icon.bg} bg-opacity-10 ${icon.text} p-3 rounded-circle fs-4">
                            ${icon.emoji}
                        </span>
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-1">${event.Title}</h5>
                            <small class="${timeBadgeClass}">${timeBadgeText}</small>
                        </div>

                        ${event.Description ? `<p class="mb-2 small">${event.Description}</p>` : ''}
                        
                        <small class="d-block text-muted">
                            📅 ${eventDate.toLocaleDateString()} • ⏰ ${event.StartTime} - ${event.EndTime}
                        </small>
                        <small class="d-block text-muted">📍 ${event.Venue}</small>
                        <small class="d-block text-muted">
                            👥 Capacity: ${event.RegisteredCount}/${event.Capacity}
                        </small>

                        <div class="mt-3">
                            <span class="badge ${capacityBadgeClass}">${capacityBadgeText}</span>
                            <button class="btn btn-sm btn-outline-primary ms-2 register-btn-tab" 
                                data-event-id="${event.EventID}" ${registerButtonDisabled}>
                                Register Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
}
// Show confirmation for activities tab registration
function showEventConfirmationTab(eventId) {
    showEventConfirmation(eventId);
}

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

function populateRegisteredEventsTab(events) {
    const container = document.getElementById('registeredEventsList');
    if (!container) return;

    if (!events || events.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">You haven\'t registered for any events yet.</div>';
        return;
    }

    // Helper function to check if event is currently happening (NOW between start and end time)
    function isEventOngoing(proposedDate, startTime, endTime) {
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
            
            console.log(`[Ongoing Check] ${proposedDate} ${startTime}-${endTime}`);
            console.log(`  Now: ${now.toLocaleTimeString()}, Start: ${eventStart.toLocaleTimeString()}, End: ${eventEnd.toLocaleTimeString()}`);
            console.log(`  Is Ongoing: ${now >= eventStart && now <= eventEnd}`);
            
            // Return true ONLY if current time is between start and end time
            return now >= eventStart && now <= eventEnd;
        } catch (e) {
            console.error('Error in isEventOngoing:', e);
            return false;
        }
    }

    // Helper function to check event status based on current time
    function checkEventStatus(proposedDate, startTime, endTime) {
        try {
            const now = new Date();
            
            // Parse date string (format: YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS)
            const datePart = proposedDate.split('T')[0]; // Remove any time part if present
            const [year, month, day] = datePart.split('-');
            
            // Parse start time (format: HH:MM:SS)
            const [startHour, startMin, startSec] = (startTime || '00:00:00').split(':');
            // Parse end time (format: HH:MM:SS)
            const [endHour, endMin, endSec] = (endTime || '23:59:59').split(':');
            
            // Create date objects using local time to match database timezone
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
            
            console.log(`[Registered Event Status Check]`);
            console.log(`  Current Time: ${now.toString()}`);
            console.log(`  Event Start: ${eventStart.toString()}`);
            console.log(`  Event End: ${eventEnd.toString()}`);
            console.log(`  Now > EventEnd: ${now > eventEnd}`);
            console.log(`  Now >= EventStart && Now <= EventEnd: ${now >= eventStart && now <= eventEnd}`);
            
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
            console.error('Error in checkEventStatus (Registered):', e, proposedDate, startTime, endTime);
            return 'Scheduled';
        }
    }

    container.innerHTML = events.map(event => {
        const eventDate = new Date(event.ProposedDate);
        const eventStatus = checkEventStatus(event.ProposedDate, event.StartTime, event.EndTime);
        const attended = event.AttendanceCount > 0;
        const isOngoing = isEventOngoing(event.ProposedDate, event.StartTime, event.EndTime);
        
        // Debug logging
        console.log(`Event: ${event.Title}, EventStatus from API: ${event.EventStatus}, Calculated Status: ${eventStatus}, AttendanceCount: ${event.AttendanceCount}, Attended: ${attended}, IsOngoing: ${isOngoing}`);
        console.log(`  ProposedDate: ${event.ProposedDate}, StartTime: ${event.StartTime}, EndTime: ${event.EndTime}`);

        let statusBadge = '';
        if (attended) statusBadge = '<span class="badge bg-success">✓ Attended</span>';
        else if (eventStatus === 'Ongoing') statusBadge = '<span class="badge bg-danger">🔴 Ongoing</span>';
        else if (eventStatus === 'Near') statusBadge = '<span class="badge bg-warning text-dark">⚠️ Near</span>';
        else if (eventStatus === 'Scheduled') statusBadge = '<span class="badge bg-primary">📅 Scheduled</span>';
        else statusBadge = '<span class="badge bg-secondary">✓ Completed</span>';

        const unregisterBtn = (eventStatus === 'Scheduled' || eventStatus === 'Near')
            ? `<button class="btn btn-sm btn-outline-danger unregister-btn" data-event-id="${event.EventID}" data-event-title="${escapeHtml(event.Title)}">Cancel Registration</button>`
            : '';

        // Show attendance button ONLY if event is currently happening (now is between start and end time)
        const attendanceBtn = (isOngoing && !attended)
            ? `<button class="btn btn-sm btn-danger" onclick="openAttendanceModal(${event.EventID}, '${escapeHtml(event.Title)}', '${event.ProposedDate}', '${event.StartTime}')">
                <i class="bi bi-qr-code"></i> Mark Attendance (QR)
              </button>`
            : '';

        const regDate = new Date(event.RegistrationDate);

        return `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                    <h6 class="mb-1">${event.Title}</h6>
                    ${statusBadge}
                </div>
                <p class="mb-2 text-muted small">
                    📅 ${eventDate.toLocaleDateString()} | ⏰ ${event.StartTime} - ${event.EndTime}<br>
                    📍 ${event.Venue}<br>
                    Registered on: ${regDate.toLocaleDateString()}
                </p>
                ${event.Description ? `<p class="mb-2 small">${event.Description}</p>` : ''}
                <div class="d-flex gap-2">
                    ${unregisterBtn}
                    ${attendanceBtn}
                </div>
            </div>
        `;
    }).join('');
}

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

function populateCompletedEventsTab(events) {
    const container = document.getElementById('completedEventsList');
    if (!container) return;

    if (!events || events.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">No completed events yet.</div>';
        return;
    }

    // Helper function to check event status based on current time
    function checkEventStatus(proposedDate, startTime, endTime) {
        try {
            const now = new Date();
            
            // Parse date string (format: YYYY-MM-DD)
            const [year, month, day] = proposedDate.split('-');
            
            // Parse start time (format: HH:MM:SS)
            const [startHour, startMin, startSec] = startTime.split(':');
            // Parse end time (format: HH:MM:SS)
            const [endHour, endMin, endSec] = endTime.split(':');
            
            // Create date objects with proper timezone handling
            const eventStart = new Date(year, month - 1, day, startHour, startMin, startSec);
            const eventEnd = new Date(year, month - 1, day, endHour, endMin, endSec);
            
            console.log('Completed - Now:', now, 'Start:', eventStart, 'End:', eventEnd);
            
            // If event end time has passed, mark as Completed
            if (now > eventEnd) {
                return 'Completed';
            }
            
            // If event has started but not ended, mark as Ongoing
            if (now >= eventStart && now <= eventEnd) {
                return 'Ongoing';
            }
            
            return 'Scheduled';
        } catch (e) {
            console.error('Error in checkEventStatus:', e, proposedDate, startTime, endTime);
            return 'Scheduled';
        }
    }

    container.innerHTML = events.map(event => {
        const eventDate = new Date(event.ProposedDate);
        const eventStatus = checkEventStatus(event.ProposedDate, event.StartTime, event.EndTime);
        const attended = event.AttendanceID ? 'Yes' : 'No';
        const rating = event.Rating ? `${event.Rating}/5 ⭐` : 'Not rated';
        const hasFeedback = event.FeedbackID ? true : false;

        // Determine badge based on event status
        let attendedBadge = '';
        if (eventStatus === 'Ongoing') {
            attendedBadge = '<span class="badge bg-danger">🔴 Ongoing</span>';
        } else if (event.AttendanceID) {
            attendedBadge = '<span class="badge bg-success">Attended</span>';
        } else {
            attendedBadge = '<span class="badge bg-warning">Did not attend</span>';
        }

        let actionButton = '';
        // Only show feedback button for attended events (no attendance button in completed tab to prevent exploitation)
        if (event.AttendanceID) {
            // Show feedback button for attended events
            if (hasFeedback) {
                actionButton = `
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="openFeedbackForm(${event.EventID}, ${event.AttendanceID}, true)">
                        <i class="bi bi-pencil"></i> Update Feedback
                    </button>
                `;
            } else {
                actionButton = `
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
                    ${actionButton}
                </div>
            </div>
        `;
    }).join('');
}

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
    
    // Add event listener to stop scanner when modal is hidden
    const attendanceModalEl = document.getElementById('attendanceModal');
    attendanceModalEl.addEventListener('hidden.bs.modal', stopQRScanner, { once: true });
    
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
                // QR code scanned successfully
                document.getElementById('serialNumber').value = decodedText;
                console.log('QR Code Scanned:', decodedText);
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

function openFeedbackForm(eventId, attendanceId, hasFeedback) {
    window.location.href = `feedback.html?eventId=${eventId}&attendanceId=${attendanceId}`;
}

// ===== ACTIVITY HISTORY PAGE =====

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

function generateSerialNumber() {
    const prefix = 'RPH';
    const timestamp = new Date().getTime().toString().slice(-6);
    const random = Math.random().toString(36).substring(2, 6).toUpperCase();
    return `${prefix}-${timestamp}-${random}`;
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
}

async function verifyAttendance() {
    const serialNumber = document.getElementById('serialNumber').value.trim();
    const eventId = window.currentEventId;

    if (!serialNumber) {
        alert('Please enter a serial number or scan a QR code');
        return;
    }

    if (!eventId) {
        alert('Error: Event ID not found. Please try again.');
        return;
    }

    try {
        // First, mark attendance using the serial number (which is the registration ID or member ID)
        const response = await fetch('api/manage-attendance.php?action=markAttendance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                eventId: eventId,
                memberId: parseInt(serialNumber) || serialNumber
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Attendance marked successfully!');
            stopQRScanner();
            const modal = bootstrap.Modal.getInstance(document.getElementById('attendanceModal'));
            modal.hide();
            
            // Reload registered events to update the UI
            loadRegisteredEventsTab();
        } else {
            alert('Error: ' + (data.message || 'Failed to mark attendance'));
        }
    } catch (error) {
        console.error('Error verifying attendance:', error);
        alert('Error: ' + error.message);
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

function deactivateAccount() {
    if (confirm('Are you sure you want to deactivate your account? This action cannot be undone.')) {
        alert('Please contact support to complete account deactivation.');
    }
}

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

async function saveProfileChanges() {
    const firstName = document.getElementById('editFirstName').value.trim();
    const lastName = document.getElementById('editLastName').value.trim();
    const email = document.getElementById('editEmail').value.trim();
    const phone = document.getElementById('editPhone').value.trim();
    const photoInput = document.getElementById('editPhoto');

    if (!firstName || !lastName || !email) {
        alert('Please fill in all required fields (marked with *)');
        return;
    }

    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        alert('Please enter a valid email address');
        return;
    }

    if (!window.currentMemberId) {
        alert('Error: Member ID not loaded. Please refresh the page.');
        console.error('window.currentMemberId is not set');
        return;
    }

    try {
        let profileImagePath = null;

        if (photoInput && photoInput.files && photoInput.files.length > 0) {
            const file = photoInput.files[0];
            
            console.log('Uploading file:', file.name, 'Size:', file.size, 'Type:', file.type);
            
            if (file.size > 2 * 1024 * 1024) {
                alert('Image size must be less than 2MB');
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please upload a valid image file (JPG, PNG, GIF, or WebP)');
                return;
            }

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
            const displayFullName = document.getElementById('displayFullName');
            const displayEmail = document.getElementById('displayEmail');
            const displayPhone = document.getElementById('displayPhone');
            const displayProfileImage = document.getElementById('displayProfileImage');

            if (displayFullName) displayFullName.value = `${firstName} ${lastName}`;
            if (displayEmail) displayEmail.value = email;
            if (displayPhone) displayPhone.value = phone;

            if (profileImagePath && displayProfileImage) {
                displayProfileImage.src = profileImagePath;
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
            if (modal) {
                modal.hide();
            }
            
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

// View event details
async function viewEventDetails(eventId, eventTitle) {
    try {
        const response = await fetch(`api/get-event-details.php?id=${eventId}`);
        const data = await response.json();

        if (data.success && data.event) {
            const event = data.event;
            const eventDate = new Date(event.ProposedDate);
            const formattedDate = eventDate.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            const spotsLeft = event.Capacity - event.RegisteredCount;
            const capacityText = spotsLeft > 0 ? `${spotsLeft} spots available` : 'Event Full';
            
            const detailsHTML = `
<div style="padding: 20px; background: white; border-radius: 8px;">
  <h4 style="margin-bottom: 15px; color: #333;">${event.Title}</h4>
  <div style="line-height: 1.8;">
    <p><strong>📅 Date:</strong> ${formattedDate}</p>
    <p><strong>⏰ Time:</strong> ${event.StartTime} - ${event.EndTime}</p>
    <p><strong>📍 Location:</strong> ${event.Venue || 'TBA'}</p>
    <p><strong>👥 Capacity:</strong> ${event.RegisteredCount}/${event.Capacity} (${capacityText})</p>
    <p><strong>👨‍💼 Staff Required:</strong> ${event.StaffRequired || 0}</p>
    <p><strong>🎯 Target Participants:</strong> ${event.TargetParticipants || 'Not specified'}</p>
    <p><strong>📝 Description:</strong></p>
    <p style="color: #666; margin-left: 15px;">${event.Description || 'No additional details available'}</p>
    <p><strong>Status:</strong> ${event.Status || 'Upcoming'}</p>
  </div>
</div>
            `;
            
            const alertDiv = document.createElement('div');
            alertDiv.innerHTML = detailsHTML;
            alertDiv.style.position = 'fixed';
            alertDiv.style.top = '50%';
            alertDiv.style.left = '50%';
            alertDiv.style.transform = 'translate(-50%, -50%)';
            alertDiv.style.zIndex = '10000';
            alertDiv.style.maxWidth = '500px';
            alertDiv.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
            
            const backdrop = document.createElement('div');
            backdrop.style.position = 'fixed';
            backdrop.style.top = '0';
            backdrop.style.left = '0';
            backdrop.style.width = '100%';
            backdrop.style.height = '100%';
            backdrop.style.backgroundColor = 'rgba(0,0,0,0.5)';
            backdrop.style.zIndex = '9999';
            backdrop.onclick = () => {
                backdrop.remove();
                alertDiv.remove();
            };
            
            document.body.appendChild(backdrop);
            document.body.appendChild(alertDiv);
        } else {
            alert('Could not load event details');
        }
    } catch (error) {
        console.error('Error loading event details:', error);
        alert('Error loading event details. Please try again.');
    }
}

// Event listener for register buttons using data attributes
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('register-btn')) {
        console.log('Register button clicked!', e.target);
        const btn = e.target;
        const eventId = btn.dataset.eventId;
        
        console.log('Event ID from button:', eventId);
        
        // Fetch event details from database instead of inline data
        registerForEventDashboard(eventId);
    }
});

// Event listener for view details buttons using data attributes
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('view-event-btn')) {
        const btn = e.target;
        const eventId = btn.dataset.eventId;
        const eventTitle = btn.dataset.eventTitle;
        
        viewEventDetails(eventId, eventTitle);
    }
});

// Event listener for scan QR code buttons using data attributes
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('scan-qr-btn')) {
        const btn = e.target;
        const eventId = btn.dataset.eventId;
        
        showQRScanModal(eventId);
    }
});

// Event listener for activities tab register buttons using data attributes
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('register-btn-tab')) {
        const btn = e.target;
        const eventId = btn.dataset.eventId;
        
        // Fetch event details from database instead of inline data
        showEventConfirmationTab(eventId);
    }
});

// ===== QR CODE AND ATTENDANCE FUNCTIONS =====

// Show QR code scan modal for attendance
async function showQRScanModal(eventId) {
    try {
        // You can show a modal or start QR scanning here
        // For now, we'll open the attendance section and select the event
        const select = document.getElementById('attendanceEventSelect');
        if (select) {
            select.value = eventId;
            // Trigger change event
            select.dispatchEvent(new Event('change'));
            
            // Navigate to attendance section
            const attendanceNav = document.querySelector('a[href="#attendance"]');
            if (attendanceNav) {
                attendanceNav.click();
            }
        }
    } catch (error) {
        console.error('Error showing QR scan modal:', error);
    }
}

// Load ongoing events for attendance
async function loadOngoingEventsForAttendance() {
    try {
        const response = await fetch('api/manage-attendance.php?action=getUpcomingAndOngoingEvents');
        const data = await response.json();
        
        if (data.success && data.events) {
            // Filter for only ongoing/near events
            const ongoingEvents = data.events.filter(e => 
                e.status === 'Ongoing' || e.status === 'Near'
            );
            
            const select = document.getElementById('attendanceEventSelect');
            const container = document.getElementById('attendanceTableContainer');
            const noEventMsg = document.getElementById('noEventMessage');
            
            if (ongoingEvents.length === 0) {
                select.innerHTML = '<option value="">-- Choose an Event --</option>';
                container.style.display = 'none';
                noEventMsg.style.display = 'block';
                document.getElementById('scanQRBtn').disabled = true;
            } else {
                select.innerHTML = '<option value="">-- Choose an Event --</option>' + 
                    ongoingEvents.map(e => 
                        `<option value="${e.EventID}" data-serial="${e.SerialNumber}">${e.EventName} (${e.status})</option>`
                    ).join('');
                noEventMsg.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error loading ongoing events:', error);
    }
}

// Handle event selection change
async function handleAttendanceEventChange(eventId) {
    if (!eventId) {
        document.getElementById('attendanceTableContainer').style.display = 'none';
        document.getElementById('scanQRBtn').disabled = true;
        return;
    }
    
    try {
        const response = await fetch(`api/manage-attendance.php?action=getEventDetailsWithAttendance&eventId=${eventId}`);
        const data = await response.json();
        
        if (data.success && data.eventDetails) {
            const eventDetails = data.eventDetails;
            document.getElementById('selectedEventTitle').textContent = 
                `${eventDetails.EventName} - ${eventDetails.Venue}`;
            
            // Populate attendance table
            const tbody = document.getElementById('attendanceTableBody');
            if (eventDetails.registrations && eventDetails.registrations.length > 0) {
                tbody.innerHTML = eventDetails.registrations.map((reg, idx) => `
                    <tr>
                        <td>${idx + 1}</td>
                        <td>${reg.MemberName || 'N/A'}</td>
                        <td>${reg.Email || 'N/A'}</td>
                        <td>
                            <span class="badge ${reg.Status === 'Checked In' ? 'bg-success' : 'bg-warning'}">
                                ${reg.Status || 'Not Checked'}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="markMemberAttendance(${reg.RegistrationID})">
                                Mark Present
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No registered members</td></tr>';
            }
            
            document.getElementById('attendanceTableContainer').style.display = 'block';
            document.getElementById('scanQRBtn').disabled = false;
        }
    } catch (error) {
        console.error('Error loading event details:', error);
    }
}

// Scan QR code for attendance
function scanQRCodeForAttendance() {
    const eventId = document.getElementById('attendanceEventSelect').value;
    if (!eventId) {
        alert('Please select an event first');
        return;
    }
    
    // Start QR code scanner
    try {
        const html5QrcodeScanner = new Html5QrcodeScanner(
            "qrReaderAttendance",
            { fps: 10, qrbox: 250 },
            false
        );
        
        html5QrcodeScanner.render(onScanSuccess, onScanError);
    } catch (error) {
        console.error('Error starting QR scanner:', error);
        alert('Could not start QR code scanner. Please try again.');
    }
}

// Mark member attendance
async function markMemberAttendance(registrationId) {
    try {
        const response = await fetch('api/manage-attendance.php?action=recordAttendance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                registrationId: registrationId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Attendance marked successfully');
            // Reload the attendance table
            const eventId = document.getElementById('attendanceEventSelect').value;
            handleAttendanceEventChange(eventId);
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error marking attendance:', error);
    }
}

// Initialize attendance event listener
document.addEventListener('DOMContentLoaded', function() {
    const attendanceEventSelect = document.getElementById('attendanceEventSelect');
    if (attendanceEventSelect) {
        attendanceEventSelect.addEventListener('change', function() {
            handleAttendanceEventChange(this.value);
        });
    }
});


// Event listener for unregister buttons using data attributes
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('unregister-btn')) {
        const btn = e.target;
        const eventId = btn.dataset.eventId;
        const eventTitle = btn.dataset.eventTitle;
        
        unregisterFromEvent(eventId, eventTitle);
    }
});