<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Daily Attendance Report — {{ $summary['date'] }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

    .page-header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 16px; }
    .page-header h1 { font-size: 18px; font-weight: bold; color: #4f46e5; }
    .page-header p  { font-size: 11px; color: #555; margin-top: 2px; }

    .summary { display: table; width: 100%; margin-bottom: 16px; border-collapse: separate; border-spacing: 8px 0; }
    .stat-box { display: table-cell; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 14px; text-align: center; background: #f9fafb; }
    .stat-box .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; }
    .stat-box .value { font-size: 22px; font-weight: bold; color: #111; margin-top: 2px; }
    .stat-box.green .value { color: #16a34a; }

    table.report { width: 100%; border-collapse: collapse; }
    table.report thead tr { background: #4f46e5; color: #fff; }
    table.report thead th { padding: 7px 10px; text-align: left; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.04em; }
    table.report tbody tr { border-bottom: 1px solid #e5e7eb; }
    table.report tbody tr:nth-child(even) { background: #f9fafb; }
    table.report tbody td { padding: 6px 10px; vertical-align: top; }

    .time-in  { color: #16a34a; font-weight: bold; font-family: DejaVu Sans Mono, monospace; }
    .time-out { color: #4b5563; font-weight: bold; font-family: DejaVu Sans Mono, monospace; }
    .dash     { color: #9ca3af; }

    .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }
    .badge-rfid        { background: #dbeafe; color: #1d4ed8; }
    .badge-fingerprint { background: #ede9fe; color: #7c3aed; }
    .badge-face        { background: #fce7f3; color: #be185d; }
    .badge-manual      { background: #fef3c7; color: #92400e; }

    .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: right; }
    .no-records { text-align: center; padding: 30px; color: #9ca3af; font-style: italic; }
</style>
</head>
<body>

{{-- Header --}}
<div class="page-header">
    <h1>Daily Attendance Report</h1>
    <p>
        {{ \Carbon\Carbon::parse($summary['date'])->format('l, d F Y') }}
        &nbsp;·&nbsp;
        Generated {{ now()->format('d M Y \a\t H:i') }}
    </p>
</div>

{{-- Summary --}}
<div class="summary">
    <div class="stat-box">
        <div class="label">Date</div>
        <div class="value" style="font-size:13px;">{{ \Carbon\Carbon::parse($summary['date'])->format('d M Y') }}</div>
    </div>
    <div class="stat-box green">
        <div class="label">People Present</div>
        <div class="value">{{ $summary['present'] }}</div>
    </div>
    <div class="stat-box">
        <div class="label">Total Scans</div>
        <div class="value">{{ $summary['total'] }}</div>
    </div>
</div>

{{-- Table --}}
@if(count($rows))
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
        @foreach($rows as $row)
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
@else
<div class="no-records">No attendance records for this date.</div>
@endif

<div class="footer">Attendance Management System &nbsp;·&nbsp; {{ config('app.name') }}</div>

</body>
</html>
