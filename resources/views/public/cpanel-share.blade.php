<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $name }} - Archivo Compartido</title>
    @php
        $faviconUrl = tenant()?->faviconUrl() ?: '/favicon.ico';
    @endphp
    <link rel="icon" href="{{ $faviconUrl }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        <nav class="bg-white shadow-sm">
            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <span class="text-xl font-bold text-purple-600">{{ tenant()?->name ?? config('app.name') }}</span>
                        <span class="ml-2 text-gray-500">| Archivo Compartido</span>
                    </div>
                </div>
            </div>
        </nav>

        <main class="flex-grow container mx-auto px-4 py-8 w-full">
            <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <h1 class="text-lg font-semibold text-gray-800">{{ $name }}</h1>
                    </div>

                    <div class="flex space-x-2">
                        @if($isOffice)
                            <a href="{{ route('public.cpanel.onlyoffice', ['token' => $link->token]) }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                Abrir
                            </a>
                        @endif

                        <a href="{{ route('public.cpanel.download', ['token' => $link->token]) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Descargar
                        </a>
                    </div>
                </div>

                <div class="p-6">
                    <div class="text-center py-8">
                        <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $name }}</h3>
                        @if($size > 0)
                            <p class="mt-1 text-sm text-gray-500">{{ number_format($size / 1024, 2) }} KB</p>
                        @endif
                        <div class="mt-6">
                            <a href="{{ route('public.cpanel.download', ['token' => $link->token]) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Descargar Archivo
                            </a>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <p class="text-xs text-gray-500 text-center">
                        Compartido por {{ $ownerName }} · Expira: {{ $expiresAt }}
                    </p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
