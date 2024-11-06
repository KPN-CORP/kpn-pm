@extends('layouts_.vertical', ['page_title' => 'Reports'])

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
        @can('reportpadetail')                                
            <div class="row">
                <div class="col-lg">
                    <div class="mb-3 text-end">
                        <button id="reportAppraisalDetailsButton" type="button" class="btn btn-sm btn-outline-success" title="Download Detail Report"><i class="ri-download-cloud-2-line me-1 fs-16"></i>Download Detail Report</button>
                    </div>
                </div>
            </div>
        @endcan
        <div class="row">
            <div class="col-auto">
                <div class="mb-3 p-1 bg-info-subtle rounded shadow">
                    <span class="mx-2">C = Calibrator</span>|
                    <span class="mx-2">P = Peers</span>|
                    <span class="mx-2">S = Subordinate</span>|
                    <span class="mx-2"><i class="ri-check-line bg-success-subtle text-success rounded fs-18"></i> = Done</span>|
                    <span class="mx-2"><i class="ri-error-warning-line bg-warning-subtle text-warning rounded fs-20"></i> = Pending</span>
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
                            @foreach(['P1', 'P2', 'P3'] as $peers)
                            <th>{{ $peers }}</th>
                            @endforeach
                            @foreach(['S1', 'S2', 'S3'] as $subordinate)
                            <th>{{ $subordinate }}</th>
                            @endforeach
                            @foreach($layerHeaders as $calibrator)
                                <th>{{ $calibrator }}</th>
                            @endforeach
                            <th>Final Rating</th>
                            @can('reportpadetail')                                
                            <th class="sorting_1">Details</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($datas as $index => $employee)
                        <tr data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="{!! $employee['popoverContent'] !!}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $employee['id'] }}</td>
                            <td>{{ $employee['name'] }}</td>
    
                            {{-- Peers Layers --}}
                            @foreach (range(1, 3) as $layer)
                                @php
                                    $peerLayer = $employee['approvalStatus']['peers'][$layer - 1] ?? null;
                                @endphp
                                <td class="text-center
                                    @if ($peerLayer) 
                                        {{ $peerLayer['status'] ? 'table-success' : 'table-warning' }} 
                                    @else
                                        table-light
                                    @endif
                                "
                                data-id="{{ $peerLayer ? ($peerLayer['status'] ? 'Approved - '.$peerLayer['approver_name'].' ('.$peerLayer['approver_id'].')' : 'Pending - '.$peerLayer['approver_name'].' ('.$peerLayer['approver_id'].')') : '-' }}">
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
                                    $subordinateLayer = $employee['approvalStatus']['subordinate'][$layer - 1] ?? null;
                                @endphp
                                <td class="text-center
                                    @if ($subordinateLayer) 
                                        {{ $subordinateLayer['status'] ? 'table-success' : 'table-warning' }} 
                                    @else
                                        table-light
                                    @endif"
                                data-id="{{ $subordinateLayer ? ($subordinateLayer['status'] ? 'Approved - '.$subordinateLayer['approver_name'].' ('.$subordinateLayer['approver_id'].')' : 'Pending - '.$subordinateLayer['approver_name'].' ('.$subordinateLayer['approver_id'].')') : '-' }}">
                                    @if ($subordinateLayer)
                                        @if($subordinateLayer['status'])
                                            <i class="ri-check-line text-success fs-20 fw-medium"></i>
                                        @else
                                            <i class="ri-error-warning-line text-warning fs-20 fw-medium"></i>
                                        @endif
                                    @endif
                                </td>
                            @endforeach

                            {{-- Calibrator Layers --}}
                            @foreach ($layerBody as $layer)
                                @php
                                    $calibratorLayer = collect($employee['approvalStatus']['calibrator'] ?? [])->firstWhere('layer', $layer);
                                @endphp
                                <td class="text-center
                                    @if ($calibratorLayer) 
                                        {{ $calibratorLayer['status'] ? 'table-success' : 'table-warning' }} 
                                    @else
                                        table-light
                                    @endif"
                                data-id="{{ $calibratorLayer ? ($calibratorLayer['status'] ? 'Approved - '.$calibratorLayer['approver_name'].' ('.$calibratorLayer['approver_id'].')' : 'Pending - '.$calibratorLayer['approver_name'].' ('.$calibratorLayer['approver_id'].')') : '-' }}">
                                    @if ($calibratorLayer)
                                        @if($calibratorLayer['status'])
                                            <i class="ri-check-line text-success fs-20 fw-medium"></i>
                                        @else
                                            <i class="ri-error-warning-line text-warning fs-20 fw-medium"></i>{{ $calibratorLayer['status'] }}
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                            
                            <td class="text-center">{{ $employee['finalScore'] }}</td>
                            @can('reportpadetail')
                            <td class="sorting_1 text-center">
                                <!--count(collect($employee['approvalStatus'])->except('calibrator'))-->
                                @if ($employee['appraisalStatus'] && count(collect($employee['approvalStatus'])) != 0)
                                    <a href="{{ route('admin.appraisal.details', $employee['id']) }}" class="btn btn-sm btn-outline-info"><i class="ri-eye-line"></i></a>
                                @else
                                    <a class="btn btn-sm btn-outline-secondary" onclick="alert('no data appraisal or pending reviewer')"><i class="ri-eye-line"></i></a>
                                @endif
                            </td>
                            @endcan
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