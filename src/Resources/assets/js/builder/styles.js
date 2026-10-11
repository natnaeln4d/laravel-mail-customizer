import { FONTS } from './fonts';

// Style panel. Background controls live in the "Settings" tab (they need to
// combine image + overlay + gradient, which the plain CSS inputs can't do).
export const SECTORS = [
    {
        name: 'Text',
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

// Canvas-only CSS (never exported). Shows a 600px email on a grey page and
// gives empty drop zones a visible target.
export const CANVAS_CSS = `
html { background: #e5e7eb; }
body {
  margin: 0 auto; max-width: 600px; min-height: 100vh; background: #ffffff;
  font-family: Arial, Helvetica, sans-serif; box-shadow: 0 0 0 1px rgba(15,23,42,.08);
}
img { max-width: 100%; }
table { border-collapse: collapse; }
[data-mc-root] { min-height: 100vh; background: #ffffff; }
[data-mc-drop]:empty { min-height: 56px; outline: 1px dashed #94a3b8; outline-offset: -2px; }
[data-mc-root]:empty::before {
  content: 'Drag a block here, or click a block on the left to add it';
  display: block; padding: 120px 24px; text-align: center; color: #64748b; font: 15px Arial, sans-serif;
}
`;
