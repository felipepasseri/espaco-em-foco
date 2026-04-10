import abrirMenu from "./header.js"
import mudarAbasTopicos from "./topics.js"

document.addEventListener("DOMContentLoaded", () => {
  abrirMenu()
  mudarAbasTopicos()

  // Animação ao scroll
  const observerOptions = {
    threshold: 0.2,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animate');
        observer.unobserve(entry.target); // Uma vez só
      }
    });
  }, observerOptions);

  document.querySelectorAll('.fade-in').forEach(el => {
    observer.observe(el);
  });
})
