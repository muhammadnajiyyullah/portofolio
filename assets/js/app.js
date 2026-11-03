/* ==================================================================
   Dev Journal — public interactions
   Semua fitur dibuat defensif: jika elemen tidak ada, dilewati.
   ================================================================== */

(function () {
    'use strict';

    var DJ = window.DJ || {};
    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var finePointer = window.matchMedia && window.matchMedia('(pointer: fine)').matches;

    document.documentElement.classList.add('js');

    /* ------------------------------------------------------------------
       Toast
       ------------------------------------------------------------------ */

    var toastEl = $('#toast');
    var toastTimer = null;

    function toast(message, type) {
        if (!toastEl) return;
        toastEl.textContent = message;
        toastEl.classList.toggle('is-error', type === 'error');
        toastEl.classList.add('is-visible');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            toastEl.classList.remove('is-visible');
        }, 3200);
    }
    window.djToast = toast;

    /* ------------------------------------------------------------------
       Loader
       ------------------------------------------------------------------ */

    var loader = $('#loader');

    function hideLoader() {
        if (loader) loader.classList.add('is-done');
    }
    if (document.readyState === 'complete') {
        setTimeout(hideLoader, 260);
    } else {
        window.addEventListener('load', function () { setTimeout(hideLoader, 260); });
        setTimeout(hideLoader, 1600);
    }

    /* ------------------------------------------------------------------
       Theme
       ------------------------------------------------------------------ */

    function currentMode() {
        return document.documentElement.dataset.mode === 'dark' ? 'dark' : 'light';
    }

    function applyTheme(mode, persist) {
        document.documentElement.dataset.mode = mode;
        $$('#themeToggle').forEach(function (btn) {
            var label = mode === 'dark' ? 'Mode terang' : 'Mode gelap';
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
            btn.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
        });
        var meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.setAttribute('content', mode === 'dark' ? '#12101E' : '#FAF8FF');
        if (persist) {
            try { localStorage.setItem('dj-mode', mode); } catch (e) {}
        }
    }

    applyTheme(currentMode(), false);

    $$('#themeToggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var next = currentMode() === 'dark' ? 'light' : 'dark';
            applyTheme(next, true);
            toast(next === 'dark' ? 'Mode gelap aktif.' : 'Mode terang aktif.');
        });
    });

    /* ------------------------------------------------------------------
       Language switch
       ------------------------------------------------------------------ */

    $$('[data-lang]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var lang = btn.getAttribute('data-lang') === 'en' ? 'en' : 'id';
            if (lang === (DJ.lang || 'id')) return;
            document.cookie = 'lang=' + lang + ';path=/;max-age=' + (60 * 60 * 24 * 365) + ';samesite=lax';
            window.location.reload();
        });
    });

    /* ------------------------------------------------------------------
       Header, mobile menu, scroll progress
       ------------------------------------------------------------------ */

    var siteHeader = $('#siteHeader');
    var menuToggle = $('#menuToggle');
    var mobileMenu = $('#mobileMenu');
    var scrollProgress = $('#scrollProgress');
    var backTop = $('#backTop');

    function closeMenu() {
        document.body.classList.remove('menu-open');
        if (menuToggle) menuToggle.setAttribute('aria-expanded', 'false');
        if (mobileMenu) mobileMenu.setAttribute('aria-hidden', 'true');
    }

    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function () {
            var open = !document.body.classList.contains('menu-open');
            document.body.classList.toggle('menu-open', open);
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            mobileMenu.setAttribute('aria-hidden', open ? 'false' : 'true');
            if (open) lockBody();
            else syncLock();
        });
        $$('a', mobileMenu).forEach(function (a) {
            a.addEventListener('click', function () { closeMenu(); syncLock(); });
        });
    }

    /* ------------------------------------------------------------------
       Scroll handlers
       ------------------------------------------------------------------ */

    var ticking = false;

    function onScroll() {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(function () {
            var y = window.scrollY || window.pageYOffset;
            var doc = document.documentElement;
            var max = Math.max(1, doc.scrollHeight - window.innerHeight);
            if (siteHeader) siteHeader.classList.toggle('is-scrolled', y > 12);
            if (scrollProgress) scrollProgress.style.transform = 'scaleX(' + Math.min(1, y / max) + ')';
            if (backTop) backTop.classList.toggle('is-visible', y > 520);
            updateParallax(y);
            ticking = false;
        });
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    if (backTop) {
        backTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
        });
    }

    /* ------------------------------------------------------------------
       Reveal on scroll
       ------------------------------------------------------------------ */

    var revealEls = $$('.reveal');

    if (!reduced && 'IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -8% 0px' });
        revealEls.forEach(function (el) { io.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('is-visible'); });
    }

    /* ------------------------------------------------------------------
       Parallax
       ------------------------------------------------------------------ */

    var parallaxEls = $$('[data-parallax]');
    var parallaxScene = $('[data-parallax-scene]');

    function updateParallax(y) {
        if (!parallaxScene || !parallaxEls.length || reduced || !finePointer) return;
        var rect = parallaxScene.getBoundingClientRect();
        var offset = (rect.top + rect.height / 2) - window.innerHeight / 2;
        parallaxEls.forEach(function (el) {
            var speed = parseFloat(el.getAttribute('data-parallax')) || 0.04;
            el.style.transform = 'translate3d(0,' + (-offset * speed).toFixed(2) + 'px,0)';
        });
    }

    /* ------------------------------------------------------------------
       Typewriter
       ------------------------------------------------------------------ */

    $$('[data-roles]').forEach(function (el) {
        var roles = (el.getAttribute('data-roles') || '').split(',').map(function (r) { return r.trim(); }).filter(Boolean);
        if (!roles.length) return;
        if (reduced) { el.textContent = roles[0]; return; }

        var roleIndex = 0;
        var charIndex = 0;
        var deleting = false;

        function tick() {
            var role = roles[roleIndex % roles.length];
            if (!deleting) {
                charIndex++;
                el.textContent = role.slice(0, charIndex);
                if (charIndex >= role.length) {
                    deleting = true;
                    setTimeout(tick, 1700);
                    return;
                }
                setTimeout(tick, 62 + Math.random() * 40);
            } else {
                charIndex--;
                el.textContent = role.slice(0, charIndex);
                if (charIndex <= 0) {
                    deleting = false;
                    roleIndex++;
                    setTimeout(tick, 280);
                    return;
                }
                setTimeout(tick, 30);
            }
        }
        setTimeout(tick, 500);
    });

    /* ------------------------------------------------------------------
       Custom cursor
       ------------------------------------------------------------------ */

    var cursorDot = $('#cursorDot');
    var cursorRing = $('#cursorRing');

    if (cursorDot && cursorRing && finePointer && !reduced && Number(DJ.customCursor) !== 0) {
        document.body.classList.add('has-cursor');
        var dotX = -100, dotY = -100, ringX = -100, ringY = -100;
        var mouseX = -100, mouseY = -100;
        var cursorVisible = false;

        window.addEventListener('mousemove', function (e) {
            mouseX = e.clientX;
            mouseY = e.clientY;
            if (!cursorVisible) {
                cursorVisible = true;
                ringX = mouseX;
                ringY = mouseY;
            }
        }, { passive: true });

        document.addEventListener('mouseleave', function () {
            cursorVisible = false;
            document.body.classList.remove('cursor-hover');
        });

        $$('a, button, [data-open-palette], input, textarea, select, [contenteditable]').forEach(function (el) {
            el.addEventListener('mouseenter', function () { document.body.classList.add('cursor-hover'); });
            el.addEventListener('mouseleave', function () { document.body.classList.remove('cursor-hover'); });
        });

        (function cursorLoop() {
            dotX += (mouseX - dotX) * 0.55;
            dotY += (mouseY - dotY) * 0.55;
            ringX += (mouseX - ringX) * 0.16;
            ringY += (mouseY - ringY) * 0.16;
            cursorDot.style.transform = 'translate3d(' + dotX + 'px,' + dotY + 'px,0)';
            cursorRing.style.transform = 'translate3d(' + ringX + 'px,' + ringY + 'px,0)';
            window.requestAnimationFrame(cursorLoop);
        })();
    }

    /* ------------------------------------------------------------------
       Body scroll lock
       ------------------------------------------------------------------ */

    var paletteEl = $('#palette');
    var recruiterModal = $('#recruiterModal');

    function lockBody() { document.body.classList.add('is-locked'); }

    function syncLock() {
        var locked = document.body.classList.contains('menu-open')
            || (paletteEl && paletteEl.classList.contains('is-open'))
            || (recruiterModal && recruiterModal.classList.contains('is-open'));
        document.body.classList.toggle('is-locked', !!locked);
    }

    /* ------------------------------------------------------------------
       Project filters
       ------------------------------------------------------------------ */

    var filterButtons = $$('[data-filter]');
    var projectCards = $$('[data-category]');
    var filterEmpty = $('#filterEmpty');

    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var filter = btn.getAttribute('data-filter') || 'all';
            filterButtons.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');

            var visible = 0;
            projectCards.forEach(function (card) {
                var match = filter === 'all' || card.getAttribute('data-category') === filter;
                card.classList.toggle('is-hidden', !match);
                if (match) visible++;
            });
            if (filterEmpty) filterEmpty.hidden = visible !== 0;
        });
    });

    /* ------------------------------------------------------------------
       Recruiter mode
       ------------------------------------------------------------------ */

    var lastFocused = null;

    function openRecruiter() {
        if (!recruiterModal) return;
        lastFocused = document.activeElement;
        recruiterModal.classList.add('is-open');
        recruiterModal.setAttribute('aria-hidden', 'false');
        syncLock();
        var closeBtn = $('[data-close-recruiter]', recruiterModal);
        if (closeBtn) setTimeout(function () { closeBtn.focus(); }, 60);
    }

    function closeRecruiter() {
        if (!recruiterModal) return;
        recruiterModal.classList.remove('is-open');
        recruiterModal.setAttribute('aria-hidden', 'true');
        syncLock();
        if (lastFocused && lastFocused.focus) lastFocused.focus();
    }

    $$('[data-open-recruiter]').forEach(function (btn) {
        btn.addEventListener('click', openRecruiter);
    });
    $$('[data-close-recruiter]').forEach(function (el) {
        el.addEventListener('click', closeRecruiter);
    });

    /* ------------------------------------------------------------------
       Copy & share helpers
       ------------------------------------------------------------------ */

    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            try {
                var tmp = document.createElement('textarea');
                tmp.value = text;
                tmp.setAttribute('readonly', '');
                tmp.style.position = 'fixed';
                tmp.style.opacity = '0';
                document.body.appendChild(tmp);
                tmp.select();
                document.execCommand('copy');
                document.body.removeChild(tmp);
                resolve();
            } catch (err) {
                reject(err);
            }
        });
    }

    $$('[data-copy-email]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var email = btn.getAttribute('data-copy-email') || DJ.email || '';
            copyText(email).then(function () {
                toast('Email ' + email + ' disalin.');
            }).catch(function () {
                toast('Gagal menyalin. Email: ' + email, 'error');
            });
        });
    });

    $$('[data-share]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var type = btn.getAttribute('data-share');
            var url = window.location.href.split('#')[0];
            if (type !== 'copy') return;
            copyText(url).then(function () {
                toast('Tautan halaman disalin.');
            }).catch(function () {
                toast('Gagal menyalin tautan.', 'error');
            });
        });
    });

    /* ------------------------------------------------------------------
       Contact form
       ------------------------------------------------------------------ */

    var contactForm = $('#contactForm');

    if (contactForm) {
        contactForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var status = $('[data-form-status]', contactForm);
            var submit = $('button[type="submit"]', contactForm);
            var label = $('[data-submit-label]', contactForm);
            var originalLabel = label ? label.textContent : '';

            var name = contactForm.elements.name ? contactForm.elements.name.value.trim() : '';
            var email = contactForm.elements.email ? contactForm.elements.email.value.trim() : '';
            var message = contactForm.elements.message ? contactForm.elements.message.value.trim() : '';

            function show(text, ok) {
                if (!status) return;
                status.textContent = text;
                status.classList.toggle('is-success', !!ok);
                status.classList.toggle('is-error', !ok);
            }

            if (name.length < 2) { show('Mohon isi nama kamu.', false); return; }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { show('Mohon masukkan email yang valid.', false); return; }
            if (message.length < 10) { show('Pesan minimal 10 karakter.', false); return; }

            if (submit) submit.disabled = true;
            if (label) label.textContent = 'Mengirim…';
            show('Mengirim pesan…', true);

            fetch(contactForm.getAttribute('action') || 'api.php', {
                method: 'POST',
                body: new FormData(contactForm),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            }).then(function (res) {
                return res.json();
            }).then(function (data) {
                if (data.ok) {
                    contactForm.reset();
                    show(data.message || 'Pesan terkirim. Terima kasih!', true);
                    toast(data.message || 'Pesan terkirim.');
                } else {
                    show(data.error || 'Pesan gagal dikirim.', false);
                }
            }).catch(function () {
                show('Koneksi bermasalah. Coba lagi ya.', false);
            }).finally(function () {
                if (submit) submit.disabled = false;
                if (label) label.textContent = originalLabel;
            });
        });
    }

    /* ------------------------------------------------------------------
       Guestbook
       ------------------------------------------------------------------ */

    var guestbookForm = $('#guestbookForm');
    var guestbookList = $('#guestbookList');

    if (guestbookForm && guestbookList) {
        guestbookForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var status = $('[data-form-status]', guestbookForm);
            var submit = $('button[type="submit"]', guestbookForm);
            var name = guestbookForm.elements.name ? guestbookForm.elements.name.value.trim() : '';
            var message = guestbookForm.elements.message ? guestbookForm.elements.message.value.trim() : '';

            function show(text, ok) {
                if (!status) return;
                status.textContent = text;
                status.classList.toggle('is-success', !!ok);
                status.classList.toggle('is-error', !ok);
            }

            if (name.length < 2) { show('Mohon isi nama kamu.', false); return; }
            if (message.length < 3) { show('Tulis pesan sedikit lebih panjang.', false); return; }

            if (submit) submit.disabled = true;
            show('Menyimpan…', true);

            fetch(guestbookForm.getAttribute('action') || 'api.php', {
                method: 'POST',
                body: new FormData(guestbookForm),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            }).then(function (res) { return res.json(); }).then(function (data) {
                if (!data.ok) {
                    show(data.error || 'Gagal menyimpan pesan.', false);
                    return;
                }

                var empty = $('.guestbook-empty', guestbookList);
                if (empty) empty.remove();

                var entry = document.createElement('article');
                entry.className = 'guestbook-entry';

                var avatar = document.createElement('span');
                avatar.className = 'guestbook-entry__avatar';
                avatar.textContent = data.entry.initial;

                var body = document.createElement('div');
                var head = document.createElement('div');
                head.className = 'guestbook-entry__head';
                var nameEl = document.createElement('strong');
                nameEl.textContent = data.entry.name;
                var dateEl = document.createElement('span');
                dateEl.textContent = data.entry.date;
                head.appendChild(nameEl);
                head.appendChild(dateEl);
                var msgEl = document.createElement('p');
                msgEl.textContent = data.entry.message;
                body.appendChild(head);
                body.appendChild(msgEl);

                entry.appendChild(avatar);
                entry.appendChild(body);
                guestbookList.insertBefore(entry, guestbookList.firstChild);

                guestbookForm.reset();
                show(data.message || 'Terima kasih sudah mampir!', true);
                toast(data.message || 'Pesan guestbook tersimpan.');
            }).catch(function () {
                show('Koneksi bermasalah. Coba lagi ya.', false);
            }).finally(function () {
                if (submit) submit.disabled = false;
            });
        });
    }

    /* ------------------------------------------------------------------
       Reactions
       ------------------------------------------------------------------ */

    $$('.reaction-bar').forEach(function (bar) {
        var projectId = bar.getAttribute('data-project-id');

        $$('.reaction', bar).forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!projectId || btn.disabled) return;
                btn.disabled = true;
                var body = new URLSearchParams();
                body.set('action', 'react');
                body.set('project_id', projectId);
                body.set('emoji', btn.getAttribute('data-emoji') || '');
                if (DJ.csrf) body.set('csrf', DJ.csrf);

                fetch('api.php', {
                    method: 'POST',
                    body: body,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': DJ.csrf || ''
                    },
                    credentials: 'same-origin'
                }).then(function (res) { return res.json(); }).then(function (data) {
                    if (data.ok) {
                        var count = $('[data-count]', btn);
                        if (count) count.textContent = data.count;
                        btn.classList.add('is-reacted');
                        toast('Terima kasih atas reaksinya!');
                    } else {
                        toast(data.error || 'Reaksi gagal dikirim.', 'error');
                    }
                }).catch(function () {
                    toast('Koneksi bermasalah.', 'error');
                }).finally(function () {
                    btn.disabled = false;
                });
            });
        });
    });

    /* ------------------------------------------------------------------
       Command palette + terminal
       ------------------------------------------------------------------ */

    var paletteInput = $('#paletteInput');
    var paletteResults = $('#paletteResults');
    var paletteTerminal = $('#paletteTerminal');
    var terminalLog = $('#terminalLog');
    var paletteMode = $('#paletteMode');
    var terminalActive = false;
    var paletteItems = [];
    var activeIndex = 0;
    var terminalHistory = [];
    var historyIndex = -1;

    var iconSearch = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/></svg>';
    var iconPage = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg>';
    var iconFolder = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>';
    var iconCmd = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 17 6-6-6-6"/><path d="M12 19h8"/></svg>';

    function iconFor(group) {
        if (group === 'projects') return iconFolder;
        if (group === 'commands') return iconCmd;
        return iconPage;
    }

    function openPalette(mode) {
        if (!paletteEl) return;
        lastFocused = document.activeElement;
        paletteEl.classList.add('is-open');
        paletteEl.setAttribute('aria-hidden', 'false');
        syncLock();
        setTerminal(mode === 'terminal');
        renderPalette('');
        setTimeout(function () { if (paletteInput) paletteInput.focus(); }, 60);
    }

    function closePalette() {
        if (!paletteEl) return;
        paletteEl.classList.remove('is-open');
        paletteEl.setAttribute('aria-hidden', 'true');
        syncLock();
        if (paletteInput) paletteInput.value = '';
        if (lastFocused && lastFocused.focus) lastFocused.focus();
    }

    function setTerminal(on) {
        terminalActive = !!on;
        if (!paletteInput || !paletteResults || !paletteTerminal) return;
        paletteEl.classList.toggle('is-terminal', terminalActive);
        if (paletteMode) {
            paletteMode.classList.toggle('is-active', terminalActive);
            var text = $('span', paletteMode);
            if (text) text.textContent = terminalActive ? 'search' : 'terminal';
        }
        paletteInput.placeholder = terminalActive ? 'Ketik perintah — coba "help"' : (paletteInput.getAttribute('data-placeholder') || 'Cari halaman, proyek, atau perintah…');
        paletteResults.hidden = terminalActive;
        paletteTerminal.hidden = !terminalActive;
        if (terminalActive) {
            paletteInput.value = '';
            paletteInput.focus();
        } else {
            renderPalette(paletteInput.value);
            paletteInput.focus();
        }
    }

    function getPaletteSource() {
        var items = [];

        (DJ.pages || []).forEach(function (page) {
            items.push({
                group: 'Pages',
                label: page.label,
                href: page.href,
                action: page.action,
                icon: iconPage
            });
        });

        (DJ.projects || []).forEach(function (project) {
            items.push({
                group: 'Projects',
                label: project.title,
                small: project.category,
                href: 'project.php?slug=' + encodeURIComponent(project.slug),
                icon: iconFolder
            });
        });

        items.push(
            { group: 'Commands', label: 'Toggle theme terang / gelap', small: 'theme', action: 'theme', icon: iconCmd },
            { group: 'Commands', label: 'Ganti bahasa Indonesia / English', small: 'lang', action: 'lang', icon: iconCmd },
            { group: 'Commands', label: 'Buka terminal mode', small: 'terminal', action: 'terminal', icon: iconCmd },
            { group: 'Commands', label: 'Recruiter mode', small: 'briefcase', action: 'recruiter', icon: iconCmd },
            { group: 'Commands', label: 'Resume builder / CV', small: 'cv.php', href: 'cv.php', icon: iconPage },
            { group: 'Commands', label: 'Salin alamat email', small: 'copy', action: 'copy', icon: iconCmd },
            { group: 'Commands', label: 'Kembali ke atas', small: 'top', action: 'top', icon: iconCmd }
        );

        return items;
    }

    function renderPalette(query) {
        if (!paletteResults) return;
        var q = (query || '').toLowerCase().trim();
        var source = getPaletteSource();
        paletteItems = source.filter(function (item) {
            if (!q) return true;
            return (item.label + ' ' + (item.small || '') + ' ' + item.group).toLowerCase().indexOf(q) !== -1;
        });

        paletteResults.innerHTML = '';
        activeIndex = 0;

        if (!paletteItems.length) {
            var empty = document.createElement('p');
            empty.className = 'palette__empty';
            empty.textContent = (DJ.i18n && DJ.i18n.noResults) || 'Tidak ada hasil.';
            paletteResults.appendChild(empty);
            return;
        }

        var groups = {};
        paletteItems.forEach(function (item, index) {
            item.index = index;
            if (!groups[item.group]) groups[item.group] = [];
            groups[item.group].push(item);
        });

        Object.keys(groups).forEach(function (group, groupIndex) {
            var label = document.createElement('p');
            label.className = 'palette__group';
            label.textContent = group;
            paletteResults.appendChild(label);

            groups[group].forEach(function (item) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'palette__item' + (item.index === 0 ? ' is-active' : '');
                btn.dataset.index = item.index;

                var iconWrap = document.createElement('span');
                iconWrap.innerHTML = iconFor(group === 'Projects' ? 'projects' : (group === 'Commands' ? 'commands' : 'pages'));
                iconWrap.style.display = 'contents';

                var text = document.createElement('span');
                text.textContent = item.label;

                var small = document.createElement('small');
                if (item.small) small.textContent = item.small;

                var kbd = document.createElement('kbd');
                kbd.textContent = '↵';

                btn.appendChild(iconWrap.firstChild || document.createTextNode(''));
                btn.appendChild(text);
                if (item.small) btn.appendChild(small);
                btn.appendChild(kbd);

                btn.addEventListener('mouseenter', function () {
                    activeIndex = item.index;
                    highlightPaletteItem();
                });
                btn.addEventListener('click', function () { runPaletteItem(item); });

                paletteResults.appendChild(btn);
            });
        });

        highlightPaletteItem();
    }

    function highlightPaletteItem() {
        if (!paletteResults) return;
        var items = $$('.palette__item', paletteResults);
        items.forEach(function (el) {
            var on = Number(el.dataset.index) === activeIndex;
            el.classList.toggle('is-active', on);
            if (on) el.scrollIntoView({ block: 'nearest' });
        });
    }

    function runPaletteItem(item) {
        if (!item) return;
        closePalette();

        if (item.href) {
            window.location.href = item.href;
            return;
        }

        switch (item.action) {
            case 'theme':
                var next = currentMode() === 'dark' ? 'light' : 'dark';
                applyTheme(next, true);
                toast(next === 'dark' ? 'Mode gelap aktif.' : 'Mode terang aktif.');
                break;
            case 'lang':
                document.cookie = 'lang=' + ((DJ.lang || 'id') === 'en' ? 'id' : 'en') + ';path=/;max-age=31536000;samesite=lax';
                window.location.reload();
                break;
            case 'terminal':
                openPalette('terminal');
                break;
            case 'recruiter':
                openRecruiter();
                break;
            case 'copy':
                copyText(DJ.email || '').then(function () {
                    toast('Email ' + DJ.email + ' disalin.');
                }).catch(function () { toast('Gagal menyalin email.', 'error'); });
                break;
            case 'top':
                window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
                break;
        }
    }

    if (paletteInput) {
        paletteInput.setAttribute('data-placeholder', paletteInput.placeholder);
        paletteInput.addEventListener('input', function () {
            if (!terminalActive) renderPalette(paletteInput.value);
        });

        paletteInput.addEventListener('keydown', function (event) {
            if (terminalActive) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    runTerminal(paletteInput.value);
                    paletteInput.value = '';
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    if (!terminalHistory.length) return;
                    historyIndex = Math.max(0, Math.min(terminalHistory.length - 1, historyIndex + 1));
                    paletteInput.value = terminalHistory[terminalHistory.length - 1 - historyIndex] || '';
                } else if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    historyIndex = Math.max(-1, historyIndex - 1);
                    paletteInput.value = historyIndex < 0 ? '' : (terminalHistory[terminalHistory.length - 1 - historyIndex] || '');
                }
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                activeIndex = Math.min(paletteItems.length - 1, activeIndex + 1);
                highlightPaletteItem();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = Math.max(0, activeIndex - 1);
                highlightPaletteItem();
            } else if (event.key === 'Enter') {
                event.preventDefault();
                runPaletteItem(paletteItems[activeIndex]);
            }
        });
    }

    $$('[data-open-palette]').forEach(function (btn) {
        btn.addEventListener('click', function () { openPalette(); });
    });
    $$('[data-open-terminal]').forEach(function (btn) {
        btn.addEventListener('click', function () { openPalette('terminal'); });
    });
    $$('[data-close-palette]').forEach(function (el) {
        el.addEventListener('click', closePalette);
    });
    if (paletteMode) {
        paletteMode.addEventListener('click', function () { setTerminal(!terminalActive); });
    }

    function terminalLine(text, cls) {
        if (!terminalLog) return;
        var line = document.createElement('div');
        line.className = 't-line' + (cls ? ' ' + cls : '');
        line.textContent = text;
        terminalLog.appendChild(line);
        terminalLog.scrollTop = terminalLog.scrollHeight;
    }

    function terminalHtml(html, cls) {
        if (!terminalLog) return;
        var line = document.createElement('div');
        line.className = 't-line' + (cls ? ' ' + cls : '');
        line.innerHTML = html;
        terminalLog.appendChild(line);
        terminalLog.scrollTop = terminalLog.scrollHeight;
    }

    function terminalHelp() {
        terminalLine('Perintah yang tersedia:', 't-muted');
        terminalHtml('<b>whoami</b> · <b>about</b> · <b>projects</b> · <b>skills</b>');
        terminalHtml('<b>contact</b> · <b>cv</b> · <b>theme</b> · <b>lang</b>');
        terminalHtml('<b>goto</b> &lt;halaman&gt; · <b>open</b> &lt;slug&gt; · <b>clear</b>');
    }

    function runTerminal(raw) {
        var value = (raw || '').trim();
        if (!value) return;
        terminalHistory.push(value);
        historyIndex = -1;
        terminalLine(value, 't-cmd');

        var parts = value.split(/\s+/);
        var cmd = parts[0].toLowerCase();
        var arg = parts.slice(1).join(' ').toLowerCase();

        switch (cmd) {
            case 'help':
            case '?':
                terminalHelp();
                break;
            case 'whoami':
                terminalHtml('Informatics graduate · web &amp; software developer. <span class="t-muted">Siap belajar dan membangun.</span>');
                break;
            case 'about':
                terminalLine('Buka bagian About untuk cerita lengkapnya.');
                setTimeout(function () {
                    closePalette();
                    document.getElementById('about') && document.getElementById('about').scrollIntoView({ behavior: reduced ? 'auto' : 'smooth' });
                }, 500);
                break;
            case 'projects':
                terminalLine('Ditemukan ' + (DJ.projects || []).length + ' proyek:');
                (DJ.projects || []).slice(0, 6).forEach(function (project) {
                    terminalHtml('· ' + project.title + ' <span class="t-muted">(' + project.category + ')</span>');
                });
                break;
            case 'skills':
                terminalLine('Kategori skill:');
                terminalLine('Frontend · Backend · Database · Tools · Soft skills', 't-muted');
                break;
            case 'contact':
                terminalHtml('Email: <span class="t-accent">' + (DJ.email || '—') + '</span>');
                break;
            case 'cv':
                terminalLine('Membuka resume builder…');
                setTimeout(function () { window.location.href = 'cv.php'; }, 500);
                break;
            case 'theme':
                var next = currentMode() === 'dark' ? 'light' : 'dark';
                applyTheme(next, true);
                terminalLine('Tema diubah ke mode ' + next + '.', 't-accent');
                break;
            case 'lang':
                document.cookie = 'lang=' + ((DJ.lang || 'id') === 'en' ? 'id' : 'en') + ';path=/;max-age=31536000;samesite=lax';
                terminalLine('Mengganti bahasa…');
                setTimeout(function () { window.location.reload(); }, 400);
                break;
            case 'goto':
                var target = (DJ.pages || []).filter(function (page) {
                    return page.href && page.label.toLowerCase().indexOf(arg) !== -1;
                })[0];
                if (target) {
                    terminalLine('Menuju ' + target.label + '…', 't-accent');
                    setTimeout(function () { window.location.href = target.href; }, 450);
                } else {
                    terminalLine('Halaman tidak ditemukan. Coba "help".', 't-error');
                }
                break;
            case 'open':
                var project = (DJ.projects || []).filter(function (p) {
                    return p.slug.toLowerCase().indexOf(arg) !== -1 || p.title.toLowerCase().indexOf(arg) !== -1;
                })[0];
                if (project) {
                    terminalLine('Membuka ' + project.title + '…', 't-accent');
                    setTimeout(function () {
                        window.location.href = 'project.php?slug=' + encodeURIComponent(project.slug);
                    }, 450);
                } else {
                    terminalLine('Proyek tidak ditemukan.', 't-error');
                }
                break;
            case 'clear':
                if (terminalLog) terminalLog.innerHTML = '';
                break;
            case 'date':
                terminalLine(new Date().toString());
                break;
            case 'echo':
                terminalLine(parts.slice(1).join(' ') || '');
                break;
            case 'sudo':
                terminalLine('Nice try. Tidak ada akses root di sini 😄', 't-error');
                break;
            case 'coffee':
                terminalLine('☕ Kopi diseduh. Waktu ngoding +20%.');
                break;
            default:
                terminalLine('Perintah "' + cmd + '" tidak dikenal. Ketik "help".', 't-error');
        }
    }

    /* ------------------------------------------------------------------
       GitHub live stats (dengan fallback demo)
       ------------------------------------------------------------------ */

    var githubPanel = $('[data-github-user]');

    function setGh(name, value) {
        $$('[data-gh="' + name + '"]').forEach(function (el) { el.textContent = value; });
    }

    function renderHeatmap(days) {
        var grid = $('#ghHeatmap');
        if (!grid) return;
        grid.innerHTML = '';
        var frag = document.createDocumentFragment();
        days.forEach(function (day) {
            var cell = document.createElement('span');
            cell.className = 'gh-cell';
            cell.setAttribute('data-level', String(Math.max(0, Math.min(4, Number(day.level) || 0))));
            if (day.date) cell.title = day.date + ' · ' + (day.count || 0) + ' kontribusi';
            frag.appendChild(cell);
        });
        grid.appendChild(frag);
    }

    function fallbackHeatmap() {
        var days = [];
        var seed = 7;
        for (var i = 0; i < 371; i++) {
            seed = (seed * 9301 + 49297) % 233280;
            var r = seed / 233280;
            var level = r > 0.86 ? 4 : r > 0.72 ? 3 : r > 0.52 ? 2 : r > 0.3 ? 1 : 0;
            days.push({ level: level });
        }
        renderHeatmap(days);
        return days.reduce(function (sum, d) { return sum + d.level; }, 0) * 6;
    }

    function renderRepos(repos) {
        var list = $('#ghRepos');
        if (!list) return;
        list.innerHTML = '';
        repos.slice(0, 5).forEach(function (repo) {
            var a = document.createElement('a');
            a.className = 'gh-repo';
            a.href = repo.url || '#';
            if (repo.url) {
                a.target = '_blank';
                a.rel = 'noopener';
            }
            var left = document.createElement('div');
            var strong = document.createElement('strong');
            strong.textContent = repo.name;
            var desc = document.createElement('span');
            desc.textContent = repo.desc || 'Tanpa deskripsi';
            left.appendChild(strong);
            left.appendChild(desc);

            var meta = document.createElement('em');
            meta.textContent = repo.meta || '';

            a.appendChild(left);
            if (repo.meta) a.appendChild(meta);
            list.appendChild(a);
        });
    }

    function fallbackRepos() {
        var repos = (DJ.projects || []).slice(0, 5).map(function (p) {
            return {
                name: p.slug,
                desc: p.title,
                meta: p.category,
                url: 'project.php?slug=' + encodeURIComponent(p.slug)
            };
        });
        renderRepos(repos);
        return repos.length;
    }

    if (githubPanel) {
        var ghUser = (githubPanel.getAttribute('data-github-user') || '').trim();
        if (!ghUser) {
            setGh('repos', '—');
            setGh('stars', '—');
            setGh('followers', '—');
            setGh('contrib', '—');
            var repoList = $('#ghRepos');
            if (repoList) repoList.innerHTML = '<span class="github-loading">Username GitHub belum diatur.</span>';
            var heat = $('#ghHeatmap');
            if (heat) heat.innerHTML = '<span class="github-loading">Data kontribusi belum tersedia.</span>';
        } else {
            Promise.all([
                fetch('https://api.github.com/users/' + encodeURIComponent(ghUser), { headers: { Accept: 'application/vnd.github+json' } }).then(function (r) {
                    if (!r.ok) throw new Error('user');
                    return r.json();
                }),
                fetch('https://api.github.com/users/' + encodeURIComponent(ghUser) + '/repos?per_page=100&sort=updated', { headers: { Accept: 'application/vnd.github+json' } }).then(function (r) {
                    if (!r.ok) throw new Error('repos');
                    return r.json();
                }),
                fetch('https://github-contributions-api.jogruber.de/v4/' + encodeURIComponent(ghUser) + '?y=last').then(function (r) {
                    if (!r.ok) throw new Error('contrib');
                    return r.json();
                })
            ]).then(function (results) {
                var user = results[0];
                var repos = Array.isArray(results[1]) ? results[1] : [];
                var contrib = results[2] || {};

                var publicRepos = repos.filter(function (repo) { return !repo.fork; });
                var days = Array.isArray(contrib.contributions) ? contrib.contributions : [];
                var total = days.reduce(function (sum, d) { return sum + (Number(d.count) || 0); }, 0);

                /* Akun yang masih kosong tetap menampilkan demo yang layak,
                   bukan statistik nol dan daftar repo kosong. */
                if (!publicRepos.length) {
                    setGh('repos', fallbackRepos());
                    setGh('stars', '—');
                    setGh('followers', user.followers != null ? user.followers : '—');
                    setGh('contrib', total > 0 ? total.toLocaleString('id-ID') : fallbackHeatmap().toLocaleString('id-ID'));
                    if (days.length && total > 0) {
                        renderHeatmap(days.map(function (d) {
                            return { date: d.date, count: d.count, level: d.level };
                        }));
                    }
                    return;
                }

                setGh('repos', user.public_repos != null ? user.public_repos : publicRepos.length);
                setGh('stars', publicRepos.reduce(function (sum, repo) { return sum + (repo.stargazers_count || 0); }, 0));
                setGh('followers', user.followers != null ? user.followers : 0);
                setGh('contrib', total.toLocaleString('id-ID'));

                if (days.length) {
                    renderHeatmap(days.map(function (d) {
                        return { date: d.date, count: d.count, level: d.level };
                    }));
                } else {
                    fallbackHeatmap();
                }

                renderRepos(publicRepos
                    .sort(function (a, b) { return (b.stargazers_count || 0) - (a.stargazers_count || 0); })
                    .map(function (repo) {
                        return {
                            name: repo.name,
                            desc: repo.description || 'Tanpa deskripsi',
                            meta: repo.language || (repo.stargazers_count ? '★ ' + repo.stargazers_count : ''),
                            url: repo.html_url
                        };
                    }));
            }).catch(function () {
                var contrib = fallbackHeatmap();
                var count = fallbackRepos();
                setGh('repos', count || '—');
                setGh('stars', '—');
                setGh('followers', '—');
                setGh('contrib', contrib.toLocaleString('id-ID'));
            });
        }
    }

    /* ------------------------------------------------------------------
       Konami code → confetti
       ------------------------------------------------------------------ */

    var konami = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];
    var konamiIndex = 0;
    var confettiCanvas = $('#confetti');

    function launchConfetti() {
        if (!confettiCanvas || reduced) return;
        var ctx = confettiCanvas.getContext('2d');
        if (!ctx) return;

        var dpr = Math.min(2, window.devicePixelRatio || 1);
        var w = window.innerWidth;
        var h = window.innerHeight;
        confettiCanvas.width = w * dpr;
        confettiCanvas.height = h * dpr;
        confettiCanvas.style.width = w + 'px';
        confettiCanvas.style.height = h + 'px';
        ctx.scale(dpr, dpr);

        var colors = ['#C9B8FF', '#8B6CFF', '#2B1B5A', '#FAF8FF', '#A98FFF'];
        var pieces = [];
        for (var i = 0; i < 140; i++) {
            pieces.push({
                x: w / 2 + (Math.random() - 0.5) * w * 0.3,
                y: h * 0.35 + (Math.random() - 0.5) * 80,
                vx: (Math.random() - 0.5) * 11,
                vy: -Math.random() * 12 - 3,
                size: 4 + Math.random() * 6,
                color: colors[Math.floor(Math.random() * colors.length)],
                rot: Math.random() * Math.PI,
                vr: (Math.random() - 0.5) * 0.3
            });
        }

        confettiCanvas.classList.add('is-active');
        var start = performance.now();

        function frame(now) {
            var elapsed = now - start;
            ctx.clearRect(0, 0, w, h);
            pieces.forEach(function (p) {
                p.vy += 0.32;
                p.vx *= 0.995;
                p.x += p.vx;
                p.y += p.vy;
                p.rot += p.vr;
                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rot);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 1.6);
                ctx.restore();
            });
            if (elapsed < 3200) {
                window.requestAnimationFrame(frame);
            } else {
                confettiCanvas.classList.remove('is-active');
                ctx.clearRect(0, 0, w, h);
            }
        }
        window.requestAnimationFrame(frame);
        toast('🎉 Easter egg ditemukan!');
    }

    document.addEventListener('keydown', function (event) {
        var key = event.key;
        if (key === konami[konamiIndex]) {
            konamiIndex++;
            if (konamiIndex === konami.length) {
                konamiIndex = 0;
                launchConfetti();
            }
        } else {
            konamiIndex = key === konami[0] ? 1 : 0;
        }
    });

    /* ------------------------------------------------------------------
       Global keyboard shortcuts
       ------------------------------------------------------------------ */

    document.addEventListener('keydown', function (event) {
        var isField = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement && document.activeElement.tagName) || (document.activeElement && document.activeElement.isContentEditable);

        if ((event.metaKey || event.ctrlKey) && key(event, 'k')) {
            event.preventDefault();
            if (paletteEl && paletteEl.classList.contains('is-open')) closePalette();
            else openPalette();
            return;
        }

        if (event.key === 'Escape') {
            if (paletteEl && paletteEl.classList.contains('is-open')) { closePalette(); return; }
            if (recruiterModal && recruiterModal.classList.contains('is-open')) { closeRecruiter(); return; }
            if (document.body.classList.contains('menu-open')) { closeMenu(); syncLock(); return; }
        }

        if (isField) return;

        if (event.key === '/') {
            event.preventDefault();
            openPalette();
        }

        if (event.key.toLowerCase() === 'r' && !event.metaKey && !event.ctrlKey && !event.altKey) {
            openRecruiter();
        }

        if (event.key.toLowerCase() === 't' && !event.metaKey && !event.ctrlKey && !event.altKey) {
            openPalette('terminal');
        }
    });

    function key(event, value) {
        return (event.key || '').toLowerCase() === value;
    }

    /* ------------------------------------------------------------------
       Focus trap untuk dialog
       ------------------------------------------------------------------ */

    function trapFocus(container, event) {
        if (!container || event.key !== 'Tab') return;
        var focusables = $$('a[href], button:not([disabled]), input, textarea, select, [tabindex]:not([tabindex="-1"])', container)
            .filter(function (el) { return el.offsetParent !== null; });
        if (!focusables.length) return;
        var first = focusables[0];
        var last = focusables[focusables.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    document.addEventListener('keydown', function (event) {
        if (paletteEl && paletteEl.classList.contains('is-open')) trapFocus(paletteEl, event);
        if (recruiterModal && recruiterModal.classList.contains('is-open')) trapFocus(recruiterModal, event);
    });

    /* ------------------------------------------------------------------
       Close palettes when clicking the backdrop only
       ------------------------------------------------------------------ */

    if (paletteEl) {
        paletteEl.addEventListener('mousedown', function (event) {
            if (event.target === paletteEl || (event.target.classList && event.target.classList.contains('modal-backdrop'))) {
                closePalette();
            }
        });
    }

    if (recruiterModal) {
        recruiterModal.addEventListener('mousedown', function (event) {
            if (event.target === recruiterModal || (event.target.classList && event.target.classList.contains('modal-backdrop'))) {
                closeRecruiter();
            }
        });
    }

    /* ------------------------------------------------------------------
       Smooth anchor scrolling untuk link di halaman yang sama
       ------------------------------------------------------------------ */

    $$('a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            var id = link.getAttribute('href');
            if (!id || id === '#') return;
            var target = document.querySelector(id);
            if (!target) return;
            event.preventDefault();
            closeMenu();
            target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
            history.replaceState(null, '', id);
        });
    });

    /* ------------------------------------------------------------------
       Current year di elemen opsional
       ------------------------------------------------------------------ */

    $$('[data-year]').forEach(function (el) {
        el.textContent = String(new Date().getFullYear());
    });

})();
