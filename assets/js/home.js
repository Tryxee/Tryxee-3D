// assets/js/home.js (robuste) — logs + fallback pour reveal
(() => {

  // small safety wrapper to avoid runtime stop
  try {
    // Typing / subtle title shimmer
    const title = document.querySelector('.hero-title');
    if (title) {
      let t = 0;
      setInterval(()=> {
        const shift = Math.sin(t/40) * 6;
        title.style.textShadow = `0 ${2 + shift}px ${12 + Math.abs(shift)}px rgba(2,8,14,0.45)`;
        t++;
      }, 80);
    }

    // IntersectionObserver reveal with fallback
    const revealEls = Array.from(document.querySelectorAll('.reveal'));
    function revealNow(el) {
      if (!el) return;
      el.classList.add('revealed');
    }

    if ('IntersectionObserver' in window && revealEls.length > 0) {
      const io = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('revealed');
            obs.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12 });

      revealEls.forEach(el => {
        io.observe(el);
      });

      // ensure we reveal anything already in viewport on load
      window.addEventListener('load', () => {
        revealEls.forEach(el => {
          const rect = el.getBoundingClientRect();
          if (rect.top < window.innerHeight) {
            el.classList.add('revealed');
            // ensure it's unobserved if needed
          }
        });
      });

    } else {
      // fallback: reveal everything after a short delay (for old browsers or if IO missing)
      console.warn('[home.js] IntersectionObserver non disponible — fallback: reveal all');
      setTimeout(() => revealEls.forEach(revealNow), 220);
    }

    // hero visual parallax (mouse)
    const hero = document.querySelector('.hero');
    const visual = document.querySelector('.hero-visual');
    if (hero && visual) {
      hero.addEventListener('mousemove', (ev) => {
        const r = hero.getBoundingClientRect();
        const mx = (ev.clientX - r.left) / r.width - 0.5;
        const my = (ev.clientY - r.top) / r.height - 0.5;
        visual.style.transform = `translate3d(${mx * 12}px, ${my * 10}px, 0) rotate(${mx * 2}deg)`;
        visual.style.transition = 'transform 160ms linear';
      });
      hero.addEventListener('mouseleave', () => {
        visual.style.transform = 'translate3d(0,0,0)';
      });
    }

    // tilt for printer cards
    document.querySelectorAll('.printer-card').forEach(card => {
      card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width;
        const y = (e.clientY - rect.top) / rect.height;
        const rx = (y - 0.5) * 6;
        const ry = (x - 0.5) * -6;
        card.style.transform = `perspective(900px) rotateX(${rx}deg) rotateY(${ry}deg) translateZ(0)`;
      });
      card.addEventListener('mouseleave', () => {
        card.style.transform = '';
      });
    });

    // svg dash sync (safe)
    const ribbonPath = document.querySelector('.ribbon-sharp');
    if (ribbonPath && ribbonPath.getTotalLength) {
      const len = ribbonPath.getTotalLength();
      ribbonPath.style.strokeDasharray = len;
      function syncDash() {
        const scroll = window.scrollY;
        const max = document.body.scrollHeight - window.innerHeight;
        const p = Math.min(1, scroll / Math.max(1, max));
        ribbonPath.style.strokeDashoffset = String(Math.floor(len * (1 - p)));
      }
      syncDash();
      window.addEventListener('scroll', syncDash);
    }
  } catch (err) {
    console.error('[home.js] erreur interne', err);
    // fallback: reveal everything so the page isn't stuck invisible
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('revealed'));
  }
})();
