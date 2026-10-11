import { icon } from './icons';

const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform || '');
export const MOD = isMac ? '⌘' : 'Ctrl';

/**
 * Menu bar dropdowns. `defs` maps a menu name to a function returning its items,
 * evaluated every time the menu opens so checkmarks/disabled states are current:
 *   { label, icon?, shortcut?, run, disabled?, checked? } | { sep: true }
 */
export function createMenus({ root, layer, defs }) {
    const buttons = [...root.querySelectorAll('.mc-menus button[data-menu]')];
    let openName = null;
    let menuEl = null;

    function close() {
        if (menuEl) {
            const el = menuEl;
            el.classList.add('is-leaving');
            setTimeout(() => el.remove(), 120);
        }
        menuEl = null;
        openName = null;
        buttons.forEach(b => {
            b.classList.remove('is-open');
            b.setAttribute('aria-expanded', 'false');
        });
    }

    function open(name) {
        const button = buttons.find(b => b.dataset.menu === name);
        if (!button || !defs[name]) return;

        if (menuEl) {
            menuEl.remove();
            menuEl = null;
        }
        openName = name;
        buttons.forEach(b => {
            const on = b === button;
            b.classList.toggle('is-open', on);
            b.setAttribute('aria-expanded', String(on));
        });

        const menu = document.createElement('div');
        menu.className = 'mc-menu';
        menu.setAttribute('role', 'menu');

        defs[name]().forEach(item => {
            if (item.sep) {
                const sep = document.createElement('div');
                sep.className = 'mc-menu-sep';
                menu.appendChild(sep);
                return;
            }

            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'mc-mi';
            row.setAttribute('role', 'menuitem');
            row.disabled = !!item.disabled;
            row.innerHTML =
                `<span class="mc-mi-ico">${item.checked ? icon('check', 14) : item.icon ? icon(item.icon, 14) : ''}</span>` +
                `<span class="mc-mi-label">${item.label}</span>` +
                `<kbd>${item.shortcut || ''}</kbd>`;
            row.addEventListener('click', () => {
                close();
                item.run && item.run();
            });
            menu.appendChild(row);
        });

        const rootBox = root.getBoundingClientRect();
        const box = button.getBoundingClientRect();
        menu.style.left = `${Math.round(box.left - rootBox.left)}px`;
        menu.style.top = `${Math.round(box.bottom - rootBox.top + 2)}px`;
        layer.appendChild(menu);
        menuEl = menu;
    }

    buttons.forEach(b => {
        b.setAttribute('aria-haspopup', 'menu');
        b.setAttribute('aria-expanded', 'false');
        b.addEventListener('click', e => {
            e.stopPropagation();
            openName === b.dataset.menu ? close() : open(b.dataset.menu);
        });
        // Like desktop apps: once a menu is open, hovering another one switches to it.
        b.addEventListener('mouseenter', () => {
            if (openName && openName !== b.dataset.menu) open(b.dataset.menu);
        });
    });

    document.addEventListener('click', e => {
        if (menuEl && !menuEl.contains(e.target)) close();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && menuEl) close();
    });

    return { close, open };
}
