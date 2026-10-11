// Theme engine: light/dark, colour presets, host-supplied colours, or "inherit" the host page.
//
// Everything visual is driven by CSS variables (--mc-*) set on the builder's root
// element, so a host site can also override any of them from its own stylesheet.

// ---- colour helpers ----------------------------------------------------------

export function hexToRgb(hex) {
    const m = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec((hex || '').trim());
    if (!m) return null;
    let h = m[1];
    if (h.length === 3) h = h.split('').map(c => c + c).join('');
    const n = parseInt(h, 16);
    return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
}

export function rgbToHex(r, g, b) {
    return '#' + [r, g, b].map(v => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('');
}

/** Any CSS colour string (hex, rgb(), rgba(), named) -> {r,g,b} using the browser's parser. */
export function parseColor(value) {
    const hex = hexToRgb(value);
    if (hex) return hex;
    if (typeof document === 'undefined' || !value) return null;
    const probe = document.createElement('span');
    probe.style.color = '';
    probe.style.color = value;
    if (!probe.style.color) return null;
    document.body.appendChild(probe);
    const m = /rgba?\((\d+),\s*(\d+),\s*(\d+)/.exec(getComputedStyle(probe).color);
    probe.remove();
    return m ? { r: +m[1], g: +m[2], b: +m[3] } : null;
}

export function luminance({ r, g, b }) {
    const f = v => {
        v /= 255;
        return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    };
    return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
}

/** Readable text colour (white or near-black) for text placed on `color`: whichever contrasts better. */
export function onColor(color) {
    const rgb = parseColor(color);
    if (!rgb) return '#ffffff';
    const l = luminance(rgb);
    const withWhite = 1.05 / (l + 0.05);
    const withDark = (l + 0.05) / (luminance({ r: 22, g: 26, b: 35 }) + 0.05);
    // A small bias toward white keeps classic reds/blues looking as people expect.
    return withWhite + 0.4 >= withDark ? '#ffffff' : '#161a23';
}

// ---- presets -----------------------------------------------------------------
// Presets borrow *colour moods* only: Luffy = straw-hat gold + vest red over deep-sea navy;
// Goku = gi orange + sky blue; Adobe = neutral greys with a classic blue.

const NAVY_DARK = {
    '--mc-bg-0': '#080f1d',
    '--mc-bg-1': '#101a2e',
    '--mc-bg-2': '#17243c',
    '--mc-bg-3': '#1f2f4d',
    '--mc-border': '#263a5e',
    '--mc-text': '#e9eef8',
    '--mc-muted': '#8e9fbd',
    '--mc-shadow': '0 12px 32px rgba(2,6,16,.55)'
};

const SAND_LIGHT = {
    '--mc-bg-0': '#e6dfcd',
    '--mc-bg-1': '#f7f2e6',
    '--mc-bg-2': '#fffdf7',
    '--mc-bg-3': '#efe7d3',
    '--mc-border': '#ddd2b8',
    '--mc-text': '#1c2538',
    '--mc-muted': '#69707f',
    '--mc-shadow': '0 12px 28px rgba(60,48,20,.18)'
};

const ADOBE_DARK = {
    '--mc-bg-0': '#1b1b1b',
    '--mc-bg-1': '#2b2b2b',
    '--mc-bg-2': '#363636',
    '--mc-bg-3': '#434343',
    '--mc-border': '#4a4a4a',
    '--mc-text': '#e6e6e6',
    '--mc-muted': '#9a9a9a',
    '--mc-shadow': '0 12px 32px rgba(0,0,0,.55)'
};

const ADOBE_LIGHT = {
    '--mc-bg-0': '#d9d9d9',
    '--mc-bg-1': '#f0f0f0',
    '--mc-bg-2': '#ffffff',
    '--mc-bg-3': '#e4e4e4',
    '--mc-border': '#cfcfcf',
    '--mc-text': '#222222',
    '--mc-muted': '#6e6e6e',
    '--mc-shadow': '0 12px 28px rgba(0,0,0,.18)'
};

export const PRESETS = {
    luffy: {
        label: 'Luffy',
        hint: 'Vest red · straw gold · deep sea',
        accent: '#e0453a',
        secondary: '#f5c542',
        dark: NAVY_DARK,
        light: SAND_LIGHT
    },
    goku: {
        label: 'Goku',
        hint: 'Gi orange · sky blue',
        accent: '#f2711c',
        secondary: '#3b82f6',
        dark: NAVY_DARK,
        light: SAND_LIGHT
    },
    adobe: {
        label: 'Studio',
        hint: 'Neutral greys · classic blue',
        accent: '#2d8cff',
        secondary: '#9b72ff',
        dark: ADOBE_DARK,
        light: ADOBE_LIGHT
    }
};

export const DEFAULT_THEME = {
    mode: 'auto', // light | dark | auto | inherit
    preset: 'luffy',
    accent: null,
    secondary: null,
    tokens: {}, // raw --mc-* overrides
    font: null, // 'inherit' or any font stack
    brand: 'Mail Studio',
    picker: true // let end users change the theme in-app
};

const STORAGE_KEY = 'mail-customizer.theme';

export function loadStoredTheme() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null') || {};
    } catch (e) {
        return {};
    }
}

export function storeTheme(theme) {
    try {
        const { mode, preset, accent, secondary } = theme;
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ mode, preset, accent, secondary }));
    } catch (e) {
        /* storage unavailable */
    }
}

export function clearStoredTheme() {
    try {
        localStorage.removeItem(STORAGE_KEY);
    } catch (e) {
        /* ignore */
    }
}

export function systemMode() {
    return typeof matchMedia === 'function' && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function mix(a, b, pct) {
    return `color-mix(in srgb, ${a} ${pct}%, ${b})`;
}

/** First opaque background colour found walking up from `el`. */
function effectiveBackground(el) {
    for (let e = el; e; e = e.parentElement) {
        const c = getComputedStyle(e).backgroundColor;
        if (parseColor(c) && c !== 'transparent' && !/^rgba\(.*,\s*0\)$/.test(c)) return c;
    }
    return '#ffffff';
}

/** Derive a full surface palette from the host page's own colours. */
function inheritedSurfaces(root) {
    const host = root.parentElement || document.body;
    const cs = getComputedStyle(host);
    const bg = effectiveBackground(host);

    const text = cs.color || '#111111';
    const dark = luminance(parseColor(bg)) < 0.4;

    return {
        mode: dark ? 'dark' : 'light',
        tokens: {
            '--mc-bg-1': bg,
            '--mc-bg-0': mix(bg, text, 90),
            '--mc-bg-2': mix(bg, dark ? '#fff' : '#000', dark ? 90 : 97),
            '--mc-bg-3': mix(bg, text, 86),
            '--mc-border': mix(bg, text, 82),
            '--mc-text': text,
            '--mc-muted': mix(text, bg, 62),
            '--mc-shadow': '0 12px 28px rgba(0,0,0,.2)'
        },
        accent: ['--primary', '--accent', '--bs-primary', '--color-primary', '--brand']
            .map(v => cs.getPropertyValue(v).trim())
            .find(Boolean)
    };
}

/**
 * Resolve a theme config to the concrete mode + CSS variables.
 */
export function resolveTheme(theme, root) {
    const t = { ...DEFAULT_THEME, ...theme };
    let mode = t.mode;
    let surfaces;
    let inheritedAccent;

    if (mode === 'inherit' && root) {
        const inherited = inheritedSurfaces(root);
        mode = inherited.mode;
        surfaces = inherited.tokens;
        inheritedAccent = inherited.accent;
    } else {
        if (mode === 'auto' || !['light', 'dark'].includes(mode)) mode = systemMode();
        const preset = PRESETS[t.preset] || PRESETS.luffy;
        surfaces = preset[mode];
    }

    const preset = PRESETS[t.preset] || PRESETS.luffy;
    const accent = t.accent || inheritedAccent || preset.accent;
    const secondary = t.secondary || preset.secondary;

    return {
        mode,
        tokens: {
            ...surfaces,
            '--mc-accent': accent,
            '--mc-secondary': secondary,
            '--mc-on-accent': onColor(accent),
            '--mc-on-secondary': onColor(secondary),
            // Selection colour must stand out on the pasteboard: gold on dark, accent on light.
            '--mc-select': mode === 'dark' ? secondary : accent,
            '--mc-on-select': onColor(mode === 'dark' ? secondary : accent),
            ...t.tokens
        }
    };
}

export function applyTheme(root, theme) {
    const { mode, tokens } = resolveTheme(theme, root);

    root.classList.add('mc-theming');
    clearTimeout(root.__mcThemeTimer);
    root.__mcThemeTimer = setTimeout(() => root.classList.remove('mc-theming'), 380);

    root.dataset.mcTheme = mode;
    Object.entries(tokens).forEach(([k, v]) => root.style.setProperty(k, v));
    if (theme.font) root.style.setProperty('--mc-font', theme.font === 'inherit' ? 'inherit' : theme.font);

    return { mode, tokens };
}
