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
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <div class="container">
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="mb-0 fs-20">Create Flows</p>
                                    <a href="{{ route('flows.index') }}" type="button" class="btn-close" aria-label="Close"></a>
                                </div>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <form action="{{ route('flows.store') }}" method="POST">
                            @csrf

                            <div class="mb-4">
                                <div class="row mb-4">
                                    <div class="form-group col-md-4">
                                        <label for="module_transaction">Module Transaction*</label>
                                        <select name="module_transaction" class="form-control form-control-sm mb-1 module-select" required data-placeholder="Select module transaction">
                                            <option></option>
                                            @foreach($moduleTransactions as $key => $value)
                                                <option value="{{ $key }}" {{ old("module_transaction") == $key ? 'selected' : '' }}>{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="form-group col-md-4">
                                        <label for="flow_name">Flow Name*</label>
                                        <input type="text" id="flow_name" name="flow_name" value="{{ old('flow_name') }}" class="form-control" required>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="form-group col-md-4">
                                        <label for="description">Description</label>
                                        <textarea id="description" name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="form-group col-md-8">
                                        <label for="assignments">Assignments*</label>
                                        <select multiple name="assignments[]" class="form-control form-control-sm mb-1 assignment-select" required data-placeholder="Select assignments">
                                            @foreach($assignments as $key => $value)
                                                <option value="{{ $key }}" {{ old("assignments") == $key ? 'selected' : '' }}>{{ $value }}</option>
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
                                                {{-- Baris pertama tidak bisa dihapus --}}
                                                <tr class="flow-row" data-index="0">
                                                    <td><span class="row-number">1</span></td>
                                                    <td>
                                                        <select name="initiator[0][role]"
                                                                class="form-select form-select-sm flow-select initiator-select"
                                                                required data-placeholder="Select...">
                                                            <option></option>
                                                            @foreach($approverRoles as $key => $value)
                                                                @if(is_numeric($key))
                                                                    {{-- Role bawaan Laravel Permission --}}
                                                                    <option value="role|{{ $key }}|{{ $value }}">{{ $value }}</option>
                                                                @else
                                                                    {{-- State khusus --}}
                                                                    <option value="state|{{ strtolower($key) }}|{{ $value }}">{{ $value }}</option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select name="initiator[0][approval_flow]"
                                                                class="form-select form-select-sm flow-select approvalflow-select"
                                                                required data-placeholder="Select...">
                                                            <option></option>
                                                            @foreach($approvalFlow as $key => $name)
                                                                <option value="{{ $key }}|{{ $name }}">{{ $name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" id="add-flow-btn" class="btn btn-sm btn-outline-primary fw-medium mt-2">
                                        <i class="ri-add-line"></i> Add Initiator
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <a href="{{ route('flows.index') }}" type="button" class="btn btn-light me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Flow</button>
                            </div>
                        </form>
                    </div>
                </div> <!-- end card-body -->
            </div> <!-- end card-->
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
