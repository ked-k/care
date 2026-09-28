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
                    <td style="background-color:#1e3a5f; padding:24px 32px;">
                        <span style="color:#ffffff; font-size:18px; font-weight:700; letter-spacing:0.02em;">{{ $agencyName }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 16px; font-size:15px; color:#1f2937;">
                            {{ __('Hi :name,', ['name' => $user->first_name ?: $user->name]) }}
                        </p>

                        @if ($serviceUserName)
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#374151;">
                                @if ($plainPassword)
                                    {{ __('An account has been created for you on :agency\'s CareTrust family portal, giving you access to updates for :su.', ['agency' => $agencyName, 'su' => $serviceUserName]) }}
                                @else
                                    {{ __('Your existing CareTrust login now also has access to updates for :su', ['su' => $serviceUserName]) }}{{ $relationship ? ' ('.$relationship.')' : '' }}.
                                @endif
                            </p>
                        @else
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#374151;">
                                {{ __('An account has been created for you on :agency\'s CareTrust system.', ['agency' => $agencyName]) }}
                            </p>
                        @endif

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb; border-radius:8px; margin:0 0 24px;">
                            <tr>
                                <td style="padding:16px 20px;">
                                    <p style="margin:0 0 6px; font-size:13px; color:#6b7280;">{{ __('Login email') }}</p>
                                    <p style="margin:0 0 14px; font-size:15px; font-family:monospace; color:#111827;">{{ $user->email }}</p>
                                    @if ($plainPassword)
                                        <p style="margin:0 0 6px; font-size:13px; color:#6b7280;">{{ __('Temporary password') }}</p>
                                        <p style="margin:0; font-size:15px; font-family:monospace; color:#111827;">{{ $plainPassword }}</p>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                            <tr>
                                <td style="border-radius:8px; background-color:#2e6f8e;">
                                    <a href="{{ $loginUrl }}" style="display:inline-block; padding:12px 28px; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none;">
                                        {{ __('Log in to CareTrust') }}
                                    </a>
                                </td>
                            </tr>
                        </table>

                        @if ($plainPassword)
                            <p style="margin:0 0 8px; font-size:13px; line-height:1.6; color:#6b7280;">
                                {{ __('This password was generated automatically and is only shown once — we recommend signing in and updating it from your profile.') }}
                            </p>
                        @endif

                        <p style="margin:24px 0 0; font-size:13px; line-height:1.6; color:#9ca3af;">
                            {{ __('If you weren\'t expecting this email, please contact :agency directly.', ['agency' => $agencyName]) }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
