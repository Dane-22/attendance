// assets/auth.js
// Adds fade transitions between pages and small form UI helpers

document.addEventListener('DOMContentLoaded', () => {
  // Fade-in visible elements
  document.querySelectorAll('.fade-in').forEach(el => setTimeout(()=> el.classList.add('visible'), 40));

  // Intercept links with data-transition and fade out before navigating
  document.querySelectorAll('a[data-transition]').forEach(a => {
    a.addEventListener('click', (e) => {
      const href = a.getAttribute('href');
      if (!href || href.startsWith('#')) return;
      e.preventDefault();
      document.body.classList.add('fade-out');
      setTimeout(() => { window.location = href; }, 280);
    });
  });

});
