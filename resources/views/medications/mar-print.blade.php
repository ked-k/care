<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>MAR Chart — {{ $serviceUser->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1f2937; padding: 24px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .subtitle { color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; text-transform: uppercase; font-size: 10px; }
        td.center { text-align: center; }
        .med-name { font-weight: bold; }
        .med-meta { color: #6b7280; font-size: 10px; }
        @media print { a.no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <a href="#" class="no-print" onclick="window.print(); return false;">Print</a>
    <h1>Medication Administration Record</h1>
    <p class="subtitle">{{ $serviceUser->name }} — Week of {{ \Carbon\Carbon::parse($weekStart)->format('d M Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Medication</th>
                @foreach ($days as $date => $label)
                    <th class="center">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($scheduledMeds as $med)
                <tr>
                    <td>
                        <div class="med-name">{{ $med['name'] }}</div>
                        <div class="med-meta">{{ $med['dosage'] }} · {{ ucfirst($med['route']) }} · {{ $med['time'] }}</div>
                    </td>
                    @foreach ($days as $date => $label)
                        @php $cell = $grid[$med['id']][$date] ?? ['state' => 'n/a']; @endphp
                        <td class="center">
                            {{ $cell['administration'] ? strtoupper(substr($cell['state'], 0, 1)) : ($cell['state'] === 'n/a' ? '—' : '') }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="8">No scheduled medications.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="med-meta">G = Given · P = Prompted · R = Refused · M = Missed · blank = not yet recorded/due</p>

    @if (! empty($prnLogsThisWeek))
        <h3>PRN Doses This Week</h3>
        <table>
            <thead><tr><th>Time</th><th>Status</th><th>Recorded By</th></tr></thead>
            <tbody>
                @foreach ($prnLogsThisWeek as $log)
                    <tr>
                        <td>{{ $log['actual_time'] ?? $log['scheduled_time'] }}</td>
                        <td>{{ ucfirst($log['status']) }}</td>
                        <td>{{ $log['administered_by'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
