@extends('reports.layouts.pdf')

@section('title', 'Attendance Summary Report')

@section('period', $startDate->format('M j, Y') . ' - ' . $endDate->format('M j, Y'))

@section('content')
    <div class="summary-box">
        <div class="summary-item">
            <span class="summary-label">Total Staff:</span> {{ $summary['total_staff'] }}
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Hours:</span> {{ number_format($summary['total_hours'], 2) }}
        </div>
        <div class="summary-item">
            <span class="summary-label">Average Hours per Staff:</span> {{ number_format($summary['average_hours'], 2) }}
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Days Worked:</span> {{ $summary['total_days'] }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Staff Name</th>
                <th>Position</th>
                <th>Days Worked</th>
                <th>Total Hours</th>
                <th>Avg Hours/Day</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
            <tr>
                <td>{{ $attendance['staff_name'] }}</td>
                <td>{{ $attendance['position'] }}</td>
                <td class="text-center">{{ $attendance['total_days'] }}</td>
                <td class="text-right">{{ number_format($attendance['total_hours'], 2) }}</td>
                <td class="text-right">{{ number_format($attendance['average_hours_per_day'], 2) }}</td>
                <td class="text-center">
                    @if($attendance['total_hours'] >= 40)
                        <span style="color: green;">Full Time</span>
                    @elseif($attendance['total_hours'] >= 20)
                        <span style="color: orange;">Part Time</span>
                    @else
                        <span style="color: red;">Minimal</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection