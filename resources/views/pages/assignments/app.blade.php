@extends('layouts_.vertical', ['page_title' => 'Assignment'])

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
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-end mb-2">
            <a href="{{ route('assignments.create', 'assignment') }}" class="btn btn-sm btn-primary">Create Assignment</a>
        </div>
        <!-- Content Row -->
        <div class="row">
          <div class="col-md-12">

            <div class="card shadow mb-4">
              <div class="card-body">
                  <div class="table-responsive">
                      <table class="table table-hover m-0" id="tableAssignment" width="100%" cellspacing="0">
                          <thead class="thead-light">
                              <tr>
                                  <th>#</th>
                                  <th>Assignment Name</th>
                                  <th>Created On</th>
                                  <th>Updated On</th>
                                  <th class="text-center">Actions</th>
                              </tr>
                          </thead>
                      </table>
                  </div>
              </div>
            </div>
          </div>
      </div>
    </div>
@endsection
@push('scripts')
<script>
    window.tableUrl = "{{ route('assignments.data') }}";
</script>
@endpush