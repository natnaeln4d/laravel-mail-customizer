{{-- src/Resources/views/editor.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mail Customizer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.7.9/dist/axios.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <div id="mail-customizer-app"
             data-mail-customizer-vue
             data-template-id="{{ $templateId ?? '' }}"
             data-variables='@json($variables ?? ['name' => 'John Doe', 'email' => 'john@example.com'])'></div>
    </div>

    <script src="{{ asset('vendor/mail-customizer/js/mail-customizer.js') }}"></script>
    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] =
            document.querySelector('meta[name="csrf-token"]').content;
    </script>
</body>
</html>
