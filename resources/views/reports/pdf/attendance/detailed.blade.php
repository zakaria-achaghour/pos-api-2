@extends('reports.layouts.pdf')

@section('title', 'Detailed Attendance Report')

@section('period', $startDate->format('M j, Y') . ' - ' . $endDate->format('M j, Y'))

@section('content')
    <table>
        <thead>
            <tr>
                <th>Staff Name</th>
                <th>Date</th>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th>Break (min)</th>
                <th>Hours Worked</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
            <tr>
                <td>{{ $attendance->staff->full_name }}</td>
                <td>{{ $attendance->clock_in->format('M j, Y') }}</td>
                <td>{{ $attendance->clock_in->format('g:i A') }}</td>
                <td>{{ $attendance->clock_out ? $attendance->clock_out->format('g:i A') : 'Still Active' }}</td>
                <td class="text-center">{{ $attendance->break_minutes }}</td>
                <td class="text-right">{{ number_format($attendance->hours_worked ?? 0, 2) }}</td>
                <td>{{ $attendance->notes ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <div class="summary-item">
            <span class="summary-label">Total Records:</span> {{ $attendances->count() }}
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Hours:</span> {{ number_format($attendances->sum('hours_worked'), 2) }}
        </div>
    </div>
@endsection