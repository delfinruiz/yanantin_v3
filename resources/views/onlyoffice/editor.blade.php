{{-- resources/views/onlyoffice/editor.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editor de Documento</title>
    @php
        $defaultFaviconUrl = tenant()?->faviconUrl() ?: '/favicon.ico';

        $fileType = strtolower((string) data_get($config, 'document.fileType', ''));

        $iconFile = match ($fileType) {
            'doc', 'docx' => 'word.png',
            'xls', 'xlsx' => 'excel.png',
            'ppt', 'pptx' => 'powerpoint.png',
            'pdf' => 'pdf.png',
            default => null,
        };

        $iconPublicRelativePath = $iconFile ? "asset/images/file-icons/{$iconFile}" : null;
        $iconPublicAbsolutePath = $iconPublicRelativePath ? public_path($iconPublicRelativePath) : null;

        $faviconUrl = $defaultFaviconUrl;
        $faviconMime = null;
        if ($iconPublicAbsolutePath && is_file($iconPublicAbsolutePath)) {
            $faviconUrl = '/'.$iconPublicRelativePath.'?v='.filemtime($iconPublicAbsolutePath);
            $faviconMime = 'image/png';
        } else {
            $faviconUrl = $defaultFaviconUrl.'?t='.urlencode($fileType ?: 'default');
        }
    @endphp
    <link rel="icon" @if($faviconMime)type="{{ $faviconMime }}"@endif href="{{ $faviconUrl }}">
    <script src="https://onlyoffice.cahilt.pro/web-apps/apps/api/documents/api.js"></script>
    <style>
        html, body, #editor {
            margin: 0;
            padding: 0;
            height: 100%;
        }
    </style>
</head>
<body>
    <div id="editor"></div>

<script> const docEditor = new DocsAPI.DocEditor("editor", <?= json_encode($config) ?>); </script>
</body>
</html>
