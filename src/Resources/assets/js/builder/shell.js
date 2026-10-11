import { LOGO, icon } from './icons';

const esc = value =>
    String(value).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));

const panel = (id, iconName, title, body) => `
  <section class="mc-panel" data-panel="${id}">
    <button type="button" class="mc-panel-head" aria-expanded="true">
      <span class="mc-caret">${icon('chevronRight', 12)}</span>
      <span class="mc-panel-ico">${icon(iconName, 14)}</span>
      <span class="mc-panel-title">${title}</span>
    </button>
    <div class="mc-panel-body"><div class="mc-panel-inner">${body}</div></div>
  </section>`;

/**
 * The whole app chrome: menu bar, options bar, toolbox, blocks, stage, dock, status bar.
 * GrapesJS mounts into the .mc-blocks / .mc-style / .mc-traits / .mc-layers / .mc-gjs placeholders.
 */
export function shellHtml({ brand, picker }) {
    return `
<div class="mc-progress" aria-hidden="true"><i></i></div>

<header class="mc-menubar">
  <div class="mc-brand">${LOGO}<span class="mc-brand-name">${esc(brand)}</span></div>
  <nav class="mc-menus" aria-label="Menu">
    <button type="button" data-menu="file">File</button>
    <button type="button" data-menu="edit">Edit</button>
    <button type="button" data-menu="view">View</button>
    <button type="button" data-menu="help">Help</button>
  </nav>
  <div class="mc-doc" title="Current template">
    <span class="mc-doc-dot" data-state="clean"></span>
    <span class="mc-doc-title">Untitled template</span>
  </div>
  <div class="mc-actions">
    ${
        picker
            ? `<button type="button" class="mc-icon-btn mc-theme-toggle" data-act="theme-toggle" title="Switch light / dark" aria-label="Switch light or dark theme">
                 <span class="mc-ti-sun">${icon('sun')}</span><span class="mc-ti-moon">${icon('moon')}</span>
               </button>
               <button type="button" class="mc-icon-btn" data-act="theme-open" title="Theme &amp; colors" aria-label="Theme and colors">${icon('palette')}</button>
               <span class="mc-vr"></span>`
            : ''
    }
    <button type="button" class="mc-btn" data-act="preview">${icon('eye')}<span>Preview</span></button>
    <button type="button" class="mc-btn" data-act="test">${icon('send')}<span>Send test</span></button>
    <button type="button" class="mc-btn mc-primary" data-act="save">${icon('save')}<span>Save</span></button>
  </div>
</header>

<div class="mc-optionsbar">
  <div class="mc-docfields">
    <label><span>Name</span><input class="mc-name" type="text" placeholder="Template name" autocomplete="off"></label>
    <label><span>Subject</span><input class="mc-subject" type="text" placeholder="Email subject line" autocomplete="off"></label>
  </div>
  <div class="mc-ctx" aria-live="polite"></div>
</div>

<div class="mc-workspace">
  <aside class="mc-tools" aria-label="Toolbox">
    <button type="button" class="mc-tool is-active" data-act="toggle-blocks" data-tip="Blocks panel" aria-label="Toggle blocks panel">${icon('panelLeft', 18)}</button>
    <span class="mc-tool-sep"></span>
    <div class="mc-toolgroup" data-toolbox></div>
    <span class="mc-tool-sep"></span>
    <button type="button" class="mc-tool is-active" data-act="outlines" data-tip="Outlines" aria-label="Toggle outlines">${icon('grid', 18)}</button>
    <div class="mc-tool-spacer"></div>
    <button type="button" class="mc-tool" data-act="help" data-tip="Keyboard shortcuts" aria-label="Keyboard shortcuts">${icon('keyboard', 18)}</button>
  </aside>

  <aside class="mc-blocks-panel" aria-label="Blocks">
    <div class="mc-panel-bar"><span>Blocks</span><small>drag or click</small></div>
    <label class="mc-search">${icon('search', 14)}<input type="search" placeholder="Search blocks" aria-label="Search blocks"></label>
    <div class="mc-blocks"></div>
  </aside>

  <main class="mc-stage"><div class="mc-gjs"></div></main>

  <aside class="mc-dock" aria-label="Panels">
    ${panel(
        'properties',
        'sliders',
        'Properties',
        `<div class="mc-empty">${icon('select', 22)}<p>Select an element to style it.</p></div><div class="mc-style"></div>`
    )}
    ${panel(
        'appearance',
        'droplet',
        'Appearance',
        `<div class="mc-empty">${icon('droplet', 22)}<p>Select an element to set its link, background color, image, overlay or gradient.</p></div><div class="mc-traits"></div>`
    )}
    ${panel('layers', 'layers', 'Layers', '<div class="mc-layers"></div>')}
  </aside>
</div>

<footer class="mc-statusbar">
  <div class="mc-zoom">
    <button type="button" data-zoom="out" title="Zoom out" aria-label="Zoom out">${icon('zoomOut', 15)}</button>
    <button type="button" data-zoom="reset" class="mc-zoom-val" title="Actual size">100%</button>
    <button type="button" data-zoom="in" title="Zoom in" aria-label="Zoom in">${icon('zoomIn', 15)}</button>
    <button type="button" data-zoom="fit" title="Fit to screen" aria-label="Fit to screen">${icon('fit', 15)}</button>
  </div>
  <div class="mc-crumbs" aria-label="Selection path"></div>
  <div class="mc-devices" role="group" aria-label="Preview width">
    <button type="button" data-device="desktop" class="is-active" title="Desktop preview">${icon('desktop', 15)}</button>
    <button type="button" data-device="tablet" title="Tablet preview">${icon('tablet', 15)}</button>
    <button type="button" data-device="mobile" title="Mobile preview">${icon('phone', 15)}</button>
  </div>
  <div class="mc-savestate" data-state="clean"><i></i><span>All changes saved</span></div>
</footer>

<div class="mc-pop-layer"></div>
<div class="mc-toast" role="status" hidden></div>`;
}
