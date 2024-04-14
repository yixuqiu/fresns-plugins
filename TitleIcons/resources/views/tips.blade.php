@extends('TitleIcons::commons.layout')

@section('content')
    <div aria-live="polite" aria-atomic="true" class="position-fixed top-50 start-50 translate-middle">
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <img src="/static/images/icon.png" width="20px" height="20px" class="rounded me-2" alt="Fresns">
                <strong class="me-auto">Fresns</strong>
                <small>{{ $code }}</small>
            </div>
            <div class="toast-body">
                {{ $message }}
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="/static/js/fresns-callback.js"></script>
    <script>
        const code = {{ $code }};

        if (code == 0) {
            let callbackAction = {
                postMessageKey: '{{ $data["postMessageKey"] ?? "" }}',
                windowClose: true,
                redirectUrl: '',
                dataHandler: '{{ $data["dataHandler"] ?? "" }}'
            };
            let apiData = @json($data['detail'] ?? []);

            // /static/js/fresns-callback.js
            FresnsCallback.send(callbackAction, apiData, 0, 'ok', 2000);
        }
    </script>
@endpush
