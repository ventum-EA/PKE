<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Šaha analīzes un mācību platforma. Augšupielādē PGN, analizē savas kļūdas un uzlabo spēli ar interaktīviem treniņiem.">

    <title>Šaha Analīzes Platforma</title>

    {{-- PWA manifest --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#f59e0b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Šahs">
    <link rel="icon" type="image/svg+xml" href="/icon.svg">
    <link rel="apple-touch-icon" href="/icon.svg">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0c0c0e] theme-bg-app transition-colors duration-300">
    <div id="app"></div>
</body>
</html>
