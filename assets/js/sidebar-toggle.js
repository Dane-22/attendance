// Sidebar toggle for mobile off-canvas behavior
(function(){


(function(){
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const toggleButtons = Array.from(document.querySelectorAll('#sidebarToggle, #mobileOpenBtn'));

  function openSidebar(){
    if(!sidebar) return;
    sidebar.classList.add('open');
    backdrop?.classList.add('show');
    document.body.classList.add('sidebar-open');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar(){
    if(!sidebar) return;
    sidebar.classList.remove('open');
    backdrop?.classList.remove('show');
    document.body.classList.remove('sidebar-open');
    document.body.style.overflow = '';
  }

  toggleButtons.forEach(btn=>{
    if(!btn) return;
    btn.addEventListener('click', function(e){
      e.preventDefault();
      if(sidebar.classList.contains('open')) closeSidebar(); else openSidebar();
    });
  });

  // Click on backdrop closes
  backdrop?.addEventListener('click', closeSidebar);

  // Esc closes
  document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ closeSidebar(); } });

  // If viewport is large, ensure sidebar visible
  function handleResize(){
    if(window.innerWidth >= 1024){
      sidebar?.classList.add('open');
      backdrop?.classList.remove('show');
      document.body.style.overflow = '';
    } else {
      sidebar?.classList.remove('open');
      backdrop?.classList.remove('show');
    }
  }

  window.addEventListener('resize', handleResize);
  handleResize();
})();
