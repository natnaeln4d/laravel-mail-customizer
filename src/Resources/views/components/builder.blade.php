@props([
    'templateId' => null,
    // Values used by "Preview email" / "Send test"; each one is also offered as a draggable variable block.
    'variables' => null,
    // CSS height of the builder.
    'height' => '88vh',
    // Theme overrides, merged over config('mail-customizer.theme'), e.g. ['mode' => 'dark', 'accent' => '#7c3aed'].
    'theme' => [],
    // Load the interface font (Inter) from Google Fonts. Set false to use the system font stack.
    'uiFonts' => true,
    // Where vendor:publish put the bundle.
    'script' => null,
])
@php
    $mountId = 'mail-builder-' . \Illuminate\Support\Str::random(6);
    $themeConfig = array_merge((array) config('mail-customizer.theme', []), (array) $theme);
@endphp

{{-- The builder is self-contained: no Vue, axios or CDN needed. It reads the CSRF token from this meta tag. --}}
@once
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ $script ?? asset('vendor/mail-customizer/js/mail-customizer.js') }}"></script>
@endonce

<div id="{{ $mountId }}"
     data-mail-builder
     data-template-id="{{ $templateId }}"
     data-api-base="{{ trim(config('mail-customizer.route_prefix', 'api/mail-customizer'), '/') }}"
     data-height="{{ $height }}"
     data-ui-fonts="{{ $uiFonts ? 'true' : 'false' }}"
     data-theme='@json($themeConfig)'
     data-variables='@json($variables ?? config('mail-customizer.default_variables', []))'
     {{ $attributes }}></div>
