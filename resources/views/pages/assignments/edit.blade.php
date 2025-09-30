@extends('layouts_.vertical', ['page_title' => 'Edit Assignment'])

@section('css')
<style>
.dataTables_scrollHeadInner {
    width: 100% !important;
}
.table-responsive, .dataTables_scroll {
    width: 100%;
}
</style>
<style>
    /* Penyesuaian kecil agar Select2 terlihat bagus dengan form-control-sm */
    .select2-container--bootstrap-5 .select2-selection {
        min-height: calc(1.5em + .5rem + 2px);
        padding: .25rem .5rem;
        font-size: .875rem;
        border-radius: .2rem;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
        top: 50%;
        transform: translateY(-50%);
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible mt-3">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <p class="mb-0 fs-20">Edit Assignment</p>
                                <a href="{{ route('assignments.index') }}" type="button" class="btn-close" aria-label="Close"></a>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('assignments.update', $assignment->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <div class="row mb-2">
                                <div class="form-group col-md-4">
                                    <label for="name" class="form-label">Assignment Name*</label>
                                    <input type="text" id="name" name="name" value="{{ $assignment->name }}" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5 class="mb-2 fs-16">Attributes</h5>
                            <div class="table-responsive">
                                <table class="table fs-12 table-centered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th style="width: 30%;">ATTRIBUTE</th>
                                            <th>VALUE</th>
                                            <th style="width: 100px;" class="text-center">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody id="attributes-container">
                                        @foreach ($selectedAttributes as $index => $attr)
                                            <tr class="attribute-row" data-index="{{ $index }}">
                                                <td><span class="row-number">{{ $index + 1 }}</span></td>
                                                <td>
                                                    <select name="attributes[{{ $index }}][name]"
                                                            class="form-select form-select-sm attribute-select"
                                                            required>
                                                        <option></option>
                                                        @foreach($attributeData as $attributeName => $values)
                                                            <option value="{{ $attributeName }}" {{ $attributeName == $attr['name'] ? 'selected' : '' }}>
                                                                {{ $attributeName }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="value-cell">
                                                    <select name="attributes[{{ $index }}][value][]"
                                                            class="form-select form-select-sm value-select"
                                                            multiple required>
                                                        @foreach ($attributeData[$attr['name']] as $val)
                                                            <option value="{{ $val }}" {{ in_array($val, $attr['value']) ? 'selected' : '' }}>
                                                                {{ $val }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="action-cell text-center">
                                                    @if ($index > 0)
                                                        <button type="button" class="btn btn-sm btn-danger delete-row-btn p-1">
                                                            <i class="ri-delete-bin-line px-1"></i>
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" id="add-attribute-btn" class="btn btn-sm btn-outline-primary fw-medium mt-2">
                                <i class="ri-add-line"></i> Add Another Attribute
                            </button>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a type="button" href="{{ route('assignments.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Assignment</button>
                        </div>
                    </form>
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
    // 1. MENYEDIAKAN DATA DARI PHP KE JAVASCRIPT
    const attributeValueMap = @json($attributeData);
</script>
@endpush