<?php
// config/mail-customizer.php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be applied to all routes of the package.
    | By default, we use the web and auth middleware (session + CSRF), which
    | is what the bundled browser UI expects. Use e.g. 'auth:sanctum' if you
    | drive the API from a token-based SPA.
    |
    */
    'middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be added to all routes of the package.
    |
    */
    'route_prefix' => 'api/mail-customizer',

    /*
    |--------------------------------------------------------------------------
    | Built-in Builder Page
    |--------------------------------------------------------------------------
    |
    | A ready-made page at /{path} (and /{path}/{templateId}) that hosts the
    | builder. Disable it if you embed <x-mail-customizer::builder /> yourself.
    |
    */
    'ui' => [
        'enabled' => true,
        'path' => 'mail-customizer',
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Builder Theme
    |--------------------------------------------------------------------------
    |
    | How the builder looks. By default it ships a studio-style interface in
    | the "luffy" colour theme (vest red, straw-hat gold, deep-sea navy) and
    | follows the visitor's light/dark preference.
    |
    |  mode      light | dark | auto (follow the OS) | inherit (match the page
    |            the builder is embedded in: its background, text and accent)
    |  preset    luffy | goku | adobe (neutral greys + classic blue)
    |  accent    hex colour for primary actions, e.g. '#7c3aed'
    |  secondary hex colour for highlights/selection (used in dark mode)
    |  tokens    raw CSS variable overrides, e.g. ['--mc-bg-1' => '#ffffff']
    |  font      null, 'inherit' (use your site's font) or any CSS font stack
    |  brand     name shown top-left
    |  picker    let each user change the theme from inside the builder
    |            (their choice is remembered in their browser)
    |
    */
    'theme' => [
        'mode' => 'auto',
        'preset' => 'luffy',
        'accent' => null,
        'secondary' => null,
        'tokens' => [],
        'font' => null,
        'brand' => 'Mail Studio',
        'picker' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Renderer
    |--------------------------------------------------------------------------
    |
    | Controls the table-based HTML generated from the builder's blocks.
    | 600px is the safe, widely-supported email width.
    |
    */
    'renderer' => [
        'width' => 600,
        'background' => '#f4f4f4',
        'content_background' => '#ffffff',
        'font_family' => 'Arial, Helvetica, sans-serif',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Variables
    |--------------------------------------------------------------------------
    |
    | These variables will be available by default in all email templates.
    | You can add your own variables here.
    |
    */
    'default_variables' => [
        'name' => 'Customer Name',
        'email' => 'customer@example.com',
        'company' => 'Your Company Name',
        'website' => 'https://example.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Styles
    |--------------------------------------------------------------------------
    |
    | These are the styles that users can customize in the email templates.
    | You can add more styles here.
    |
    */
    'available_styles' => [
        'body' => [
            'font-family' => ['Arial, sans-serif', 'Helvetica, sans-serif', 'Georgia, serif', "'Times New Roman', serif"],
            'font-size' => '14px',
            'color' => '#333333',
            'background-color' => '#ffffff',
            'line-height' => '1.5',
        ],
        'header' => [
            'background-color' => '#f8f9fa',
            'color' => '#333333',
            'padding' => '20px',
            'text-align' => 'center',
        ],
        'footer' => [
            'background-color' => '#f8f9fa',
            'color' => '#666666',
            'padding' => '20px',
            'text-align' => 'center',
            'font-size' => '12px',
        ],
        'button' => [
            'background-color' => '#007bff',
            'color' => '#ffffff',
            'padding' => '10px 20px',
            'text-decoration' => 'none',
            'border-radius' => '5px',
            'display' => 'inline-block',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Layouts
    |--------------------------------------------------------------------------
    |
    | These are the layouts that users can choose from for their email templates.
    | You can add more layouts here.
    |
    */
    'available_layouts' => [
        'default' => 'Default Layout',
        'sidebar' => 'Sidebar Layout',
        'centered' => 'Centered Layout',
        'newsletter' => 'Newsletter Layout',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Testing
    |--------------------------------------------------------------------------
    |
    | Configuration for email testing functionality.
    |
    */
    'testing' => [
        'enabled' => true,
        'default_test_email' => null, // Set to a specific email to use as default for tests
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for template storage.
    |
    */
    'storage' => [
        'disk' => 'local',
        'path' => 'mail-templates',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for image handling in email templates.
    |
    */
    'images' => [
        'max_upload_size' => 2048,
        'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'default_storage' => 'public',
        'storage_path' => 'mail-templates/images',

        'defaults' => [
            'inline' => [
                'shape' => 'rectangle',
                'position' => 'left',
                'width' => '200px',
                'height' => 'auto',
            ],
            'background' => [
                'size' => 'cover',
                'position' => 'center',
                'repeat' => 'no-repeat',
            ]
        ],
        
        'shapes' => [
            'rectangle' => 'Rectangle',
            'rounded' => 'Rounded Corners',
            'circle' => 'Circle',
        ],
        
        'positions' => [
            'left' => 'Left',
            'right' => 'Right',
            'center' => 'Center',
            'top' => 'Top',
            'bottom' => 'Bottom',
        ],
        
        'background_sizes' => [
            'cover' => 'Cover',
            'contain' => 'Contain',
            'auto' => 'Auto',
        ],
        
        'background_repeats' => [
            'no-repeat' => 'No Repeat',
            'repeat' => 'Repeat',
            'repeat-x' => 'Repeat Horizontally',
            'repeat-y' => 'Repeat Vertically',
        ],
    ],
];