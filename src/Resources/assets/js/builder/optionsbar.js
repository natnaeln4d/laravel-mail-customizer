import { FONTS } from './fonts';
import { icon } from './icons';
import { parseColor, rgbToHex } from './theme';

// Contextual options bar (like Photoshop's): the controls that matter for whatever is selected.

const TEXT_TYPES = ['mc-heading', 'mc-text', 'mc-button'];
const BOX_TYPES = ['mc-section', 'mc-cell', 'mc-heading', 'mc-text', 'mc-button'];
const RADIUS_TYPES = ['mc-section', 'mc-button', 'mc-cell'];

const TYPE_ICON = {
    'mc-section': 'section',
    'mc-columns': 'columns2',
    'mc-cell': 'columns2',
    'mc-heading': 'heading',
    'mc-text': 'text',
    'mc-button': 'button',
    'mc-button-wrap': 'button',
    'mc-image': 'image',
    'mc-image-wrap': 'image',
    'mc-divider': 'divider',
    'mc-spacer': 'spacer'
};

const toHex = value => {
    const rgb = parseColor(value);
    return rgb ? rgbToHex(rgb.r, rgb.g, rgb.b) : '#000000';
};

const firstFamily = stack => (stack || '').split(',')[0].replace(/["']/g, '').trim().toLowerCase();

const group = (label, inner) => `<div class="mc-ctl"><span class="mc-ctl-label">${label}</span>${inner}</div>`;

export function createOptionsBar(editor, container) {
    let current = null;
    let frame = 0;
    let internal = false; // true while the bar itself is writing, so typing isn't interrupted by a re-render

    const read = () => {
        const el = current && current.getEl && current.getEl();
        return el ? getComputedStyle(el) : null;
    };

    const quietly = fn => {
        internal = true;
        fn();
        setTimeout(() => (internal = false), 250);
    };

    const style = (prop, value) => {
        if (!current) return;
        quietly(() => current.addStyle({ [prop]: value }));
    };

    function render() {
        const component = editor.getSelected();
        current = component && component.get('type') !== 'wrapper' ? component : null;

        if (!current) {
            container.innerHTML = `<span class="mc-ctx-hint">Select something on the canvas — its controls appear here.</span>`;
            return;
        }

        const type = current.get('type');
        const cs = read();
        const html = [];

        html.push(
            `<span class="mc-chip">${icon(TYPE_ICON[type] || 'section', 14)}<b>${current.getName() || 'Element'}</b></span>`
        );

        if (TEXT_TYPES.includes(type) && cs) {
            const family = firstFamily(cs.fontFamily);
            const options = FONTS.map(
                f => `<option value="${f.id.replace(/"/g, '&quot;')}"${firstFamily(f.id) === family ? ' selected' : ''}>${f.label}</option>`
            ).join('');

            html.push(group('Font', `<select data-ctl="font" aria-label="Font">${options}</select>`));
            html.push(
                group(
                    'Size',
                    `<span class="mc-num"><input type="number" min="8" max="96" step="1" data-ctl="size" value="${parseInt(cs.fontSize, 10) || 16}" aria-label="Font size"><i>px</i></span>`
                )
            );
            html.push(group('Color', `<input type="color" data-ctl="color" value="${toHex(cs.color)}" aria-label="Text color">`));
            html.push(
                `<button type="button" class="mc-seg${parseInt(cs.fontWeight, 10) >= 600 ? ' is-active' : ''}" data-ctl="bold" title="Bold" aria-label="Bold">${icon('bold', 15)}</button>`
            );
            html.push(
                `<span class="mc-segs">${['left', 'center', 'right']
                    .map(
                        a =>
                            `<button type="button" class="mc-seg${cs.textAlign === a || (a === 'left' && cs.textAlign === 'start') ? ' is-active' : ''}" data-ctl="align" data-v="${a}" title="Align ${a}" aria-label="Align ${a}">${icon(
                                'align' + a[0].toUpperCase() + a.slice(1),
                                15
                            )}</button>`
                    )
                    .join('')}</span>`
            );
        }

        if (BOX_TYPES.includes(type)) {
            const bg = current.get('bgColor') || '';
            html.push('<span class="mc-vr"></span>');
            html.push(
                group(
                    'Fill',
                    `<span class="mc-fill"><input type="color" data-ctl="bg" value="${toHex(bg || (cs && cs.backgroundColor) || '#ffffff')}" aria-label="Background color"><button type="button" data-ctl="bg-clear" class="mc-clear${bg ? '' : ' is-hidden'}" title="Remove fill" aria-label="Remove fill">${icon('close', 12)}</button></span>`
                )
            );
        }

        if (RADIUS_TYPES.includes(type) && cs) {
            html.push(
                group(
                    'Radius',
                    `<span class="mc-num"><input type="number" min="0" max="80" step="1" data-ctl="radius" value="${parseInt(cs.borderTopLeftRadius, 10) || 0}" aria-label="Corner radius"><i>px</i></span>`
                )
            );
        }

        if (type === 'mc-spacer' && cs) {
            html.push(
                group(
                    'Height',
                    `<span class="mc-num"><input type="number" min="1" max="400" step="1" data-ctl="height" value="${parseInt(cs.height, 10) || 30}" aria-label="Spacer height"><i>px</i></span>`
                )
            );
        }

        container.innerHTML = html.join('');
    }

    function schedule() {
        if (internal) return;
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(render);
    }

    // Delegated handlers: the bar is re-rendered often.
    container.addEventListener('input', e => {
        const t = e.target;
        const ctl = t.dataset && t.dataset.ctl;
        if (!ctl || !current) return;

        if (ctl === 'font') style('font-family', t.value);
        else if (ctl === 'size') t.value && style('font-size', `${Math.max(8, Math.min(96, +t.value))}px`);
        else if (ctl === 'color') style('color', t.value);
        else if (ctl === 'bg') quietly(() => current.set('bgColor', t.value));
        else if (ctl === 'radius') style('border-radius', `${Math.max(0, +t.value || 0)}px`);
        else if (ctl === 'height') style('height', `${Math.max(1, +t.value || 1)}px`);
    });

    // After a committed change (not each keystroke) refresh so the clear button etc. update.
    container.addEventListener('change', e => {
        if (e.target.dataset && e.target.dataset.ctl === 'bg') setTimeout(render, 300);
    });

    container.addEventListener('click', e => {
        const btn = e.target.closest('button[data-ctl]');
        if (!btn || !current) return;

        if (btn.dataset.ctl === 'bold') {
            const bold = parseInt((read() || {}).fontWeight, 10) >= 600;
            style('font-weight', bold ? '400' : '700');
            setTimeout(render, 30);
        } else if (btn.dataset.ctl === 'align') {
            style('text-align', btn.dataset.v);
            setTimeout(render, 30);
        } else if (btn.dataset.ctl === 'bg-clear') {
            quietly(() => current.set('bgColor', ''));
            setTimeout(render, 30);
        }
    });

    editor.on('component:toggled component:styleUpdate component:update:style change:selectedComponent', schedule);
    render();

    return { refresh: schedule };
}
