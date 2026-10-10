{{-- src/Resources/views/builder.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Email Template Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.7.9/dist/axios.min.js"></script>
    <style>
        .builder-container {
            margin: 20px;
        }
    </style>
</head>
<body>
    <div class="builder-container">
        {{-- The builder has its own Save / Preview / Send Test buttons. --}}
        <div id="mail-builder-app"
             data-mail-builder
             data-template-id="{{ $templateId ?? '' }}"
             data-variables='@json($variables ?? new stdClass)'></div>
    </div>

    <script src="{{ asset('vendor/mail-customizer/js/mail-customizer.js') }}"></script>
    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] =
            document.querySelector('meta[name="csrf-token"]').content;
    </script>
</body>
</html>
