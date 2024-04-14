<!doctype html>
<html lang="{{ App::getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="author" content="Fresns" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fresns Placeholder</title>
    <link rel="stylesheet" href="/static/css/bootstrap.min.css">
</head>

<body>
    <table class="table table-hover">
        <thead>
            <tr class="table-primary">
                <th scope="col">Key</th>
                <th scope="col">Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($params as $key => $value)
                <tr>
                    <th scope="row">{{ $key }}</th>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h5 class="mt-5 ms-2">Headers</h5>
    <table class="table table-hover">
        <thead>
            <tr class="table-primary">
                <th scope="col">Key</th>
                <th scope="col">Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($headers as $key => $value)
                @if ($key == 'deviceInfo')
                    @continue;
                @endif

                <tr>
                    <th scope="row">{{ $key }}</th>
                    <td class="text-wrap text-break">{{ $value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h5 class="mt-5 ms-2">Device Info</h5>
    <table class="table table-hover">
        <thead>
            <tr class="table-primary">
                <th scope="col">Key</th>
                <th scope="col">Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($headers['deviceInfo'] as $key => $value)
                <tr>
                    <th scope="row">{{ $key }}</th>
                    <td class="text-wrap text-break">{{ $value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <script src="/static/js/fresns-callback.js"></script>
    <script>
        let callbackAction = {
            postMessageKey: 'test',
            windowClose: false,
            redirectUrl: '',
            dataHandler: '',
        };

        // /static/js/fresns-callback.js
        FresnsCallback.send(callbackAction);
    </script>
</body>

</html>
