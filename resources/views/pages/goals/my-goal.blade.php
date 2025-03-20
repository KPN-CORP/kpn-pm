@extends('layouts_.vertical', ['page_title' => 'Goals'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
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
                <div class="col-auto">
                    <div class="mb-3">
                        <label class="form-label" for="filterYear">{{ __('Year') }}</label>
                        <select name="filterYear" id="filterYear" onchange="yearGoal(this)" class="form-select border-secondary" @style('width: 180px')>
                            <option value="">{{ __('select all') }}</option>
                            @foreach ($selectYear as $year)
                                <option value="{{ $year->year }}" {{ $year->year == $filterYear ? 'selected' : '' }}>{{ $year->year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="mb-3 text-end">
                        <a href="{{ $access ? route('goals.form', Auth::user()->employee_id) : '#' }}" onclick="showLoader()" class="btn {{ $access ? 'btn-primary shadow' : 'btn-secondary-subtle disabled' }}">{{ __('Create Goal') }}</a>
                    </div>
                </div>
            </div>
        </form>
        @forelse ($data as $goalIndex => $row)
            @php
                // Assuming $dateTimeString is the date string '2024-04-29 06:52:40'
                $formData = json_decode($row->request->goal['form_data'], true);
            @endphp
            <div class="row">
                <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between pb-0">
                        <h4 class="m-0 font-weight-bold text-primary">{{ __('Goal') }} {{ $row->request->period }}</h4>
                        @if ($period == $row->request->goal->period && $row->request->goal->form_status != 'Draft')
                        <a class="btn btn-outline-warning fw-semibold {{ Auth::user()->employee_id == $row->request->initiated->employee_id ? '' : 'd-none' }}" href="{{ route('goals.edit', $row->request->goal->id) }}" onclick="showLoader()">{{ __('Revise Goals') }}</a>
                        @endif
                        @if ($row->request->goal->form_status == 'Draft' || $row->request->status == 'Pending' && count($row->request->approval) == 0 || $row->request->sendback_to == $row->request->employee_id)
                            <a class="btn btn-outline-warning fw-semibold {{ Auth::user()->employee_id == $row->request->initiated->employee_id ? '' : 'd-none' }}" href="{{ route('goals.edit', $row->request->goal->id) }}" onclick="showLoader()">{{ $row->request->status === 'Sendback' ? __('Revise Goals') : __('Edit') }}</a>
                        @endif
                    </div>
                    <div class="card-body">
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
                                <h5>{{ __('Adjusted By') }}</h5>
                                <p class="mt-2 mb-0 text-muted">{{ $row->request->updatedBy ? $row->request->updatedBy->name.' '.$row->request->updatedBy->employee_id : '-' }}{{ $row->request->adjustedBy && empty($adjustByManager) ? ' (Admin)': '' }}</p>
                            </div>
                            <div class="col-lg col-sm-12 p-2">
                                <h5>Status</h5>
                                <div>
                                    <a href="javascript:void(0)" data-bs-id="{{ $row->request->employee_id }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $row->request->goal->form_status == 'Draft' ? 'Draft' : ($row->approvalLayer ? 'Manager L'.$row->approvalLayer.' : '.$row->name : $row->name) }}" class="badge {{ $row->request->goal->form_status == 'Draft' || $row->request->sendback_to == $row->request->employee_id ? 'bg-secondary' : ($row->request->status === 'Approved' ? 'bg-success' : 'bg-warning')}} rounded-pill py-1 px-2">{{ $row->request->goal->form_status == 'Draft' ? 'Draft': ($row->request->status == 'Pending' ? __('Pending') : ($row->request->sendback_to == $row->request->employee_id ? 'Waiting For Revision' : $row->request->status)) }}</a>
                                </div>
                            </div>
                        </div>
                        @if ($row->request->sendback_messages && $row->request->sendback_to == $row->request->employee_id)
                            <hr class="mt-2 mb-2">
                            <div class="row p-2">
                                <div class="col-lg col-sm-12 px-2">
                                    <div class="form-group">
                                        <h5>Revision Notes :</h5>
                                        <p class="mt-1 mb-0 text-muted">{{ $row->request->sendback_messages }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="row">
                            <div class="col text-end">
                                <a data-bs-toggle="collapse" href="#collapse{{ $goalIndex }}" aria-expanded="true" aria-controls="collapse{{ $goalIndex }}">
                                    Detail <i class="ri-arrow-down-s-line"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="collapse" id="collapse{{ $goalIndex }}" style="">
                        <div class="card-body p-0">
                            <table class="table table-striped table-bordered m-0">
                                <tbody>
                                @if ($formData)
                                @foreach ($formData as $index => $data)
                                    <tr>
                                        <td scope="row">
                                            <div class="row p-2">
                                                <div class="col-lg-4 col-sm-12 p-2">
                                                    <div class="form-group">
                                                        <h5>KPI {{ $index + 1 }}</h5>
                                                        <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['kpi'] }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-lg col-sm-12 p-2">
                                                    <div class="form-group">
                                                        <h5>Target</h5>
                                                        <p class="mt-1 mb-0 text-muted">{{ $data['target'] }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-lg col-sm-12 p-2">
                                                    <div class="form-group">
                                                        <h5>{{ __('Uom') }}</h5>
                                                        <p class="mt-1 mb-0 text-muted">{{ is_null($data['custom_uom']) ? $data['uom'] : $data['custom_uom'] }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-lg col-sm-12 p-2">
                                                    <div class="form-group">
                                                        <h5>{{ __('Type') }}</h5>
                                                        <p class="mt-1 mb-0 text-muted">{{ $data['type'] }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-lg col-sm-12 p-2">
                                                    <div class="form-group">
                                                        <h5>{{ __('Weightage') }}</h5>
                                                        <p class="mt-1 mb-0 text-muted">{{ $data['weightage'] }}%</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row p-2">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <h5>{{ __('Description') }}</h5>
                                                        <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['description'] ?? '-' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                    @else
                                    <p>No form data available.</p>
                                    @endif 
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col text-end">
                                    <a data-bs-toggle="collapse" href="#collapse{{ $goalIndex }}" aria-expanded="true" aria-controls="collapse{{ $goalIndex }}">
                                        Close <i class="ri-arrow-up-s-line"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        @empty
            <div class="row">
                <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        {{ __('No Goals Found. Please Create Your Goals ') }}<i class="ri-arrow-right-up-line"></i>
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
                    title: "Cannot create goals",
                    text: '{{ Session::get('error') }}',
                    confirmButtonText: "OK",
                });
            });            
        </script>
        @endif
        <script>
            function yearGoal() {
                $("#formYearGoal").submit();
            }
        </script>
    @endpush