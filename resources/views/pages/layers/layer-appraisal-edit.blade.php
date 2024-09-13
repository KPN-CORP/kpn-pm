@extends('layouts_.vertical', ['page_title' => 'Layer Appraisal'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="detail-employee">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md">
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Employee Name</p>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->fullname }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Employee ID</p>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->employee_id }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Join Date</p>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->formattedDoj }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Business Unit</p>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->group_company }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Company</p>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->company_name }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Unit</p>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->unit }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Designation</p>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->designation }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <p class="text-muted mb-1">Office Location</p>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->office_area }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form action="{{ route('layer-appraisal.update') }}" method="post">
            @csrf
            <input type="hidden" id="employee_id" name="employee_id" value="{{ $datas->employee_id }}">
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-body p-1 p-md-3">
                            <div class="row">
                                <div class="col">
                                    <div class="card bg-secondary-subtle shadow-none">
                                        <div class="card-body">
                                            <h5>Manager</h5>
                                            @foreach ($groupLayers['manager'] as $layerManager)
                                            <select name="manager" id="manager" class="form-select selection2" required>
                                                <option value="">- Please Select -</option>
                                                @foreach ($employee as $item)
                                                    <option value="{{ $item->employee_id }}" {{ $item->employee_id == $layerManager->approver_id ? 'selected' : '' }}>{{ $item->fullname }} {{ $item->employee_id }}</option>
                                                @endforeach
                                            </select>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="card bg-secondary-subtle shadow-none">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md">
                                                    <div class="mb-2">
                                                        <h5>Peers 1</h5>
                                                        <select name="peers[]" id="peer1" class="form-select selection2">
                                                            <option value="">- Please Select -</option>
                                                            @foreach ($employee as $item)
                                                                <option value="{{ $item->employee_id }}"
                                                                @if (!empty($groupLayers['peers']) && !empty($groupLayers['peers'][0]) && $item->employee_id == $groupLayers['peers'][0]->approver_id)
                                                                    selected
                                                                @endif
                                                                >{{ $item->fullname }} {{ $item->employee_id }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    @if ($errors->any())
                                                        <div class="alert alert-danger">
                                                            <ul>
                                                                @foreach ($errors->all() as $error)
                                                                    <li>{{ $error }}</li>
                                                                    <div class="text-danger error-message fs-14"></div>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md">
                                                    <div class="mb-2">
                                                        <h5>Peers 2</h5>
                                                        <select name="peers[]" id="peer2" class="form-select selection2">
                                                            <option value="">- Please Select -</option>
                                                            @foreach ($employee as $item)
                                                                <option value="{{ $item->employee_id }}"
                                                                @if (!empty($groupLayers['peers']) && !empty($groupLayers['peers'][1]) && $item->employee_id == $groupLayers['peers'][1]->approver_id)
                                                                    selected
                                                                @endif
                                                                >{{ $item->fullname }} {{ $item->employee_id }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="text-danger error-message fs-14"></div>
                                                </div>
                                                <div class="col-md">
                                                    <div class="mb-2">
                                                        <h5>Peers 3</h5>
                                                        <select name="peers[]" id="peer3" class="form-select selection2">
                                                            <option value="">- Please Select -</option>
                                                            @foreach ($employee as $item)
                                                                <option value="{{ $item->employee_id }}"
                                                                @if (!empty($groupLayers['peers']) && !empty($groupLayers['peers'][2]) && $item->employee_id == $groupLayers['peers'][2]->approver_id)
                                                                    selected
                                                                @endif
                                                                >{{ $item->fullname }} {{ $item->employee_id }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="text-danger error-message fs-14"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="card bg-secondary-subtle shadow-none">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md">
                                                    <div class="mb-2">
                                                        <h5>Subordinate 1</h5>
                                                        <select name="subs[]" id="sub1" class="form-select selection2">
                                                            <option value="">- Please Select -</option>
                                                            @foreach ($employee as $item)
                                                                <option value="{{ $item->employee_id }}" 
                                                                @if (!empty($groupLayers['subordinate']) && !empty($groupLayers['subordinate'][0]) && $item->employee_id == $groupLayers['subordinate'][0]->approver_id)
                                                                    selected
                                                                @endif
                                                                >{{ $item->fullname }} {{ $item->employee_id }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="text-danger error-message fs-14"></div>
                                                </div>
                                                <div class="col-md">
                                                    <div class="mb-2">
                                                        <h5>Subordinate 2</h5>
                                                        <select name="subs[]" id="sub2" class="form-select selection2">
                                                            <option value="">- Please Select -</option>
                                                            @foreach ($employee as $item)
                                                                <option value="{{ $item->employee_id }}" 
                                                                @if (!empty($groupLayers['subordinate']) && !empty($groupLayers['subordinate'][1]) && $item->employee_id == $groupLayers['subordinate'][1]->approver_id)
                                                                    selected
                                                                @endif
                                                                >{{ $item->fullname }} {{ $item->employee_id }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="text-danger error-message fs-14"></div>
                                                </div>
                                                <div class="col-md">
                                                    <div class="mb-2">
                                                        <h5>Subordinate 3</h5>
                                                        <select name="subs[]" id="sub3" class="form-select selection2">
                                                            <option value="">- Please Select -</option>
                                                            @foreach ($employee as $item)
                                                                <option value="{{ $item->employee_id }}" 
                                                                @if (!empty($groupLayers['subordinate']) && !empty($groupLayers['subordinate'][2]) && $item->employee_id == $groupLayers['subordinate'][2]->approver_id)
                                                                    selected
                                                                @endif
                                                                >{{ $item->fullname }} {{ $item->employee_id }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="text-danger error-message fs-14"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="card bg-secondary-subtle shadow-none">
                                        <div class="card-body">
                                                @if (isset($groupLayers['calibrator']))
                                                    @foreach ($groupLayers['calibrator'] as $index => $layerCalibrator)
                                                    <div class="row" id="calibrator-row-{{ $index + 1 }}">
                                                        <div class="col-10">
                                                            <div class="mb-2">
                                                                <h5>Calibrator {{ $index + 1 }}</h5>
                                                                <select name="calibrators[]" id="calibrator{{ $index + 1 }}" class="form-select selection2">
                                                                    <option value="">- Please Select -</option>
                                                                    @foreach ($employee as $item)
                                                                        <option value="{{ $item->employee_id }}" {{ $item->employee_id == $layerCalibrator->approver_id ? 'selected' : '' }}>{{ $item->fullname }} {{ $item->employee_id }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="text-danger error-message fs-14"></div>
                                                        </div>
                                                        @if ( $index > 0)
                                                        <div class="col-2 d-flex align-items-end justify-content-end">
                                                            <div class="mt-1 mb-2">
                                                                <a class="btn btn-outline-danger rounded remove-calibrator" data-calibrator-id="{{ $index + 1 }}">
                                                                <i class="ri-delete-bin-line"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        @endif
                                                    </div>
                                                    @endforeach
                                                    @else
                                                    <div class="row">
                                                        <div class="col-10">
                                                            <div class="mb-2">
                                                                <h5>Calibrator 1</h5>
                                                                <select name="calibrators[]" id="calibrator1" class="form-select selection2">
                                                                    <option value="">- Please Select -</option>
                                                                    @foreach ($employee as $item)
                                                                        <option value="{{ $item->employee_id }}">{{ $item->fullname }} {{ $item->employee_id }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            <div id="calibrator-container"></div> <!-- Container for dynamic calibrators -->
                                            <div class="row">
                                                <div class="col">
                                                    <a id="add-calibrator" class="btn btn-sm rounded btn-outline-primary"><i class="ri-add-line me-1 fs-16"></i>Add Calibrator</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row justify-content-end">
                                <div class="col-6 col-md-auto">
                                    <a href="{{ route('layer-appraisal') }}" class="btn btn-outline-secondary w-100 w-md-auto">{{ __('Cancel') }}</a>
                                </div>
                                <div class="col-6 col-md-auto">
                                    <button class="btn btn-primary px-3 w-100 w-md-auto">Save</button>
                                </div>
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
    var index = {{ $index + 1 }};
</script>
@endpush