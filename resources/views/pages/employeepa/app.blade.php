@extends('layouts_.vertical', ['page_title' => 'Ratings'])

@section('css')
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
                <a href="" class="btn btn-success shadow">Export to Excel</a>
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
                                  <th>Action</th>
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
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning" title="Edit" onclick="showEditModal({{ json_encode($employee) }})">
                                            <i class="ri-edit-box-line"></i>
                                        </button>
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
    <div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg"> <!-- modal-lg for wider modal -->
            <div class="modal-content">
                <form id="editEmployeeForm" action="{{ route('employeepa.update') }}" method="POST">
                    @csrf
                    @method('PUT')
    
                    <div class="modal-header">
                        <h5 class="modal-title" id="editEmployeeModalLabel">Edit Employee</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editEmployeeId" class="form-label">Employee ID</label>
                                <input type="text" class="form-control bg-light" id="editEmployeeId" name="employee_id" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editFullname" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="editFullname" name="fullname" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editDateOfJoining" class="form-label">Date of Joining</label>
                                <input type="date" class="form-control" id="editDateOfJoining" name="date_of_joining" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editContributionLevelCode" class="form-label">Company</label>
                                <select class="form-control" id="editContributionLevelCode" name="contribution_level_code" required>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->contribution_level_code }}">
                                            {{ $company->contribution_level_code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editUnit" class="form-label">Unit</label>
                                <input type="text" class="form-control" id="editUnit" name="unit" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editDesignationName" class="form-label">Designation</label>
                                <input type="text" class="form-control" id="editDesignationName" name="designation_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editJobLevel" class="form-label">Job Level</label>
                                <input type="text" class="form-control" id="editJobLevel" name="job_level" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editOfficeArea" class="form-label">Office Area</label>
                                <input type="text" class="form-control" id="editOfficeArea" name="office_area" required>
                            </div>
                        </div>
                    </div>
    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
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
            confirmButtonText: 'Yes, delete it!'
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
    function showEditModal(employee) {
        // Isi data dari karyawan yang akan diedit ke dalam modal
        document.getElementById('editEmployeeId').value = employee.employee_id;
        document.getElementById('editFullname').value = employee.fullname;
        document.getElementById('editDateOfJoining').value = employee.date_of_joining;
        document.getElementById('editContributionLevelCode').value = employee.contribution_level_code;
        document.getElementById('editUnit').value = employee.unit;
        document.getElementById('editDesignationName').value = employee.designation_name;
        document.getElementById('editJobLevel').value = employee.job_level;
        document.getElementById('editOfficeArea').value = employee.office_area;

        // Tampilkan modal
        var editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
        editModal.show();
    }
</script>
@endpush