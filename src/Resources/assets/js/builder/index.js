import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';
import './builder.css';

import { createApi } from './api';
import { registerComponents } from './components';
import { registerBlocks, starterContent } from './blocks';
import { canvasFontsUrl, usedGoogleFonts } from './fonts';
import { CANVAS_CSS, SECTORS } from './styles';

const TEMPLATE = `
<div class="mc-topbar">
  <div class="mc-fields">
    <input class="mc-name" type="text" placeholder="Template name" aria-label="Template name">
    <input class="mc-subject" type="text" placeholder="Email subject" aria-label="Email subject">
  </div>
  <div class="mc-actions">
    <div class="mc-group">
      <button type="button" data-act="desktop" class="is-active">Desktop</button>
      <button type="button" data-act="mobile">Mobile</button>
    </div>
    <div class="mc-group">
      <button type="button" data-act="undo">Undo</button>
      <button type="button" data-act="redo">Redo</button>
      <button type="button" data-act="outlines" class="is-active">Outlines</button>
    </div>
    <button type="button" data-act="preview">Preview email</button>
    <button type="button" data-act="test">Send test</button>
    <button type="button" data-act="save" class="mc-primary">Save</button>
  </div>
</div>
<div class="mc-main">
  <aside class="mc-left">
    <div class="mc-panel-title">Blocks <span>drag, or click to add</span></div>
    <div class="mc-blocks"></div>
  </aside>
  <div class="mc-canvas"><div class="mc-gjs"></div></div>
  <aside class="mc-right">
    <div class="mc-tabs">
      <button type="button" data-tab="style" class="is-active">Style</button>
      <button type="button" data-tab="settings">Settings</button>
      <button type="button" data-tab="layers">Layers</button>
    </div>
    <div class="mc-pane" data-pane="style">
      <div class="mc-empty">Select an element on the canvas to style it.</div>
      <div class="mc-style"></div>
    </div>
    <div class="mc-pane" data-pane="settings" hidden>
      <div class="mc-empty">Select an element to edit its settings: link, background color, image, overlay and gradient.</div>
      <div class="mc-traits"></div>
    </div>
    <div class="mc-pane" data-pane="layers" hidden><div class="mc-layers"></div></div>
  </aside>
</div>
<div class="mc-toast" hidden></div>
`;

const q = (root, sel) => root.querySelector(sel);

/**
 * Mount the visual email builder.
 *
 * @param {string|HTMLElement} target   element or id
 * @param {object} options
 *   templateId  number|null   template to load
 *   variables   object        sample values for preview / test email, also offered as blocks
 *   apiBase     string        API prefix (default 'api/mail-customizer')
 *   height      string        CSS height of the builder (default '88vh')
 */
export function initBuilder(target, options = {}) {
    const root = typeof target === 'string' ? document.getElementById(target) || document.querySelector(target) : target;
    if (!root) {
        console.error('[mail-customizer] mount element not found:', target);
        return null;
    }

    const defined = Object.fromEntries(Object.entries(options).filter(([, v]) => v !== undefined && v !== null));
    const opts = { templateId: null, variables: {}, apiBase: 'api/mail-customizer', height: '88vh', ...defined };
    const api = createApi(opts.apiBase);
    const state = { id: opts.templateId ? Number(opts.templateId) : null, busy: false };

    root.innerHTML = TEMPLATE;
    root.classList.add('mc-app');
    root.style.height = opts.height;

    const nameEl = q(root, '.mc-name');
    const subjectEl = q(root, '.mc-subject');
    const toastEl = q(root, '.mc-toast');

    // ---- toast ----------------------------------------------------------
    let toastTimer;
    const toast = (message, type = 'info') => {
        toastEl.textContent = message;
        toastEl.dataset.type = type;
        toastEl.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => (toastEl.hidden = true), type === 'error' ? 6000 : 3000);
    };

    // ---- editor ---------------------------------------------------------
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
        deviceManager: {
            devices: [
                { id: 'desktop', name: 'Desktop' },
                { id: 'mobile', name: 'Mobile', width: '375px', widthMedia: '480px' }
            ]
        },
        canvas: { styles: [canvasFontsUrl()] },
        protectedCss: CANVAS_CSS,
        assetManager: {
            uploadFile: async e => {
                const files = (e.dataTransfer ? e.dataTransfer.files : e.target.files) || [];
                for (const file of files) {
                    try {
                        const res = await api.upload(file);
                        editor.AssetManager.add({ type: 'image', src: res.url, name: file.name });
                        toast('Image uploaded');
                    } catch (err) {
                        toast(err.message, 'error');
                    }
                }
            }
        }
    });

    registerComponents(editor);
    registerBlocks(editor, opts.variables);

    // A freshly added, empty image opens the picker straight away (drag or click).
    // Not while a template is loading, or every saved empty image would pop it open.
    let ready = false;
    // New images carry a placeholder SVG as `src` until a picture is chosen.
    const isUnfilled = component => !component.get('src') || /^\s*<svg/i.test(component.get('src'));
    editor.on('component:add', component => {
        if (!ready || component.get('type') !== 'mc-image' || !isUnfilled(component)) return;
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
    });

    // ---- toolbar over a selected element (plain-text icons: no icon font needed) ----
    editor.on('component:selected', component => {
        const tb = [];
        if (component.parent() && component.get('type') !== 'wrapper') {
            tb.push({ label: '↰', attributes: { class: 'mc-tb', title: 'Select parent' }, command: 'core:component-exit' });
        }
        if (component.get('draggable')) {
            tb.push({ label: '✥', attributes: { class: 'mc-tb', title: 'Drag to move', draggable: true }, command: 'tlb-move' });
        }
        if (component.get('copyable') !== false && component.get('type') !== 'wrapper') {
            tb.push({ label: '⧉', attributes: { class: 'mc-tb', title: 'Duplicate' }, command: 'tlb-clone' });
        }
        if (component.get('removable') !== false && component.get('type') !== 'wrapper') {
            tb.push({ label: '✕', attributes: { class: 'mc-tb', title: 'Delete' }, command: 'tlb-delete' });
        }
        component.set('toolbar', tb);
    });

    // ---- right panel: tabs + empty states ------------------------------------
    const showTab = name => {
        root.querySelectorAll('.mc-tabs button').forEach(b => b.classList.toggle('is-active', b.dataset.tab === name));
        root.querySelectorAll('.mc-pane').forEach(p => (p.hidden = p.dataset.pane !== name));
    };
    root.querySelectorAll('.mc-tabs button').forEach(b => b.addEventListener('click', () => showTab(b.dataset.tab)));

    const syncEmptyStates = () => {
        const has = !!editor.getSelected();
        root.querySelectorAll('.mc-empty').forEach(el => (el.hidden = has));
        q(root, '.mc-style').hidden = !has;
        q(root, '.mc-traits').hidden = !has;
    };
    editor.on('component:toggled', syncEmptyStates);
    syncEmptyStates();

    // ---- payload / preview / test ---------------------------------------------
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

    const setBusy = (busy, button) => {
        state.busy = busy;
        root.querySelectorAll('.mc-actions button').forEach(b => (b.disabled = busy));
        if (button) button.textContent = busy ? 'Working…' : button.dataset.label;
    };

    async function withBusy(button, fn) {
        if (state.busy) return;
        button.dataset.label = button.textContent;
        setBusy(true, button);
        try {
            await fn();
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            setBusy(false, button);
        }
    }

    async function save(button) {
        const name = nameEl.value.trim();
        const subject = subjectEl.value.trim();
        if (!name || !subject) {
            toast('Give the template a name and an email subject first.', 'error');
            (name ? subjectEl : nameEl).focus();
            return;
        }

        await withBusy(button, async () => {
            const payload = { ...draftPayload(), name, subject, structure: editor.getProjectData() };
            delete payload.variables;

            const saved = state.id ? await api.put(`/templates/${state.id}`, payload) : await api.post('/templates', payload);
            state.id = saved.id;

            const empty = editor.getWrapper().find('[data-mc="image"]').filter(isUnfilled).length;
            toast(
                empty
                    ? `Saved ✓ — ${empty} image block${empty > 1 ? 's have' : ' has'} no picture yet and will be left out of the email.`
                    : 'Template saved ✓'
            );
            root.dispatchEvent(new CustomEvent('mail-customizer:saved', { detail: saved, bubbles: true }));
        });
    }

    async function preview(button) {
        await withBusy(button, async () => {
            const { preview: html } = await api.post('/templates/preview', draftPayload());
            openPreview(html);
        });
    }

    async function sendTest(button) {
        const remembered = (() => {
            try {
                return localStorage.getItem('mail-customizer.test-email') || '';
            } catch (e) {
                return '';
            }
        })();
        const email = window.prompt('Send a test email to:', remembered);
        if (!email) return;

        await withBusy(button, async () => {
            const res = await api.post('/templates/send-test', { ...draftPayload(), email: email.trim() });
            try {
                localStorage.setItem('mail-customizer.test-email', email.trim());
            } catch (e) {
                /* storage unavailable */
            }
            toast(res.message || 'Test email sent ✓');
        });
    }

    function openPreview(html) {
        const wrap = document.createElement('div');
        wrap.className = 'mc-preview';
        wrap.innerHTML = `
          <div class="mc-preview-bar">
            <button type="button" data-w="680" class="is-active">Desktop</button>
            <button type="button" data-w="375">Mobile</button>
            <button type="button" data-w="code">HTML</button>
          </div>
          <iframe class="mc-preview-frame" sandbox="allow-same-origin"></iframe>
          <textarea class="mc-preview-code" readonly hidden></textarea>`;

        const frame = q(wrap, 'iframe');
        const code = q(wrap, 'textarea');
        frame.srcdoc = html;
        code.value = html;

        wrap.querySelectorAll('.mc-preview-bar button').forEach(btn =>
            btn.addEventListener('click', () => {
                wrap.querySelectorAll('.mc-preview-bar button').forEach(b => b.classList.toggle('is-active', b === btn));
                const isCode = btn.dataset.w === 'code';
                frame.hidden = isCode;
                code.hidden = !isCode;
                if (!isCode) frame.style.width = btn.dataset.w + 'px';
            })
        );

        editor.Modal.open({ title: 'Email preview (exactly what recipients get)', content: wrap });
    }

    // ---- top bar -----------------------------------------------------------------
    const actions = {
        desktop: () => editor.setDevice('desktop'),
        mobile: () => editor.setDevice('mobile'),
        undo: () => editor.UndoManager.undo(),
        redo: () => editor.UndoManager.redo(),
        outlines: btn => {
            const on = editor.Commands.isActive('core:component-outline');
            if (on) editor.stopCommand('core:component-outline');
            else editor.runCommand('core:component-outline');
            btn.classList.toggle('is-active', !on);
        },
        preview,
        test: sendTest,
        save
    };

    root.querySelectorAll('.mc-actions button').forEach(btn =>
        btn.addEventListener('click', () => {
            const act = btn.dataset.act;
            if (act === 'desktop' || act === 'mobile') {
                root.querySelectorAll('[data-act="desktop"], [data-act="mobile"]').forEach(b => b.classList.toggle('is-active', b === btn));
            }
            actions[act] && actions[act](btn);
        })
    );

    // ---- load -----------------------------------------------------------------------
    editor.onReady(async () => {
        // The canvas root accepts drops; only sections/columns/content may be placed on it.
        editor.getWrapper().addAttributes({ 'data-mc-drop': '', 'data-mc-root': '' });
        editor.runCommand('core:component-outline');

        if (!state.id) {
            editor.setComponents(starterContent());
            editor.UndoManager.clear();
            ready = true;
            return;
        }

        try {
            const template = await api.get(`/templates/${state.id}`);
            nameEl.value = template.name || '';
            subjectEl.value = template.subject || '';

            if (template.structure && !Array.isArray(template.structure) && template.structure.pages) {
                editor.loadProjectData(template.structure);
            } else {
                editor.setComponents(starterContent());
                toast('This template was made with the old builder and can’t be opened here. Saving will replace it.', 'error');
            }
        } catch (err) {
            toast(err.message, 'error');
        }
        editor.UndoManager.clear();
        ready = true;
    });

    const instance = { editor, save: () => save(q(root, '[data-act="save"]')), getState: () => ({ ...state }) };
    root.mailCustomizer = instance; // handy for host pages and debugging
    return instance;
}
