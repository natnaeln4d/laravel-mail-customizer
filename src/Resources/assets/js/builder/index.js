import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';
import './builder.css';

import { createApi } from './api';
import { registerComponents } from './components';
import { registerBlocks, starterContent, TOOLBOX } from './blocks';
import { canvasFontsUrl, usedGoogleFonts } from './fonts';
import { icon } from './icons';
import { createMenus, MOD } from './menus';
import { createOptionsBar } from './optionsbar';
import { shellHtml } from './shell';
import { CANVAS_CSS, SECTORS, frameThemeCss } from './styles';
import { DEFAULT_THEME, PRESETS, applyTheme, clearStoredTheme, loadStoredTheme, storeTheme } from './theme';

const q = (root, sel) => root.querySelector(sel);
const qa = (root, sel) => [...root.querySelectorAll(sel)];

const ZOOMS = [25, 50, 75, 100, 125, 150, 200];
const UI_FONTS =
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap';

function ensureUiFonts() {
    if (document.getElementById('mc-ui-fonts')) return;
    const link = document.createElement('link');
    link.id = 'mc-ui-fonts';
    link.rel = 'stylesheet';
    link.href = UI_FONTS;
    document.head.appendChild(link);
}

/**
 * Mount the visual email builder.
 *
 * @param {string|HTMLElement} target   element or id
 * @param {object} options
 *   templateId   number|null   template to load
 *   variables    object        sample values for preview / test email; also offered as blocks
 *   apiBase      string        API prefix (default 'api/mail-customizer')
 *   height       string        CSS height of the builder (default '88vh')
 *   theme        object        { mode: light|dark|auto|inherit, preset: luffy|goku|adobe, accent, secondary,
 *                                tokens: {'--mc-*': value}, font, brand, picker }
 *   uiFonts      boolean       load Inter from Google Fonts for the interface (default true)
 *   warnOnLeave  boolean       warn before leaving with unsaved changes (default true)
 */
export function initBuilder(target, options = {}) {
    const root = typeof target === 'string' ? document.getElementById(target) || document.querySelector(target) : target;
    if (!root) {
        console.error('[mail-customizer] mount element not found:', target);
        return null;
    }

    const defined = Object.fromEntries(Object.entries(options).filter(([, v]) => v !== undefined && v !== null));
    const opts = {
        templateId: null,
        variables: {},
        apiBase: 'api/mail-customizer',
        height: '88vh',
        theme: {},
        uiFonts: true,
        warnOnLeave: true,
        ...defined
    };

    const api = createApi(opts.apiBase);
    const state = { id: opts.templateId ? Number(opts.templateId) : null, busy: false, dirty: false };

    // ---- theme: host defaults, then this browser's saved choice --------------------
    const hostTheme = { ...DEFAULT_THEME, ...(opts.theme || {}) };
    const canPick = hostTheme.picker !== false;
    let theme = { ...hostTheme, ...(canPick ? loadStoredTheme() : {}) };
    let resolved = { mode: 'dark', tokens: {} };

    if (opts.uiFonts) ensureUiFonts();

    root.innerHTML = shellHtml({ brand: theme.brand, picker: canPick });
    root.classList.add('mc-app', 'mc-enter');
    root.style.height = opts.height;
    root.dataset.preview = 'desktop';
    setTimeout(() => root.classList.remove('mc-enter'), 1400);

    const nameEl = q(root, '.mc-name');
    const subjectEl = q(root, '.mc-subject');
    const toastEl = q(root, '.mc-toast');
    const layer = q(root, '.mc-pop-layer');

    // ---- toast -------------------------------------------------------------------
    let toastTimer;
    const toast = (message, type = 'info') => {
        toastEl.innerHTML = `${type === 'error' ? icon('close', 14) : type === 'success' ? icon('check', 14) : ''}<span></span>`;
        toastEl.lastChild.textContent = message;
        toastEl.dataset.type = type;
        toastEl.hidden = false;
        toastEl.classList.remove('is-in');
        void toastEl.offsetWidth; // restart the entrance animation
        toastEl.classList.add('is-in');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => (toastEl.hidden = true), type === 'error' ? 6500 : 3200);
    };

    // ---- editor ------------------------------------------------------------------
    const editor = grapesjs.init({
        container: q(root, '.mc-gjs'),
        height: '100%',
        width: 'auto',
        fromElement: false,
        storageManager: false,
        panels: { defaults: [] },
        undoManager: { trackSelection: false },
        selectorManager: { componentFirst: true },
        blockManager: { appendTo: q(root, '.mc-blocks'), appendOnClick: true },
        styleManager: { appendTo: q(root, '.mc-style'), sectors: SECTORS },
        traitManager: { appendTo: q(root, '.mc-traits') },
        layerManager: { appendTo: q(root, '.mc-layers') },
        // ONE design, many widths: devices only change the preview width. An empty widthMedia
        // means no media-query styles are ever written, so Desktop and Mobile can't diverge.
        deviceManager: {
            devices: [
                { id: 'desktop', name: 'Desktop' },
                { id: 'tablet', name: 'Tablet', width: '768px', widthMedia: '' },
                { id: 'mobile', name: 'Mobile', width: '375px', widthMedia: '' }
            ]
        },
        devicePreviewMode: true,
        cssIcons: '', // we ship our own SVG icons; skip GrapesJS's Font Awesome CDN request
        canvas: { styles: [canvasFontsUrl()] },
        protectedCss: CANVAS_CSS,
        assetManager: {
            uploadFile: async e => {
                const files = (e.dataTransfer ? e.dataTransfer.files : e.target.files) || [];
                for (const file of files) {
                    try {
                        const res = await api.upload(file);
                        editor.AssetManager.add({ type: 'image', src: res.url, name: file.name });
                        toast('Image uploaded', 'success');
                    } catch (err) {
                        toast(err.message, 'error');
                    }
                }
            }
        }
    });

    registerComponents(editor);
    registerBlocks(editor, opts.variables);

    let ready = false;

    // ---- theme application ----------------------------------------------------------
    function syncFrame() {
        const doc = editor.Canvas.getDocument();
        if (!doc || !doc.head) return;
        let style = doc.getElementById('mc-frame-theme');
        if (!style) {
            style = doc.createElement('style');
            style.id = 'mc-frame-theme';
            doc.head.appendChild(style);
        }
        style.textContent = frameThemeCss(resolved.tokens, resolved.mode);
    }

    function applyAll() {
        resolved = applyTheme(root, theme);
        syncFrame();
        refreshThemePopover();
    }

    editor.on('canvas:frame:load', syncFrame);
    if (typeof matchMedia === 'function') {
        matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => theme.mode === 'auto' && applyAll());
    }

    // ---- a freshly added element pops in; an empty image opens the picker ---------------
    const isUnfilled = component => !component.get('src') || /^\s*<svg/i.test(component.get('src'));
    let popBatch = false;

    editor.on('component:add', component => {
        if (!ready) return;

        if (!popBatch) {
            popBatch = true;
            setTimeout(() => (popBatch = false), 60);
            setTimeout(() => {
                const el = component.getEl && component.getEl();
                if (el && el.classList) {
                    el.classList.add('mc-just-added');
                    setTimeout(() => el.classList.remove('mc-just-added'), 800);
                }
            }, 0);
        }

        if (component.get('type') === 'mc-image' && isUnfilled(component)) {
            setTimeout(() => {
                editor.select(component);
                editor.runCommand('open-assets', {
                    target: component,
                    types: ['image'],
                    accept: 'image/*',
                    // One click applies the picture and closes the dialog.
                    select: asset => {
                        component.set({ src: asset.get('src') });
                        editor.Modal.close();
                        editor.AssetManager.setTarget(null);
                    }
                });
            }, 0);
        }
    });

    // ---- floating toolbar over a selected element -------------------------------------
    editor.on('component:selected', component => {
        const isWrapper = component.get('type') === 'wrapper';
        const tb = [];
        const item = (name, title, command, extra = {}) =>
            tb.push({ label: icon(name, 14), attributes: { class: 'mc-tb', title, ...extra }, command });

        if (component.parent() && !isWrapper) item('parent', 'Select parent', 'core:component-exit');
        if (component.get('draggable')) item('move', 'Drag to move', 'tlb-move', { draggable: true });
        if (component.get('copyable') !== false && !isWrapper) item('copy', 'Duplicate', 'tlb-clone');
        if (component.get('removable') !== false && !isWrapper) item('trash', 'Delete', 'tlb-delete');
        component.set('toolbar', tb);
    });

    // ---- toolbox (quick-add) --------------------------------------------------------------
    const toolbox = q(root, '[data-toolbox]');
    TOOLBOX.forEach(([, iconName, label]) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mc-tool';
        btn.dataset.tip = `Add ${label}`;
        btn.setAttribute('aria-label', `Add ${label}`);
        btn.innerHTML = icon(iconName, 18);
        btn.addEventListener('click', () => {
            const block = qa(root, '.gjs-block').find(b => b.getAttribute('title') === label);
            if (block) block.click();
        });
        toolbox.appendChild(btn);
    });

    // ---- dock: collapsible panels, empty states ---------------------------------------------
    qa(root, '.mc-panel-head').forEach(head =>
        head.addEventListener('click', () => {
            const collapsed = head.parentElement.classList.toggle('is-collapsed');
            head.setAttribute('aria-expanded', String(!collapsed));
        })
    );

    const syncEmptyStates = () => {
        const has = !!editor.getSelected();
        qa(root, '.mc-empty').forEach(el => (el.hidden = has));
        q(root, '.mc-style').hidden = !has;
        q(root, '.mc-traits').hidden = !has;
    };
    editor.on('component:toggled', syncEmptyStates);
    syncEmptyStates();

    // ---- blocks search -------------------------------------------------------------------------
    q(root, '.mc-search input').addEventListener('input', e => {
        const term = e.target.value.trim().toLowerCase();
        qa(root, '.gjs-block').forEach(b => {
            b.style.display = !term || (b.getAttribute('title') || '').toLowerCase().includes(term) ? '' : 'none';
        });
        qa(root, '.gjs-block-category').forEach(cat => {
            const any = qa(cat, '.gjs-block').some(b => b.style.display !== 'none');
            cat.style.display = any ? '' : 'none';
        });
    });

    // ---- breadcrumb + options bar ------------------------------------------------------------------
    const crumbs = q(root, '.mc-crumbs');
    let crumbPath = [];

    function renderCrumbs() {
        const selected = editor.getSelected();
        if (!selected) {
            crumbs.innerHTML = '<span class="mc-crumb-empty">Nothing selected</span>';
            crumbPath = [];
            return;
        }
        crumbPath = [];
        for (let c = selected; c; c = c.parent()) {
            if (c.get('selectable') !== false) crumbPath.unshift(c);
        }
        crumbs.innerHTML = crumbPath
            .map((c, i) => `<button type="button" data-i="${i}">${c.get('type') === 'wrapper' ? 'Email' : c.getName()}</button>`)
            .join('<span class="mc-crumb-sep">›</span>');
    }
    crumbs.addEventListener('click', e => {
        const b = e.target.closest('button[data-i]');
        if (b && crumbPath[+b.dataset.i]) editor.select(crumbPath[+b.dataset.i]);
    });
    editor.on('component:toggled', renderCrumbs);
    renderCrumbs();

    createOptionsBar(editor, q(root, '.mc-ctx'));

    // ---- zoom --------------------------------------------------------------------------------------------
    let zoom = 100;
    const zoomLabel = q(root, '.mc-zoom-val');

    function setZoom(value) {
        zoom = Math.max(25, Math.min(200, Math.round(value)));
        editor.Canvas.setZoom(zoom);
        zoomLabel.textContent = `${zoom}%`;
    }
    const stepZoom = dir => {
        const next = dir > 0 ? ZOOMS.find(z => z > zoom) : [...ZOOMS].reverse().find(z => z < zoom);
        if (next) setZoom(next);
    };
    const fitZoom = () => setZoom(Math.min(150, ((q(root, '.mc-stage').clientWidth - 48) / 600) * 100));

    // ---- preview width (a *preview*: the design itself stays one) --------------------------------------------
    function setDevice(name) {
        editor.setDevice(name);
        root.dataset.preview = name;
        qa(root, '.mc-devices [data-device]').forEach(b => b.classList.toggle('is-active', b.dataset.device === name));
    }

    // ---- save state --------------------------------------------------------------------------------------------
    const stateEl = q(root, '.mc-savestate');
    const dotEl = q(root, '.mc-doc-dot');
    const titleEl = q(root, '.mc-doc-title');

    function setDirty(dirty) {
        state.dirty = dirty;
        stateEl.dataset.state = dirty ? 'dirty' : 'clean';
        dotEl.dataset.state = dirty ? 'dirty' : 'clean';
        q(stateEl, 'span').textContent = dirty ? 'Unsaved changes' : 'All changes saved';
    }
    editor.on('update', () => ready && !state.dirty && setDirty(true));
    nameEl.addEventListener('input', () => {
        titleEl.textContent = nameEl.value.trim() || 'Untitled template';
        ready && setDirty(true);
    });
    subjectEl.addEventListener('input', () => ready && setDirty(true));
    window.addEventListener('beforeunload', e => {
        if (opts.warnOnLeave && state.dirty && document.body.contains(root)) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // ---- payloads, busy state, save / preview / test / export ----------------------------------------------------
    const draftPayload = () => {
        const css = editor.getCss({ avoidProtected: true }) || '';
        return {
            html: editor.getWrapper().getInnerHTML(),
            css,
            fonts: usedGoogleFonts(css),
            subject: subjectEl.value.trim(),
            variables: opts.variables
        };
    };

    async function withBusy(button, fn) {
        if (state.busy) return;
        state.busy = true;
        root.classList.add('is-busy');
        qa(root, '.mc-actions button').forEach(b => (b.disabled = true));
        button && button.classList.add('is-working');
        try {
            await fn();
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            state.busy = false;
            root.classList.remove('is-busy');
            qa(root, '.mc-actions button').forEach(b => (b.disabled = false));
            button && button.classList.remove('is-working');
        }
    }

    const actionButton = name => q(root, `[data-act="${name}"]`);

    async function save() {
        const name = nameEl.value.trim();
        const subject = subjectEl.value.trim();
        if (!name || !subject) {
            toast('Give the template a name and an email subject first.', 'error');
            (name ? subjectEl : nameEl).focus();
            return;
        }

        await withBusy(actionButton('save'), async () => {
            const payload = { ...draftPayload(), name, subject, structure: editor.getProjectData() };
            delete payload.variables;

            const saved = state.id ? await api.put(`/templates/${state.id}`, payload) : await api.post('/templates', payload);
            state.id = saved.id;
            setDirty(false);

            const empty = editor.getWrapper().find('[data-mc="image"]').filter(isUnfilled).length;
            toast(
                empty
                    ? `Saved — ${empty} image block${empty > 1 ? 's have' : ' has'} no picture yet and will be left out of the email.`
                    : 'Template saved',
                'success'
            );
            root.dispatchEvent(new CustomEvent('mail-customizer:saved', { detail: saved, bubbles: true }));
        });
    }

    async function preview() {
        await withBusy(actionButton('preview'), async () => {
            const { preview: html } = await api.post('/templates/preview', draftPayload());
            openPreview(html);
        });
    }

    async function sendTest() {
        let remembered = '';
        try {
            remembered = localStorage.getItem('mail-customizer.test-email') || '';
        } catch (e) {
            /* storage unavailable */
        }
        const email = window.prompt('Send a test email to:', remembered);
        if (!email) return;

        await withBusy(actionButton('test'), async () => {
            const res = await api.post('/templates/send-test', { ...draftPayload(), email: email.trim() });
            try {
                localStorage.setItem('mail-customizer.test-email', email.trim());
            } catch (e) {
                /* storage unavailable */
            }
            toast(res.message || 'Test email sent', 'success');
        });
    }

    async function exportHtml() {
        await withBusy(null, async () => {
            const { preview: html } = await api.post('/templates/preview', draftPayload());
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([html], { type: 'text/html' }));
            link.download = (nameEl.value.trim() || 'email').replace(/[^\w-]+/g, '-') + '.html';
            link.click();
            setTimeout(() => URL.revokeObjectURL(link.href), 1000);
            toast('HTML exported', 'success');
        });
    }

    function openPreview(html) {
        const wrap = document.createElement('div');
        wrap.className = 'mc-preview';
        wrap.innerHTML = `
          <div class="mc-preview-bar">
            <button type="button" data-w="680" class="is-active">${icon('desktop', 14)} Desktop</button>
            <button type="button" data-w="375">${icon('phone', 14)} Mobile</button>
            <button type="button" data-w="code">HTML</button>
          </div>
          <iframe class="mc-preview-frame" sandbox="allow-same-origin" title="Email preview"></iframe>
          <textarea class="mc-preview-code" readonly hidden></textarea>`;

        const frame = q(wrap, 'iframe');
        const code = q(wrap, 'textarea');
        frame.srcdoc = html;
        code.value = html;

        qa(wrap, '.mc-preview-bar button').forEach(btn =>
            btn.addEventListener('click', () => {
                qa(wrap, '.mc-preview-bar button').forEach(b => b.classList.toggle('is-active', b === btn));
                const isCode = btn.dataset.w === 'code';
                frame.hidden = isCode;
                code.hidden = !isCode;
                if (!isCode) frame.style.width = btn.dataset.w + 'px';
            })
        );

        editor.Modal.open({ title: 'Email preview — exactly what recipients get', content: wrap });
    }

    function openShortcuts() {
        const rows = [
            [`${MOD} S`, 'Save'],
            [`${MOD} Z`, 'Undo'],
            [`${MOD} ⇧ Z`, 'Redo'],
            [`${MOD} D`, 'Duplicate selection'],
            ['Delete', 'Delete selection'],
            [`${MOD} + / −`, 'Zoom in / out'],
            [`${MOD} 0`, 'Actual size (100%)'],
            [`${MOD} C / V`, 'Copy / paste'],
            ['Double-click', 'Edit text or change an image'],
            ['Esc', 'Close menus and dialogs']
        ];
        const wrap = document.createElement('div');
        wrap.className = 'mc-shortcuts';
        wrap.innerHTML = rows.map(([k, v]) => `<div><kbd>${k}</kbd><span>${v}</span></div>`).join('');
        editor.Modal.open({ title: 'Keyboard shortcuts', content: wrap });
    }

    // ---- theme popover ------------------------------------------------------------------------------------------------
    let popover = null;

    function closePopover() {
        if (popover) {
            const el = popover;
            el.classList.add('is-leaving');
            setTimeout(() => el.remove(), 140);
            popover = null;
        }
    }

    function commitTheme() {
        applyAll();
        storeTheme(theme);
    }

    function refreshThemePopover() {
        if (!popover) return;
        qa(popover, '[data-mode]').forEach(b => b.classList.toggle('is-active', b.dataset.mode === theme.mode));
        qa(popover, '[data-preset]').forEach(b => b.classList.toggle('is-active', b.dataset.preset === theme.preset && !theme.accent));
        const accent = popover.querySelector('[data-k="accent"]');
        const secondary = popover.querySelector('[data-k="secondary"]');
        if (accent) accent.value = (resolved.tokens['--mc-accent'] || '#e0453a').slice(0, 7);
        if (secondary) secondary.value = (resolved.tokens['--mc-secondary'] || '#f5c542').slice(0, 7);
    }

    function openThemePopover() {
        if (popover) return closePopover();

        const pop = document.createElement('div');
        pop.className = 'mc-popover';
        pop.innerHTML = `
          <div class="mc-pop-title">Appearance</div>
          <div class="mc-pop-label">Mode</div>
          <div class="mc-seg-group">
            <button type="button" data-mode="light">${icon('sun', 14)}<span>Light</span></button>
            <button type="button" data-mode="dark">${icon('moon', 14)}<span>Dark</span></button>
            <button type="button" data-mode="auto"><span>Auto</span></button>
            <button type="button" data-mode="inherit" title="Match the colors of this website"><span>Match site</span></button>
          </div>
          <div class="mc-pop-label">Color theme</div>
          <div class="mc-presets">
            ${Object.entries(PRESETS)
                .map(
                    ([id, p]) => `
              <button type="button" class="mc-preset" data-preset="${id}">
                <span class="mc-sw"><i style="background:${p.accent}"></i><i style="background:${p.secondary}"></i></span>
                <span class="mc-preset-txt"><b>${p.label}</b><small>${p.hint}</small></span>
              </button>`
                )
                .join('')}
          </div>
          <div class="mc-pop-label">Custom colors</div>
          <div class="mc-colors">
            <label><span>Accent</span><input type="color" data-k="accent"></label>
            <label><span>Highlight</span><input type="color" data-k="secondary"></label>
          </div>
          <div class="mc-pop-foot"><button type="button" data-reset>${icon('reset', 13)} Reset to default</button></div>`;

        const rootBox = root.getBoundingClientRect();
        const anchor = q(root, '[data-act="theme-open"]').getBoundingClientRect();
        pop.style.top = `${Math.round(anchor.bottom - rootBox.top + 6)}px`;
        pop.style.right = `${Math.max(8, Math.round(rootBox.right - anchor.right - 40))}px`;
        layer.appendChild(pop);
        popover = pop;

        pop.addEventListener('click', e => {
            const mode = e.target.closest('[data-mode]');
            const preset = e.target.closest('[data-preset]');
            if (mode) {
                theme.mode = mode.dataset.mode;
                commitTheme();
            } else if (preset) {
                theme.preset = preset.dataset.preset;
                theme.accent = null;
                theme.secondary = null;
                commitTheme();
            } else if (e.target.closest('[data-reset]')) {
                clearStoredTheme();
                theme = { ...hostTheme };
                applyAll();
            }
        });
        pop.addEventListener('input', e => {
            const k = e.target.dataset && e.target.dataset.k;
            if (k) {
                theme[k] = e.target.value;
                commitTheme();
            }
        });

        refreshThemePopover();
    }

    document.addEventListener('click', e => {
        if (popover && !popover.contains(e.target) && !e.target.closest('[data-act="theme-open"]')) closePopover();
    });
    document.addEventListener('keydown', e => e.key === 'Escape' && closePopover());

    const toggleMode = () => {
        theme.mode = resolved.mode === 'dark' ? 'light' : 'dark';
        commitTheme();
    };

    // ---- panels / outlines ------------------------------------------------------------------------------------------------
    function toggleBlocks(force) {
        const hide = force === undefined ? !root.classList.contains('blocks-hidden') : !force;
        root.classList.toggle('blocks-hidden', hide);
        q(root, '[data-act="toggle-blocks"]').classList.toggle('is-active', !hide);
        setTimeout(() => editor.refresh(), 340);
    }

    function toggleOutlines() {
        const on = editor.Commands.isActive('core:component-outline');
        if (on) editor.stopCommand('core:component-outline');
        else editor.runCommand('core:component-outline');
        q(root, '[data-act="outlines"]').classList.toggle('is-active', !on);
    }

    // ---- actions + menus ------------------------------------------------------------------------------------------------------
    const A = {
        save,
        preview,
        test: sendTest,
        exportHtml,
        undo: () => editor.UndoManager.undo(),
        redo: () => editor.UndoManager.redo(),
        duplicate: () => editor.getSelected() && editor.runCommand('tlb-clone'),
        remove: () => editor.getSelected() && editor.runCommand('core:component-delete'),
        parent: () => editor.getSelected() && editor.runCommand('core:component-exit'),
        zoomIn: () => stepZoom(1),
        zoomOut: () => stepZoom(-1),
        zoom100: () => setZoom(100),
        fit: fitZoom,
        outlines: toggleOutlines,
        blocks: () => toggleBlocks(),
        help: openShortcuts
    };

    const device = () => root.dataset.preview;
    const hasSelection = () => !!editor.getSelected() && editor.getSelected().get('type') !== 'wrapper';

    const menus = createMenus({
        root,
        layer,
        defs: {
            file: () => [
                { label: 'Save', icon: 'save', shortcut: `${MOD} S`, run: A.save },
                { label: 'Preview email', icon: 'eye', run: A.preview },
                { label: 'Send test email…', icon: 'send', run: A.test },
                { sep: true },
                { label: 'Export HTML…', icon: 'download', run: A.exportHtml }
            ],
            edit: () => [
                { label: 'Undo', icon: 'undo', shortcut: `${MOD} Z`, run: A.undo, disabled: !editor.UndoManager.hasUndo() },
                { label: 'Redo', icon: 'redo', shortcut: `${MOD} ⇧ Z`, run: A.redo, disabled: !editor.UndoManager.hasRedo() },
                { sep: true },
                { label: 'Select parent', icon: 'parent', run: A.parent, disabled: !hasSelection() },
                { label: 'Duplicate', icon: 'copy', shortcut: `${MOD} D`, run: A.duplicate, disabled: !hasSelection() },
                { label: 'Delete', icon: 'trash', shortcut: 'Del', run: A.remove, disabled: !hasSelection() }
            ],
            view: () => [
                { label: 'Zoom in', icon: 'zoomIn', shortcut: `${MOD} +`, run: A.zoomIn },
                { label: 'Zoom out', icon: 'zoomOut', shortcut: `${MOD} −`, run: A.zoomOut },
                { label: 'Fit to screen', icon: 'fit', run: A.fit },
                { label: 'Actual size', shortcut: `${MOD} 0`, run: A.zoom100 },
                { sep: true },
                { label: 'Show outlines', checked: editor.Commands.isActive('core:component-outline'), run: A.outlines },
                { label: 'Show blocks panel', checked: !root.classList.contains('blocks-hidden'), run: A.blocks },
                { sep: true },
                { label: 'Preview as desktop', checked: device() === 'desktop', run: () => setDevice('desktop') },
                { label: 'Preview as tablet', checked: device() === 'tablet', run: () => setDevice('tablet') },
                { label: 'Preview as mobile', checked: device() === 'mobile', run: () => setDevice('mobile') },
                ...(canPick ? [{ sep: true }, { label: 'Theme & colors…', icon: 'palette', run: openThemePopover }] : [])
            ],
            help: () => [{ label: 'Keyboard shortcuts', icon: 'keyboard', run: A.help }]
        }
    });

    // Buttons with a data-act / data-zoom / data-device.
    root.addEventListener('click', e => {
        const act = e.target.closest('[data-act]');
        const zoomBtn = e.target.closest('[data-zoom]');
        const dev = e.target.closest('.mc-devices [data-device]');

        if (zoomBtn) {
            const z = zoomBtn.dataset.zoom;
            if (z === 'in') A.zoomIn();
            else if (z === 'out') A.zoomOut();
            else if (z === 'reset') A.zoom100();
            else if (z === 'fit') A.fit();
        } else if (dev) {
            setDevice(dev.dataset.device);
        } else if (act) {
            const name = act.dataset.act;
            if (name === 'theme-toggle') toggleMode();
            else if (name === 'theme-open') openThemePopover();
            else if (name === 'toggle-blocks') A.blocks();
            else if (name === 'outlines') A.outlines();
            else if (name === 'help') A.help();
            else if (A[name]) A[name]();
        }
        menus.close();
    });

    // Shortcuts work inside the canvas frame too (GrapesJS keymaps cover both documents).
    const km = editor.Keymaps;
    km.add('mc:save', '⌘+s, ctrl+s', () => A.save(), { prevent: true });
    km.add('mc:duplicate', '⌘+d, ctrl+d', () => A.duplicate(), { prevent: true });
    km.add('mc:zoom-in', '⌘+=, ctrl+=, ⌘+plus, ctrl+plus', () => A.zoomIn(), { prevent: true });
    km.add('mc:zoom-out', '⌘+-, ctrl+-', () => A.zoomOut(), { prevent: true });
    km.add('mc:zoom-100', '⌘+0, ctrl+0', () => A.zoom100(), { prevent: true });

    // ---- narrow screens: start with the blocks panel tucked away ----------------------------------------------------------------
    if (root.clientWidth && root.clientWidth < 1040) toggleBlocks(false);

    // ---- load ----------------------------------------------------------------------------------------------------------------------
    applyAll();

    editor.onReady(async () => {
        editor.getWrapper().addAttributes({ 'data-mc-drop': '', 'data-mc-root': '' });
        editor.runCommand('core:component-outline');
        syncFrame();

        const finish = () => {
            editor.UndoManager.clear();
            setDirty(false);
            ready = true;
        };

        if (!state.id) {
            editor.setComponents(starterContent());
            return finish();
        }

        try {
            const template = await api.get(`/templates/${state.id}`);
            nameEl.value = template.name || '';
            subjectEl.value = template.subject || '';
            titleEl.textContent = template.name || 'Untitled template';

            if (template.structure && !Array.isArray(template.structure) && template.structure.pages) {
                editor.loadProjectData(template.structure);
            } else {
                editor.setComponents(starterContent());
                toast('This template was made with the old builder and can’t be opened here. Saving will replace it.', 'error');
            }
        } catch (err) {
            toast(err.message, 'error');
        }
        finish();
    });

    const instance = {
        editor,
        save,
        getState: () => ({ ...state }),
        /** Change colours/mode at runtime, e.g. when the host app's own theme changes. */
        setTheme: partial => {
            theme = { ...theme, ...partial };
            applyAll();
        }
    };
    root.mailCustomizer = instance; // handy for host pages and debugging
    return instance;
}
