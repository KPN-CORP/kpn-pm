@extends('layouts_.vertical', ['page_title' => 'Create Flow'])

@section('css')
<style>
.dataTables_scrollHeadInner {
    width: 100% !important;
}
.table-responsive, .dataTables_scroll {
    width: 100%;
}
</style>
@endsection


@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <div class="container">
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="mb-0 fs-20">Create Approval Flow</p>
                                    <a href="{{ route('approval-flow.index') }}" type="button" class="btn-close" aria-label="Close"></a>
                                </div>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <div class="alert alert-success mt-3">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form action="{{ route('approval-flow.store') }}" method="POST">
                            @csrf

                            <div class="mb-4">
                                <div class="row mb-2">
                                    <div class="form-group col-md-4">
                                        <label for="flow_name">Name*</label>
                                        <input type="text" id="flow_name" name="flow_name" value="{{ old('flow_name') }}" class="form-control" required>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="form-group col-md-7">
                                        <label for="description">Description</label>
                                        <textarea id="description" name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                                    </div>
                                </div>
                                <div class="form-group form-check">
                                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="form-check-input">
                                    <label for="is_active" class="form-check-label">Active</label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <p class="mb-2 fs-18">Approval Stage</p>
                                <div class="table-responsive">
                                    <table class="table fs-12">
                                        <thead class="bg-light">
                                            <tr>
                                                <th style="width: 50px;">ORDER</th>
                                                <th style="width: 80px;">ROW ID</th>
                                                <th style="min-width: 150px;">APPROVAL STAGE NAME</th>
                                                <th style="min-width: 250px;">APPROVERS</th>
                                                {{-- <th style="width: 100px;">ALLOTTED TIME</th> --}}
                                                <th></th> <!-- For delete button -->
                                            </tr>
                                        </thead>
                                        <tbody id="steps-container">
                                            @if (old('steps'))
                                                @foreach (old('steps') as $index => $step)
                                                    <tr data-index="{{ $index }}" class="align-middle">
                                                        <td><span class="step-number-display">{{ $index + 1 }}</span></td>
                                                        <td>R{{ $index + 1 }}</td>
                                                        <td>
                                                            <input type="text" name="steps[{{ $index }}][step_name]" value="{{ $step['step_name'] ?? '' }}" class="form-control form-control-sm">
                                                            <input type="hidden" name="steps[{{ $index }}][step_number]" value="{{ $index + 1 }}" class="form-control form-control-sm">
                                                        </td>
                                                        <td class="approvers-cell">
                                                            <div class="form-group mb-2">
                                                                <label class="form-label" for="steps-{{ $index }}-approver_role">Select Approvers</label>
                                                                <select multiple name="steps[{{ $index }}][approver_role][]" class="form-select form-select-sm mb-1 select360" id="steps-{{ $index }}-approver_role" data-placeholder="Select approver">
                                                                    @foreach($approverRoles as $key => $value)
                                                                        <option value="{{ $value }}"
                                                                            @if(in_array($value, old("steps.{$index}.approver_role", []))) selected @endif>
                                                                            {{ $value }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label" for="steps[{{ $index }}][approver_user_id]">Select Employee (Optional)</label>
                                                                <select multiple name="steps[{{ $index }}][approver_user_id][]" class="form-select form-select-sm mb-1 select360" id="steps-{{ $index }}-approver_user_id" data-placeholder="Select employee">
                                                                    @foreach($employees as $item)
                                                                        <option value="{{ $item['id'] }}"
                                                                        @if(in_array($item['id'], old("steps.{$index}.approver_user_id", []))) selected @endif>
                                                                        {{ $item['value'] }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </td>
                                                        <td class="d-none">
                                                            <input type="number" name="steps[{{ $index }}][allotted_time]" value="{{ $step['allotted_time'] ?? '' }}" class="form-control form-control-sm">
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-light rounded py-1 px-2 m-1 fs-14 additional-settings-btn" data-toggle="modal" data-target="#additionalSettingsModal" data-step-index="{{ $index }}">
                                                                <i class="ri-more-2-fill"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-light rounded py-1 px-2 fs-14 remove-step"><i class="ri-delete-bin-line"></i></button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                {{-- Initial empty step --}}
                                                <tr data-index="0" class="align-middle">
                                                    <td><span class="step-number-display">1</span></td>
                                                    <td>R1</td>
                                                    <td>
                                                        <input type="text" name="steps[0][step_name]" class="form-control form-control-sm">
                                                        <input type="hidden" name="steps[0][step_number]" value="1" class="form-control form-control-sm">
                                                    </td>
                                                    <td class="approvers-cell">
                                                        <div class="form-group mb-2">
                                                            <label class="form-label" for="steps-0-approver_role">Select Approvers</label>
                                                            <select multiple name="steps[0][approver_role][]" class="form-select form-select-sm mb-1 select360" id="steps-0-approver_role" required data-placeholder="Select approver">
                                                                @foreach($approverRoles as $key => $value)
                                                                    <option value="{{ $value }}" {{ old("steps.0.approver_role") == $value ? 'selected' : '' }}>{{ $value }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="form-label" for="steps-0-approver_user_id">Select Employee (Optional)</label>
                                                            <select multiple name="steps[0][approver_user_id][]" class="form-select form-select-sm mb-1 select360" id="steps-0-approver_user_id" data-placeholder="Select employee">
                                                                @foreach($employees as $item)
                                                                    <option value="{{ $item['id'] }}">{{ $item['value'] }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <td class="d-none">
                                                        <input type="number" name="steps[0][allotted_time]" class="form-control form-control-sm">
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-light rounded py-1 px-2 m-1 fs-14 additional-settings-btn" data-toggle="modal" data-target="#additionalSettingsModal" data-step-index="0">
                                                            <i class="ri-more-2-fill"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-light rounded py-1 px-2 fs-14 remove-step"><i class="ri-delete-bin-line"></i></button>
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" id="add-step" class="btn btn-sm btn-outline-primary fw-medium mt-3">Add Another Field</button>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <a href="{{ route('approval-flow.index') }}" type="button" class="btn btn-light me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Approal Flow</button>
                            </div>
                        </form>
                    </div>
                </div> <!-- end card-body -->
            </div> <!-- end card-->
        </div>
    </div>
</div>

<!-- Additional Settings Modal (Single Instance) -->
<div class="modal fade" id="additionalSettingsModal" tabindex="-1" role="dialog" aria-labelledby="additionalSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header justify-content-between">
                <h5 class="modal-title" id="additionalSettingsModalLabel">Pengaturan Tambahan untuk Langkah <span id="modal-step-display"></span></h5>
                <button type="button" class="btn p-0 px-1" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="ri-close-line fs-18"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-current-step-index" value="">

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="modal_settings_hide_stage_from">Hide Stage from:</label>
                        <select id="modal_settings_hide_stage_from" class="form-control form-control-sm">
                            <option value="">Select</option>
                            <option value="option1">Option 1</option>
                            <option value="option2">Option 2</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="modal_settings_form_visibility">Form Visibility:</label>
                        <select id="modal_settings_form_visibility" multiple class="form-control form-control-sm" size="5">
                            @foreach($formVisibilityOptions as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="modal_settings_re_route">Re-Route:</label>
                        <select id="modal_settings_re_route" class="form-control form-control-sm">
                            <option value="">Select</option>
                            <option value="optionA">Option A</option>
                            <option value="optionB">Option B</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="modal_settings_skip_for">Skip for:</label>
                        <select id="modal_settings_skip_for" class="form-control form-control-sm">
                            <option value="">Select</option>
                            <option value="optionX">Option X</option>
                            <option value="optionY">Option Y</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="modal_settings_skip_settings">Skip Settings:</label>
                        <select id="modal_settings_skip_settings" class="form-control form-control-sm">
                            <option value="">Select</option>
                            <option value="no_assignee">No Assignee (Ver 1)</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="modal_settings_sia_settings">Sia Settings:</label>
                        <select id="modal_settings_sia_settings" class="form-control form-control-sm">
                            <option value="">Select</option>
                            <option value="no_sia">No Sia</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check mb-2">
                        <input type="checkbox" id="modal_settings_replace_buttons" value="1" class="form-check-input">
                        <label for="modal_settings_replace_buttons" class="form-check-label">Replace Approve and Reject buttons with submit button</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" id="modal_settings_allow_send_back" value="1" class="form-check-input">
                        <label for="modal_settings_allow_send_back" class="form-check-label">Allow Send Back</label>
                    </div>
                </div>

                <h3 class="h5 mb-3">Configure Button Aliases</h3>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="modal_settings_alias_approve">Approve:</label>
                        <input type="text" id="modal_settings_alias_approve" value="Approve" class="form-control form-control-sm">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="modal_settings_alias_reject">Reject:</label>
                        <input type="text" id="modal_settings_alias_reject" value="Reject" class="form-control form-control-sm">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="modal_settings_alias_save_as_draft">Save as Draft:</label>
                        <input type="text" id="modal_settings_alias_save_as_draft" value="Save as Draft" class="form-control form-control-sm">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="modal_settings_alias_delegate">Delegate:</label>
                        <input type="text" id="modal_settings_alias_delegate" value="Delegate" class="form-control form-control-sm">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="modal_settings_alias_view_details">View Details:</label>
                        <input type="text" id="modal_settings_alias_view_details" value="View Details" class="form-control form-control-sm">
                    </div>
                </div>

                <h3 class="h5 mb-3">Configure Emails</h3>
                {{-- Email when approver/assignee is assigned --}}
                <div class="mb-3 p-3 bg-light rounded">
                    <h4 class="h6 mb-2">Email when approver/assignee is assigned</h4>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Select Email Template:</label>
                            <input type="text" id="modal_settings_email_approver_assigned_template" placeholder="No Email Template (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Default Email Recipients:</label>
                            <input type="text" id="modal_settings_email_approver_assigned_default_recipients" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Additional Recipients:</label>
                            <input type="text" id="modal_settings_email_approver_assigned_additional_recipients" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>CC Recipient Users:</label>
                            <input type="text" id="modal_settings_email_approver_assigned_cc_users" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>CC Recipient Roles:</label>
                            <input type="text" id="modal_settings_email_approver_assigned_cc_roles" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>

                {{-- Email when approvers approve --}}
                <div class="mb-3 p-3 bg-light rounded">
                    <h4 class="h6 mb-2">Email when approvers approve</h4>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Select Email Template:</label>
                            <input type="text" id="modal_settings_email_approvers_approve_template" placeholder="No Email Template (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Default Email Recipients:</label>
                            <input type="text" id="modal_settings_email_approvers_approve_default_recipients" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Additional Recipients:</label>
                            <input type="text" id="modal_settings_email_approvers_approve_additional_recipients" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>CC Recipient Users:</label>
                            <input type="text" id="modal_settings_email_approvers_approve_cc_users" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>CC Recipient Roles:</label>
                            <input type="text" id="modal_settings_email_approvers_approve_cc_roles" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>

                {{-- Email when approvers reject --}}
                <div class="mb-3 p-3 bg-light rounded">
                    <h4 class="h6 mb-2">Email when approvers reject</h4>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Select Email Template:</label>
                            <input type="text" id="modal_settings_email_approvers_reject_template" placeholder="No Email Template (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Default Email Recipients:</label>
                            <input type="text" id="modal_settings_email_approvers_reject_default_recipients" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Additional Recipients:</label>
                            <input type="text" id="modal_settings_email_approvers_reject_additional_recipients" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>CC Recipient Users:</label>
                            <input type="text" id="modal_settings_email_approvers_reject_cc_users" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>CC Recipient Roles:</label>
                            <input type="text" id="modal_settings_email_approvers_reject_cc_roles" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>

                {{-- Email when the due date is completed --}}
                <div class="mb-3 p-3 bg-light rounded">
                    <h4 class="h6 mb-2">Email when the due date is completed</h4>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Select Email Template:</label>
                            <input type="text" id="modal_settings_email_due_date_completed_template" placeholder="No Email Template (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Default Email Recipients:</label>
                            <input type="text" id="modal_settings_email_due_date_completed_default_recipients" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Additional Recipients:</label>
                            <input type="text" id="modal_settings_email_due_date_completed_additional_recipients" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>CC Recipient Users:</label>
                            <input type="text" id="modal_settings_email_due_date_completed_cc_users" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label>CC Recipient Roles:</label>
                            <input type="text" id="modal_settings_email_due_date_completed_cc_roles" placeholder="Select (simulated)" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveModalSettings">Save changes</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.documentElement.setAttribute('data-sidenav-size', 'condensed');
    });
    window.oldSteps = {!! json_encode(old('steps')) ?? '[]' !!};
    window.approverData = {!! json_encode($approverRoles) !!};
    window.employeeData = {!! json_encode($employees) !!};
</script>
@endpush
