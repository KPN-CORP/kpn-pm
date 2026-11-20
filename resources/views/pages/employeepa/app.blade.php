@extends('layouts_.vertical', ['page_title' => 'Ratings'])

@section('css')
<style>
    .table {
            border-collapse: separate;
            width: 100%;
            position: relative;
            overflow: auto;
        }

        .table thead th {
            position: -webkit-sticky !important;
            /* For Safari */
            position: sticky !important;
            top: 0 !important;
            z-index: 2 !important;
            background-color: #fff !important;
            /* border-bottom: 2px solid #ddd !important; */
            padding-right: 6px;
            box-shadow: inset 2px 0 0 #fff;
        }

        .table tbody td {
            background-color: #fff !important;
            padding-right: 10px;
            position: relative;
        }

        .table th.sticky-col-header {
            position: -webkit-sticky !important;
            /* For Safari */
            position: sticky !important;
            left: 0 !important;
            z-index: 3 !important;
            background-color: #fff !important;
            /* border-right: 2px solid #ddd !important; */
            padding-right: 10px;
            box-shadow: inset 2px 0 0 #fff;
        }

        .table td.sticky-col {
            position: -webkit-sticky !important;
            /* For Safari */
            position: sticky !important;
            left: 0 !important;
            z-index: 1 !important;
            background-color: #fff !important;
            /* border-right: 2px solid #ddd !important; */
            padding-right: 10px;
            box-shadow: inset 6px 0 0 #fff;
        }
</style>
@endsection

@section('content')
    <div class="container-fluid">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible border-0 fade show" role="alert">
                <button type="button" class="btn-close btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <strong>Success - </strong> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <div class="row">
            <div class="col-10">
            <div class="col-md-auto">
              <div class="mb-3">
                <div class="input-group" style="width: 30%;">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-dark-subtle"><i class="ri-search-line"></i></span>
                  </div>
                  <input type="text" name="customsearch" id="customsearch" class="form-control  border-dark-subtle border-left-0" placeholder="search.." aria-label="search" aria-describedby="search">
                </div>
              </div>
            </div>
            </div>
            <div class="col-2" style="text-align:right">
                <a href="{{ route('employeepa.export') }}" class="btn btn-success shadow">Export to Excel</a>
            </div>
        </div>
        
        <div class="row">
          <div class="col-md-12">
            <div class="card shadow mb-4">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="card-title"></h3>
                </div>
                  <div class="table-responsive">
                      <table class="table table-hover dt-responsive nowrap" id="scheduleTable" width="100%" cellspacing="0">
                          <thead class="thead-light">
                              <tr class="text-center">
                                  <th>No</th>
                                  <th>Employee ID</th>
                                  <th>Name</th>
                                  <th>Join Date</th>
                                  <th>Company</th>
                                  <th>Unit</th>
                                  <th>Designation</th>
                                  <th>Job Level</th>
                                  <th>Office Location</th>
                                  <th class="sticky-col-header" style="background-color: white">Action</th>
                              </tr>
                          </thead>
                          <tbody>

                            @foreach($employees as $employee)
                              <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $employee->employee_id }}</td>
                                    <td>{{ $employee->fullname }}</td>
                                    <td>{{ $employee->date_of_joining }}</td>
                                    <td>{{ $employee->contribution_level_code }}</td>
                                    <td>{{ $employee->unit }}</td>
                                    <td>{{ $employee->designation_name }}</td>
                                    <td>{{ $employee->job_level }}</td>
                                    <td>{{ $employee->office_area }}</td>
                                    <td class="text-center" style="background-color: white;" class="sticky-col">
                                        <a class="btn btn-sm btn-danger" title="Delete" onclick="handleDeleteEmployeePA(this)" data-id="{{ $employee->employee_id }}"><i class="ri-delete-bin-line"></i></a>
                                    </td>
                              </tr>
                              @endforeach
                          </tbody>
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
    function handleDeleteEmployeePA(element) {
        var id = element.getAttribute('data-id');
        var deleteUrl = "{{ route('admemployeeDestroy', ':id') }}";
        deleteUrl = deleteUrl.replace(':id', id);
        
        Swal.fire({
            title: 'Are you sure?',
            text: "This Employee will deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Jika dikonfirmasi, buat form dan submit ke server
                var form = document.createElement('form');
                form.action = deleteUrl;
                form.method = 'POST';
                form.innerHTML = `
                    @csrf
                    @method('DELETE')
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endpush