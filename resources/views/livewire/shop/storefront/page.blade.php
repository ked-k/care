<div class="s-wrap" style="padding-bottom:48px">
    <nav class="s-crumbs" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('shop.home', $store->slug) }}" wire:navigate>{{ __('Home') }}</a>
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
        <span>{{ $title }}</span>
    </nav>
    <h1 class="s-page-title">{{ $title }}</h1>

    @if ($deliveryMethods->isNotEmpty())
        <div class="s-panel s-mt s-table-wrap" style="max-width:760px">
            <table class="s-table">
                <thead><tr><th>{{ __('Option') }}</th><th>{{ __('Typical time') }}</th><th class="num">{{ __('Price') }}</th></tr></thead>
                <tbody>
                    @foreach ($deliveryMethods as $dm)
                        <tr>
                            <td><strong>{{ $dm->name }}</strong>@if ($dm->description)<br><span class="s-muted s-small">{{ $dm->description }}</span>@endif</td>
                            <td>{{ $dm->eta() ?? '—' }}</td>
                            <td class="num">
                                {{ (float) $dm->price > 0 ? $store->money($dm->price) : __('Free') }}
                                @if ($dm->free_over !== null && (float) $dm->price > 0)<br><span class="s-muted s-small">{{ __('Free over :amount', ['amount' => $store->money($dm->free_over)]) }}</span>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="s-panel s-panel-pad s-mt s-prose" style="white-space:pre-line">{{ $body }}</div>
</div>
