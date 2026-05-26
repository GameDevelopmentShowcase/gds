const yearElement = document.getElementById('year');
if (yearElement) {
  yearElement.textContent = new Date().getFullYear();
}

if ('IntersectionObserver' in window) {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
      }
    });
  }, { threshold: .12 });

  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
} else {
  document.querySelectorAll('.reveal').forEach(el => el.classList.add('visible'));
}

/* NEWS TOGGLE */

document.querySelectorAll('.news-toggle').forEach(button => {

  button.addEventListener('click', () => {

    const entry = button.closest('.news-entry');

    if (entry) {
      entry.classList.toggle('open');
    }

  });

});
