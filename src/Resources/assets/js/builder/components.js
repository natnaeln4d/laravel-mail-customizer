import { BG_DEFAULTS, backgroundModel, backgroundTraits } from './background';

// Where content may be dropped: inside a section / column cell, or straight onto the canvas.
// The canvas root gets data-mc-drop + data-mc-root when the editor is ready (see index.js).
export const DROP_TARGETS = '[data-mc-drop]';
// Sections only live at the top level.
export const ROOT_ONLY = '[data-mc-root]';

// Scaffolding (tbody/tr) that users should never see or select.
export const INERT = {
    selectable: false,
    hoverable: false,
    highlightable: false,
    droppable: false,
    draggable: false,
    removable: false,
    copyable: false,
    layerable: false
};

/**
 * Component types. Every type carries a `data-mc` marker that survives export,
 * so the server knows what it is looking at (button -> bulletproof table,
 * spacer -> sized cell, …) when it hardens the HTML for Outlook/Gmail.
 */
export function registerComponents(editor) {
    const dc = editor.DomComponents;
    const bgTraits = backgroundTraits(editor);
    const withBackground = traits => ['id', ...traits, ...bgTraits];

    // Full-width band: the thing you give a background, padding and drop content into.
    dc.addType('mc-section', {
        model: {
            defaults: {
                ...BG_DEFAULTS,
                name: 'Section',
                tagName: 'div',
                droppable: true,
                draggable: ROOT_ONLY,
                attributes: { 'data-mc': 'section', 'data-mc-drop': '' },
                traits: withBackground([])
            },
            ...backgroundModel()
        }
    });

    // Columns: a real table so they sit side by side in every client.
    dc.addType('mc-columns', {
        model: {
            defaults: {
                name: 'Columns',
                tagName: 'table',
                droppable: false,
                draggable: DROP_TARGETS,
                attributes: {
                    role: 'presentation',
                    width: '100%',
                    cellpadding: '0',
                    cellspacing: '0',
                    border: '0',
                    'data-mc': 'columns'
                },
                traits: ['id']
            }
        }
    });

    dc.addType('mc-cell', {
        model: {
            defaults: {
                ...BG_DEFAULTS,
                name: 'Column',
                tagName: 'td',
                droppable: true,
                draggable: false,
                removable: false,
                copyable: false,
                classes: ['mc-stack'],
                attributes: { valign: 'top', 'data-mc': 'cell', 'data-mc-drop': '' },
                traits: withBackground([])
            },
            ...backgroundModel()
        }
    });

    const textLike = (name, tagName, mc) => ({
        extend: 'text',
        model: {
            defaults: {
                ...BG_DEFAULTS,
                name,
                tagName,
                draggable: DROP_TARGETS,
                attributes: { 'data-mc': mc },
                traits: withBackground([])
            },
            ...backgroundModel()
        }
    });

    dc.addType('mc-heading', textLike('Heading', 'h2', 'heading'));
    dc.addType('mc-text', textLike('Text', 'p', 'text'));

    // Button = a centring wrapper (div) + the clickable link.
    dc.addType('mc-button-wrap', {
        model: {
            defaults: {
                name: 'Button wrapper',
                tagName: 'div',
                droppable: false,
                draggable: DROP_TARGETS,
                attributes: { 'data-mc': 'button-wrap' },
                traits: ['id']
            }
        }
    });

    dc.addType('mc-button', {
        extend: 'link',
        model: {
            defaults: {
                ...BG_DEFAULTS,
                name: 'Button',
                tagName: 'a',
                draggable: false,
                copyable: false,
                attributes: { 'data-mc': 'button', href: '#' },
                traits: [
                    { type: 'text', name: 'href', label: 'Link URL' },
                    'title',
                    {
                        type: 'select',
                        name: 'target',
                        label: 'Open in',
                        options: [
                            { id: '', value: '', label: 'Same tab', name: 'Same tab' },
                            { id: '_blank', value: '_blank', label: 'New tab', name: 'New tab' }
                        ]
                    },
                    ...bgTraits
                ]
            },
            ...backgroundModel()
        }
    });

    // Image = a centring wrapper + the <img> (double-click to choose/upload).
    dc.addType('mc-image-wrap', {
        model: {
            defaults: {
                name: 'Image wrapper',
                tagName: 'div',
                droppable: false,
                draggable: DROP_TARGETS,
                attributes: { 'data-mc': 'image-wrap' },
                traits: ['id']
            }
        }
    });

    dc.addType('mc-image', {
        extend: 'image',
        model: {
            defaults: {
                name: 'Image',
                draggable: false,
                copyable: false,
                attributes: { 'data-mc': 'image', alt: '' },
                traits: ['alt', 'title']
            }
        }
    });

    dc.addType('mc-divider', {
        model: {
            defaults: {
                name: 'Divider',
                tagName: 'div',
                droppable: false,
                draggable: DROP_TARGETS,
                attributes: { 'data-mc': 'divider' },
                traits: ['id']
            }
        }
    });

    dc.addType('mc-spacer', {
        extend: 'text',
        model: {
            defaults: {
                name: 'Spacer',
                tagName: 'div',
                editable: false,
                draggable: DROP_TARGETS,
                attributes: { 'data-mc': 'spacer' },
                traits: ['id']
            }
        }
    });
}
