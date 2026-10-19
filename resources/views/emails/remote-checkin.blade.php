<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $agencyName }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                <tr>
                    <td style="background-color:#92400e; padding:24px 32px;">
                        <span style="color:#ffffff; font-size:18px; font-weight:700; letter-spacing:0.02em;">{{ __('Remote check-in') }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 16px; font-size:15px; color:#1f2937;">
                            {{ __('Hi :name,', ['name' => $recipient->first_name ?: $recipient->name]) }}
                        </p>

                        <p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#374151;">
                            {{ __(':carer checked in for :client outside the expected location, using :agency\'s "allow remote check-in" setting.', [
                                'carer' => $carer->name,
                                'client' => $clientName,
                                'agency' => $agencyName,
                            ]) }}
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fffbeb; border:1px solid #fde68a; border-radius:8px; margin:0 0 24px;">
                            <tr>
                                <td style="padding:16px 20px;">
                                    <p style="margin:0 0 6px; font-size:13px; color:#92400e;">{{ __('Carer') }}</p>
                                    <p style="margin:0 0 14px; font-size:15px; color:#111827;">{{ $carer->name }}</p>

                                    <p style="margin:0 0 6px; font-size:13px; color:#92400e;">{{ __('Service user') }}</p>
                                    <p style="margin:0 0 14px; font-size:15px; color:#111827;">{{ $clientName }}</p>

                                    <p style="margin:0 0 6px; font-size:13px; color:#92400e;">{{ __('Checked in at') }}</p>
                                    <p style="margin:0 0 14px; font-size:15px; color:#111827;">{{ $checkin->checkin_time->format('D, d M Y H:i') }}</p>

                                    @if ($deviationReason)
                                        <p style="margin:0 0 6px; font-size:13px; color:#92400e;">{{ __('Detail') }}</p>
                                        <p style="margin:0; font-size:15px; color:#111827;">{{ $deviationReason }}</p>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0; font-size:13px; line-height:1.6; color:#6b7280;">
                            {{ __('If this is expected (a known address issue, a difficult GPS spot), no action is needed. If it looks wrong, follow up with :carer directly.', ['carer' => $carer->name]) }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
