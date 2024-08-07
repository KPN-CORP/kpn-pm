@extends('layouts_.vertical', ['page_title' => 'Goals'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item">{{ $parentLink }}</li>
                            <li class="breadcrumb-item active">{{ $link }}</li>
                        </ol>
                    </div>
                    <h4 class="page-title">{{ $link }}</h4>
                </div>
            </div>
        </div>
        @if(session('success'))
            <div class="alert alert-success mt-3">
                {{ session('success') }}
            </div>
        @endif
        <div class="mandatory-field">
            <div id="alertField" class="alert alert-danger alert-dismissible {{ Session::has('error') ? '':'fade' }}" role="alert" {{ Session::has('error') ? '':'hidden' }}>
                <strong>{{ Session::get('error') }}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <form id="formYearGoal" action="{{ route('goals') }}" method="GET">
            @php
                $filterYear = request('filterYear');
            @endphp
            <div class="row align-items-end">
                <div class="col">
                    <div class="mb-3 d-none">
                        <label class="form-label" for="filterYear">Year</label>
                        <select name="filterYear" id="filterYear" onchange="yearGoal()" class="form-select border-secondary" @style('width: 120px')>
                            <option value="">select all</option>
                            @foreach ($selectYear as $year)
                                <option value="{{ $year->year }}" {{ $year->year == $filterYear ? 'selected' : '' }}>{{ $year->year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="mb-3 mt-3">
                        <a href="{{ route('form.appraisal', Auth::user()->employee_id) }}" class="btn rounded-pill btn-primary shadow">Initiate Appraisal</a>
                    </div>
                </div>
            </div>
        </form>
        @forelse ($data as $row)
            @php
                $year = date('Y', strtotime($row->request->created_at));
            @endphp
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                            <h4 class="m-0 font-weight-bold text-primary">Appraisals {{ $row->request->appraisal->period }}</h4>
                            @if ($row->request->status == 'Pending' && count($row->request->approval) == 0 || $row->request->sendback_to == $row->request->employee_id)
                                <a class="btn btn-outline-warning border-2 fw-semibold rounded-pill" href="{{ route('edit.appraisal', $row->request->appraisal->id) }}">Edit</a>
                            @endif
                        </div>
                        <div class="card-body mb-2" style="background-color: ghostwhite">
                            <div class="row px-2">
                                <div class="col-lg col-sm-12 p-2">
                                    <h5>Initiated By</h5>
                                    <p class="mt-2 mb-0 text-muted">{{ $row->request->initiated->name.' ('.$row->request->initiated->employee_id.')' }}</p>
                                </div>
                                <div class="col-lg col-sm-12 p-2">
                                    <h5>Initiated Date</h5>
                                    <p class="mt-2 mb-0 text-muted">{{ $row->request->formatted_created_at }}</p>
                                </div>
                                <div class="col-lg col-sm-12 p-2">
                                    <h5>Last Updated On</h5>
                                    <p class="mt-2 mb-0 text-muted">{{ $row->request->formatted_updated_at }}</p>
                                </div>
                                <div class="col-lg col-sm-12 p-2">
                                    <h5>Adjusted By</h5>
                                    <p class="mt-2 mb-0 text-muted">{{ $row->request->updatedBy ? $row->request->updatedBy->name.' '.$row->request->updatedBy->employee_id : '-' }}{{ $row->request->adjustedBy && empty($adjustByManager) ? ' (Admin)': '' }}</p>
                                </div>
                                <div class="col-lg col-sm-12 p-2">
                                    <h5>Status</h5>
                                    <div>
                                        <a href="javascript:void(0)" data-bs-id="{{ $row->request->employee_id }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $row->request->goal->form_status == 'Draft' ? 'Draft' : ($row->approvalLayer ? 'Manager L'.$row->approvalLayer.' : '.$row->name : $row->name) }}" class="badge {{ $row->request->goal->form_status == 'Draft' || $row->request->sendback_to == $row->request->employee_id ? 'bg-secondary' : ($row->request->status === 'Approved' ? 'bg-success' : 'bg-warning')}} rounded-pill py-1 px-2">{{ $row->request->goal->form_status == 'Draft' ? 'Draft': ($row->request->status == 'Pending' ? 'Waiting For Approval' : ($row->request->sendback_to == $row->request->employee_id ? 'Waiting For Revision' : $row->request->status)) }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col mb-2">
                                    <h4>Your Rating : B</h4>
                                </div>
                            </div>
                            @forelse ($appraisalData['formData'] as $indexItem => $item)
                            <div class="row">
                                <button class="btn btn-sm rounded-pill mb-2 py-1 bg-danger bg-opacity-10 text-danger align-items-center d-flex justify-content-between" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $indexItem }}" aria-expanded="false" aria-controls="collapse-{{ $indexItem }}">
                                    <span class="fs-16 ms-1">{{ $item['formName'] }}</span>  
                                    <span>
                                        <p class="d-none d-md-inline me-1">Details</p><i class="ri-arrow-down-s-line"></i>
                                    </span>                               
                                </button>
                                @if ($item['formName'] == 'Leadership')
                                <div class="collapse" id="collapse-{{ $indexItem }}">
                                    <div class="card card-body mb-3">
                                        @forelse($formData['formData'] as $form)
                                            @if($form['formName'] === 'Leadership')
                                                @foreach($form as $key => $item)
                                                    @if(is_numeric($key))
                                                    <div class="{{ $loop->last ? '':'border-bottom' }} mb-3">
                                                        @if(isset($item['title']))
                                                            <h5 class="mb-3"><u>{{ $item['title'] }}</u></h5>
                                                        @endif
                                                        @foreach($item as $subKey => $subItem)
                                                            @if(is_array($subItem))
                                                            <ul class="ps-3">
                                                                <li>
                                                                    <div>
                                                                        @if(isset($subItem['formItem']))
                                                                            <p class="mb-1">{{ $subItem['formItem'] }}</p>
                                                                        @endif
                                                                        @if(isset($subItem['score']))
                                                                            <p><strong>Score:</strong> {{ $subItem['score'] }}</p>
                                                                        @endif
                                                                    </div>
                                                                </li>
                                                            </ul>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                    @endif
                                                @endforeach
                                            @endif
                                        @empty
                                            <p>No Data</p>
                                        @endforelse
                                    </div>
                                </div>
                                @elseif($item['formName'] == 'Culture')
                                <div class="collapse" id="collapse-{{ $indexItem }}">
                                    <div class="card card-body mb-3">
                                        @forelse($formData['formData'] as $form)
                                            @if($form['formName'] === 'Culture')
                                                @foreach($form as $key => $item)
                                                    @if(is_numeric($key))
                                                    <div class="{{ $loop->last ? '':'border-bottom' }} mb-3">
                                                        @if(isset($item['title']))
                                                            <h5 class="mb-3"><u>{{ $item['title'] }}</u></h5>
                                                        @endif
                                                        @foreach($item as $subKey => $subItem)
                                                            @if(is_array($subItem))
                                                            <ul class="ps-3">
                                                                <li>
                                                                    <div>
                                                                        @if(isset($subItem['formItem']))
                                                                            <p class="mb-1">{{ $subItem['formItem'] }}</p>
                                                                        @endif
                                                                        @if(isset($subItem['score']))
                                                                            <p><strong>Score:</strong> {{ $subItem['score'] }}</p>
                                                                        @endif
                                                                    </div>
                                                                </li>
                                                            </ul>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                    @endif
                                                @endforeach
                                            @endif
                                        @empty
                                            <p>No Data</p>
                                        @endforelse
                                    </div>
                                </div>
                                @else    
                                <div class="collapse" id="collapse-{{ $indexItem }}">
                                    <div class="card card-body mb-3 py-0">
                                        @forelse ($formData['formData'] as $form)
                                        @if ($form['formName'] === 'Self Review')
                                        <div class="table-responsive">

                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>KPI</th>
                                                        <th>Type</th>
                                                        <th>Weightage</th>
                                                        <th>Target</th>
                                                        <th>Actual</th>
                                                        <th>Achievement</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($form as $key => $data)
                                                    @if (is_array($data))
                                                    <tr>
                                                        <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                            <p class="mt-1 mb-0">{{ $key + 1 }}</p>
                                                        </td>
                                                        <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                            <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['kpi'] }}</p>
                                                        </td>
                                                        <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                            <p class="mt-1 mb-0 text-muted">{{ $data['type'] }}</p>
                                                        </td>
                                                        <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                            <p class="mt-1 mb-0 text-muted">{{ $data['weightage'] }}%</p>
                                                        </td>
                                                        <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                            <p class="mt-1 mb-0 text-muted">{{ $data['target'] }} {{ is_null($data['custom_uom']) ? $data['uom']: $data['custom_uom'] }}</p>
                                                        </td>
                                                        <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                            <p class="mt-1 mb-0 text-muted">{{ $data['achievement'] }} {{ is_null($data['custom_uom']) ? $data['uom']: $data['custom_uom'] }}</p>
                                                        </td>
                                                        <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                            <p class="mt-1 mb-0 text-muted">{{ round($data['percentage']) }}%</p>
                                                        </td>
                                                    </tr>
                                                    @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @endif
                                        @empty
                                        <p>No form data available.</p>
                                        @endforelse
                                    </div>
                                </div>
                                @endif
                            </div>
                            @empty
                                No Data
                            @endforelse
                            
                        </div> <!-- end card-body-->
                    </div> <!-- end card-->
                </div> <!-- end col-->
            </div>
        @empty
            <div class="row">
                <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        {{ __('No Appraisals Found. Please Initiate Your Appraisals ') }}<i class="ri-arrow-right-up-line"></i>
                    </div>
                </div>
                </div>
            </div>
        @endforelse
    </div>
    @endsection
    @push('scripts')
        <script src="{{ asset('js/goal-approval.js') }}?v={{ config('app.version') }}"></script>
        @if(Session::has('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {                
                Swal.fire({
                    icon: "error",
                    title: "Cannot initiate appraisal!",
                    text: '{{ Session::get('error') }}',
                    confirmButtonText: "OK",
                });
            });
        </script>
        @endif
    @endpush