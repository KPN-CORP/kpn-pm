@extends('layouts_.vertical', ['page_title' => 'Flow Settings'])

@section('css')
<style>
.dataTables_scrollHeadInner {
    width: 100% !important;
}
.table-responsive, .dataTables_scroll {
    width: 100%;
}
.table th,
.table td {
    padding: 0.35rem 0.5rem; /* kecilkan padding */
    font-size: 0.85rem; /* perkecil font */
    vertical-align: middle; /* pastikan teks rata tengah */
}

.table thead {
    background-color: #f9fafb; /* sesuai bg-gray-50 */
}

.table .table-header {
    font-weight: 600;
    color: #4b5563; /* abu-abu gelap */
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
            <div class="flex text-end mb-2">
                <a href="{{ route('flows.create', 'flow') }}" class="btn btn-sm btn-primary">Create Flow</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-body p-2">        

                @if ($flows->isEmpty())
                    <p class="text-center m-0 text-gray-600">No flows has been created.</p>
                @else
                    <table id="tableFlow" class="table table-hover m-0">
                        <thead class="thead-light">
                            <tr>
                                <th class="table-header rounded-tl-lg">Flow Name</th>
                                <th class="table-header">Description</th>
                                <th class="table-header rounded-tr-lg">Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                @endif
                </div> <!-- end card-body -->
            </div> <!-- end card-->
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    window.tableUrl = "{{ route('flows.data') }}";
</script>
@endpush
