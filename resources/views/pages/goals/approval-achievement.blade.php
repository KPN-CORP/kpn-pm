@extends('layouts_.vertical', ['page_title' => 'Approval Achievement'])

@section('css')
<style>
.version-header {
    padding: 8px 12px;
    border-radius: 4px;
    display: flex;
    align-items: center;
}
.header-before {
    background-color: #f1f3f5;
    border-left: 4px solid #6c757d;
}
.header-after {
    background-color: #e7f1ff;
    border-left: 4px solid #0d6efd;
}
.kpi-label {
    font-size: 0.65rem;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 2px;
    color: #6c757d;
}
.month-box {
    border-radius: 6px;
    padding: 6px;
    text-align: center;
    border: 1px solid #dee2e6;
    background-color: #fff;
}
.month-box-old {
    background-color: #f8f9fa;
    border-style: dashed;
    opacity: 0.8;
}
.month-label {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #6c757d;
    display: block;
    margin-bottom: 2px;
}
.month-input {
    width: 100%;
    border: none;
    background: transparent;
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
    color: #212529;
    outline: none;
}
.month-input-old {
    color: #6c757d;
}
.btn-evid {
    font-size: 10px !important;
    padding: 2px !important;
    margin-top: 4px;
    display: block;
    width: 100%;
    line-height: 1.2;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-body p-2 px-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="d-flex mb-1">
                        <div class="text-muted small fw-medium" style="width: 110px;">Employee Name</div>
                        <div class="fw-semibold small text-dark">: Metta Saputra (Dummy)</div>
                    </div>
                    <div class="d-flex mb-1">
                        <div class="text-muted small fw-medium" style="width: 110px;">Employee ID</div>
                        <div class="fw-semibold small text-dark">: 12345678</div>
                    </div>
                    <div class="d-flex">
                        <div class="text-muted small fw-medium" style="width: 110px;">Job Level</div>
                        <div class="fw-semibold small text-dark">: Staff</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex mb-1">
                        <div class="text-muted small fw-medium" style="width: 110px;">Business Unit</div>
                        <div class="fw-semibold small text-dark">: IT Technology</div>
                    </div>
                    <div class="d-flex mb-1">
                        <div class="text-muted small fw-medium" style="width: 110px;">Division</div>
                        <div class="fw-semibold small text-dark">: Software Development</div>
                    </div>
                    <div class="d-flex">
                        <div class="text-muted small fw-medium" style="width: 110px;">Designation</div>
                        <div class="fw-semibold small text-dark">: Programmer</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="achievementApprovalForm" action="#" method="post">
        @csrf
        
        <h6 class="fw-bold text-dark mb-3 mt-4">{{ __('Achievement Target') }} 2025</h6>

        @php
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            $dummyData = [
                [
                    'kpi' => 'Create recruitment module',
                    'target' => '100',
                    'uom' => '%',
                    'weightage' => '50',
                    'has_old_data' => true 
                ],
                [
                    'kpi' => 'Maintain Server Uptime',
                    'target' => '99',
                    'uom' => '%',
                    'weightage' => '50',
                    'has_old_data' => false 
                ]
            ];
        @endphp

        @foreach ($dummyData as $i => $data)
        
        @if ($data['has_old_data'])
        
        <div class="p-3 mb-4 rounded shadow-sm" style="background-color: #f8f9fa; border: 1px solid #eef0f2;">
            
            <div class="row mb-3 bg-white p-2 rounded border mx-0">
                <div class="col-md-5">
                    <div class="kpi-label text-primary">KPI {{ $i + 1 }}</div>
                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $data['kpi'] }}</div>
                </div>
                <div class="col-md-3 col-6 mt-2 mt-md-0">
                    <div class="kpi-label">Target / UoM</div>
                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $data['target'] }} {{ $data['uom'] }}</div>
                </div>
                <div class="col-md-4 col-6 mt-2 mt-md-0">
                    <div class="kpi-label">Weightage</div>
                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $data['weightage'] }}%</div>
                </div>
            </div>

            <div class="row align-items-stretch">
                
                <div class="col-lg-12 mb-3 mb-lg-0">
                    <div class="card shadow-none border h-100" style="background-color: #fafafa;">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                <h6 class="fw-bold text-secondary mb-0" style="font-size: 0.8rem;"><i class="ri-history-line me-1"></i>BEFORE (Previous Achievement)</h6>
                            </div>
                            
                            <div class="row g-2">
                                @foreach($months as $monthIndex => $month)
                                <div class="col-xl-1 col-lg-2 col-md-3 col-4">
                                    <div class="month-box month-box-old">
                                        <span class="month-label">{{ $month }}</span>
                                        <input type="text" value="{{ $monthIndex * 5 }}" class="month-input month-input-old" readonly>
                                        <a href="#" class="btn btn-outline-secondary btn-evid">FILE</a>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card border-primary border-opacity-50 bg-white shadow-sm h-100">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom border-primary border-opacity-25">
                                <h6 class="fw-bold text-primary mb-0" style="font-size: 0.8rem;"><i class="ri-file-edit-line me-1"></i>AFTER (Current Submission)</h6>
                            </div>
                            
                            <div class="row g-2">
                                @foreach($months as $monthIndex => $month)
                                <div class="col-xl-1 col-lg-2 col-md-3 col-4">
                                    <div class="month-box border-primary border-opacity-25">
                                        <span class="month-label text-primary">{{ $month }}</span>
                                        <input type="text" name="ach[{{$i}}][{{$month}}]" value="{{ ($monthIndex * 5) + 5 }}" class="month-input">
                                        <a href="#" class="btn btn-success btn-evid border-0">VIEW</a>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @else
        
        <div class="p-3 mb-4 rounded shadow-sm" style="background-color: #f8f9fa; border: 1px solid #eef0f2;">
            
            <div class="row mb-3 bg-white p-2 rounded border mx-0 align-items-center">
                <div class="col-md-5">
                    <div class="kpi-label text-primary">KPI {{ $i + 1 }} <span class="badge bg-success ms-1" style="font-size: 0.6rem;">FIRST SUBMISSION</span></div>
                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $data['kpi'] }}</div>
                </div>
                <div class="col-md-3 col-6 mt-2 mt-md-0">
                    <div class="kpi-label">Target / UoM</div>
                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $data['target'] }} {{ $data['uom'] }}</div>
                </div>
                <div class="col-md-4 col-6 mt-2 mt-md-0">
                    <div class="kpi-label">Weightage</div>
                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $data['weightage'] }}%</div>
                </div>
            </div>

            <div class="row align-items-stretch">
                <div class="col-12">
                    <div class="card border-primary border-opacity-50 bg-white shadow-sm h-100">
                        <div class="card-body p-2">
                            <div class="row g-2">
                                @foreach($months as $monthIndex => $month)
                                <div class="col-xl-1 col-lg-2 col-md-3 col-4">
                                    <div class="month-box border-primary border-opacity-25">
                                        <span class="month-label text-primary">{{ $month }}</span>
                                        <input type="text" name="ach[{{$i}}][{{$month}}]" value="{{ $monthIndex == 0 ? '99' : '' }}" class="month-input" placeholder="-">
                                        @if($monthIndex == 0)
                                            <a href="#" class="btn btn-success btn-evid border-0">VIEW</a>
                                        @else
                                            <span class="btn btn-outline-secondary btn-evid disabled" style="opacity: 0.5;">NONE</span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @endif

        @endforeach
        
        <div class="card shadow-sm border-0 mt-2 mb-4">
            <div class="card-body p-2 px-3 bg-light rounded border">
                <label class="kpi-label text-dark">Messages*</label>
                <textarea name="messages" class="form-control form-control-sm py-1" placeholder="Enter messages.." rows="2" style="font-size: 0.85rem;"></textarea>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-3 bg-white rounded">
                <div class="d-flex flex-column flex-md-row justify-content-end align-items-center">
                    <div class="w-100 w-md-auto">
                        <div class="d-flex flex-wrap justify-content-center justify-content-md-end gap-2">
                            <div class="dropdown">
                                <button class="btn btn-warning btn-sm fw-medium dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ __('Send Back') }}
                                </button>
                                <div class="dropdown-menu shadow-sm" style="font-size: 0.8rem;">
                                    <h6 class="dropdown-header text-dark fw-bold">Select person below:</h6>
                                    <a class="dropdown-item py-1" href="#">Metta Saputra (12345678)</a>
                                </div> 
                            </div>
                            <a href="#" class="btn btn-outline-secondary btn-sm fw-medium">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary btn-sm fw-medium px-4">
                                {{ __('Approve') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
</script>
@endpush