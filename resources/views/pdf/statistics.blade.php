<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reservation Statistics Report</title>
    <style>
        * {
            font-family: sans-serif;
        }
        body {
            font-family: sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 24px;
            color: #1e40af;
            margin: 0 0 10px 0;
        }
        .period {
            font-size: 14px;
            color: #6b7280;
        }
        .summary {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .summary-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 15px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        .summary-item .label {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .summary-item .value {
            font-size: 24px;
            font-weight: bold;
        }
        .summary-item.blue .value { color: #3b82f6; }
        .summary-item.red .value { color: #ef4444; }
        .summary-item.orange .value { color: #f59e0b; }
        
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            border-left: 4px solid #3b82f6;
            padding-left: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: bold;
            padding: 10px;
            text-align: left;
            border: 1px solid #d1d5db;
        }
        td {
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Reservation Statistics Report</h1>
        <div class="period">
            Period: {{ $startDate }} - {{ $endDate }}
        </div>
        <div style="font-size: 10px; color: #9ca3af; margin-top: 5px;">
            Generated: {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    <!-- Summary -->
    <div class="summary">
        <div class="summary-item blue">
            <div class="label">Total Confirmed</div>
            <div class="value">{{ number_format($totalConfirmed) }}</div>
        </div>
        <div class="summary-item red">
            <div class="label">Total Canceled</div>
            <div class="value">{{ number_format($totalCanceled) }}</div>
        </div>
        <div class="summary-item orange">
            <div class="label">Avg Cancellation Rate</div>
            <div class="value">{{ $averageCancellationRate }}%</div>
        </div>
    </div>

    <!-- Monthly Data -->
    <div class="section">
        <div class="section-title">Monthly Reservation Data</div>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="text-right">Applications</th>
                    <th class="text-right">Confirmed</th>
                    <th class="text-right">Canceled</th>
                    <th class="text-right">Cancel Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($totalByMonth as $month => $total)
                    <tr>
                        <td>{{ $month }}</td>
                        <td class="text-right">{{ number_format($total) }}</td>
                        <td class="text-right">{{ number_format($confirmedByMonth[$month] ?? 0) }}</td>
                        <td class="text-right">{{ number_format($canceledByMonth[$month] ?? 0) }}</td>
                        <td class="text-right">{{ $cancellationRateByMonth[$month] ?? 0 }}%</td>
                    </tr>
                @endforeach
                <tr style="background-color: #f3f4f6; font-weight: bold;">
                    <td>Total</td>
                    <td class="text-right">{{ number_format($totalByMonth->sum()) }}</td>
                    <td class="text-right">{{ number_format($totalConfirmed) }}</td>
                    <td class="text-right">{{ number_format($totalCanceled) }}</td>
                    <td class="text-right">{{ $averageCancellationRate }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Reservation Sources -->
    <div class="section">
        <div class="section-title">Reservation Sources</div>
        <table>
            <thead>
                <tr>
                    <th>Source</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">Percentage</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total = $bySource->sum();
                    $sourceMap = [
                        'web' => 'Web',
                        'phone' => 'Phone',
                        'walk-in' => 'Walk-in',
                        'asoview' => 'Asoview',
                        'jalan' => 'Jalan'
                    ];
                @endphp
                @foreach($bySource as $source => $count)
                    <tr>
                        <td>{{ $sourceMap[$source] ?? $source }}</td>
                        <td class="text-right">{{ number_format($count) }}</td>
                        <td class="text-right">{{ $total > 0 ? number_format(($count / $total) * 100, 1) : 0 }}%</td>
                    </tr>
                @endforeach
                <tr style="background-color: #f3f4f6; font-weight: bold;">
                    <td>Total</td>
                    <td class="text-right">{{ number_format($total) }}</td>
                    <td class="text-right">100.0%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        Morioka Handmade Village Management System - Reservation Statistics Report
    </div>
</body>
</html>

