<!doctype html>
<html lang="{{ $langTag }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="author" content="Fresns" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>S3 Storage</title>
        <link rel="stylesheet" href="/static/css/bootstrap.min.css">
        <link rel="stylesheet" href="/static/css/bootstrap-icons.min.css">
        @stack('css')
    </head>

    <body>
        <main class="m-3" id="main">
            @yield('content')
        </main>

        <div class="fresns-tips"></div>

        <script src="/static/js/bootstrap.bundle.min.js"></script>
        <script src="/static/js/jquery.min.js"></script>
        <script src="/static/js/fresns-callback.js"></script>
        <script>
            /* fresns token */
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // submit button
            $(document).on('submit', 'form', function () {
                var btn = $(this).find('button[type="submit"]');

                btn.prop('disabled', true);
                if (btn.children('.spinner-border').length == 0) {
                    btn.prepend('<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span> ');
                }
                btn.children('.spinner-border').removeClass('d-none');
            });

            // set timeout toast hide
            const setTimeoutToastHide = () => {
                $('.toast.show').each((k, v) => {
                    setTimeout(function () {
                        $(v).hide();
                    }, 2000);
                });
            };

            // tips
            window.tips = function (message, autohide = false) {
                let html = `<div aria-live="polite" aria-atomic="true" class="position-fixed top-50 start-50 translate-middle" style="z-index:2048">
                    <div class="toast align-items-center text-bg-primary border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">${message}</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                </div>`;

                $('.fresns-tips').prepend(html);

                if (autohide) {
                    setTimeoutToastHide();
                }
            };
        </script>
        @stack('script')
    </body>
</html>
