<!doctype html>
<html @yield('htmlProperties','')>
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="@yield('pageFavIco','')" />
    <meta name="viewport" content="width=device-width, minimum-scale=0.5, maximum-scale=5, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('pageTitle','')</title>
    <meta name="keywords" content="@yield('pageKeywords','')">
    <meta name="description" content="@yield('pageDescription','')">
    @if(0)<link rel="stylesheet" href="@asset('asset/vendor/iconfont/iconfont.css')">@endif
    <link rel="stylesheet" href="//at.alicdn.com/t/font_2778046_y8el9mrh46b.css">
    <link rel="stylesheet" href="@asset('asset/font-awesome/css/font-awesome.min.css')">
    <script>
        window.__msCDN = "{{\ModStart\Core\Assets\AssetsUtil::cdn()}}";
        window.__msRoot = "{{modstart_web_url()}}";
        window.__dataConfig = {
            chunkSize: {{\ModStart\Core\Util\EnvUtil::env('uploadMaxSize')}},
            category: {!! json_encode(config('data.upload')) !!}
        };
    </script>
    <script src="@asset('asset/vendor/jquery.js')"></script>
    <script src="@asset('asset/common/base.js')"></script>
    <script src="@asset('asset/layui/layui.js')"></script>
    <link rel="stylesheet" href="@asset('asset/theme/default/base.css')">
    <link rel="stylesheet" href="@asset('asset/layui/css/layui.css')">
    <link rel="stylesheet" href="@asset('asset/theme/default/style.css')">
    {!! \ModStart\ModStart::css() !!}
    {!! \ModStart\ModStart::style() !!}
    @section('headAppend')@show
</head>
<body>
    @section('body')@show
    {!! \ModStart\ModStart::js() !!}
    {!! \ModStart\ModStart::script() !!}
    @section('bodyAppend')@show
</body>
</html>
