<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>
            @section("title")
                Otto App
            @show
        </title>
        <link rel="shortcut icon" href="/favicon-32x32.png" type="image/png"/>
        @section('stylesheets')
            @if (config('app.env') === 'local')
                <link href="/css/app.css" rel="stylesheet" type="text/css">
            @else
                <link href="/css/app.{!! config('app.version_hash') !!}.css" rel="stylesheet" type="text/css">
            @endif
        @show

        @section('javascript-head')
            <script type="text/javascript">
                // redirect to browser support page for Internet Explorer users
                var isInternetExplorer = window.navigator.userAgent.indexOf('MSIE ') >= 0 || window.navigator.userAgent.indexOf('Trident/') >= 0;
                if(isInternetExplorer){
                    window.location = 'browser-support-list.html';
                }
            </script>
            <script src="https://connect2.finicity.com/assets/sdk/finicity-connect.min.js"></script>
            <script>
                window.appEnv = window.appEnv || {};
                window.appEnv.baseURL = location.origin;
                window.appEnv.clientPlatform = 'web';
                window.appEnv.initialized = true;
                window.appEnv.ga_id = 'UA-142524823-1';
                window.appEnv.userflow_token = "{!! config('services.userflow.token') !!}";
                window.appEnv.recaptchaDisabled = "{{ config('recaptcha.disabled') }}";
                window.appEnv.recaptchaSiteKey = "{{ config('recaptcha.siteKey') }}";
            </script>
            @if (config('app.env') !== 'local')
                <!-- Global site tag (gtag.js) - Google Analytics -->
                <script async src="https://www.googletagmanager.com/gtag/js?id=UA-142524823-1"></script>
                <script>
                    window.dataLayer = window.dataLayer || [];
                    function gtag(){
                        dataLayer.push(arguments);
                    }
                    gtag('js', new Date());
                    gtag('config', window.appEnv.ga_id);
                </script>
            @endif
        @show
    </head>
    <body>
        <div id="vueApp">
            <div class="vueOnePage">
                <router-view></router-view>
            </div>
            <div class="font_preload">
                <span class="fa"></span>
            </div>
        </div>

        @section('javascript-footer')


            @if (config('app.env') === 'local')
                <script type="text/javascript" src="/js/app.js"></script>
                <script id="__bs_script__">//<![CDATA[
                    document.write("<script async src='http://HOST:3000/browser-sync/browser-sync-client.js?v=2.18.6'><\/script>".replace("HOST", location.hostname));
                    //]]>
                </script>
            @else
                <script type="text/javascript" src="/js/app.{!! config('app.version_hash') !!}.js"></script>
            @endif
        @show
    </body>
</html>
