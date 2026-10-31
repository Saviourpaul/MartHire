<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $subject }}</title></head>
<body style="margin:0;background:#f4f6f5;color:#153d31;font-family:Arial,sans-serif;line-height:1.6">
    <main style="max-width:600px;margin:32px auto;padding:32px;background:#fff;border:1px solid #e0e7e3;border-radius:12px">
        <p style="font-size:13px;color:#65766e">{{ $organizationName }}</p><h1 style="font-size:22px">{{ $subject }}</h1>
        <div style="white-space:pre-wrap">{{ $body }}</div>
        @if ($confirmUrl && $declineUrl)
            <p style="margin-top:28px"><a href="{{ $confirmUrl }}" style="display:inline-block;padding:10px 18px;background:#153d31;color:#fff;text-decoration:none;border-radius:6px">{{ __('Confirm attendance') }}</a> <a href="{{ $declineUrl }}" style="display:inline-block;padding:10px 18px;color:#153d31;border:1px solid #ccd8d1;text-decoration:none;border-radius:6px">{{ __('Decline invitation') }}</a></p>
            <p style="font-size:12px;color:#65766e">{{ __('These personal response links expire when this invitation changes or its response window closes.') }}</p>
        @endif
    </main>
</body></html>
