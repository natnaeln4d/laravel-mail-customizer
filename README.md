# Laravel Mail Customizer

A **visual, drag-and-drop email builder** you can drop into any Laravel dashboard. Your users build an email the way they would in Webflow or Elementor — drag blocks onto a live canvas, click anything to restyle it, no code — save it as a template, and your app sends it with their own variables.

- Drag **sections, columns, headings, text, images, buttons, dividers and spacers** onto the canvas. Start from ready-made **Header / Hero / Footer** sections.
- Style anything: **fonts, sizes, colours, spacing, borders, rounded corners, shadows**, and per-element **background colour, background image, image overlay, two-colour gradient**.
- Desktop and **mobile** preview, undo/redo, layer tree, image upload, `{{variables}}`, live **email preview** and **send test**.
- The output is **email-safe HTML**: a server-side step converts whatever the user built into table layout with inline styles, so it holds up in Gmail, Outlook and Apple Mail.

```
 Visual builder (GrapesJS)          Laravel                              Recipient
┌───────────────────────┐   ┌──────────────────────────────┐   ┌──────────────────────────┐
│ drag & drop blocks    │   │ MailTemplate (DB)            │   │ Gmail / Outlook /        │
│ style panel · layers  │──▶│  structure = editable project│──▶│ Apple Mail …             │
│ background settings   │   │  content   = email-safe HTML │   │ table layout, inline CSS │
│ preview · send test   │   │ EmailRenderer (hardening)    │   │                          │
└───────────────────────┘   └──────────────────────────────┘   └──────────────────────────┘
```

## Contents

- [Requirements](#requirements) · [Installation](#installation) · [Quick start](#quick-start)
- [Using the builder](#using-the-builder)
- [Sending emails](#sending-emails) · [Variables](#variables)
- [Configuration](#configuration) · [Authentication & authorization](#authentication--authorization)
- [How the email output is made](#how-the-email-output-is-made) · [Email client compatibility](#email-client-compatibility)
- [REST API](#rest-api) · [JavaScript API](#javascript-api)
- [Programmatic rendering (no UI)](#programmatic-rendering-no-ui)
- [Customizing views & assets](#customizing-views--assets)
- [Troubleshooting](#troubleshooting) · [Known limitations & roadmap](#known-limitations--roadmap)
- [Development](#development) · [Testing](#testing) · [Security](#security) · [License](#license)

---

## Requirements

| | |
|---|---|
| PHP | 8.0+ |
| Laravel | 9, 10, 11, 12 (the test-suite currently runs on Laravel 10 / PHP 8.2) |
| Browser | Any modern browser. The builder is self-contained: **no Vue, React, axios or CDN is required on your page.** |

You do **not** need Node.js to *use* the package — the compiled JavaScript ships in `dist/` (≈1.2 MB, because it contains the whole editor).

## Installation

```bash
composer require natnaeln4d/laravel-mail-customizer

php artisan vendor:publish --tag=assets
php artisan migrate
php artisan storage:link        # so uploaded images are publicly reachable
```

Optional:

```bash
php artisan vendor:publish --tag=config   # config/mail-customizer.php
php artisan vendor:publish --tag=views    # resources/views/vendor/mail-customizer
```

> After upgrading the package, re-publish the assets so browsers get the new JavaScript:
> `php artisan vendor:publish --tag=assets --force`

The service provider is auto-discovered.

## Quick start

### Option 1 — the built-in page (zero code)

Log in to your app and open:

```
/mail-customizer          → new template
/mail-customizer/12       → edit template #12
```

The page is protected by the same middleware as the API (`web` + `auth` by default). Change the URL or disable it in the [config](#configuration) (`ui.path`, `ui.enabled`).

### Option 2 — put it in your dashboard (Blade component)

```blade
<x-mail-customizer::builder />

{{-- edit an existing template, with sample values for preview / test emails --}}
<x-mail-customizer::builder :template-id="$template->id" :variables="['name' => 'Ann']" height="85vh" />
```

| Prop | Default | |
|---|---|---|
| `template-id` | `null` | Template to load. Omit for a new one. |
| `variables` | `config('mail-customizer.default_variables')` | Sample values for **Preview** and **Send test**. Each key also appears as a draggable `{{key}}` block. |
| `height` | `88vh` | Any CSS height. |
| `script` | published asset | Override the bundle URL. |

The component loads the bundle once per page and reads your route prefix from the config. It needs nothing else.

### Option 3 — plain HTML or any framework

Include the bundle and add a div with data attributes; it mounts automatically:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="/vendor/mail-customizer/js/mail-customizer.js"></script>

<div data-mail-builder
     data-template-id="12"
     data-api-base="api/mail-customizer"
     data-height="85vh"
     data-variables='{"name":"Ann"}'></div>
```

Because the builder is plain JavaScript, the same div works inside a Vue, React, Livewire or Inertia page — mount it in an effect/`mounted` hook, or call `MailCustomizer.initBuilder(element, options)` yourself (see the [JavaScript API](#javascript-api)). CSRF: the builder reads the `csrf-token` meta tag, or Laravel's `XSRF-TOKEN` cookie.

## Using the builder

**Layout.** Blocks on the left, the email canvas in the middle (a 600 px email on a grey page), and **Style / Settings / Layers** on the right.

**Adding things.** Drag a block onto the canvas — onto the page, or *into* a section or a column — or just **click a block** to append it. Drag the ✥ handle on a selected element to move it; ↰ selects the parent; ⧉ duplicates; ✕ deletes. Double-click text to edit it, and double-click an image to choose or upload one.

| Block | Notes |
|---|---|
| **Section** | A full-width band. Give it a background, padding, borders; drop anything inside. |
| **2 / 3 columns** | Sit side by side, and stack on phones. Click a column to style it. |
| **Heading · Text** | Rich text. Use the Style tab for font, size, weight, colour, spacing, alignment. |
| **Image** | The picker opens as soon as you add it: upload a file, then click it. Double-click the image later to change it. Blocks left without a picture are omitted from the email. |
| **Button** | Label and link in *Settings*; colours, padding, radius in *Style*. |
| **Divider · Spacer** | A line (style it like any element) and empty vertical space (change its height). |
| **Header · Hero · Footer** | Ready-made sections to start from. |
| **Variables** | `{{name}}`, `{{company}}`, … from the `variables` you pass in. |

**Style tab** (any selected element): font (web-safe stacks plus Roboto, Open Sans, Lato, Montserrat, Poppins, Inter, Playfair Display, Merriweather), size, weight, colour, line height, letter spacing, alignment, width / max width / height, padding, margin, border, border radius, box shadow.

**Settings tab** — *Background*, for sections, columns, headings, text and buttons:

| Control | What it does |
|---|---|
| Background color | Solid colour (also the fallback where gradients/images aren't supported). |
| Background image / Choose image | Paste a URL or pick/upload one. |
| Image overlay color + strength | A tinted layer over the image, e.g. black at 40 % so white text stays readable. |
| Gradient background, from, to, direction | A two-colour linear gradient. |
| Image size / position / repeat | Cover, contain, positioning, repeat. |

Layers combine: an overlay sits on top of a gradient, which sits on top of the image. *Note: a fully opaque gradient hides an image beneath it — use the overlay for tinting, or the gradient on its own.*

**Top bar.** Template name and subject, Desktop / Mobile (edits made in Mobile apply only to phones), Undo / Redo, Outlines, **Preview email** (the server-rendered result, with desktop / mobile / HTML views), **Send test**, **Save**.

## Sending emails

Templates are Eloquent models:

```php
use Illuminate\Support\Facades\Mail;
use Natnaeln4d\MailCustomizer\Models\MailTemplate;

$template = MailTemplate::named('Welcome');          // active template by name (404 if missing)
// or: MailTemplate::findOrFail($id);

Mail::to($user)->send($template->mailable([
    'name'    => $user->name,
    'company' => config('app.name'),
]));

Mail::to($user)->queue($template->mailable(['name' => $user->name]));   // queued
```

`CustomMailable` takes the subject from the template and the HTML from the stored, already-hardened `content`, after [variable replacement](#variables).

Via the API:

```http
POST /api/mail-customizer/templates/12/send-test      {"email": "me@example.com", "variables": {"name": "Ann"}}
POST /api/mail-customizer/templates/12/send-custom    {"emails": "a@x.com, b@x.com", "name": "Ann"}
```

## Variables

Type `{{name}}` (or `{{ name }}`) anywhere — text, button label, link, image URL. At send time each placeholder is replaced with the value you pass, **HTML-escaped**, so user data cannot inject markup.

- Pass variables as an array to `->mailable([...])`.
- Placeholders with no value are left as-is, so they are visible in testing.
- `default_variables` in the config are sample values for **previews and test emails sent through the API**. They are *not* added to emails you send from your own code — pass everything you need.

## Configuration

`php artisan vendor:publish --tag=config`

| Key | Default | Purpose |
|---|---|---|
| `middleware` | `['web', 'auth']` | Middleware for the REST API. |
| `route_prefix` | `api/mail-customizer` | URL prefix of the REST API. The Blade component and built-in page pass it to the builder automatically. |
| `ui.enabled` / `ui.path` / `ui.middleware` | `true` / `mail-customizer` / `['web','auth']` | The built-in builder page. |
| `renderer.width` | `600` | Email container width in px (min 320). |
| `renderer.background` | `#f4f4f4` | Outer page colour. |
| `renderer.content_background` | `#ffffff` | Email body colour. |
| `renderer.font_family` | `Arial, Helvetica, sans-serif` | Fallback font stack. |
| `default_variables` | name, email, company, website | Sample values for previews/test sends. |
| `testing.default_test_email` | `null` | Fallback recipient for a test send when no address is given and nobody is logged in. |
| `images.max_upload_size` | `2048` (KB) | Upload limit. |
| `images.default_storage` | `public` | Filesystem disk for uploads. |
| `images.storage_path` | `mail-templates/images` | Upload folder. Deleting is only allowed inside it. |

## Authentication & authorization

By default every route requires an authenticated session user (`web` + `auth`). **Any logged-in user can then create, edit, delete and send templates.** In most apps restrict that to admins:

```php
Gate::define('manage-mail-templates', fn ($user) => $user->is_admin);
```

```php
// config/mail-customizer.php
'middleware' => ['web', 'auth', 'can:manage-mail-templates'],
'ui' => ['enabled' => true, 'path' => 'mail-customizer', 'middleware' => ['web', 'auth', 'can:manage-mail-templates']],
```

The bundled UI uses cookie sessions + CSRF, so keep `web`. For a token-based SPA calling the API yourself: `'middleware' => ['api', 'auth:sanctum']`.

## How the email output is made

The builder produces ordinary HTML and CSS — great for a canvas, but email clients are far less forgiving than browsers. When you **save**, **preview** or **send a test**, the server runs the HTML through `EmailRenderer::renderHtml()`:

1. **Sanitises**: removes scripts, iframes, `on*` handlers, `javascript:` links and `@import`.
2. **Inlines CSS** onto every element (Gmail ignores most `<style>`).
3. **Hardens for Outlook**: Outlook ignores padding, background and borders on `div`, `p`, `h1–h6` and `a`. Those styles are moved onto a wrapping `<table><tr><td>` (with `bgcolor` and a `background` attribute for images). Buttons become a coloured table cell with the link inside, aligned from their wrapper. Spacers become fixed-height cells.
4. **Cleans up**: absolute image/link URLs, `width`/`height`/`alt` attributes on images, editor-only attributes removed.
5. **Keeps phone styles**: edits made in *Mobile* become a `@media (max-width: 480px)` block with `!important`.
6. **Wraps** it all in a 600 px container with a hidden preheader slot, Google Fonts `<link>` for fonts you used, and responsive column stacking.

The saved template stores both the editable **project** (`structure`) and the final HTML (`content`), so you can keep editing later and sending stays fast.

## Email client compatibility

| Feature | Gmail / Apple Mail / iOS / most webmail | Outlook desktop (Windows) |
|---|---|---|
| Layout, columns, fonts (web-safe), colours, padding, buttons | ✅ | ✅ |
| Background colour | ✅ | ✅ |
| Background **image** | ✅ | ⚠️ `background` attribute only: may show the fallback colour instead |
| **Gradient**, **overlay** | ✅ | ⚠️ shows the solid fallback colour (the *Gradient from* colour, or *Background color*) |
| Border radius (buttons, sections) | ✅ | ⚠️ square corners |
| Box shadow | ✅ in most modern clients | ❌ ignored |
| Google web fonts | Apple Mail / iOS ✅ | ❌ falls back to the next font in the stack |

Rule of thumb: **always set a solid Background color under a gradient or image** and a readable text colour, so Outlook users still see a good email. Send a test to Outlook, Gmail and your phone before a big send; for a full matrix use Litmus or Email on Acid.

## REST API

All routes live under `route_prefix` (default `/api/mail-customizer`) with the configured middleware. Send `Accept: application/json`.

| Method & path | Body | Returns |
|---|---|---|
| `GET /templates` | — | Collection of templates. |
| `POST /templates` | `name`, `subject`, **`html`**, **`css`**, `fonts[]`, `structure{}` | `201` + template. The server renders `content` from `html`+`css`. |
| `GET /templates/{id}` | — | `id, name, subject, content, structure, images, styles, layout, is_active, …` |
| `PUT /templates/{id}` | same as POST | Updated template. |
| `DELETE /templates/{id}` | — | `{ message }` |
| `POST /templates/{id}/preview` | `variables{}` | `{ preview: "<html>…" }` |
| `POST /templates/preview` | `html`, `css`, `fonts[]`, `variables{}` | Same, for an **unsaved draft**. |
| `POST /templates/{id}/send-test` | `email`, `variables{}` | Sends one email (defaults to the current user, then `testing.default_test_email`). |
| `POST /templates/send-test` | `email`, `subject`, `html`, `css`, `variables{}` | Test send of an unsaved draft. |
| `POST /templates/{id}/send-custom` | `emails` (comma-separated), variables | Sends to each address. |
| `POST /upload-image` | multipart `image` | `{ success, url, path }` |
| `DELETE /delete-image` | `path` | `{ success }` — only inside `images.storage_path`. |
| `GET /builder/elements` | — | The legacy block palette (used by the [block-tree renderer](#programmatic-rendering-no-ui)). |

You can also send a plain `content` string (stored as-is) or a legacy block-tree `structure` list.

## JavaScript API

Loading the bundle defines `window.MailCustomizer`:

```js
const builder = MailCustomizer.initBuilder(document.getElementById('app'), {
  templateId: 12,                       // optional
  variables: { name: 'Ann' },           // sample values
  apiBase: 'api/mail-customizer',       // default
  height: '85vh',
});

builder.editor;   // the underlying GrapesJS editor (full API: https://grapesjs.com/docs/api/editor.html)
builder.save();   // programmatic save
document.addEventListener('mail-customizer:saved', e => console.log(e.detail));   // template JSON after each save
```

Other helpers: `MailCustomizer.api.*` (REST wrappers using the global `axios`, if you use it), `MailCustomizer.events` (tiny event bus), and `MailCustomizer.initVue(id, { component: 'customizer' })` for the older form-style editor (needs Vue 2).

## Programmatic rendering (no UI)

You can render email-safe HTML without the editor — e.g. for system emails defined in code. Two inputs are supported.

**HTML + CSS** (what the builder sends):

```php
use Natnaeln4d\MailCustomizer\Rendering\EmailRenderer;

$html = app(EmailRenderer::class)->renderHtml($bodyHtml, $css, [
    'fonts' => ['Poppins'], 'preheader' => 'Your account is ready', 'title' => 'Welcome',
]);
```

**A JSON block tree** (text / image / button / divider / spacer / columns), rendered straight to tables:

```php
$renderer = app(EmailRenderer::class);

$html = $renderer->render([
    ['type' => 'text',   'content' => '<h2>Hello {{name}}</h2>'],
    ['type' => 'button', 'properties' => ['text' => 'Open', 'href' => 'https://example.com'],
                         'styles' => ['background-color' => '#6366f1', 'border-radius' => '8px']],
], ['preheader' => 'Your account is ready']);

$text = $renderer->toPlainText($structure);        // plain-text alternative
```

Register your own block type for the block-tree renderer:

```php
use Natnaeln4d\MailCustomizer\Rendering\Blocks\Block;

class ProductCardBlock extends Block
{
    public function render(array $element): string
    {
        $p = $this->props($element);
        return $this->row('<strong>' . e($p['name'] ?? '') . '</strong>', ['padding' => '16px 20px']);
    }
}

app(EmailRenderer::class)->registerBlock('product-card', ProductCardBlock::class);
```

Always escape user-controlled strings with `e()` in custom blocks.

## Customizing views & assets

```bash
php artisan vendor:publish --tag=views    # resources/views/vendor/mail-customizer/*
php artisan vendor:publish --tag=assets   # public/vendor/mail-customizer/js/mail-customizer.js
```

Views: `builder` (full page), `components/builder` (the Blade component), plus the older `editor`/`form` pages for the form-style editor.

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| Builder area is blank; 404 on `mail-customizer.js` | Assets not published: `php artisan vendor:publish --tag=assets`. |
| `419 Page Expired` on save/preview/test | CSRF token missing. Use the Blade component / built-in page, or add `<meta name="csrf-token" …>`. |
| `401` or redirect to login | Routes require `auth`. Log in, or change `middleware`. |
| `403` / `404` on `/mail-customizer` | Your `can:` middleware denied you, or `ui.enabled` is `false`. |
| "Name and subject first" toast | Both fields are required to save. |
| Dropping a block does nothing | Sections can only be dropped on the page itself; content can go on the page or inside a section/column. Click a block to append it instead. |
| Uploaded images don't show in emails | Run `php artisan storage:link` and set `APP_URL` correctly — email clients need absolute URLs. |
| Emails show `{{name}}` literally | You didn't pass `name` to `->mailable([...])`. |
| Looks different in Outlook | See [compatibility](#email-client-compatibility): gradients, overlays, shadows and rounded corners degrade. |
| "made with the old builder" toast | Templates saved with the previous block-list builder can't be opened in the visual builder. Saving replaces them. |

## Known limitations & roadmap

- **Outlook desktop** does not render gradients, overlays or rounded corners, and may not show background images (VML background support is planned).
- **No custom block API for the visual builder yet.** The block-tree renderer is extensible; the builder's palette is not.
- **Web fonts** only load where the client supports them; the list is fixed (web-safe + 8 Google fonts).
- **Image library**: the picker shows images uploaded in the current session plus those already used in the template. A server-side library of everything uploaded earlier is planned.
- **Old block-list templates** can't be reopened in the new builder.
- **No built-in authorization policy** — bring your own Gate/middleware.
- **Plain-text alternative** exists in the block-tree renderer (`toPlainText`) but isn't attached to `CustomMailable` automatically.
- **React / Vue 2 form-style editors** (`data-mail-customizer-react` / `-vue`) are the earlier, simpler editors and are not part of the visual builder.
- The bundle is ≈1.2 MB; loading it only on the builder page is recommended.
- Tested on Laravel 10 / PHP 8.2 only.

## Development

```bash
git clone … && cd laravel-mail-customizer
composer install
npm install

npm run watch      # rebuild on change (keeps running — start it in its own terminal)
npm run build      # production bundle → dist/mail-customizer.js
composer test      # PHPUnit + Orchestra Testbench
```

**`dist/` is committed on purpose**: it is what `vendor:publish --tag=assets` ships, so users don't need Node. Rebuild and commit it whenever you change anything under `src/Resources/assets/js`.

```
src/
  Http/Controllers/MailTemplateController.php   REST API
  Rendering/EmailRenderer.php (+ Blocks/*, Css)  html/css -> email HTML · block tree -> email HTML
  Services/                                      TemplateCompiler (variables), DragDropBuilder (legacy palette)
  Mail/CustomMailable.php
  Resources/assets/js/builder/                   the visual builder (GrapesJS): blocks, components, background controls, UI
  Resources/assets/js/mail-customizer.js         bundle entry (auto-mount, window.MailCustomizer)
  Resources/views/                               builder page + <x-mail-customizer::builder>
  routes/api.php · routes/web.php
tests/ · tests/fixtures/editor-output.json       real html+css produced by the builder in a browser
```

## Testing

```bash
composer test
```

The suite covers the API (CRUD, preview, test sends, auth config, image-path guard), the **hardening of real builder output** (fixture captured from the builder itself: padding/background → table cells, bulletproof buttons, spacers, media queries, sanitising, fonts), the block-tree renderer, the Blade component and the model helpers. Use `Mail::fake()` + `Mail::assertSent(CustomMailable::class)` to test your own sending code.

## Security

- Builder HTML is sanitised on the server (no scripts, iframes, event handlers, `javascript:` links or `@import`); CSS is filtered; variable values are HTML-escaped.
- Routes are authenticated by default — **add authorization** for multi-user apps.
- Image deletion works only inside the configured upload folder; uploads are validated as images and size-limited.

Please report vulnerabilities to natnaeln4d@gmail.com rather than opening a public issue.

## License

MIT — see [LICENSE.md](LICENSE.md).
