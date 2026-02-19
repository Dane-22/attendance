// Reveal-on-scroll
document.addEventListener('DOMContentLoaded', () => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('active');
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -50px 0px' });

  document.querySelectorAll('.reveal').forEach(el => {
    observer.observe(el);
  });

  // Smooth scrolling for navigation links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // Add a small parallax / subtle movement on mouse for hero SVG (non-essential)
  const hero = document.querySelector('section.hero-gradient');
  if (hero) {
    hero.addEventListener('mousemove', (e) => {
      const x = (e.clientX - window.innerWidth/2) / 60;
      const y = (e.clientY - window.innerHeight/2) / 60;
      hero.style.transform = `translate(${x}px, ${y}px)`;
    });
    hero.addEventListener('mouseleave', () => { hero.style.transform = ''; });
  }
});

// Sidebar toggle and responsive helpers (unified across pages)
(function(){
  const sidebar = document.getElementById('sidebar');
  if (!sidebar) return;

  const desktopToggle = document.getElementById('sidebarToggle');
  const mobileOpenBtn = document.getElementById('mobileOpenBtn');
  const backdrop = document.getElementById('sidebarBackdrop');
  const mainContent = document.querySelector('.main-content');

  const isMobile = () => window.matchMedia('(max-width: 767.98px)').matches;

  function openSidebar() {
    sidebar.classList.add('active');
    if (desktopToggle) desktopToggle.setAttribute('aria-expanded', 'true');
    if (mobileOpenBtn) mobileOpenBtn.setAttribute('aria-expanded', 'true');
  }

  function closeSidebar() {
    sidebar.classList.remove('active');
    if (desktopToggle) desktopToggle.setAttribute('aria-expanded', 'false');
    if (mobileOpenBtn) mobileOpenBtn.setAttribute('aria-expanded', 'false');
  }

  function toggleSidebar() {
    sidebar.classList.toggle('active');
    const expanded = sidebar.classList.contains('active') ? 'true' : 'false';
    if (desktopToggle) desktopToggle.setAttribute('aria-expanded', expanded);
    if (mobileOpenBtn) mobileOpenBtn.setAttribute('aria-expanded', expanded);
  }

  // Desktop toggle
  if (desktopToggle) {
    desktopToggle.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggleSidebar();
    });
  }

  // Mobile hamburger
  if (mobileOpenBtn) {
    mobileOpenBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      openSidebar();
    });
  }

  // Backdrop closes the sidebar
  if (backdrop) {
    backdrop.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeSidebar();
    });
  }

  // Prevent clicks inside the sidebar from closing it
  sidebar.addEventListener('click', (e) => {
    e.stopPropagation();
  });

  // Clicking outside (main content) on mobile closes the sidebar
  if (mainContent) {
    mainContent.addEventListener('click', (e) => {
      e.stopPropagation();
      if (isMobile() && sidebar.classList.contains('active')) {
        closeSidebar();
      }
    });
  }

  // Global click-outside handler (safety net)
  document.addEventListener('click', (e) => {
    const clickedInsideSidebar = sidebar.contains(e.target);
    const clickedToggle = (desktopToggle && desktopToggle.contains(e.target)) || (mobileOpenBtn && mobileOpenBtn.contains(e.target));
    if (isMobile() && sidebar.classList.contains('active') && !clickedInsideSidebar && !clickedToggle) {
      closeSidebar();
    }
  });

  // Close on ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('active')) {
      closeSidebar();
    }
  });

  // Ensure sidebar starts closed on mobile when resizing
  window.addEventListener('resize', () => {
    if (isMobile()) {
      closeSidebar();
    }
  });

  // Expose for debugging
  window.appSidebar = { open: openSidebar, close: closeSidebar, toggle: toggleSidebar };
})();
