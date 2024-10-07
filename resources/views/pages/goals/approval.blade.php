@extends('layouts_.vertical', ['page_title' => 'Approval Goals'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        
    @if ($errors->any())
    <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                {{ $error }}
            @endforeach
    </div>
    @endif
        <div class="mandatory-field"></div>
        @foreach ($data as $index => $row)
        <form id="goalApprovalForm" action="{{ route('approval.goal') }}" method="post">
            @csrf
            <input type="hidden" name="id" value="{{ $row->request->goal->id }}">
            <input type="hidden" name="employee_id" value="{{ $row->request->employee_id }}">
            <input type="hidden" name="current_approver_id" value="{{ $row->request->current_approval_id }}">
            <div class="row">
                <div class="col-lg">
                    <div class="mb-3">
                        <h4>{{ $row->request->employee->fullname }} / <span class="font-weight-light">{{ $row->request->employee->employee_id }}</span></h4>
                    </div>
                </div>
            </div>
              <!-- Content Row -->
              <div class="container-card">
                @php
                    $formData = json_decode($row->request->goal['form_data'], true);
                @endphp
                @if ($formData)
                @foreach ($formData as $index => $data)
                    <div class="card col-md-12 mb-4 shadow-sm">
                        <div class="card-header bg-white align-items-center">
                            <h4>KPI {{ $index + 1 }}</h4>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="form-label" for="kpi">KPI</label>
                                        <textarea name="kpi[]" class="form-control" required>{{ $data['kpi'] }}</textarea>
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label class="form-label" for="target">Target</label>
                                        <input type="number" oninput="validateDigits(this)" name="target[]" value="{{ $data['target'] }}" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label class="form-label" for="uom">{{ __('Uom') }}</label>
                                        <input type="text" name="uom[]" id="uom" value="{{ $data['uom'] !== 'Other' ? $data['uom'] : $data['custom_uom'] }}" class="form-control bg-secondary-subtle" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label class="form-label" for="type">{{ __('Type') }}</label>
                                        <input type="text" name="type[]" id="type" value="{{ $data['type'] }}" class="form-control bg-secondary-subtle" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label class="form-label" for="weightage">{{ __('Weightage') }}</label>
                                        <div class="input-group">
                                            <input name="weightage[]" class="form-control" value="{{ $data['weightage'] }}" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        <!-- Tambahkan kode untuk menampilkan error weightage jika ada -->
                                        @if ($errors->has("weightage"))
                                            <span class="text-danger">{{ $errors->first("weightage") }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="row">
                    <div class="col-lg">
                        <div class="mt-2 mb-3">
                            <label class="form-label" for="messages">Messages*</label>
                            <textarea name="messages" id="messages{{ $row->request->id }}" class="form-control" placeholder="Enter messages..">{{ $row->request->messages }}</textarea>
                        </div>
                    </div>
                </div>
                @else
                    <p>No form data available.</p>
                @endif                
            </div>
        </form>
        <div class="row">
            <div class="col-lg">
                <div class="align-items-center mb-3">
                    <h4>{{ __('Total Weightage') }} : <span class="font-weight-bold text-success" id="totalWeightage">100%</span></h4>
                </div>
            </div>
            <div class="col-lg">
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
                    <div class="d-flex align-items-center mt-4">
                        <div class="form-group w-100">
                            <label>Sendback Messages</label>
                            <textarea class="form-control" @disabled(true)>{{ $row->request->sendback_messages }}</textarea>
                        </div>
                    </div>
                    @endif
                    <div class="row">
                        <div class="col-lg">
                            <div class="text-center text-lg-end mb-3">
                                <a class="btn btn-warning px-2 me-2 dropdown-toggle" href="javascript:void(0)" role="button" aria-haspopup="true" data-bs-toggle="dropdown" data-bs-offset="0,10" aria-expanded="false">Send back</a>
                                    <div class="dropdown-menu shadow-sm">
                                        <h6 class="dropdown-header dark">Select person below :</h6>
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="sendBack('{{ $row->request->id }}','{{ $row->request->employee->employee_id }}','{{ $row->request->employee->fullname }}')">{{ $row->request->employee->fullname .' '.$row->request->employee->employee_id }}</a>
                                        @foreach ($row->request->approval as $item)
                                            <a class="dropdown-item" href="javascript:void(0)" onclick="sendBack('{{ $item->request_id }}','{{ $item->approver_id }}','{{ $item->approverName->fullname }}')">{{ $item->approverName->fullname.' '.$item->approver_id }}</a>
                                        @endforeach
                                    </div> 
                                <a href="{{ url()->previous() }}" class="btn btn-outline-dark px-2 me-2">{{ __('Cancel') }}</a>
                                <a href="javascript:void(0)" id="submitButton" onclick="confirmAprroval()" class="btn btn-primary px-2"><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>Approve</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endforeach
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