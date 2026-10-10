@props([
    'templateId' => null,
    'variables' => null,
    // Load Vue 2 + axios from a CDN if your page doesn't already provide them.
    'cdn' => true,
    // Where vendor:publish put the bundle.
    'script' => null,
])
@php($mountId = 'mail-builder-' . \Illuminate\Support\Str::random(6))

@once
    @if ($cdn)
        <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/axios@1.7.9/dist/axios.min.js"></script>
    @endif
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            if (window.axios) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
                axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            }
        });
    </script>
    <script src="{{ $script ?? asset('vendor/mail-customizer/js/mail-customizer.js') }}"></script>
@endonce

<div id="{{ $mountId }}"
     data-mail-builder
     data-template-id="{{ $templateId }}"
     data-variables='@json($variables ?? config('mail-customizer.default_variables', []))'
     {{ $attributes }}></div>
