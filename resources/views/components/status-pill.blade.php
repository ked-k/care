@props(['status' => '', 'label' => null])

@php
    $map = [
        // inventory
        'in-stock' => ['classes' => 'bg-green-500/10 text-green-600', 'dot' => 'bg-green-500', 'text' => 'In Stock'],
        'low'      => ['classes' => 'bg-amber-500/10 text-amber-600', 'dot' => 'bg-amber-500', 'text' => 'Low Stock'],
        'out'      => ['classes' => 'bg-accent-500/10 text-accent-600', 'dot' => 'bg-accent-500', 'text' => 'Out of Stock'],
        // documents / accounting
        'paid'     => ['classes' => 'bg-green-500/10 text-green-600', 'dot' => 'bg-green-500', 'text' => 'Paid'],
        'unpaid'   => ['classes' => 'bg-amber-500/10 text-amber-600', 'dot' => 'bg-amber-500', 'text' => 'Unpaid'],
        'overdue'  => ['classes' => 'bg-accent-500/10 text-accent-600', 'dot' => 'bg-accent-500', 'text' => 'Overdue'],
        'partial'  => ['classes' => 'bg-purple-500/10 text-purple-600', 'dot' => 'bg-purple-500', 'text' => 'Partial'],
        'draft'    => ['classes' => 'bg-gray-100 text-gray-600', 'dot' => 'bg-gray-400', 'text' => 'Draft'],
        'sent'     => ['classes' => 'bg-primary-500/10 text-primary-600', 'dot' => 'bg-primary-500', 'text' => 'Sent'],
        'active'   => ['classes' => 'bg-green-500/10 text-green-600', 'dot' => 'bg-green-500', 'text' => 'Active'],
    ];
    $s = $map[$status] ?? ['classes' => 'bg-gray-100 text-gray-600', 'dot' => 'bg-gray-400', 'text' => ucfirst($status)];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium', $s['classes']]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span>{{ $label ?? __($s['text']) }}
</span>
