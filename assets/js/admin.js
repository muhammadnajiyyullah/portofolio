/* ==================================================================
   Dev Journal — admin workspace interactions
   ================================================================== */

(function () {
    'use strict';

    var ADMIN = window.ADMIN || {};
    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

    /* ------------------------------------------------------------------
       Flash helper
       ------------------------------------------------------------------ */

    function adminFlash(message, type) {
        var content = $('.admin-content');
        if (!content) return;
        var el = document.createElement('div');
        el.className = 'admin-flash admin-flash--' + (type || 'success');
        var span = document.createElement('span');
        span.textContent = message;
        el.appendChild(span);
        content.insertBefore(el, content.firstChild);
        setTimeout(function () { dismissFlash(el); }, 4200);
        return el;
    }

    function dismissFlash(el) {
        if (!el || !el.parentNode) return;
        el.style.transition = 'opacity .2s ease, transform .2s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-4px)';
        setTimeout(function () {
            if (el.parentNode) el.parentNode.removeChild(el);
        }, 220);
    }

    $$('[data-dismiss-flash]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            dismissFlash(btn.closest('.admin-flash'));
        });
    });

    $$('.admin-flash').forEach(function (el) {
        setTimeout(function () { dismissFlash(el); }, 6500);
    });

    /* ------------------------------------------------------------------
       Mobile sidebar
       ------------------------------------------------------------------ */

    var sidebarOpen = $('#sidebarOpen');
    var sidebarClose = $('#sidebarClose');
    var overlay = $('#adminOverlay');

    function openSidebar() {
        document.body.classList.add('sidebar-open');
    }

    function closeSidebar() {
        document.body.classList.remove('sidebar-open');
    }

    if (sidebarOpen) sidebarOpen.addEventListener('click', openSidebar);
    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    $$('.admin-nav a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.matchMedia('(max-width: 1080px)').matches) closeSidebar();
        });
    });

    /* ------------------------------------------------------------------
       Admin theme
       ------------------------------------------------------------------ */

    var adminTheme = $('#adminTheme');

    function applyAdminTheme(mode, persist) {
        document.documentElement.dataset.mode = mode;
        if (adminTheme) {
            adminTheme.setAttribute('aria-label', mode === 'dark' ? 'Gunakan mode terang' : 'Gunakan mode gelap');
            adminTheme.setAttribute('title', mode === 'dark' ? 'Mode terang' : 'Mode gelap');
        }
        if (persist) {
            try { localStorage.setItem('dj-admin-mode', mode); } catch (e) {}
        }
    }

    if (adminTheme) {
        applyAdminTheme(document.documentElement.dataset.mode === 'dark' ? 'dark' : 'light', false);
        adminTheme.addEventListener('click', function () {
            var next = document.documentElement.dataset.mode === 'dark' ? 'light' : 'dark';
            applyAdminTheme(next, true);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
            closeSidebar();
        }
    });

    /* ------------------------------------------------------------------
       Password visibility
       ------------------------------------------------------------------ */

    $$('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-toggle-password'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
            btn.classList.toggle('is-active', show);
        });
    });

    /* ------------------------------------------------------------------
       Image preview sebelum upload
       ------------------------------------------------------------------ */

    function previewTargets(input) {
        var spec = input.getAttribute('data-preview') || '';
        var scopes = [
            input.closest('[data-image-field]'),
            input.closest('.media-field'),
            input.closest('.profile-form'),
            document
        ];
        var targets = [];

        spec.split(',').forEach(function (selector) {
            selector = selector.trim();
            if (!selector) return;

            if (selector.indexOf('[data-preview-target]') !== -1) {
                var scope = scopes[0];
                if (scope) {
                    $$('[data-preview-target]', scope).forEach(function (el) {
                        if (targets.indexOf(el) === -1) targets.push(el);
                    });
                }
                return;
            }

            for (var i = 0; i < scopes.length; i++) {
                if (!scopes[i]) continue;
                try {
                    var found = $$(selector, scopes[i]);
                    if (found.length) {
                        found.forEach(function (el) {
                            if (targets.indexOf(el) === -1) targets.push(el);
                        });
                        break;
                    }
                } catch (err) { /* selector tidak valid — lewati */ }
            }
        });

        return targets;
    }

    $$('input[type="file"][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file || !/^image\//.test(file.type)) return;
            var url = URL.createObjectURL(file);
            previewTargets(input).forEach(function (img) {
                img.src = url;
                img.style.opacity = '1';
                img.addEventListener('load', function () { URL.revokeObjectURL(url); }, { once: true });
            });
        });
    });

    $$('.gallery-field__item input[type="checkbox"]').forEach(function (box) {
        box.addEventListener('change', function () {
            var item = box.closest('.gallery-field__item');
            if (item) item.classList.toggle('is-marked', box.checked);
        });
    });

    /* ------------------------------------------------------------------
       Slug otomatis
       ------------------------------------------------------------------ */

    function slugify(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 90);
    }

    $$('[data-slug-input]').forEach(function (slugInput) {
        var fromName = slugInput.getAttribute('data-slug-from') || 'title';
        var form = slugInput.closest('form');
        if (!form) return;
        var source = $('[name="' + fromName + '"]', form);
        if (!source) return;

        if (slugInput.value.trim() !== '') slugInput.dataset.touched = '1';

        slugInput.addEventListener('input', function () { slugInput.dataset.touched = '1'; });

        source.addEventListener('input', function () {
            if (slugInput.dataset.touched === '1') return;
            slugInput.value = slugify(source.value);
        });

        source.addEventListener('blur', function () {
            if (slugInput.dataset.touched === '1' || slugInput.value !== '') return;
            slugInput.value = slugify(source.value);
        });
    });

    /* ------------------------------------------------------------------
       Rich text editor
       ------------------------------------------------------------------ */

    function syncEditor(rte) {
        var editor = $('[data-rte-editor]', rte);
        var input = $('[data-rte-input]', rte);
        if (editor && input) input.value = editor.innerHTML;
    }

    $$('[data-rte]').forEach(function (rte) {
        var editor = $('[data-rte-editor]', rte);
        var input = $('[data-rte-input]', rte);
        var toolbar = $('.rte__toolbar', rte);
        if (!editor) return;

        if (!editor.textContent.trim() && !editor.querySelector('img, hr, iframe')) {
            editor.setAttribute('data-empty', 'true');
        }

        editor.addEventListener('input', function () {
            editor.setAttribute('data-empty', editor.textContent.trim() ? 'false' : 'true');
            syncEditor(rte);
        });

        editor.addEventListener('paste', function (event) {
            if (!event.clipboardData) return;
            var text = event.clipboardData.getData('text/plain');
            if (!text) return;
            event.preventDefault();
            document.execCommand('insertText', false, text);
            syncEditor(rte);
        });

        if (toolbar) {
            $$('button[data-cmd]', toolbar).forEach(function (btn) {
                btn.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                });
                btn.addEventListener('click', function () {
                    var cmd = btn.getAttribute('data-cmd');
                    var value = btn.getAttribute('data-value') || null;
                    editor.focus();

                    try {
                        if (cmd === 'createLink') {
                            var url = window.prompt('Masukkan URL tautan (termasuk https://):', 'https://');
                            if (!url) return;
                            document.execCommand('createLink', false, url);
                        } else if (cmd === 'formatBlock') {
                            document.execCommand('formatBlock', false, value || 'p');
                        } else {
                            document.execCommand(cmd, false, value);
                        }
                    } catch (err) {
                        adminFlash('Format teks tidak didukung browser ini.', 'error');
                    }

                    syncEditor(rte);
                    editor.setAttribute('data-empty', editor.textContent.trim() ? 'false' : 'true');
                });
            });
        }

        var form = rte.closest('form');
        if (form) {
            form.addEventListener('submit', function () { syncEditor(rte); });
        }
        syncEditor(rte);
    });

    /* ------------------------------------------------------------------
       Konfirmasi aksi destruktif
       ------------------------------------------------------------------ */

    $$('[data-confirm]').forEach(function (el) {
        if (el.tagName === 'FORM') {
            el.addEventListener('submit', function (event) {
                if (!window.confirm(el.getAttribute('data-confirm') || 'Lanjutkan?')) {
                    event.preventDefault();
                }
            });
        } else {
            el.addEventListener('click', function (event) {
                if (!window.confirm(el.getAttribute('data-confirm') || 'Lanjutkan?')) {
                    event.preventDefault();
                }
            });
        }
    });

    /* ------------------------------------------------------------------
       Drag & drop reorder
       ------------------------------------------------------------------ */

    $$('table[data-sortable]').forEach(function (table) {
        var tbody = $('tbody', table);
        if (!tbody) return;

        var dragged = null;
        var originalIds = $$('tr[data-id]', tbody).map(function (row) { return row.getAttribute('data-id'); });
        var saving = false;

        function rows() {
            return $$('tr[data-id]', tbody);
        }

        function ids() {
            return rows().map(function (row) { return row.getAttribute('data-id'); });
        }

        function clearOver() {
            rows().forEach(function (row) { row.classList.remove('is-over'); });
        }

        function saveOrder() {
            if (saving) return;
            var current = ids();
            if (current.join(',') === originalIds.join(',')) return;

            var type = table.getAttribute('data-type') || '';
            saving = true;
            table.style.opacity = '.6';

            fetch('collection.php?type=' + encodeURIComponent(type) + '&action=reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': ADMIN.csrf || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify({ ids: current })
            }).then(function (res) { return res.json(); }).then(function (data) {
                if (!data.ok) throw new Error(data.error || 'Gagal menyimpan urutan.');
                originalIds = current;
                adminFlash('Urutan berhasil disimpan.');
            }).catch(function (err) {
                adminFlash(err.message || 'Urutan gagal disimpan.', 'error');
                var lookup = {};
                rows().forEach(function (row) { lookup[row.getAttribute('data-id')] = row; });
                originalIds.forEach(function (id) {
                    if (lookup[id]) tbody.appendChild(lookup[id]);
                });
            }).finally(function () {
                saving = false;
                table.style.opacity = '';
            });
        }

        rows().forEach(function (row) {
            row.addEventListener('dragstart', function (event) {
                dragged = row;
                row.classList.add('is-dragging');
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    try { event.dataTransfer.setData('text/plain', row.getAttribute('data-id')); } catch (e) {}
                }
            });

            row.addEventListener('dragend', function () {
                row.classList.remove('is-dragging');
                clearOver();
                dragged = null;
                saveOrder();
            });
        });

        tbody.addEventListener('dragover', function (event) {
            if (!dragged) return;
            event.preventDefault();
            if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';

            var target = event.target.closest('tr[data-id]');
            clearOver();
            if (!target || target === dragged) return;
            target.classList.add('is-over');

            var rect = target.getBoundingClientRect();
            var after = event.clientY > rect.top + rect.height / 2;
            tbody.insertBefore(dragged, after ? target.nextSibling : target);
        });

        tbody.addEventListener('drop', function (event) {
            if (!dragged) return;
            event.preventDefault();
            clearOver();
        });
    });

    /* ------------------------------------------------------------------
       Live preview palet & font di halaman tampilan
       ------------------------------------------------------------------ */

    var presetRadios = $$('input[name="theme_preset"]');
    var preview = $('[data-theme-preview]');
    var presetStyles = {
        lavender: ['#C9B8FF', '#FAF8FF', '#2B1B5A', '#8B6CFF'],
        sage: ['#CDE7D4', '#F7FBF8', '#173A2C', '#4E9B72'],
        neon: ['#C6F24E', '#F7F8FF', '#14122B', '#6D4AFF']
    };

    function applyPreview(name) {
        if (!preview) return;
        var colors = presetStyles[name];
        if (!colors) return;
        preview.style.setProperty('--accent', colors[3]);
        preview.style.setProperty('--accent-strong', colors[3]);
        preview.style.setProperty('--accent-2', colors[0]);
        preview.style.setProperty('--accent-soft', 'color-mix(in srgb, ' + colors[3] + ' 14%, ' + colors[1] + ')');
        preview.style.setProperty('--surface', colors[1]);
        preview.style.setProperty('--ink', colors[2]);
    }

    presetRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (radio.checked) applyPreview(radio.value);
        });
    });

    if (preview) {
        var checkedPreset = presetRadios.filter(function (r) { return r.checked; })[0];
        if (checkedPreset) applyPreview(checkedPreset.value);
    }

    /* ------------------------------------------------------------------
       Preview file CV terpilih
       ------------------------------------------------------------------ */

    $$('input[type="file"][name="cv_file"]').forEach(function (input) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;
            var holder = input.closest('.media-field');
            var note = holder ? $('p', holder) : null;
            if (note) note.textContent = 'File dipilih: ' + file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
        });
    });

    /* ------------------------------------------------------------------
       Toast kecil untuk interaksi lain
       ------------------------------------------------------------------ */

    window.adminFlash = adminFlash;

})();
