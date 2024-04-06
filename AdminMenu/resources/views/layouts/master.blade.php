<!doctype html>
<html lang="{{ $langTag }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="author" content="Fresns" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Admin Menu</title>
        <link rel="stylesheet" href="/static/css/bootstrap.min.css">
        <link rel="stylesheet" href="/static/css/bootstrap-icons.min.css">
        <link rel="stylesheet" href="/static/css/select2.min.css">
        <link rel="stylesheet" href="/static/css/select2-bootstrap-5-theme.min.css">
        <style>
            .fs-7 {
                font-size: 0.9rem;
            }
        </style>
        @stack('css')
    </head>

    <body>
        <main class="m-3" id="main">
            @yield('content')
        </main>

        <div class="fresns-tips"></div>

        <script src="/static/js/bootstrap.bundle.min.js"></script>
        <script src="/static/js/jquery.min.js"></script>
        <script src="//res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
        <script src="/static/js/fresns-callback.js"></script>
        <script>
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // set timeout toast hide
            const setTimeoutToastHide = () => {
                $('.toast.show').each((k, v) => {
                    setTimeout(function () {
                        $(v).hide();
                    }, 1500);
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

            // api-request-form
            $('.api-request-form').submit(function (e) {
                e.preventDefault();
                let form = $(this);
                btn = $(this).find('button[type="submit"]');

                btn.prop('disabled', true);
                if (btn.children('.spinner-border').length == 0) {
                    btn.prepend('<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span> ');
                }
                btn.children('.spinner-border').removeClass('d-none');

                const actionUrl = form.attr('action'),
                    methodType = form.attr('method') || 'POST',
                    data = form.serialize();

                $.ajax({
                    url: actionUrl,
                    type: methodType,
                    data: data,
                    success: function (res) {
                        if (res.code != 0) {
                            tips(res.message, true);
                            return;
                        }

                        let deleteConfirmModal = document.getElementById('deleteConfirmModal');
                        if (deleteConfirmModal) {
                            new bootstrap.Modal(deleteConfirmModal).hide();
                        }

                        let statusConfirmModal = document.getElementById('statusConfirmModal');
                        if (statusConfirmModal) {
                            new bootstrap.Modal(statusConfirmModal).hide();
                        }

                        let groupSelectModal = document.getElementById('groupSelectModal');
                        if (groupSelectModal) {
                            new bootstrap.Modal(groupSelectModal).hide();
                        }

                        tips(res.message, false);
                        $('#main').addClass('d-none');

                        fresnsCallbackSend('remove', res.data);
                    },
                    complete: function (e) {
                        btn.prop('disabled', false);
                        btn.find('.spinner-border').remove();
                    },
                });
            });

            // postMessage
            function fresnsCallbackSend(dataHandler, detail = []) {
                let callbackAction = {
                    postMessageKey: '{{ $postMessageKey }}',
                    windowClose: true,
                    redirectUrl: '',
                    dataHandler: dataHandler,
                };

                // /static/js/fresns-callback.js
                FresnsCallback.send(callbackAction, detail);
            }
        </script>
        @stack('script')
    </body>
</html>
