@extends('layouts_.vertical', ['page_title' => 'Appraisal'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
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
        <form id="formYearAppraisal" action="{{ route('appraisals') }}" method="GET">
            @php
                $filterYear = request('filterYear');
            @endphp
            <div class="row align-items-end">
                <div class="col">
                    <div class="mb-3">
                        <label class="form-label" for="filterYear">{{ __('Year') }}</label>
                        <select name="filterYear" id="filterYear" onchange="yearAppraisal()" class="form-select border-secondary" @style('width: 120px')>
                            <option value="">{{ __('select all') }}</option>
                            @foreach ($selectYear as $period)
                                <option value="{{ $period->period }}" {{ $period->period == $filterYear ? 'selected' : '' }}>{{ $period->period }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="mb-3 mt-3">
                        <a href="{{ route('form.appraisal', Auth::user()->employee_id) }}" class="btn btn-primary shadow">{{ __('Initiate Appraisal') }}</a>
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
                                <a class="btn btn-outline-warning fw-semibold rounded-pill" href="{{ route('edit.appraisal', $row->request->appraisal->id) }}">{{ __('Edit') }}</a>
                            @endif
                        </div>
                        <div class="card-body mb-2 bg-light-subtle">
                            <div class="row px-2">
                                <div class="col-lg col-sm-12 p-2">
                                    <h5>{{ __('Initiated By') }}</h5>
                                    <p class="mt-2 mb-0 text-muted">{{ $row->request->initiated->name.' ('.$row->request->initiated->employee_id.')' }}</p>
                                </div>
                                <div class="col-lg col-sm-12 p-2">
                                    <h5>{{ __('Initiated Date') }}</h5>
                                    <p class="mt-2 mb-0 text-muted">{{ $row->request->formatted_created_at }}</p>
                                </div>
                                <div class="col-lg col-sm-12 p-2">
                                    <h5>{{ __('Last Updated On') }}</h5>
                                    <p class="mt-2 mb-0 text-muted">{{ $row->request->formatted_updated_at }}</p>
                                </div>
                                <div class="col-lg col-sm-12 p-2">
                                    <h5>Final Rating</h5>
                                    <p class="mt-2 mb-0 text-muted">-</p>
                                    {{-- Final score belum tersedia --}}
                                </div>
                                <div class="col-lg col-sm-12 p-2">
                                    <h5>Status</h5>
                                    <div>
                                        <a href="javascript:void(0)" data-bs-id="{{ $row->request->employee_id }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $row->request->appraisal->first()->goal->form_status == 'Draft' ? 'Draft' : ($row->approvalLayer ? 'Manager L'.$row->approvalLayer.' : '.$row->name : $row->name) }}" class="badge {{ $row->request->appraisal->first()->goal->form_status == 'Draft' || $row->request->sendback_to == $row->request->employee_id ? 'bg-secondary' : ($row->request->status === 'Approved' ? 'bg-success' : 'bg-warning')}} rounded-pill py-1 px-2">{{ $row->request->appraisal->first()->goal->form_status == 'Draft' ? 'Draft': ($row->request->status == 'Pending' ? __('Pending') : ($row->request->sendback_to == $row->request->employee_id ? 'Waiting For Revision' : $row->request->status)) }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if ($row->request->created_by === $row->request->employee->id)
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <div class="mb-2 text-primary fw-semibold fs-16">
                                        Total Score : {{ $formData['totalScore'] }}
                                    </div>
                                </div>
                            </div>
                            @forelse ($appraisalData['formData'] as $indexItem => $item)
                            <div class="row">
                                <button class="btn rounded mb-2 py-2 bg-secondary-subtle bg-opacity-10 text-primary align-items-center d-flex justify-content-between" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $indexItem }}" aria-expanded="false" aria-controls="collapse-{{ $indexItem }}">
                                    <span class="fs-16 ms-1">
                                        {{ $item['formName'] }} 
                                        | Score : {{ $item['formName'] === 'KPI' ? $appraisalData['kpiScore'] : ($item['formName'] === 'Culture' ? $appraisalData['cultureScore'] : $appraisalData['leadershipScore']) }}
                                    </span>  
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
                                        @if ($form['formName'] === 'KPI')
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>KPI</th>
                                                        <th>{{ __('Type') }}</th>
                                                        <th>{{ __('Weightage') }}</th>
                                                        <th>Target</th>
                                                        <th>{{ __('Actual') }}</th>
                                                        <th>{{ __('Achievement') }}</th>
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
                        @endif
                    </div> <!-- end card-->
                </div> <!-- end col-->
            </div>
        @empty
            <div class="row">
                <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        {{ __('No Appraisal Found. Please Initiate Your Appraisal ') }}<i class="ri-arrow-right-up-line"></i>
                    </div>
                </div>
                </div>
            </div>
        @endforelse
    </div>
    @endsection
    @push('scripts')
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