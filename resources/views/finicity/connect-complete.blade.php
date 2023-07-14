<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>
            @section("title")
                Otto
            @show
        </title>
        @section('stylesheets')
            @if (config('app.env') === 'local')
                <link href="/css/app.css" rel="stylesheet" type="text/css">
            @else
                <link href="/css/app.{!! config('app.version_hash') !!}.css" rel="stylesheet" type="text/css">
            @endif
        @show
        <link rel="shortcut icon" href="/favicon-32x32.png" type="image/png"/>
    </head>
    <body>
        <div class="w-100 mt-5">
            <img src="/images/logo.png" class="d-block mx-auto img-fluid px-5" />
            <h3 class="text-center mt-3">Your accounts have been successfully connected. Click "Done" to return to the app.</h3>
        </div>
    </body>
</html>
