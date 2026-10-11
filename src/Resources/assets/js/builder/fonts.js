// Font choices offered in the Style panel.
// Web-safe stacks work everywhere. Google fonts load in Apple Mail, iOS Mail
// and some others; the rest of the stack is the fallback everywhere else.

export const FONTS = [
    { id: 'Arial, Helvetica, sans-serif', label: 'Arial' },
    { id: "Verdana, Geneva, sans-serif", label: 'Verdana' },
    { id: "Tahoma, Geneva, sans-serif", label: 'Tahoma' },
    { id: "'Trebuchet MS', Helvetica, sans-serif", label: 'Trebuchet MS' },
    { id: "Georgia, 'Times New Roman', serif", label: 'Georgia' },
    { id: "'Times New Roman', Times, serif", label: 'Times New Roman' },
    { id: "'Courier New', Courier, monospace", label: 'Courier New' },
    { id: "'Roboto', Arial, sans-serif", label: 'Roboto (web font)', google: 'Roboto' },
    { id: "'Open Sans', Arial, sans-serif", label: 'Open Sans (web font)', google: 'Open Sans' },
    { id: "'Lato', Arial, sans-serif", label: 'Lato (web font)', google: 'Lato' },
    { id: "'Montserrat', Arial, sans-serif", label: 'Montserrat (web font)', google: 'Montserrat' },
    { id: "'Poppins', Arial, sans-serif", label: 'Poppins (web font)', google: 'Poppins' },
    { id: "'Inter', Arial, sans-serif", label: 'Inter (web font)', google: 'Inter' },
    { id: "'Playfair Display', Georgia, serif", label: 'Playfair Display (web font)', google: 'Playfair Display' },
    { id: "'Merriweather', Georgia, serif", label: 'Merriweather (web font)', google: 'Merriweather' }
];

export const DEFAULT_FONT = FONTS[0].id;

const GOOGLE = FONTS.filter(f => f.google).map(f => f.google);

/** Google fonts actually referenced by the template's CSS. */
export function usedGoogleFonts(css) {
    return GOOGLE.filter(name => css.includes(name));
}

/** One stylesheet with every offered Google font, for the editor canvas only. */
export function canvasFontsUrl() {
    const families = GOOGLE.map(n => 'family=' + n.replace(/ /g, '+') + ':wght@400;700').join('&');
    return `https://fonts.googleapis.com/css2?${families}&display=swap`;
}
