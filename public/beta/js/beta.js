/* =====================================================
   My Pottery Studio — Beta Portal JS
   ===================================================== */

(function () {
    'use strict';

    // ---- Vote buttons ----
    document.querySelectorAll('[data-vote]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const feedbackId = this.dataset.vote;
            const el         = this;
            el.disabled = true;

            fetch('/beta/api/vote.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ feedback_id: parseInt(feedbackId, 10) }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    const countEl = el.querySelector('[data-vote-count]');
                    if (countEl) countEl.textContent = data.votes;
                    el.classList.toggle('voted', data.voted);
                    el.setAttribute('title', data.voted ? 'Remove vote' : 'Vote for this');
                }
            })
            .catch(function () { /* silent */ })
            .finally(function () { el.disabled = false; });
        });
    });

    // ---- Type tab switching (submit form) ----
    document.querySelectorAll('.beta-type-tab').forEach(function (tab) {
        const radio = tab.querySelector('input[type="radio"]');
        if (!radio) return;
        radio.addEventListener('change', function () {
            document.querySelectorAll('.beta-type-tab').forEach(function (t) {
                t.classList.toggle('beta-type-tab--active', t.querySelector('input[type="radio"]')?.checked);
            });
        });
    });

    // ---- Expandable text (truncated bodies) ----
    document.querySelectorAll('[data-expandable]').forEach(function (el) {
        const fullText = el.dataset.expandable;
        const maxLen   = parseInt(el.dataset.maxlen || '160', 10);
        if (fullText.length <= maxLen) { el.textContent = fullText; return; }

        const short  = fullText.slice(0, maxLen) + '…';
        let expanded = false;
        el.textContent = short;

        const toggle = document.createElement('button');
        toggle.className = 'beta-expand-btn';
        toggle.textContent = 'Show more';
        toggle.style.cssText = 'background:none;border:none;color:var(--gold-lt);cursor:pointer;font-size:.8rem;padding:0;margin-left:4px;';

        toggle.addEventListener('click', function () {
            expanded = !expanded;
            el.textContent = expanded ? fullText : short;
            toggle.textContent = expanded ? 'Show less' : 'Show more';
            el.parentNode.insertBefore(toggle, el.nextSibling);
        });

        el.parentNode.insertBefore(toggle, el.nextSibling);
    });

    // ---- Mobile sidebar toggle ----
    const sidebar = document.querySelector('.beta-sidebar');
    const menuBtn = document.querySelector('[data-sidebar-toggle]');
    if (sidebar && menuBtn) {
        menuBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ---- Tab switching ----
    document.querySelectorAll('[data-tabs]').forEach(function (container) {
        const tabs    = container.querySelectorAll('[data-tab]');
        const panels  = container.querySelectorAll('[data-panel]');

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                const target = this.dataset.tab;
                tabs.forEach(function (t) { t.classList.toggle('active', t.dataset.tab === target); });
                panels.forEach(function (p) { p.classList.toggle('hidden', p.dataset.panel !== target); });
            });
        });
    });

})();
