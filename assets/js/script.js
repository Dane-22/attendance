document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');

  function applySidebarState(collapsed){
    if (!sidebar) return;
    if (collapsed) {
      sidebar.classList.add('collapsed');
      sidebar.classList.remove('open');
    } else {
      sidebar.classList.remove('collapsed');
      sidebar.classList.remove('open');
    }
  }

  function toggleSidebar(){
    if (!sidebar) return;
    if (window.innerWidth <= 1000){
      sidebar.classList.toggle('open');
    } else {
      sidebar.classList.toggle('collapsed');
      const collapsed = sidebar.classList.contains('collapsed');
      try{ localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0'); }catch(e){}
    }
  }

  // initialize from saved state
  try{
    const saved = localStorage.getItem('sidebarCollapsed');
    if (saved === '1') applySidebarState(true);
  }catch(e){}

  if (sidebarToggle) sidebarToggle.addEventListener('click', (e)=>{ e.preventDefault(); toggleSidebar(); });

  // Close overlay when clicking outside on small screens
  document.addEventListener('click', (e)=>{
    if (!sidebar) return;
    if (window.innerWidth > 1000) return;
    if (!sidebar.classList.contains('open')) return;
    if (e.target.closest('.sidebar') || e.target.closest('#sidebarToggle')) return;
    sidebar.classList.remove('open');
  });
});
