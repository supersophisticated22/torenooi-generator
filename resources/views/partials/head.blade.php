<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Toernooigenerator') : config('app.name', 'Toernooigenerator') }}
</title>

<link rel="icon" href="/cropped-favicon-32x32.gif" type="image/gif" sizes="32x32">
<link rel="apple-touch-icon" href="/cropped-favicon-32x32.gif">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Toernooigenerator') : config('app.name', 'Toernooigenerator') }}">
<meta property="og:site_name" content="{{ config('app.name', 'Toernooigenerator') }}">
<meta property="og:image" content="{{ url('/cropped-favicon-32x32.gif') }}">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Toernooigenerator') : config('app.name', 'Toernooigenerator') }}">
<meta name="twitter:image" content="{{ url('/cropped-favicon-32x32.gif') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
