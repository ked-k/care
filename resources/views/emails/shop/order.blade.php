@php
    $ink = $store->brand_color ?: '#14283F';
    $action = $store->accent_color ?: '#3B5BDB';
    $intro = match ($kind) {
        'placed' => $order->payment_method === 'bank_transfer'
            ? __('Thanks for your order. Please pay by bank transfer using the details below — we’ll dispatch as soon as the payment arrives.')
            : __('Thanks for your order. You’ll pay when it’s delivered or collected.'),
        'confirmed' => __('Thanks — your payment has been received and we’re getting your order ready.'),
        'dispatched' => __('Good news: your order is on its way.'),
        'ready' => __('Your order is packed and ready to collect.'),
        'cancelled' => __('Your order has been cancelled. If you’ve already paid, a refund will follow.'),
        'staff_new_order' => __('A new order has been placed on :store.', ['store' => $store->name]),
        default => '',
    };
    $name = $order->billing_address['first_name'] ?? \Illuminate\Support\Str::before($order->billing_address['name'] ?? '', ' ');
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $heading }}</title>
</head>
<body style="margin:0; padding:0; background-color:#eef3f6; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#14283f;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef3f6; padding:28px 12px;">
<tr><td align="center">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #d5dee5;">
    <tr>
        <td style="background-color:{{ $ink }}; padding:22px 28px;">
            <span style="color:#ffffff; font-size:18px; font-weight:700;">{{ $store->name }}</span>
        </td>
    </tr>
    <tr>
        <td style="padding:28px;">
            <h1 style="margin:0 0 10px; font-size:22px; line-height:1.25;">{{ $heading }}</h1>
            @if ($kind !== 'staff_new_order' && $name)
                <p style="margin:0 0 12px; font-size:15px;">{{ __('Hi :name,', ['name' => $name]) }}</p>
            @endif
            <p style="margin:0 0 20px; font-size:15px; line-height:1.6; color:#3d5068;">{{ $intro }}</p>

            @if ($kind === 'dispatched' && $order->tracking_number)
                <p style="margin:0 0 20px; font-size:15px; background:#e8edfc; padding:12px 14px; border-radius:8px;">
                    {{ __('Tracking') }}: <strong>{{ $order->carrier ? $order->carrier.' — ' : '' }}{{ $order->tracking_number }}</strong>
                </p>
            @endif

            @if (! empty($bankDetails))
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f9fb; border:1px solid #d5dee5; border-radius:8px; margin:0 0 20px;">
                    <tr><td style="padding:14px 16px; font-size:15px; line-height:1.7;">
                        @foreach ($bankDetails as $label => $value)
                            <span style="color:#64748b;">{{ $label }}:</span> <strong>{{ $value }}</strong><br>
                        @endforeach
                        <span style="color:#64748b;">{{ __('Reference') }}:</span> <strong>{{ $order->number }}</strong><br>
                        <span style="color:#64748b;">{{ __('Amount') }}:</span> <strong>{{ $store->money($order->total) }}</strong>
                        @if ($bankInstructions)<br><span style="color:#64748b; font-size:14px;">{{ $bankInstructions }}</span>@endif
                    </td></tr>
                </table>
            @endif

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; border-collapse:collapse;">
                <tr>
                    <td style="padding:8px 0; border-bottom:2px solid {{ $ink }}; font-weight:700;">{{ __('Order :n', ['n' => $order->number]) }}</td>
                    <td style="padding:8px 0; border-bottom:2px solid {{ $ink }}; text-align:right; color:#64748b;">{{ $order->created_at->format('j M Y, g:ia') }}</td>
                </tr>
                @foreach ($order->items as $item)
                    <tr>
                        <td style="padding:10px 0; border-bottom:1px solid #e6ecf0;">{{ $item->qty }} × {{ $item->name }}@if ($item->sku)<br><span style="color:#64748b; font-size:12px;">{{ $item->sku }}</span>@endif</td>
                        <td style="padding:10px 0; border-bottom:1px solid #e6ecf0; text-align:right; white-space:nowrap;">{{ $store->money($item->line_total) }}</td>
                    </tr>
                @endforeach
                @if ($order->discount_total > 0)
                    <tr><td style="padding:6px 0; color:#2f7d4f;">{{ __('Discount') }} {{ $order->discount_code }}</td><td style="padding:6px 0; text-align:right; color:#2f7d4f;">−{{ $store->money($order->discount_total) }}</td></tr>
                @endif
                <tr><td style="padding:6px 0;">{{ $order->delivery_name ?: __('Delivery') }}</td><td style="padding:6px 0; text-align:right;">{{ $order->delivery_price > 0 ? $store->money($order->delivery_price) : __('Free') }}</td></tr>
                @unless ($order->prices_include_vat)
                    <tr><td style="padding:6px 0;">{{ __('VAT') }}</td><td style="padding:6px 0; text-align:right;">{{ $store->money($order->vat_total) }}</td></tr>
                @endunless
                <tr>
                    <td style="padding:10px 0; border-top:2px solid {{ $ink }}; font-weight:700; font-size:16px;">{{ __('Total') }}</td>
                    <td style="padding:10px 0; border-top:2px solid {{ $ink }}; text-align:right; font-weight:700; font-size:16px;">{{ $store->money($order->total) }}</td>
                </tr>
                @if ($order->prices_include_vat && $order->vat_total > 0)
                    <tr><td colspan="2" style="text-align:right; color:#64748b; font-size:12px;">{{ __('Includes :amount VAT', ['amount' => $store->money($order->vat_total)]) }}</td></tr>
                @endif
            </table>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 0; font-size:14px; line-height:1.55;">
                <tr>
                    <td valign="top" width="50%" style="padding-right:10px;">
                        <strong>{{ $order->shipping_address ? __('Delivering to') : __('Collecting from') }}</strong><br>
                        @if ($order->shipping_address)
                            @foreach (['name', 'company', 'line1', 'line2', 'city', 'postcode'] as $f)
                                @if (! empty($order->shipping_address[$f])){{ $order->shipping_address[$f] }}<br>@endif
                            @endforeach
                        @else
                            {!! nl2br(e($store->address)) !!}
                        @endif
                    </td>
                    <td valign="top" width="50%">
                        <strong>{{ __('Payment') }}</strong><br>
                        {{ $order->paymentMethodLabel() }}<br>
                        <span style="color:#64748b;">{{ $order->paymentStatusLabel() }}</span>
                        @if ($kind === 'staff_new_order')
                            <br><br><strong>{{ __('Customer') }}</strong><br>{{ $order->email }}@if ($order->phone)<br>{{ $order->phone }}@endif
                        @endif
                    </td>
                </tr>
            </table>

            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0 0;">
                <tr><td style="border-radius:8px; background-color:{{ $action }};">
                    <a href="{{ $orderUrl }}" style="display:inline-block; padding:12px 24px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none;">
                        {{ $kind === 'staff_new_order' ? __('Open in CareAffinity') : __('View your order') }}
                    </a>
                </td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 28px; background:#f6f9fb; border-top:1px solid #e6ecf0; font-size:12px; line-height:1.6; color:#64748b;">
            {{ $store->name }}@if ($store->address) — {{ str_replace("\n", ', ', $store->address) }}@endif<br>
            @if ($store->contact_email){{ __('Questions? Reply to this email or write to :email.', ['email' => $store->contact_email]) }}@endif
            @if ($store->company_number)<br>{{ __('Company no. :n', ['n' => $store->company_number]) }}@endif
            @if ($store->vat_number) — {{ __('VAT no. :n', ['n' => $store->vat_number]) }}@endif
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
