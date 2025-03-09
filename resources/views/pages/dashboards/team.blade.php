@extends('layouts_.vertical', ['page_title' => 'Dashboard'])

@section('css')
@endsection

@section('content')
<div class="container">
    <div class="mb-2">
        <h1>Dashboard</h1>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5>Total Employees</h5>
                    <p>{{ $totalEmployees }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5>Goals Approved</h5>
                    <p>{{ $goalsApproved }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5>Pending Appraisals</h5>
                    <p>{{ $pendingAppraisals }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5>Average Appraisal Score</h5>
                    <p>{{ number_format($averageAppraisalScore, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mt-4">
        <div class="col-md-6">
            <h5>Goal Progress by Department</h5>
            <canvas id="goalProgressChart"></canvas>
        </div>
        <div class="col-md-6">
            <h5>Performance Trends</h5>
            <canvas id="performanceTrendsChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Goal Progress Chart
    const goalProgressCtx = document.getElementById('goalProgressChart').getContext('2d');
    const goalProgressChart = new Chart(goalProgressCtx, {
        type: 'pie',
        data: {
            labels: {!! json_encode($goalProgress->pluck('department')) !!},
            datasets: [{
                data: {!! json_encode($goalProgress->pluck('total')) !!},
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545'],
            }]
        }
    });

    // Performance Trends Chart
    const performanceTrendsCtx = document.getElementById('performanceTrendsChart').getContext('2d');
    const performanceTrendsChart = new Chart(performanceTrendsCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($performanceTrends->pluck('month')) !!},
            datasets: [{
                label: 'Average Score',
                data: {!! json_encode($performanceTrends->pluck('average_score')) !!},
                borderColor: '#007bff',
                fill: false,
            }]
        }
    });
</script>
@endsection