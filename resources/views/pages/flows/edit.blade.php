@extends('layouts_.vertical', ['page_title' => 'Edit Flow'])

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
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success mt-3">
            {{ session('success') }}
        </div>
    @endif
    <div class="mandatory-field">
        <div id="alertField" class="alert alert-danger alert-dismissible {{ Session::has('error') ? '' : 'fade' }}" role="alert" {{ Session::has('error') ? '' : 'hidden' }}>
            <strong>{{ Session::get('error') }}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <div class="container">
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="mb-0 fs-20">Edit Flows</p>
                                    <a href="{{ route('flows.index') }}" type="button" class="btn-close" aria-label="Close"></a>
                                </div>
                            </div>
                        </div>
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form action="{{ route('flows.update', $flow->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-4">
                                <div class="row mb-4">
                                    <div class="form-group col-md-4">
                                        <label for="module_transaction">Module Transaction*</label>
                                        <select name="module_transaction" class="form-control form-control-sm mb-1 module-select" required>
                                            <option></option>
                                            @foreach($moduleTransactions as $key => $value)
                                                <option value="{{ $key }}" {{ old('module_transaction', $flow->module_transaction) == $key ? 'selected' : '' }}>{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="form-group col-md-4">
                                        <label for="flow_name">Flow Name*</label>
                                        <input type="text" id="flow_name" name="flow_name" value="{{ old('flow_name', $flow->flow_name) }}" class="form-control" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="form-group col-md-4">
                                        <label for="description">Description</label>
                                        <textarea id="description" name="description" rows="3" class="form-control">{{ old('description', $flow->description) }}</textarea>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="form-group col-md-8">
                                        <label for="assignments">Assignments*</label>
                                        <select multiple name="assignments[]" class="form-control form-control-sm mb-1 assignment-select" required>
                                            @foreach($assignments as $key => $value)
                                                <option value="{{ $key }}" {{ in_array($key, old('assignments', $flow->assignments)) ? 'selected' : '' }}>{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h5 class="mb-2 fs-16">Approval Flows</h5>
                                    <div class="table-responsive">
                                        <table class="table fs-12 table-centered">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th style="width: 50px;">#</th>
                                                    <th style="width: 30%;">Initiator</th>
                                                    <th>Approval Flow</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="flows-container">
                                                @php
                                                    $initiators = old('initiator', $flow->initiator);
                                                @endphp
                                                @foreach($initiators as $index => $step)
                                                    <tr class="flow-row" data-index="{{ $index }}">
                                                        <td><span class="row-number">{{ $index + 1 }}</span></td>
                                                        <td>
                                                            <select name="initiator[{{ $index }}][role]"
                                                                    class="form-select form-select-sm flow-select initiator-select"
                                                                    required>
                                                                <option></option>
                                                                @foreach($approverRoles as $key => $value)
                                                                    @php
                                                                        $val = is_numeric($key)
                                                                            ? "role|{$key}|{$value}"
                                                                            : "state|" . strtolower($key) . "|{$value}";
                                                                    @endphp
                                                                    <option value="{{ $val }}" {{ old("initiator.{$index}.role", ($step['type'] ?? '') . '|' . ($step['role_id'] ?? $step['state_key'] ?? '') . '|' . ($step['role_name'] ?? $step['state_label'] ?? '')) == $val ? 'selected' : '' }}>
                                                                        {{ $value }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="initiator[{{ $index }}][approval_flow]"
                                                                    class="form-select form-select-sm flow-select approvalflow-select"
                                                                    required>
                                                                <option></option>
                                                                @foreach($approvalFlow as $key => $name)
                                                                    @php
                                                                        $val = "{$key}|{$name}";
                                                                    @endphp
                                                                    <option value="{{ $val }}" {{ old("initiator.{$index}.approval_flow", ($step['approval_flow_id'] ?? '') . '|' . ($step['approval_flow_name'] ?? '')) == $val ? 'selected' : '' }}>
                                                                        {{ $name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            @if($index > 0)
                                                                <button type="button" class="btn btn-sm btn-light rounded remove-flow">
                                                                    <i class="ri-delete-bin-line"></i>
                                                                </button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" id="add-flow-btn" class="btn btn-sm btn-outline-primary fw-medium mt-2">
                                        <i class="ri-add-line"></i> Add Initiator
                                    </button>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <a href="{{ route('flows.index') }}" class="btn btn-light me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Flow</button>
                            </div>
                        </form>
                    </div>
                </div>
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
    const approverRolesData = @json($approverRoles);
    const approvalFlowData = @json($approvalFlow);
</script>
@endpush