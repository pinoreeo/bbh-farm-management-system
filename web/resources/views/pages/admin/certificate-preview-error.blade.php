<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css'])
</head>
<body class="certificate-preview-error-page">
    <div class="certificate-preview-error-alert">
        <h1 class="certificate-preview-error-title">Dokumen belum siap</h1>
        <p class="certificate-preview-error-message">{{ $message }}</p>
    </div>
</body>
</html>
