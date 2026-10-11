import { INERT } from './components';
import { DEFAULT_FONT } from './fonts';
import { icon } from './icons';

const FONT = DEFAULT_FONT;

const textStyle = (extra = {}) => ({
    margin: '0',
    padding: '10px 20px',
    'font-family': FONT,
    'font-size': '16px',
    'line-height': '1.5',
    color: '#374151',
    ...extra
});

const headingStyle = (extra = {}) => ({
    margin: '0',
    padding: '10px 20px',
    'font-family': FONT,
    'font-size': '28px',
    'font-weight': '700',
    'line-height': '1.25',
    color: '#111827',
    ...extra
});

const buttonStyle = (extra = {}) => ({
    display: 'inline-block',
    'background-color': '#6366f1',
    color: '#ffffff',
    padding: '12px 28px',
    'border-radius': '6px',
    'font-family': FONT,
    'font-size': '16px',
    'font-weight': '700',
    'text-decoration': 'none',
    ...extra
});

// ---- building blocks (functions, so every drop gets a fresh object) --------

export const heading = (content = 'Your headline', style = {}) => ({
    type: 'mc-heading',
    content,
    style: headingStyle(style)
});

export const text = (content = 'Write something your readers will enjoy. Double-click to edit this text.', style = {}) => ({
    type: 'mc-text',
    content,
    style: textStyle(style)
});

export const button = (label = 'Click me', buttonExtra = {}, bgColor = '#6366f1') => ({
    type: 'mc-button-wrap',
    style: { 'text-align': 'center', padding: '10px 20px' },
    components: [
        {
            type: 'mc-button',
            content: label,
            bgColor,
            attributes: { href: '#' },
            style: buttonStyle({ 'background-color': bgColor, ...buttonExtra })
        }
    ]
});

export const image = () => ({
    type: 'mc-image-wrap',
    style: { 'text-align': 'center', padding: '10px 20px' },
    components: [
        {
            type: 'mc-image',
            style: { display: 'inline-block', width: '100%', 'max-width': '560px', height: 'auto' }
        }
    ]
});

export const divider = () => ({
    type: 'mc-divider',
    style: { padding: '10px 20px' },
    components: [
        {
            type: 'text',
            editable: false,
            draggable: false,
            copyable: false,
            removable: false,
            name: 'Line',
            content: '&nbsp;',
            attributes: { 'data-mc': 'line' },
            style: { 'border-top': '1px solid #e5e7eb', 'font-size': '1px', 'line-height': '1px' }
        }
    ]
});

export const spacer = () => ({
    type: 'mc-spacer',
    content: '&nbsp;',
    style: { height: '30px', 'line-height': '30px', 'font-size': '1px' }
});

export const section = (style = {}, components = [], props = {}) => ({
    type: 'mc-section',
    ...props,
    style: { padding: '24px 20px', 'background-color': '#ffffff', ...style },
    components
});

export const columns = count => {
    const width = Math.floor(100 / count) + '%';
    const cell = () => ({
        type: 'mc-cell',
        attributes: { width, valign: 'top' },
        style: { width, padding: '10px' },
        components: []
    });

    return {
        type: 'mc-columns',
        style: { width: '100%', 'border-collapse': 'collapse' },
        components: [
            {
                tagName: 'tbody',
                ...INERT,
                components: [{ tagName: 'tr', ...INERT, components: Array.from({ length: count }, cell) }]
            }
        ]
    };
};

// ---- ready-made sections ---------------------------------------------------

export const headerSection = () =>
    section({ padding: '24px 20px', 'text-align': 'center' }, [
        heading('Your Brand', { 'font-size': '24px', 'text-align': 'center' })
    ], { bgColor: '#ffffff' });

export const heroSection = () =>
    section(
        {
            padding: '56px 32px',
            'text-align': 'center',
            'background-color': '#6366f1',
            'background-image': 'linear-gradient(135deg, #6366f1, #8b5cf6)',
            'background-size': 'cover',
            'background-position': 'center',
            'background-repeat': 'no-repeat'
        },
        [
            heading('Make your announcement shine', { color: '#ffffff', 'font-size': '36px', 'text-align': 'center' }),
            text('Add a short, friendly line that explains why this email matters.', {
                color: '#e0e7ff',
                'text-align': 'center'
            }),
            button('Get started', { color: '#4f46e5' }, '#ffffff')
        ],
        { gradOn: true, gradFrom: '#6366f1', gradTo: '#8b5cf6', gradAngle: '135deg', bgColor: '' }
    );

export const footerSection = () =>
    section({ padding: '24px 20px', 'background-color': '#f3f4f6', 'text-align': 'center' }, [
        text('© {{company}} · You are receiving this email because you signed up.', {
            'font-size': '12px',
            color: '#6b7280',
            'text-align': 'center'
        })
    ], { bgColor: '#f3f4f6' });

/** What a brand-new template starts with, so the canvas is never blank. */
export const starterContent = () => [
    headerSection(),
    section({ padding: '16px 0' }, [
        heading('Hello {{name}} 👋', { 'font-size': '26px' }),
        text(),
        button('Open dashboard')
    ]),
    footerSection()
];

// ---- palette ---------------------------------------------------------------

const label = (iconName, name) =>
    `<div class="mc-blk"><div class="mc-blk-ico">${icon(iconName, 20)}</div><div class="mc-blk-lbl">${name}</div></div>`;

/** Blocks the quick-add toolbox exposes (id -> icon, label). */
export const TOOLBOX = [
    ['section', 'section', 'Section'],
    ['columns-2', 'columns2', '2 columns'],
    ['heading', 'heading', 'Heading'],
    ['text', 'text', 'Text'],
    ['image', 'image', 'Image'],
    ['button', 'button', 'Button'],
    ['divider', 'divider', 'Divider'],
    ['spacer', 'spacer', 'Spacer']
];

export function registerBlocks(editor, variables = {}) {
    const add = (id, name, iconName, category, content) =>
        editor.Blocks.add(id, { label: label(iconName, name), category, content, attributes: { class: 'mc-block', title: name } });

    add('section', 'Section', 'section', 'Layout', section());
    add('columns-2', '2 columns', 'columns2', 'Layout', columns(2));
    add('columns-3', '3 columns', 'columns3', 'Layout', columns(3));

    add('heading', 'Heading', 'heading', 'Content', heading());
    add('text', 'Text', 'text', 'Content', text());
    add('image', 'Image', 'image', 'Content', image());
    add('button', 'Button', 'button', 'Content', button());
    add('divider', 'Divider', 'divider', 'Content', divider());
    add('spacer', 'Spacer', 'spacer', 'Content', spacer());

    add('header', 'Header', 'header', 'Ready-made', headerSection());
    add('hero', 'Hero', 'hero', 'Ready-made', heroSection());
    add('footer', 'Footer', 'footer', 'Ready-made', footerSection());

    Object.keys(variables || {}).forEach(key => {
        add(`var-${key}`, `{{${key}}}`, 'variable', 'Variables', text(`{{${key}}}`));
    });
}
