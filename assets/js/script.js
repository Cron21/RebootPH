document.addEventListener('DOMContentLoaded', function() {
    let initiatives = [];
    
    // Dynamic API path
    const API_URL = window.location.origin + '/api';

    const initiativesList = document.getElementById('initiativesList');
    const searchBar = document.getElementById('searchBar');
    const filterOptions = document.getElementById('filterOptions');
    const carouselInner = document.querySelector('#initiativeCarousel .carousel-inner');
    const carouselIndicators = document.querySelector('#initiativeCarousel .carousel-indicators');

    // Hide initiatives list initially
    if (initiativesList) {
        initiativesList.style.display = 'none';
    }

    // Function to load initiatives from API
    async function loadInitiatives() {
        try {
            const response = await fetch(`${API_URL}/get-initiatives.php`);
            const data = await response.json();

            if (data.success && data.initiatives) {
                initiatives = data.initiatives.map(init => ({
                    title: init.Title,
                    category: init.Category ? init.Category.toLowerCase() : 'general',
                    description: init.Description,
                    image: init.images && init.images.length > 0 ? init.images[0] : 'assets/image/placeholder.jpg'
                }));

                // Show initiatives list after data is loaded
                if (initiativesList) {
                    initiativesList.style.display = '';
                }

                // Initial display
                displayInitiatives(initiatives);
                
                // Populate carousel with first 3 initiatives (or all if less than 3)
                populateCarousel(initiatives.slice(0, Math.min(3, initiatives.length)));
            } else {
                console.error('Failed to load initiatives:', data.message);
                if (initiativesList) {
                    initiativesList.innerHTML = '<div class="alert alert-warning">No initiatives found</div>';
                    initiativesList.style.display = '';
                }
            }
        } catch (error) {
            console.error('Error loading initiatives:', error);
            if (initiativesList) {
                initiativesList.innerHTML = '<div class="alert alert-danger">Error loading initiatives</div>';
                initiativesList.style.display = '';
            }
        }
    }

    // Function to populate carousel from initiatives array
    function populateCarousel(initiativesToShow = []) {
        if (!carouselInner || !carouselIndicators || initiativesToShow.length === 0) return;

        // Clear existing carousel content
        carouselInner.innerHTML = '';
        carouselIndicators.innerHTML = '';

        // Generate carousel indicators
        initiativesToShow.forEach((_, index) => {
            const indicator = document.createElement('button');
            indicator.type = 'button';
            indicator.setAttribute('data-bs-target', '#initiativeCarousel');
            indicator.setAttribute('data-bs-slide-to', index);
            indicator.setAttribute('aria-label', `Slide ${index + 1}`);
            if (index === 0) {
                indicator.classList.add('active');
                indicator.setAttribute('aria-current', 'true');
            }
            carouselIndicators.appendChild(indicator);
        });

        // Generate carousel items
        initiativesToShow.forEach((initiative, index) => {
            const carouselItem = document.createElement('div');
            carouselItem.className = `carousel-item ${index === 0 ? 'active' : ''}`;
            carouselItem.innerHTML = `
                <img src="${initiative.image}" class="d-block w-100" alt="${initiative.title}" style="height: 400px; object-fit: cover;">
                <div class="carousel-caption" style="background: rgba(0,0,0,0.6);">
                    <h3>${initiative.title}</h3>
                    <p class="d-none d-md-block">${initiative.description.substring(0, 150)}${initiative.description.length > 150 ? '...' : ''}</p>
                    <a href="#!" class="btn highlight-btn">Learn More</a>
                </div>
            `;
            carouselInner.appendChild(carouselItem);
        });
    }

    function displayInitiatives(filteredInitiatives) {
        if (!initiativesList) return;

        if (filteredInitiatives.length === 0) {
            initiativesList.innerHTML = '<div class="alert alert-info">No initiatives match your search</div>';
            return;
        }

        initiativesList.innerHTML = filteredInitiatives.map(initiative => `
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 d-flex flex-column">
                    <img src="${initiative.image}" class="card-img-top" alt="${initiative.title}" style="height: 200px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="h5 fw-bold text-primary mb-2">${initiative.title}</h5>
                        <p class="card-text flex-grow-1">${initiative.description}</p>
                        <a href="#!" class="btn highlight-btn w-100 mt-auto">Learn More</a>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function filterInitiatives() {
        const searchTerm = searchBar.value.toLowerCase();
        const filterValue = filterOptions.value.toLowerCase();
        
        const filtered = initiatives.filter(initiative => {
            const matchesSearch = initiative.title.toLowerCase().includes(searchTerm) || 
                                initiative.description.toLowerCase().includes(searchTerm);
            const matchesCategory = filterValue === '' || initiative.category === filterValue;
            return matchesSearch && matchesCategory;
        });
        
        displayInitiatives(filtered);
    }

    if (searchBar) {
        searchBar.addEventListener('input', filterInitiatives);
    }
    
    if (filterOptions) {
        filterOptions.addEventListener('change', filterInitiatives);
    }

    // Load initiatives from API
    loadInitiatives();
});

// Hero text handling: animate heading words and typewriter description
(function() {
    function ensureHeroStyles() {
        if (document.getElementById('hero-text-styles')) return;
        const style = document.createElement('style');
        style.id = 'hero-text-styles';
        style.textContent = `
            .hero-word { display: inline-block; transition: transform .35s ease, color .35s ease; }
            .hero-word.active { color: #2f8f46; transform: translateY(-6px); }
            .hero-subtype { white-space: pre-wrap; display: inline-block; }
        `;
        document.head.appendChild(style);
    }

    function animateHeading(h1) {
        if (!h1) return;
        if (h1.dataset.heroProcessed === '1') return;
        const text = h1.textContent.trim();
        if (!text) return;

        const words = text.split(/(\s+)/).map(w => w === ' ' ? '\u00A0' : w);
        h1.innerHTML = '';
        words.forEach((w, idx) => {
            const span = document.createElement('span');
            span.className = 'hero-word';
            span.textContent = w;
            h1.appendChild(span);
        });

        // cycle active word (skip whitespace-only spans)
        let current = -1;
        const spans = Array.from(h1.querySelectorAll('.hero-word')).filter(s => s.textContent.trim() !== '\u00A0');
        if (spans.length === 0) return;

        function next() {
            if (current >= 0) spans[current].classList.remove('active');
            current = (current + 1) % spans.length;
            spans[current].classList.add('active');
        }

        // run immediately then every 1600ms
        next();
        const intervalId = setInterval(next, 1600);
        h1.dataset.heroInterval = String(intervalId);
        h1.dataset.heroProcessed = '1';
    }

    function typeWriter(p) {
        if (!p) return;
        if (p.dataset.heroProcessed === '1') return;
        const full = p.textContent.trim();
        if (!full) return;
        p.classList.add('hero-subtype');
        p.textContent = '';
        let i = 0;
        const speed = 18; // ms per char
        const id = setInterval(() => {
            p.textContent += full.charAt(i);
            i++;
            if (i >= full.length) {
                clearInterval(id);
                p.dataset.heroProcessed = '1';
            }
        }, speed);
        p.dataset.heroTypeId = String(id);
    }

    function handleHeroContent(container) {
        if (!container) return;
        // find h1 and p inside heroContent
        const h1 = container.querySelector('h1');
        const p = container.querySelector('p');
        ensureHeroStyles();
        if (h1) animateHeading(h1);
        if (p) typeWriter(p);

        // smooth-scroll for CTA buttons inside hero (optional): look for elements with data-scroll-target or .hero-cta
        const ctas = container.querySelectorAll('[data-scroll-target], .hero-cta');
        ctas.forEach(btn => {
            if (btn.dataset.scrollAttached === '1') return;
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const target = btn.dataset.scrollTarget || btn.getAttribute('href');
                if (!target) return;
                const el = document.querySelector(target);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            btn.dataset.scrollAttached = '1';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const hero = document.getElementById('heroContent');
        if (!hero) return;

        // Observe changes to the hero content (loaded dynamically)
        const observer = new MutationObserver((mutations) => {
            for (const m of mutations) {
                if (m.type === 'childList' && m.addedNodes.length > 0) {
                    // slight delay to allow injected HTML to be parsed
                    setTimeout(() => handleHeroContent(hero), 50);
                    break;
                }
            }
        });

        observer.observe(hero, { childList: true, subtree: true });

        // If content already exists (server-rendered or loaded quickly), run once
        setTimeout(() => handleHeroContent(hero), 300);
    });
})();

// Load Why Join Reboot PH cards from backend and render carousel slides
(function() {
    async function loadWhyJoinSection() {
        const container = document.getElementById('whyJoinCarousel');
        if (!container) return;

        const API_URL = window.location.origin + '/api';
        try {
            const res = await fetch(`${API_URL}/get-why-join.php`);
            const data = await res.json();
            if (!data.success || !Array.isArray(data.items) || data.items.length === 0) return;

            const items = data.items;

            // group into arrays of 3
            const groups = [];
            for (let i = 0; i < items.length; i += 3) groups.push(items.slice(i, i + 3));

            // build inner slides and indicators
            const inner = container.querySelector('.carousel-inner');
            const indicators = container.querySelector('.carousel-indicators');
            if (!inner || !indicators) return;

            inner.innerHTML = '';
            indicators.innerHTML = '';

            groups.forEach((group, gi) => {
                const itemDiv = document.createElement('div');
                itemDiv.className = `carousel-item ${gi === 0 ? 'active' : ''}`;
                const row = document.createElement('div');
                row.className = 'row g-4';

                group.forEach(card => {
                    const col = document.createElement('div');
                    col.className = 'col-12 col-md-4';

                    const cardEl = document.createElement('div');
                    cardEl.className = 'card h-100 p-4 rounded-4 shadow-sm text-center';

                    const iconWrap = document.createElement('div');
                    iconWrap.className = 'mb-3';
                    const iconClass = card.icon || getIconForTitle(card.Title || card.title || '');
                    const iEl = document.createElement('i');
                    iEl.className = `bi ${iconClass}`;
                    iEl.style.fontSize = '2.2rem';
                    iEl.style.color = '#0b4f86';
                    iconWrap.appendChild(iEl);

                    const h3 = document.createElement('h3');
                    h3.className = 'h6 fw-bold mb-2';
                    h3.textContent = card.Title || card.title || '';

                    const p = document.createElement('p');
                    p.className = 'text-muted';
                    p.textContent = card.Description || card.description || '';

                    cardEl.appendChild(iconWrap);
                    cardEl.appendChild(h3);
                    cardEl.appendChild(p);
                    col.appendChild(cardEl);
                    row.appendChild(col);
                });

                itemDiv.appendChild(row);
                inner.appendChild(itemDiv);

                const indicator = document.createElement('button');
                indicator.type = 'button';
                indicator.setAttribute('data-bs-target', '#whyJoinCarousel');
                indicator.setAttribute('data-bs-slide-to', String(gi));
                indicator.setAttribute('aria-label', `Slide ${gi + 1}`);
                if (gi === 0) { indicator.className = 'active'; indicator.setAttribute('aria-current', 'true'); }
                indicators.appendChild(indicator);
            });

            // reinit carousel to pick up changes
            try { new bootstrap.Carousel(container); } catch (e) { /* ignore if bootstrap not available */ }
        } catch (err) {
            console.error('Error loading Why Join Reboot PH section:', err);
        }
    }

    function getIconForTitle(title) {
        const t = (title || '').toLowerCase();
        if (t.includes('training') || t.includes('seminar') || t.includes('certificate')) return 'bi-mortarboard-fill';
        if (t.includes('initiative') || t.includes('local') || t.includes('national') || t.includes('globe')) return 'bi-globe2';
        if (t.includes('skill') || t.includes('building') || t.includes('brain')) return 'bi-brain';
        if (t.includes('network') || t.includes('connect') || t.includes('hand')) return 'bi-handshake';
        if (t.includes('certificate') || t.includes('recognition') || t.includes('award')) return 'bi-award';
        if (t.includes('lead') || t.includes('leadership') || t.includes('light')) return 'bi-lightbulb-fill';
        return 'bi-info-circle';
    }

    document.addEventListener('DOMContentLoaded', loadWhyJoinSection);
})();

