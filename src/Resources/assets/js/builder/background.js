// Background controls shared by sections, columns, text, headings and buttons:
// colour, image, image overlay, two-colour gradient, size/position/repeat.
//
// The values live on the component (changeProp) and are compiled into the
// real CSS properties whenever one of them changes. Keeping them as separate
// fields is what lets non-technical users combine "image + dark overlay" or
// "gradient with a fallback colour" without ever writing CSS.

export const BG_DEFAULTS = {
    bgColor: '',
    bgImage: '',
    bgOverlay: '',
    bgOverlayOpacity: 40,
    gradOn: false,
    gradFrom: '#6366f1',
    gradTo: '#8b5cf6',
    gradAngle: '180deg',
    bgSize: 'cover',
    bgPosition: 'center',
    bgRepeat: 'no-repeat'
};

export const BG_EVENTS = Object.keys(BG_DEFAULTS).map(k => `change:${k}`).join(' ');

const option = (id, label) => ({ id, value: id, label, name: label });

function toRgba(color, percent) {
    const alpha = Math.max(0, Math.min(100, Number(percent) || 0)) / 100;
    const m = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec((color || '').trim());
    if (!m) return color; // already rgb()/rgba()/named: use as-is
    let hex = m[1];
    if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
    const n = parseInt(hex, 16);
    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${alpha})`;
}

/** Compile the fields into CSS declarations ('' means "remove this property"). */
export function backgroundStyle(get) {
    const image = (get('bgImage') || '').trim();
    const overlay = (get('bgOverlay') || '').trim();
    const layers = [];

    if (image && overlay) {
        const c = toRgba(overlay, get('bgOverlayOpacity'));
        layers.push(`linear-gradient(${c}, ${c})`);
    }
    if (get('gradOn')) {
        layers.push(`linear-gradient(${get('gradAngle') || '180deg'}, ${get('gradFrom') || '#6366f1'}, ${get('gradTo') || '#8b5cf6'})`);
    }
    if (image) {
        layers.push(`url("${image.replace(/"/g, '%22')}")`);
    }

    // A solid colour is always kept as the fallback for clients that ignore gradients/images.
    const color = (get('bgColor') || '').trim() || (get('gradOn') ? get('gradFrom') || '#6366f1' : '');

    return {
        'background-color': color,
        'background-image': layers.join(', '),
        'background-size': layers.length ? get('bgSize') || 'cover' : '',
        'background-position': layers.length ? get('bgPosition') || 'center' : '',
        'background-repeat': layers.length ? get('bgRepeat') || 'no-repeat' : ''
    };
}

/** Open the media library; resolve with the chosen image URL. */
export function pickImage(editor, onPick) {
    editor.runCommand('open-assets', {
        types: ['image'],
        accept: 'image/*',
        // One click applies the picture and closes the dialog.
        select: asset => {
            const src = asset && typeof asset.get === 'function' ? asset.get('src') : asset && asset.src;
            editor.Modal.close();
            if (src) onPick(src);
        }
    });
}

export function backgroundTraits(editor) {
    return [
        { type: 'color', name: 'bgColor', label: 'Background color', changeProp: true },
        { type: 'text', name: 'bgImage', label: 'Background image', placeholder: 'https://… (or choose below)', changeProp: true },
        {
            type: 'button',
            name: 'bgImagePicker',
            text: 'Choose / upload image…',
            full: true,
            command: () => {
                const target = editor.getSelected();
                if (target) pickImage(editor, src => target.set('bgImage', src));
            }
        },
        { type: 'color', name: 'bgOverlay', label: 'Image overlay color', changeProp: true },
        { type: 'number', name: 'bgOverlayOpacity', label: 'Overlay strength %', min: 0, max: 100, changeProp: true },
        { type: 'checkbox', name: 'gradOn', label: 'Gradient background', changeProp: true },
        { type: 'color', name: 'gradFrom', label: 'Gradient from', changeProp: true },
        { type: 'color', name: 'gradTo', label: 'Gradient to', changeProp: true },
        {
            type: 'select',
            name: 'gradAngle',
            label: 'Gradient direction',
            changeProp: true,
            options: [
                option('180deg', 'Top → Bottom'),
                option('0deg', 'Bottom → Top'),
                option('90deg', 'Left → Right'),
                option('270deg', 'Right → Left'),
                option('135deg', 'Diagonal ↘'),
                option('45deg', 'Diagonal ↗')
            ]
        },
        {
            type: 'select',
            name: 'bgSize',
            label: 'Image size',
            changeProp: true,
            options: [option('cover', 'Fill (cover)'), option('contain', 'Fit (contain)'), option('auto', 'Original size')]
        },
        {
            type: 'select',
            name: 'bgPosition',
            label: 'Image position',
            changeProp: true,
            options: [
                option('center', 'Center'),
                option('top', 'Top'),
                option('bottom', 'Bottom'),
                option('left', 'Left'),
                option('right', 'Right')
            ]
        },
        {
            type: 'select',
            name: 'bgRepeat',
            label: 'Image repeat',
            changeProp: true,
            options: [option('no-repeat', 'No repeat'), option('repeat', 'Repeat'), option('repeat-x', 'Repeat horizontally'), option('repeat-y', 'Repeat vertically')]
        }
    ];
}

/** Model members to spread into a component type that supports backgrounds. */
export function backgroundModel() {
    return {
        init() {
            this.on(BG_EVENTS, this.applyBackground);
        },
        applyBackground() {
            const next = backgroundStyle(key => this.get(key));
            const style = { ...this.getStyle() };

            Object.entries(next).forEach(([prop, value]) => {
                if (value) style[prop] = value;
                else delete style[prop];
            });

            // The shorthand would override the longhands we just set.
            delete style.background;
            this.setStyle(style);
        }
    };
}
