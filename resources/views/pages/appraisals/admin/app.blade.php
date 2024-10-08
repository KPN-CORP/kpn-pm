@extends('layouts_.vertical', ['page_title' => 'Settings'])

@section('css')
<style>
    .popover {
    max-width: none; /* Allow popover to grow as wide as content */
    width: auto; /* Automatically adjust width based on content */
    white-space: nowrap; /* Prevent content from wrapping to the next line */
}
</style>
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Begin Page Content -->
    <div class="container-fluid"> 
        @if (session('success'))
            <div class="alert alert-success mt-3">
                {!! session('success') !!}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger mt-3">
                {!! session('error') !!}
            </div>
        @endif
        <div class="row">
            <div class="col-lg">
                <div class="mb-3 text-end">
                    <button type="button" class="btn btn-sm btn-outline-success me-1" title="Download Report">Download Report</button>
                    <button type="button" class="btn btn-sm btn-outline-success" title="Download Detail Report">Download Detail Report</button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-auto">
                <div class="mb-3 p-1 bg-info-subtle">
                    <span class="mx-2">L = Layer</span>|
                    <span class="mx-2">P = Peers</span>|
                    <span class="mx-2">S = Subordinate</span>|
                    <span class="mx-2"><i class="ri-check-line bg-subtle-success text-success fs-18"></i> = Done</span>|
                    <span class="mx-2"><i class="ri-error-warning-line bg-subtle-warning text-warning fs-20"></i> = Pending</span>
                </div>
            </div>
        </div>
        <!-- Content Row -->
        <div class="row">
          <div class="col-md-12">
            <div class="card shadow mb-4">
              <div class="card-body">
                <table class="table table-sm table-bordered table-hover activate-select dt-responsive nowrap w-100 fs-14 align-middle" id="adminAppraisalTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Employee ID</th>
                            <th>Employee Name</th>
                            @foreach(['L1', 'L2', 'L3', 'L4', 'L5', 'L6', 'L7', 'L8'] as $calibrator)
                                <th>{{ $calibrator }}</th>
                            @endforeach
                            @foreach(['P1', 'P2', 'P3'] as $peers)
                                <th>{{ $peers }}</th>
                            @endforeach
                            @foreach(['S1', 'S2', 'S3'] as $subordinate)
                                <th>{{ $subordinate }}</th>
                            @endforeach
                            <th>Final Score</th>
                            <th class="sorting_1">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($datas as $index => $employee)
                        <tr data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="{!! $employee['popoverContent'] !!}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $employee['id'] }}</td>
                            <td>{{ $employee['name'] }}</td>
                            
                            {{-- Calibrator Layers --}}
                            @foreach (range(1, 8) as $layer)
                                @php
                                    $calibratorLayer = collect($employee['approvalStatus']['calibrator'] ?? [])->firstWhere('layer', $layer);
                                @endphp
                                <td class="text-center
                                    @if ($calibratorLayer) 
                                        {{ $calibratorLayer['status'] ? 'table-success' : 'table-warning' }} 
                                    @endif
                                ">
                                    @if ($calibratorLayer)
                                        @if($calibratorLayer['status'])
                                            <i class="ri-check-line text-success fs-20 fw-medium"></i>
                                        @else
                                            <i class="ri-error-warning-line text-warning fs-20 fw-medium"></i>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
    
                            {{-- Peers Layers --}}
                            @foreach (range(1, 3) as $layer)
                                @php
                                    // $peerLayer = collect($employee['approvalStatus']['peers'] ?? [])->firstWhere('layer', $layer);
                                    $peerLayer = $employee['approvalStatus']['peers'][$layer - 1] ?? null;
                                @endphp
                                <td class="text-center
                                    @if ($peerLayer) 
                                        {{ $peerLayer['status'] ? 'table-success' : 'table-warning' }} 
                                    @endif
                                ">
                                    @if ($peerLayer)
                                        @if($peerLayer['status'])
                                            <i class="ri-check-line text-success fs-20 fw-medium"></i>
                                        @else
                                            <i class="ri-error-warning-line text-warning fs-20 fw-medium"></i>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
    
                            {{-- Subordinate Layers --}}
                            @foreach (range(1, 3) as $layer)
                                @php
                                    // $subordinateLayer = collect($employee['approvalStatus']['subordinate'] ?? [])->firstWhere('layer', $layer);
                                    $subordinateLayer = $employee['approvalStatus']['subordinate'][$layer - 1] ?? null;
                                @endphp
                                <td class="text-center
                                    @if ($subordinateLayer) 
                                        {{ $subordinateLayer['status'] ? 'table-success' : 'table-warning' }} 
                                    @endif
                                ">
                                    @if ($subordinateLayer)
                                        @if($subordinateLayer['status'])
                                            <i class="ri-check-line text-success fs-20 fw-medium"></i>
                                        @else
                                            <i class="ri-error-warning-line text-warning fs-20 fw-medium"></i>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                            
                            <td class="text-center">{{ $employee['finalScore'] }}</td>
                            <td class="sorting_1 text-center">
                                <button class="btn btn-sm btn-outline-info" onclick="alert('belom ada')"><i class="ri-eye-line"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
              </div>
            </div>
          </div>
      </div>
    </div>
@endsection
@push('scripts')
<script>
    var employeesData = {!! json_encode($datas) !!};
</script>
@endpush