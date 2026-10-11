{{-- src/Resources/views/builder.blade.php : full-page host for the visual builder --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Email Template Builder</title>
    <style>
        html, body { margin: 0; height: 100%; background: #080f1d; }
        .mc-page { height: 100%; padding: 10px; box-sizing: border-box; }
    </style>
</head>
<body>
    <div class="mc-page">
        <div id="mail-builder-app"
             data-mail-builder
             data-template-id="{{ $templateId ?? '' }}"
             data-api-base="{{ trim(config('mail-customizer.route_prefix', 'api/mail-customizer'), '/') }}"
             data-height="calc(100vh - 20px)"
             data-theme='@json((array) config('mail-customizer.theme', []))'
             data-variables='@json($variables ?? new stdClass)'></div>
    </div>

    <script src="{{ asset('vendor/mail-customizer/js/mail-customizer.js') }}"></script>
</body>
</html>
