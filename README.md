# Laravel Mail Customizer

A drag-and-drop **email template builder** you can drop into any Laravel dashboard.
Your users design an email visually (text, images, buttons, dividers, spacers, columns), save it as a template, and your app sends it with their own variables.

The part that makes this different from "a WYSIWYG that outputs HTML": the builder saves a **JSON block tree**, and a server-side renderer turns it into **email-safe HTML** — table layout, inline styles, fixed 600px container, Outlook fallbacks. The preview, the test email and the real email all come from the same renderer, so what you see is what recipients get.

```
 Builder UI (Vue)            Laravel                          Recipient
┌──────────────────┐   ┌─────────────────────────┐   ┌──────────────────────┐
│ drag & drop      │   │ MailTemplate (DB)       │   │ Gmail / Outlook /    │
│ blocks           │──▶│  structure  (JSON)      │──▶│ Apple Mail …         │
│ properties panel │   │  content    (rendered)  │   │ table-based HTML,    │
│ preview / test   │   │ EmailRenderer           │   │ inline CSS           │
└──────────────────┘   └─────────────────────────┘   └──────────────────────┘
```

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Sending emails](#sending-emails)
- [Variables](#variables)
- [Configuration](#configuration)
- [Authentication & authorization](#authentication--authorization)
- [Blocks](#blocks)
- [Custom blocks](#custom-blocks)
- [Using the renderer directly](#using-the-renderer-directly)
- [Embedding options](#embedding-options)
- [REST API](#rest-api)
- [JavaScript API](#javascript-api)
- [Customizing views & assets](#customizing-views--assets)
- [Email client compatibility](#email-client-compatibility)
- [Troubleshooting](#troubleshooting)
- [Known limitations & roadmap](#known-limitations--roadmap)
- [Development](#development)
- [Testing](#testing)
- [Security](#security) · [License](#license)

---

## Features

- **Drag-and-drop builder** with a properties panel, live server-rendered preview and "send test email".
- **Email-safe output** — nested tables, inline CSS, bulletproof buttons (with an Outlook VML fallback), `width`/`height` attributes on images, a hidden preheader, responsive stacking for columns.
- **Template storage** — `mail_templates` table, REST API, image upload.
- **Variables** — `{{name}}` / `{{ name }}` placeholders, HTML-escaped on output.
- **Sending** — a ready-made `Mailable`; `Mail::to($user)->send($template->mailable([...]))`.
- **Extensible** — register your own block types; configure width, colours, fonts, routes and middleware.
- **Secure defaults** — routes are behind `web` + `auth`, rich text is sanitised, style values and URLs are validated, image deletion is confined to the upload folder.
- **Frontend flexibility** — a Blade component, a built-in page, data-attribute auto-mounting, and a JS API. Vue 2 is the primary UI; a React client is included (see [limitations](#known-limitations--roadmap)).

## Requirements

| | |
|---|---|
| PHP | 8.0+ |
| Laravel | 9, 10, 11, 12 (the test-suite currently runs on Laravel 10 / PHP 8.2) |
| Browser | any modern browser. The builder UI needs **Vue 2.7** and **axios** on the page (the Blade component can load them from a CDN for you). |

You do **not** need Node.js to *use* the package — the compiled JavaScript ships in `dist/`.

## Installation

```bash
composer require natnaeln4d/laravel-mail-customizer
```

Publish the compiled JavaScript and run the migration:

```bash
php artisan vendor:publish --tag=assets
php artisan migrate
```

Optionally publish the config and/or the views:

```bash
php artisan vendor:publish --tag=config   # config/mail-customizer.php
php artisan vendor:publish --tag=views    # resources/views/vendor/mail-customizer
```

If you want image uploads to be publicly reachable (the default `public` disk), make sure the storage link exists:

```bash
php artisan storage:link
```

> After upgrading the package, re-publish the assets so the browser gets the new JavaScript:
> `php artisan vendor:publish --tag=assets --force`

The service provider is auto-discovered; no manual registration is needed.

## Quick start

### Option 1 — the built-in page (zero code)

Log in to your app and visit:

```
/mail-customizer          → new template
/mail-customizer/12       → edit template #12
```

The page is protected by the same middleware as the API (`web` + `auth` by default). Change the URL or turn it off in the [config](#configuration) (`ui.path`, `ui.enabled`).

### Option 2 — drop it into your dashboard (Blade component)

```blade
<x-mail-customizer::builder />

{{-- edit an existing template, with sample variables for preview/test sends --}}
<x-mail-customizer::builder :template-id="$template->id" :variables="['name' => 'Ann']" />
```

The component renders a mount point and — once per page — loads Vue 2.7, axios and the package bundle. Props:

| Prop | Default | |
|---|---|---|
| `template-id` | `null` | Template to load. Omit to start a new one. |
| `variables` | `config('mail-customizer.default_variables')` | Values used by preview and test emails. |
| `cdn` | `true` | Load Vue 2.7 + axios from jsDelivr. Set `:cdn="false"` if your page already provides Vue 2 and axios. |
| `script` | published asset | Override the bundle URL. |

> ⚠️ If your page already uses **Vue 3** (for example an Inertia app), don't load Vue 2 over it. Put the builder on its own page — e.g. the [built-in page](#option-1--the-built-in-page-zero-code) — or in an iframe.

### Option 3 — plain HTML / any framework

Include Vue 2, axios and the bundle, then add a div with data attributes — the bundle mounts it automatically:

```html
<script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios@1.7.9/dist/axios.min.js"></script>
<script src="/vendor/mail-customizer/js/mail-customizer.js"></script>

<div data-mail-builder data-template-id="12" data-variables='{"name":"Ann"}'></div>
```

For CSRF, set the token once (Laravel's default `resources/js/bootstrap.js` already does this):

```js
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
```

## Sending emails

Templates are Eloquent models, so sending is two lines.

```php
use Illuminate\Support\Facades\Mail;
use Natnaeln4d\MailCustomizer\Models\MailTemplate;

$template = MailTemplate::named('Welcome');            // active template by name (throws 404-style if missing)
// or: MailTemplate::findOrFail($id);

Mail::to($user)->send($template->mailable([
    'name'    => $user->name,
    'company' => config('app.name'),
]));
```

Queued sending works for saved templates:

```php
Mail::to($user)->queue($template->mailable(['name' => $user->name]));
```

`CustomMailable` takes the subject from the template and the HTML from the template's stored `content` after [variable replacement](#variables).

### From the API

```http
POST /api/mail-customizer/templates/12/send-test      {"email": "me@example.com", "variables": {"name": "Ann"}}
POST /api/mail-customizer/templates/12/send-custom    {"emails": "a@x.com, b@x.com", "name": "Ann"}
```

## Variables

Put `{{name}}` or `{{ name }}` anywhere in a text block, button label, link or image URL. At send time each placeholder is replaced with the value you pass; values are **HTML-escaped**, so user-supplied data cannot inject markup.

- Pass variables as an associative array to `->mailable([...])`.
- Placeholders with no matching variable are left as-is, so they are visible in testing.
- `config('mail-customizer.default_variables')` provides sample values for **previews and test emails** made through the API. They are *not* automatically added to emails you send from your own code — pass everything you need.

## Configuration

Publish with `php artisan vendor:publish --tag=config`. Every key:

| Key | Default | Purpose |
|---|---|---|
| `middleware` | `['web', 'auth']` | Middleware for the REST API. See [authorization](#authentication--authorization). |
| `route_prefix` | `api/mail-customizer` | URL prefix of the REST API. **The bundled JS calls `/api/mail-customizer/...`; if you change this, also set the prefix in your own client code.** |
| `ui.enabled` | `true` | Register the built-in builder page. |
| `ui.path` | `mail-customizer` | URL of the built-in page. |
| `ui.middleware` | `['web', 'auth']` | Middleware for the built-in page. |
| `renderer.width` | `600` | Email container width in px (min 320). |
| `renderer.background` | `#f4f4f4` | Outer background colour. |
| `renderer.content_background` | `#ffffff` | Email body colour. |
| `renderer.font_family` | `Arial, Helvetica, sans-serif` | Default font stack. |
| `default_variables` | name, email, company, website | Sample values for previews/test sends. |
| `testing.enabled` / `testing.default_test_email` | `true` / `null` | Fallback recipient for test sends when none is given and nobody is logged in. |
| `images.max_upload_size` | `2048` (KB) | Upload limit. |
| `images.default_storage` | `public` | Filesystem disk for uploads. |
| `images.storage_path` | `mail-templates/images` | Folder on that disk. Deleting is only allowed inside this folder. |
| `available_styles`, `available_layouts`, `storage`, `images.*` options | — | Reserved for UI options; see the file for the full list. |

## Authentication & authorization

By default every route requires an authenticated session user (`web` + `auth`). **Any logged-in user can then create, edit, delete and send templates.** In most apps you want to restrict that to admins:

```php
// AuthServiceProvider / AppServiceProvider
Gate::define('manage-mail-templates', fn ($user) => $user->is_admin);
```

```php
// config/mail-customizer.php
'middleware' => ['web', 'auth', 'can:manage-mail-templates'],
'ui' => ['middleware' => ['web', 'auth', 'can:manage-mail-templates'], /* … */],
```

Using Sanctum for a token-based SPA:

```php
'middleware' => ['api', 'auth:sanctum'],
```

(The bundled UI uses cookies + CSRF, so keep `web` for it.)

## Blocks

Each element in the canvas is a block. The saved JSON looks like:

```json
[
  { "id": "…", "type": "text",   "content": "<h2>Hello {{name}}</h2><p>Welcome!</p>", "styles": { "padding": "20px" } },
  { "id": "…", "type": "image",  "properties": { "src": "https://…/banner.png", "alt": "Banner", "width": "560px", "alignment": "center" } },
  { "id": "…", "type": "button", "properties": { "text": "Open dashboard", "href": "https://…" }, "styles": { "background-color": "#6366f1", "border-radius": "8px" } }
]
```

| Type | Reads | Output |
|---|---|---|
| `text` | `content` (rich text); styles `font-family`, `font-size`, `line-height`, `color`, `text-align`, `background-color`, `padding` | A table cell. Allowed tags: `p br strong b em i u s a ul ol li h1–h4 span blockquote`. Everything else, plus `on*`/`style`/`class`/`id` attributes and `javascript:` links, is stripped. |
| `image` | `properties.src`, `alt`, `width`, `height`, `alignment` (left/center/right), `href` (optional link) | `<img>` with `width` attribute, centred/aligned through a nested table (no floats). Width is capped to the container. Empty `src` renders nothing. |
| `button` | `properties.text`, `href`; styles `background-color`, `color`, `border-radius`, `font-size`, `font-family`, `padding`; `properties.alignment` | Bulletproof button: coloured table cell + anchor, with an Outlook VML `roundrect`. |
| `divider` | styles `border-color`/`color`, `padding` | A 1px table cell (not `<hr>`). |
| `spacer` | `properties.height` (px, 1–400) | A fixed-height table cell. |
| `columns` | `properties.columns` = list of lists of blocks (2–4 columns) | Side-by-side cells that stack on narrow screens. *Renderer only — the builder UI doesn't create columns yet.* |
| *anything else* | `content` | Rendered as a text block, so older or custom templates still show their content. |

Colours, URLs and style values are validated; anything that could break out of an attribute is dropped.

## Custom blocks

Extend `Block` and register it, e.g. in a service provider's `boot()`:

```php
use Natnaeln4d\MailCustomizer\Rendering\Blocks\Block;
use Natnaeln4d\MailCustomizer\Rendering\EmailRenderer;

class ProductCardBlock extends Block
{
    public function render(array $element): string
    {
        $p = $this->props($element);          // $element['properties'] as an array
        $name = e($p['name'] ?? '');
        $price = e($p['price'] ?? '');

        // Return one or more full <tr> rows. Use tables and inline styles only.
        return $this->row(
            "<strong style=\"font-size:16px;\">{$name}</strong><br>{$price}",
            ['padding' => '16px 20px', 'font-family' => 'Arial, sans-serif']
        );
    }

    // Optional: the plain-text version of this block.
    public function toText(array $element): string
    {
        $p = $this->props($element);
        return ($p['name'] ?? '') . ' — ' . ($p['price'] ?? '');
    }
}

// AppServiceProvider::boot()
app(EmailRenderer::class)->registerBlock('product-card', ProductCardBlock::class);
```

Helpers available in a block: `$this->props($element)`, `$this->styles($element)`, `$this->row($html, $tdStyles, $tdAttributes)`, `$this->renderer->contentWidth()`, `$this->renderer->renderBlocks([...])` (nesting), and the `Natnaeln4d\MailCustomizer\Rendering\Css` helpers (`inline`, `px`, `color`, `url`, `align`) for safe values. **Always escape user-controlled strings with `e()`.**

> Registering a block makes the *renderer* understand it (API/JSON, preview, sending). Adding it to the builder's drag-and-drop palette is not configurable yet — see the [roadmap](#known-limitations--roadmap).

## Using the renderer directly

You can render a block tree without touching the database — handy for tests or one-off emails:

```php
use Natnaeln4d\MailCustomizer\Rendering\EmailRenderer;

$renderer = app(EmailRenderer::class);

$html = $renderer->render($structure, [
    'preheader'          => 'Your account is ready',   // inbox preview text
    'title'              => 'Welcome',
    'lang'               => 'en',
    'background'         => '#f4f4f4',
    'content_background' => '#ffffff',
]);

$text = $renderer->toPlainText($structure);            // text/plain alternative
```

## Embedding options

| You have… | Use |
|---|---|
| A Blade dashboard | `<x-mail-customizer::builder />` |
| Just want a page | `/mail-customizer` |
| Any HTML page | `<div data-mail-builder>` + the three script tags |
| Programmatic control | `MailCustomizer.initVue('el-id', { templateId, variables })` |
| React | load React 18 + ReactDOM, then `<div data-mail-customizer-react>` or `MailCustomizer.initReact(...)` |
| A form-style editor (name/subject/images) | `<div data-mail-customizer-vue>` |

## REST API

All routes live under `route_prefix` (default `/api/mail-customizer`) and use the configured middleware. Send `Accept: application/json`.

| Method & path | Body | Returns |
|---|---|---|
| `GET /builder/elements` | — | `{ elements: { text: {…}, image: {…}, … } }` — the palette. |
| `GET /templates` | — | Collection of templates. |
| `POST /templates` | `name`, `subject`, `content`, `structure[]`, `styles{}`, `images[]`, `layout` | `201` + template. If `structure` is non-empty, `content` is **rendered from it** (the `content` you send is ignored). |
| `GET /templates/{id}` | — | Template: `id, name, subject, content, structure, images, styles, layout, is_active, …` |
| `PUT /templates/{id}` | same as POST | Updated template. |
| `DELETE /templates/{id}` | — | `{ message }` |
| `POST /templates/{id}/preview` | `variables{}` | `{ preview: "<html>…" }` |
| `POST /templates/preview` | `structure[]` or `content`, `variables{}` | Same, for an **unsaved draft**. |
| `POST /templates/{id}/send-test` | `email`, `variables{}` | Sends one email. `email` defaults to the current user's, then `testing.default_test_email`. |
| `POST /templates/send-test` | `email`, `subject`, `structure[]`/`content`, `variables{}` | Sends a test of an unsaved draft. |
| `POST /templates/{id}/send-custom` | `emails` (comma-separated), variables | Sends to each address. |
| `POST /upload-image` | multipart `image` | `{ success, url, path }` |
| `DELETE /delete-image` | `path` | `{ success }` — only files under `images.storage_path`. |

Example:

```bash
curl -X POST https://app.test/api/mail-customizer/templates \
  -H "Accept: application/json" -H "X-CSRF-TOKEN: …" --cookie "laravel_session=…" \
  -H "Content-Type: application/json" \
  -d '{"name":"Welcome","subject":"Welcome, {{name}}!","content":"x","structure":[{"type":"text","content":"<p>Hi {{name}}</p>"}]}'
```

## JavaScript API

Loading the bundle defines `window.MailCustomizer`:

```js
MailCustomizer.initVue('my-div', { templateId: 12, variables: { name: 'Ann' } });
MailCustomizer.initReact('my-div', { templateId: 12 });          // needs React + ReactDOM globals

MailCustomizer.api.getTemplates();
MailCustomizer.api.previewDraft({ structure, variables });
MailCustomizer.api.sendTestDraft({ email, structure });
MailCustomizer.api.uploadImage(file);
// …also: getTemplate, createTemplate, updateTemplate, deleteTemplate,
//        previewTemplate, sendTestEmail, sendCustomEmail, deleteImage

MailCustomizer.events.on('name', cb);  // simple event bus: on / off / emit
```

`api` helpers use `/api/mail-customizer/...` and the global `axios`. The bundle also exposes `window.MailBuilderVue`, `window.MailCustomizerVue` and `window.MailCustomizerReact`, and registers the `<mail-builder>` and `<mail-customizer>` Vue components globally.

## Customizing views & assets

```bash
php artisan vendor:publish --tag=views    # resources/views/vendor/mail-customizer/*
php artisan vendor:publish --tag=assets   # public/vendor/mail-customizer/js/mail-customizer.js
```

Published views: `builder` (full page), `editor`/`form` (form-style editor), `preview`, and `components/builder` (the Blade component).

## Email client compatibility

What the renderer does and why:

| Technique | Reason |
|---|---|
| Nested `<table role="presentation">`, no `div` layout | Outlook (Word engine) ignores most CSS layout. |
| Inline styles on every element | Gmail and others strip or ignore `<style>` blocks. The one `<style>` block is progressive enhancement (mobile stacking, resets). |
| 600px fixed container + `max-width:100%` | Safe desktop width that still shrinks on phones. |
| `width`/`height` attributes on images, `display:block` | Prevents Outlook resizing and the gap under images. |
| Bulletproof button + VML `roundrect` | Real clickable button with rounded corners in Outlook. |
| Spacer/divider as sized table cells | `<hr>` and margins are inconsistent. |
| Hidden preheader | Controls the inbox preview line. |
| `bgcolor` attributes alongside CSS colours | Fallback for clients that drop `background-color`. |

Test your real templates in the clients that matter to you (Litmus, Email on Acid, or send to Gmail/Outlook/Apple Mail accounts). The test-suite verifies structure, not pixel rendering in every client.

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| Builder shows nothing, console: `MailCustomizer is not defined` / 404 on `mail-customizer.js` | Assets not published. Run `php artisan vendor:publish --tag=assets`. |
| `419 Page Expired` on save/preview/send | CSRF token missing. Use the Blade component (it sets it), or set `X-CSRF-TOKEN` on axios. |
| `401` / redirect to login | Routes require `auth`. Log in, or change `middleware` in the config. |
| `403` / `404` on `/mail-customizer` | Your `can:` middleware denied you, or `ui.enabled` is `false`. |
| `Vue is not defined` / `axios is not defined` | Load Vue 2.7 and axios *before* the bundle, or use the Blade component with `:cdn="true"`. |
| Page already uses Vue 3 and things break | Don't mix Vue 2 and 3 on one page. Use the built-in page or an iframe. |
| Uploaded images don't show | Run `php artisan storage:link`; check `APP_URL` and `images.default_storage`. |
| Emails show `{{name}}` literally | You didn't pass `name` to `->mailable([...])`. |
| Route prefix changed but UI calls the old URL | The bundled JS uses `/api/mail-customizer`. Keep the default prefix, or rebuild the bundle with your prefix. |
| Outlook looks different from the preview | Expected for rounded corners on images and some fonts; buttons have a VML fallback. Keep layouts simple. |

## Known limitations & roadmap

Honest list of what is not done yet:

- **Vue 2** is end-of-life. The builder works, but a Vue 3 build is planned.
- **Columns** render correctly but the drag-and-drop UI can't create them yet.
- **The palette isn't extensible.** Custom blocks render, but appear in the builder only after they're added to `DragDropBuilder::$elements`. A config/registry hook is planned.
- **React client and the form-style Vue editor** (`MailCustomizer.vue`/`.jsx`) are older and less tested than the main builder; their preview/test calls need a saved template.
- **Route prefix** is configurable on the server but hard-coded in the bundled JS.
- **No built-in authorization policy** — bring your own Gate/middleware (see above).
- **Plain-text alternative** is available from the renderer (`toPlainText`) but isn't attached to `CustomMailable` automatically yet.
- Only Laravel 10 / PHP 8.2 are covered by the test-suite so far.

## Development

```bash
git clone … && cd laravel-mail-customizer
composer install
npm install

npm run watch      # rebuild on change
npm run build      # production bundle → dist/mail-customizer.js
composer test      # PHPUnit + Orchestra Testbench
```

**`dist/` is committed on purpose** — it is what `vendor:publish --tag=assets` ships, so users don't need Node. Run `npm run build` and commit the result whenever you change anything under `src/Resources/assets/js`.

Layout:

```
src/
  Http/Controllers/MailTemplateController.php   REST API
  Rendering/EmailRenderer.php + Blocks/*        JSON → email HTML
  Services/DragDropBuilder.php                  palette + delegates to the renderer
  Services/TemplateCompiler.php                 variable replacement
  Mail/CustomMailable.php                       the Mailable
  Resources/assets/js/                          Vue/React sources (webpack → dist/)
  Resources/views/                              Blade views + <x-mail-customizer::builder>
  routes/api.php · routes/web.php
config/mail-customizer.php · database/migrations/
```

## Testing

```bash
composer test
```

The suite covers the API (CRUD, preview, test sends, auth/middleware config, image-path guard), the renderer (table output, sanitising, Outlook button, columns, custom blocks, plain text), the Blade component and the model helpers. To test your own templates, assert on the output of `app(EmailRenderer::class)->render(...)` or fake mail with `Mail::fake()` and `Mail::assertSent(CustomMailable::class)`.

## Security

- Rich text is whitelisted (no scripts, iframes, event handlers or `javascript:` links); style values and URLs are validated before they reach an attribute.
- Variable values are HTML-escaped.
- Routes are authenticated by default; **add authorization** for multi-user apps.
- Image deletion only works inside the configured upload folder; uploads are validated as images and size-limited.

If you find a vulnerability, please email natnaeln4d@gmail.com instead of opening a public issue.

## License

MIT — see [LICENSE.md](LICENSE.md).
