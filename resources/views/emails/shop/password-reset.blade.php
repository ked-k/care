<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ __('Reset your password') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#eef3f6; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#14283f;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef3f6; padding:28px 12px;">
<tr><td align="center">
<table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px; width:100%; background:#ffffff; border-radius:12px; border:1px solid #d5dee5;">
    <tr><td style="background-color:{{ $store->brand_color }}; padding:20px 28px; border-radius:12px 12px 0 0;"><span style="color:#fff; font-size:18px; font-weight:700;">{{ $store->name }}</span></td></tr>
    <tr><td style="padding:28px; font-size:15px; line-height:1.6;">
        <p style="margin:0 0 12px;">{{ __('Hi :name,', ['name' => $customer->first_name]) }}</p>
        <p style="margin:0 0 20px; color:#3d5068;">{{ __('Someone asked to reset the password for your :store account. Use the button below to choose a new one. The link works for 60 minutes.', ['store' => $store->name]) }}</p>
        <table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="border-radius:8px; background:{{ $store->accent_color }};">
            <a href="{{ $url }}" style="display:inline-block; padding:12px 24px; color:#fff; font-weight:700; text-decoration:none;">{{ __('Choose a new password') }}</a>
        </td></tr></table>
        <p style="margin:20px 0 0; font-size:13px; color:#64748b;">{{ __('If you didn’t ask for this, you can ignore this email — your password won’t change.') }}</p>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
