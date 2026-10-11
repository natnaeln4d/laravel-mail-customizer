import { FONTS } from './fonts';

// Style panel. Background controls live in the "Appearance" panel (they need to
// combine image + overlay + gradient, which plain CSS inputs can't do).
export const SECTORS = [
    {
        name: 'Type',
        open: true,
        properties: [
            { extend: 'font-family', label: 'Font', options: FONTS.map(f => ({ id: f.id, value: f.id, label: f.label, name: f.label })) },
            'font-size',
            'font-weight',
            'color',
            'line-height',
            'letter-spacing',
            'text-align',
            'text-decoration',
            'text-transform'
        ]
    },
    {
        name: 'Size & spacing',
        open: true,
        properties: ['width', 'max-width', 'height', 'padding', 'margin']
    },
    {
        name: 'Border & shadow',
        open: false,
        properties: ['border-radius', 'border', 'box-shadow']
    }
];

/**
 * Canvas-only CSS (never exported). One design, many widths: the email is a 600px
 * artboard that becomes fluid on narrow screens and stacks its columns, exactly
 * like the real email does. Switching "device" only changes the canvas width.
 */
export const CANVAS_CSS = `
html { background: #e5e7eb; }
body {
  margin: 28px auto; max-width: 600px; background: #ffffff;
  font-family: Arial, Helvetica, sans-serif; box-shadow: 0 10px 40px rgba(0,0,0,.22);
}
img { max-width: 100%; height: auto; }
table { border-collapse: collapse; }
[data-mc-root] { min-height: 70vh; background: #ffffff; }
[data-mc-drop]:empty { min-height: 56px; outline: 1px dashed #94a3b8; outline-offset: -2px; }
[data-mc-root]:empty::before {
  content: 'Drag a block here, or click one in the Blocks panel';
  display: block; padding: 120px 24px; text-align: center; color: #64748b; font: 15px Arial, sans-serif;
}
@media (max-width: 640px) {
  body { margin: 0 auto; box-shadow: none; }
  .mc-stack { display: block !important; width: 100% !important; }
}
`;

/** Theme-aware CSS injected into the canvas frame (selection colours, pasteboard, motion). */
export function frameThemeCss(tokens, mode) {
    const select = tokens['--mc-select'] || '#f5c542';
    const pasteboard = tokens['--mc-bg-0'] || (mode === 'dark' ? '#0b1220' : '#e5e7eb');
    const outline = mode === 'dark' ? 'rgba(255,255,255,.28)' : 'rgba(0,0,0,.28)';

    return `
html { background: ${pasteboard} !important; }
.gjs-selected { outline: 2px solid ${select} !important; outline-offset: -2px; }
.gjs-hovered { outline: 1px solid ${select} !important; outline-offset: -1px; opacity: .98; }
.gjs-dashed [data-gjs-highlightable] { outline: 1px dashed ${outline}; outline-offset: -1px; }
@keyframes mc-pop {
  0% { opacity: 0; transform: translateY(8px) scale(.97); }
  55% { opacity: 1; transform: translateY(-2px) scale(1.012); }
  100% { opacity: 1; transform: none; }
}
.mc-just-added { animation: mc-pop .5s cubic-bezier(.34, 1.56, .64, 1) both; }
@media (prefers-reduced-motion: reduce) { .mc-just-added { animation: none; } }
`;
}
