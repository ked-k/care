@props(['active' => 'general'])

@php
    $sections = [
        ['key' => 'general', 'label' => __('General'), 'icon' => 'ik ik-sliders', 'url' => url('settings')],
        ['key' => 'company', 'label' => __('Company'), 'icon' => 'ik ik-briefcase', 'url' => url('settings/company')],
        ['key' => 'localization', 'label' => __('Localization'), 'icon' => 'ik ik-globe', 'url' => url('settings/localization')],
        ['key' => 'notifications', 'label' => __('Notifications'), 'icon' => 'ik ik-bell', 'url' => url('settings/notifications')],
        ['key' => 'appearance', 'label' => __('Appearance'), 'icon' => 'ik ik-droplet', 'url' => url('settings/appearance')],
        ['key' => 'security', 'label' => __('Security'), 'icon' => 'ik ik-shield', 'url' => url('settings/security')],
    ];
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-[224px_minmax(0,1fr)]">
    <aside class="lg:sticky lg:top-20 lg:self-start">
        <nav class="space-y-1" aria-label="{{ __('Settings sections') }}">
            @foreach ($sections as $s)
                <a href="{{ $s['url'] }}" @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        'bg-primary-50 text-primary-700' => $active === $s['key'],
                        'text-gray-600 hover:bg-gray-100' => $active !== $s['key'],
                    ])>
                    <i class="{{ $s['icon'] }} {{ $active === $s['key'] ? 'text-primary-600' : 'text-gray-400' }}"></i>
                    {{ $s['label'] }}
                </a>
            @endforeach
        </nav>
    </aside>
    <div class="min-w-0 space-y-6">{{ $slot }}</div>
</div>
