@extends('layouts_.vertical', ['page_title' => 'Goals'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content_ --->
    <div class="container-fluid ">
        <!-- Page Heading -->
        <div class="mandatory-field">
            <div id="alertField" class="alert alert-danger alert-dismissible {{ Session::has('error') ? '' : 'fade' }}"
                role="alert" {{ Session::has('error') ? '' : 'hidden' }}>
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
                        <select name="filterYear" id="filterYear" onchange="yearGoal()" class="form-select border-secondary"
                            @style('width: 120px')>
                            <option value="">{{ __('select all') }}</option>
                            @foreach ($selectYear as $year)
                                <option value="{{ $year->year }}" {{ $year->year == $filterYear ? 'selected' : '' }}>
                                    {{ $year->year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="mb-3 text-end">
                        <a href="{{ $goals ? route('goals.form', Auth::user()->employee_id) : '#' }}"
                            class="btn {{ $goals ? 'btn-primary shadow' : 'btn-secondary-subtle disabled' }}">{{ __('Create Goal') }}</a>
                    </div>
                </div>
            </div>
        </form>
        @forelse ($data as $row)
            @php
                // Assuming $dateTimeString is the date string '2024-04-29 06:52:40'
                $year = date('Y', strtotime($row->request->created_at));
                $formData = json_decode($row->request->goal['form_data'], true);
            @endphp
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow mb-2">
                        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                            <h4 class="m-0 font-weight-bold text-primary">{{ __('Goal') }} {{ $year }}</h4>
                            @if (
                                ($row->request->status == 'Pending' && count($row->request->approval) == 0) ||
                                    $row->request->sendback_to == $row->request->employee_id)
                                <a class="btn btn-outline-warning fw-semibold"
                                    href="{{ route('goals.edit', $row->request->goal->id) }}">{{ __('Edit') }}</a>
                            @endif
                        </div>
                        <div class="card-body pt-0">
                            <div class="row gy-2">
                                <div class="col-md col-6">
                                    <p class="h5 mb-1">{{ __('Initiated By') }}</p>
                                    <p class=" mb-0 text-muted">
                                        {{ $row->request->initiated->name . ' (' . $row->request->initiated->employee_id . ')' }}
                                    </p>
                                </div>
                                <div class="col-md col-6">
                                    <p class="h5 mb-1">{{ __('Initiated Date') }}</p>
                                    <p class="mb-0 text-muted">{{ $row->request->formatted_created_at }}</p>
                                </div>
                                <div class="col-md col-6">
                                    <p class="h5 mb-1">{{ __('Last Updated On') }}</p>
                                    <p class="mb-0 text-muted">{{ $row->request->formatted_updated_at }}</p>
                                </div>
                                <div class="col-md col-6">
                                    <p class="h5 mb-1">{{ __('Adjusted By') }}</p>
                                    <p class="mb-0 text-muted">
                                        {{ $row->request->updatedBy ? $row->request->updatedBy->name . ' ' . $row->request->updatedBy->employee_id : '-' }}{{ $row->request->adjustedBy && empty($adjustByManager) ? ' (Admin)' : '' }}
                                    </p>
                                </div>
                                <div class="col-md col-12">
                                    <p class="h5 mb-1">Status</p>
                                    <div>
                                        <a href="javascript:void(0)" data-bs-id="{{ $row->request->employee_id }}"
                                            data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-content="{{ $row->request->goal->form_status == 'Draft' ? 'Draft' : ($row->approvalLayer ? 'Manager L' . $row->approvalLayer . ' : ' . $row->name : $row->name) }}"
                                            class="badge {{ $row->request->goal->form_status == 'Draft' || $row->request->sendback_to == $row->request->employee_id ? 'bg-secondary' : ($row->request->status === 'Approved' ? 'bg-success' : 'bg-warning') }} rounded-pill py-1 px-2">{{ $row->request->goal->form_status == 'Draft' ? 'Draft' : ($row->request->status == 'Pending' ? __('Pending') : ($row->request->sendback_to == $row->request->employee_id ? 'Waiting For Revision' : $row->request->status)) }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if ($row->request->sendback_messages && $row->request->sendback_to == $row->request->employee_id)
                            <div class="card-header" style="background-color: lightyellow;">
                                <div class="row p-1">
                                    <div class="col-md col-6 px-2">
                                        <div class="form-group">
                                            <h5>Revision Notes :</h5>
                                            <p class="mt-1 mb-0 text-muted">{{ $row->request->sendback_messages }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="card-body p-0">
                            <table class="table table-striped p-0 m-0">
                                <tbody>
                                    @if ($formData)
                                        @foreach ($formData as $index => $data)
                                            <tr>
                                                <td scope="row">
                                                    <div class="row p-2 gx-2 gy-2">
                                                        <div class="col-md-5 col-12">
                                                            <div class="form-group">
                                                                <p class="h5 mb-1">KPI {{ $index + 1 }}</p>
                                                                <p class=" mb-0 text-muted">
                                                                    {{ $data['kpi'] }}</p>
                                                            </div>
                                                        </div>
                                                        <div class="col-md col-6">
                                                            <div class="form-group">
                                                                <p class="h5 mb-1">Target</p>
                                                                <p class="mt-1 mb-0 text-muted">{{ $data['target'] }}</p>
                                                            </div>
                                                        </div>
                                                        <div class="col-md col-6">
                                                            <div class="form-group">
                                                                <p class="h5 mb-1">{{ __('Uom') }}</p>
                                                                <p class="mt-1 mb-0 text-muted">
                                                                    {{ is_null($data['custom_uom']) ? $data['uom'] : $data['custom_uom'] }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="col-md col-6">
                                                            <div class="form-group">
                                                                <p class="h5 mb-1">{{ __('Type') }}</p>
                                                                <p class="mt-1 mb-0 text-muted">{{ $data['type'] }}</p>
                                                            </div>
                                                        </div>
                                                        <div class="col-md col-6">
                                                            <div class="form-group">
                                                                <p class="h5 mb-1">{{ __('Weightage') }}</p>
                                                                <p class="mt-1 mb-0 text-muted">{{ $data['weightage'] }}%
                                                                </p>
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
                    </div>
                </div> </div>
            @empty
                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow mb-2">
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
    @if (Session::has('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
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
