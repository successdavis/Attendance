<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Attendance Range Report — {{ $summary['from'] }} to {{ $summary['to'] }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

    .page-header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 16px; }
    .page-header h1 { font-size: 18px; font-weight: bold; color: #4f46e5; }
    .page-header p  { font-size: 11px; color: #555; margin-top: 2px; }

    .summary-bar { display: table; width: 100%; margin-bottom: 16px; border-collapse: separate; border-spacing: 6px 0; }
    .stat-cell { display: table-cell; border: 1px solid #e5e7eb; border-radius: 6px; padding: 7px 12px; text-align: center; background: #f9fafb; }
    .stat-cell .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; }
    .stat-cell .value { font-size: 18px; font-weight: bold; color: #111; margin-top: 1px; }
    .stat-cell.green .value { color: #16a34a; }

    .day-header { background: #4f46e5; color: #fff; padding: 6px 10px; font-weight: bold; font-size: 11px;
                  margin-top: 14px; border-radius: 4px 4px 0 0; }
    .day-header span { font-weight: normal; font-size: 10px; opacity: 0.8; margin-left: 10px; }

    table.report { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    table.report thead tr { background: #e0e7ff; }
    table.report thead th { padding: 5px 10px; text-align: left; font-size: 9.5px; font-weight: bold;
                            text-transform: uppercase; letter-spacing: 0.04em; color: #3730a3; }
    table.report tbody tr { border-bottom: 1px solid #f3f4f6; }
    table.report tbody tr:nth-child(even) { background: #fafafa; }
    table.report tbody td { padding: 5px 10px; vertical-align: top; }

    .time-in  { color: #16a34a; font-weight: bold; font-family: DejaVu Sans Mono, monospace; }
    .time-out { color: #4b5563; font-weight: bold; font-family: DejaVu Sans Mono, monospace; }
    .dash     { color: #9ca3af; }

    .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 9px; font-weight: bold; }
    .badge-rfid        { background: #dbeafe; color: #1d4ed8; }
    .badge-fingerprint { background: #ede9fe; color: #7c3aed; }
    .badge-face        { background: #fce7f3; color: #be185d; }
    .badge-manual      { background: #fef3c7; color: #92400e; }

    .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #e5e7eb;
              font-size: 9px; color: #9ca3af; text-align: right; }
    .no-records { text-align: center; padding: 30px; color: #9ca3af; font-style: italic; }
</style>
</head>
<body>

{{-- Header --}}
<div class="page-header">
    <h1>Attendance Range Report</h1>
    <p>
        {{ \Carbon\Carbon::parse($summary['from'])->format('d M Y') }}
        &nbsp;→&nbsp;
        {{ \Carbon\Carbon::parse($summary['to'])->format('d M Y') }}
        &nbsp;·&nbsp;
        Generated {{ now()->format('d M Y \a\t H:i') }}
    </p>
</div>

{{-- Summary --}}
<div class="summary-bar">
    <div class="stat-cell">
        <div class="label">Days</div>
        <div class="value">{{ $summary['days'] }}</div>
    </div>
    <div class="stat-cell green">
        <div class="label">Unique People</div>
        <div class="value">{{ $summary['present'] }}</div>
    </div>
    <div class="stat-cell">
        <div class="label">Total Scans</div>
        <div class="value">{{ $summary['total'] }}</div>
    </div>
</div>

{{-- Day groups --}}
@forelse($dayGroups as $day)
    <div class="day-header">
        {{ $day['date'] }}
        <span>{{ count($day['rows']) }} people</span>
    </div>

    <table class="report">
        <thead>
            <tr>
                <th>Name</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Duration</th>
                <th>Method</th>
                <th>Device</th>
                <th>Location</th>
            </tr>
        </thead>
        <tbody>
            @foreach($day['rows'] as $row)
            <tr>
                <td style="font-weight: {{ $row['name'] ? 'bold' : 'normal' }};">{{ $row['name'] ?: '' }}</td>
                <td class="time-in">{{ $row['check_in'] }}</td>
                <td class="time-out">{{ $row['check_out'] }}</td>
                <td>{{ $row['duration'] }}</td>
                <td>
                    @if($row['method'] !== '—')
                        <span class="badge badge-{{ $row['manual'] ? 'manual' : strtolower($row['method']) }}">
                            {{ $row['method'] }}
                        </span>
                    @else
                        <span class="dash">—</span>
                    @endif
                </td>
                <td>{{ $row['device'] }}</td>
                <td>{{ $row['location'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@empty
<div class="no-records">No attendance records in this date range.</div>
@endforelse

<div class="footer">Attendance Management System &nbsp;·&nbsp; {{ config('app.name') }}</div>

</body>
</html>
