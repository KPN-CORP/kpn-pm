@extends('layouts_.vertical', ['page_title' => 'Approval Goals'])

@section('css')
<style>
.version-header {
    padding: 10px 15px;
    border-radius: 6px;
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
    font-size: 0.7rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    font-weight: 600;
}
.border-dashed {
    border-style: dashed !important;
    border-width: 2px !important;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    
    @if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)
            {{ $error }}
        @endforeach
    </div>
    @endif

    @foreach ($data as $index => $row)
        <div class="row mt-3">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-2">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="row mb-1">
                                    <div class="col-4 text-muted small fw-medium">Employee Name</div>
                                    <div class="col-8 fw-semibold small">: {{ $row->request->employee->fullname }}</div>
                                </div>
                                <div class="row mb-1">
                                    <div class="col-4 text-muted small fw-medium">Employee ID</div>
                                    <div class="col-8 fw-semibold small">: {{ $row->request->employee->employee_id }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-4 text-muted small fw-medium">Job Level</div>
                                    <div class="col-8 fw-semibold small">: {{ $row->request->employee->job_level }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row mb-1">
                                    <div class="col-4 text-muted small fw-medium">Business Unit</div>
                                    <div class="col-8 fw-semibold small">: {{ $row->request->employee->group_company }}</div>
                                </div>
                                <div class="row mb-1">
                                    <div class="col-4 text-muted small fw-medium">Division</div>
                                    <div class="col-8 fw-semibold small">: {{ $row->request->employee->unit }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-4 text-muted small fw-medium">Designation</div>
                                    <div class="col-8 fw-semibold small">: {{ $row->request->employee->designation_name }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="mandatory-field"></div>

    <form id="goalApprovalForm" action="{{ route('approval.goal') }}" method="post">
        @csrf
        <input type="hidden" name="id" value="{{ $row->request->goal->id }}">
        <input type="hidden" name="employee_id" value="{{ $row->request->employee_id }}">
        <input type="hidden" name="current_approver_id" value="{{ $row->request->current_approval_id }}">
        
        <h5 class="fw-bold text-dark">{{ __('Target') }} {{ $row->request->period }}</h5>

        @php
            $formData = json_decode($row->request->goal['form_data'], true) ?? [];
            $oldFormData = $beforeSnapshot ?? [];
            $maxCount = max(is_array($oldFormData) ? count($oldFormData) : 0, is_array($formData) ? count($formData) : 0);
            function isChanged($old, $new) {
                return (string)$old !== (string)$new;
            }
            
        @endphp

        @for ($i = 0; $i < $maxCount; $i++)
        <div class="p-3 mb-3 rounded shadow-sm" style="background-color: #f4f6f9; border: 1px solid #eef0f2;">
            <div class="row align-items-stretch">
            <div class="col-md-6 mb-2 mb-lg-0">
                @php $oldData = $oldFormData[$i]; @endphp
                @if(isset($oldFormData[$i]))
                    <div class="card shadow-none border h-100" style="background-color: #fafafa;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary">BEFORE</span>
                                    <h6 class="card-title fw-bold text-secondary mb-0" style="font-size: 0.85rem;">Goal {{ $i + 1 }}</h6>
                                </div>
                                @if(!isset($formData[$i]))
                                    {{-- <span class="badge bg-danger">DELETED</span> --}}
                                @endif
                            </div>
                            
                            <div class="mb-2">
                                <textarea class="form-control form-control-sm text-muted bg-light" rows="1" readonly style="resize: none">{{ $oldData['kpi'] ?? '-' }}</textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="kpi-label text-secondary">Goal Descriptions</label>
                                <textarea class="form-control form-control-sm text-muted bg-light" rows="2" style="resize: none" readonly>{{ $oldData['description'] ?? "-" }}</textarea>
                            </div>
                            
                            <div class="row g-2">
                                <div class="col-md-4 col-6">
                                    <label class="kpi-label text-secondary">Target</label>
                                    <input type="text" value="{{ $oldData['target'] ?? '-' }}" class="form-control form-control-sm text-muted bg-light" readonly>
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="kpi-label text-secondary">UoM</label>
                                    <input type="text" value="{{ isset($oldData['uom']) && $oldData['uom'] !== 'Other' ? $oldData['uom'] : ($oldData['custom_uom'] ?? '-') }}" class="form-control form-control-sm text-muted bg-light" readonly>
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="kpi-label text-secondary">Type</label>
                                    <input type="text" value="{{ $oldData['type'] ?? '-' }}" class="form-control form-control-sm text-muted bg-light" readonly>
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="kpi-label text-secondary">Weightage</label>
                                    <div class="input-group input-group-sm flex-nowrap">
                                        <input type="number" class="form-control text-center text-muted bg-light" value="{{ $oldData['weightage'] ?? '0' }}" readonly>
                                        <span class="input-group-text bg-secondary text-white border-secondary">%</span>
                                    </div>
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="kpi-label text-secondary">Review Period</label>
                                   <select class="form-select form-select-sm text-muted bg-light" disabled>
                                        <option value="">- Select -</option>
                                        @foreach ($reviewPeriodOption as $label => $options)
                                            @foreach ($options as $option)
                                                <option value="{{ $option['value'] }}"
                                                    {{ $oldData['review_period'] == $option['value'] ? 'selected' : '' }}>
                                                    {{ $option['label'] }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="kpi-label text-secondary">Calc Method</label>
                                    <select class="form-select form-select-sm text-muted bg-light" disabled>
                                        <option value="">- Select -</option>
                                        @foreach ($calculationMethodOption as $label => $options)
                                            @foreach ($options as $option)
                                                <option value="{{ $option['value'] }}"
                                                    {{ $oldData['calculation_method'] == $option['value'] ? 'selected' : '' }}>
                                                    {{ $option['label'] }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card shadow-none border border-dashed border-secondary h-100 d-flex align-items-center justify-content-center" style="background-color: #f8f9fa; min-height: 150px;">
                        <div class="text-muted small fw-medium text-center">
                            <i class="ri-add-circle-line fs-4 d-block mb-1"></i>New goal added in current version
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-md-6 mb-lg-0">
                @if(isset($formData[$i]))
                    @php $data = $formData[$i];
                    @endphp
                    <div class="card border-primary border-opacity-50 bg-white shadow-sm h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-primary border-opacity-25 pb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary d-xl-none">AFTER</span>
                                    <h6 class="card-title fw-bold text-primary mb-0" style="font-size: 0.85rem;">Goal {{ $i + 1 }}</h6>
                                </div>
                                @if(!isset($oldFormData[$i]))
                                    <span class="badge bg-success">NEW</span>
                                @endif
                            </div>
                            
                            <div class="mb-2">
                                <textarea name="kpi[]" class="form-control form-control-sm {{ isChanged($oldData['kpi'] ?? '', $data['kpi']) ? 'bg-primary-subtle fw-medium' : '' }}" rows="1" style="resize: none">{{ $data['kpi'] }}</textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="kpi-label text-primary">Goal Descriptions</label>
                                <textarea name="description[]" class="form-control form-control-sm {{ isChanged($oldData['description'] ?? '', $data['description']) ? 'bg-primary-subtle fw-medium' : '' }}" rows="2" style="resize: none">{{ $data['description'] ?? "" }}</textarea>
                            </div>
                            
                            <div class="row g-2">
                                <div class="col-md-4 col-6">
                                    <label class="kpi-label text-primary">Target</label>
                                    <input type="text" name="target[]" id="target{{ $i }}" oninput="validateDigits(this, {{ $i }})" value="{{ $data['target'] }}" class="form-control form-control-sm {{ isChanged($oldData['target'] ?? '', $data['target']) ? 'bg-primary-subtle fw-medium' : '' }}">
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="kpi-label text-primary">{{ __('Uom') }}</label>
                                    <input type="text" name="uom[]" value="{{ $data['uom'] !== 'Other' ? $data['uom'] : $data['custom_uom'] }}" class="form-control form-control-sm bg-secondary-subtle {{ isChanged($oldData['uom'] ?? '', $data['uom']) ? 'bg-primary-subtle fw-medium' : '' }}" readonly>
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="kpi-label text-primary">{{ __('Type') }}</label>
                                    <input type="text" name="type[]" value="{{ $data['type'] }}" class="form-control form-control-sm bg-secondary-subtle {{ isChanged($oldData['type'] ?? '', $data['type']) ? 'bg-primary-subtle fw-medium' : '' }}" readonly>
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="kpi-label text-primary">{{ __('Weightage') }}</label>
                                    <div class="input-group input-group-sm flex-nowrap">
                                        <input type="number" min="5" max="100" step="0.1" class="form-control text-center {{ isChanged($oldData['weightage'] ?? '', $data['weightage']) ? 'bg-primary-subtle fw-medium' : '' }}" name="weightage[]" value="{{ $data['weightage'] }}">
                                        <span class="input-group-text bg-primary text-white border-primary">%</span>
                                    </div>                                          
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="kpi-label text-primary">Review Period</label>
                                    <select class="form-select select-type  {{ isChanged($oldData['review_period'] ?? '', $data['review_period']) ? 'bg-primary-subtle fw-medium' : '' }}" name="review_period[]" id="review_period{{ $index }}" required>
                                        <option value="">- Select -</option>
                                        @foreach ($reviewPeriodOption as $label => $options)
                                            @foreach ($options as $option)
                                                <option value="{{ $option['value'] }}"
                                                    {{ $data['review_period'] == $option['value'] ? 'selected' : '' }}>
                                                    {{ $option['label'] }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="kpi-label text-primary">Calc Method</label>
                                    <select class="form-select select-type {{ isChanged($oldData['calculation_method'] ?? '', $data['calculation_method']) ? 'bg-primary-subtle fw-medium' : '' }}" name="calculation_method[]" id="calculation_method{{ $index }}" required>
                                        <option value="">- Select -</option>
                                        @foreach ($calculationMethodOption as $label => $options)
                                            @foreach ($options as $option)
                                                <option value="{{ $option['value'] }}"
                                                    {{ $data['calculation_method'] == $option['value'] ? 'selected' : '' }}>
                                                    {{ $option['label'] }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card shadow-none border border-dashed border-danger h-100 d-flex align-items-center justify-content-center" style="background-color: #fff5f5; min-height: 150px;">
                        <div class="text-danger small fw-medium text-center">
                            <i class="ri-delete-bin-line fs-4 d-block mb-1"></i>Some Goal deleted in current version
                        </div>
                    </div>
                @endif
            </div>
</div>
        </div>
        @endfor
        
        <div class="card shadow-sm border-0 mt-2 mb-4">
            <div class="card-body p-3 bg-light rounded border">
                <label class="kpi-label text-dark">Messages*</label>
                <textarea name="messages" id="messages{{ $row->request->id }}" class="form-control form-control-sm" placeholder="Enter messages.." rows="2">{{ $row->request->messages }}</textarea>
            </div>
        </div>
    </form>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3 bg-white rounded">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="mb-3 mb-md-0 text-center text-md-start">
                    <h6 class="mb-0 fw-bold">{{ __('Total Weightage') }} : <span class="text-success fs-5" id="totalWeightage">100%</span></h6>
                </div>
                
                <div class="w-100 w-md-auto">
                    <form id="goalSendbackForm" action="{{ route('sendback.goal') }}" method="post">
                        @csrf
                        <input type="hidden" name="request_id" id="request_id">
                        <input type="hidden" name="sendto" id="sendto">
                        <input type="hidden" name="sendback" id="sendback" value="Sendback">
                        <textarea @style('display: none') name="sendback_message" id="sendback_message"></textarea>
                        <input type="hidden" name="form_id" value="{{ $row->request->form_id }}">
                        <input type="hidden" name="approver" id="approver" value="{{ $row->request->manager->fullname.' ('.$row->request->manager->employee_id.')' }}">
                        <input type="hidden" name="employee_id" value="{{ $row->request->employee_id }}">
                        
                        @if ($row->request->sendback_messages)
                        <div class="mb-3 text-start">
                            <label class="kpi-label text-danger">Sendback Messages</label>
                            <textarea class="form-control form-control-sm border-danger bg-danger-subtle" @disabled(true)>{{ $row->request->sendback_messages }}</textarea>
                        </div>
                        @endif
                        
                        <div class="d-flex flex-wrap justify-content-center justify-content-md-end gap-2">
                            <div class="dropdown">
                                <button class="btn btn-warning btn-sm fw-medium dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ __('Send Back') }}
                                </button>
                                <div class="dropdown-menu shadow-sm" style="font-size: 0.85rem;">
                                    <h6 class="dropdown-header text-dark fw-bold">Select person below:</h6>
                                    @if ($row->request->created_by == $row->request->employee->id)
                                        <a class="dropdown-item py-2" href="javascript:void(0)" onclick="sendBack('{{ $row->request->id }}','{{ $row->request->employee->employee_id }}','{{ $row->request->employee->fullname }}')">{{ $row->request->employee->fullname .' ('.$row->request->employee->employee_id.')' }}</a>
                                    @endif
                                    @foreach ($row->request->approval as $item)
                                        <a class="dropdown-item py-2" href="javascript:void(0)" onclick="sendBack('{{ $item->request_id }}','{{ $item->approver_id }}','{{ $item->approverName->fullname }}')">{{ $item->approverName->fullname.' ('.$item->approver_id.')' }}</a>
                                    @endforeach
                                </div> 
                            </div>
                            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm fw-medium">{{ __('Cancel') }}</a>
                            <button type="button" id="submitButton" onclick="confirmAprroval()" class="btn btn-primary btn-sm fw-medium">
                                <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>{{ __('Approve') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script>
        const uom = '{{ __('Uom') }}';
        const type = '{{ __('Type') }}';
        const weightage = '{{ __('Weightage') }}';
        const errorMessages = '{{ __('Error Messages') }}';
        const errorAlertMessages = '{{ __('Error Alert Messages') }}';
        const errorConfirmMessages = '{{ __('Error Confirm Messages') }}';
    </script>
@endpush