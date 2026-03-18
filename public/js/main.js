/* =====================================================
   My Pottery Studio — Public Site JS
   ===================================================== */

(function () {
    'use strict';

    // Sticky nav scroll effect
    const nav = document.getElementById('nav');
    if (nav) {
        const onScroll = () => {
            if (window.scrollY > 20) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    // Smooth anchor scrolling for hash links
    document.querySelectorAll('a[href^="/#"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            const hash = this.getAttribute('href').replace('/', '');
            const target = document.querySelector(hash);
            if (target && window.location.pathname === '/') {
                e.preventDefault();
                const navHeight = nav ? nav.offsetHeight : 0;
                const top = target.getBoundingClientRect().top + window.scrollY - navHeight - 12;
                window.scrollTo({ top: top, behavior: 'smooth' });
                history.pushState(null, '', hash);
            }
        });
    });

    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            const hash = this.getAttribute('href');
            if (hash === '#') return;
            const target = document.querySelector(hash);
            if (target) {
                e.preventDefault();
                const navHeight = nav ? nav.offsetHeight : 0;
                const top = target.getBoundingClientRect().top + window.scrollY - navHeight - 12;
                window.scrollTo({ top: top, behavior: 'smooth' });
                history.pushState(null, '', hash);
            }
        });
    });

    // Fade-in on scroll (intersection observer)
    const fadeEls = document.querySelectorAll('.feature-card, .screenshot-placeholder, .screenshot-item');
    if ('IntersectionObserver' in window && fadeEls.length) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        fadeEls.forEach(function (el) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(18px)';
            el.style.transition = 'opacity .4s ease, transform .4s ease';
            observer.observe(el);
        });
    }
})();
