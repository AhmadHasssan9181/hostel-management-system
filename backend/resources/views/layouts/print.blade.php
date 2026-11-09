<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('doc-title', 'Document') · {{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .print-page {
                width: auto !important;
                max-width: none !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: 0 !important;
            }

            .print-page + .print-page {
                break-before: page;
                page-break-before: always;
            }

            .voucher-copy {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-[#eceae4] font-sans text-ink antialiased">
    <div class="no-print sticky top-0 z-10 border-b border-line bg-surface/90 backdrop-blur">
        <div class="mx-auto flex w-full max-w-[210mm] items-center justify-between gap-3 px-4 py-3">
            <div class="flex min-w-0 items-center gap-2">
                <a href="{{ url()->previous() }}" class="btn btn-ghost btn-sm">
                    <x-icon name="arrow-right" class="h-3.5 w-3.5 rotate-180" />
                    Back
                </a>
                <p class="truncate text-sm font-semibold text-ink">@yield('doc-title', 'Document')</p>
            </div>
            <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">
                <x-icon name="clipboard" class="h-3.5 w-3.5" />
                Print / Save PDF
            </button>
        </div>
    </div>

    <main class="px-3 py-6 print:p-0">
        @yield('content')
    </main>
</body>
</html>
