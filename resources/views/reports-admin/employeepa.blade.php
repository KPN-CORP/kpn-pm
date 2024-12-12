<div class="row">
    <div class="col-md-12">
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table dt-responsive table-hover" id="adminReportTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr class="text-center">
                        <th>#</th>
                        <th>NIK</th>
                        <th>Name</th>
                        <th>DOJ</th>
                        <th>{{ __('Type') }}</th>
                        <th>Unit</th>
                        <th>Job</th>
                        <th>Grade</th>
                        <th>PT</th>
                        <th>Locations</th>
                        <th>BU</th>
                        <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    
                    @foreach($data as $row)
                    @php
                        $unitParts = explode('(', $row->unit);
                        $unitWithoutBrackets = trim($unitParts[0]);

                        $designationParts = explode('(', $row->designation);
                        $desgWithoutBrackets = trim($designationParts[0]);

                    @endphp
                        <tr>
                            <td>{{ $loop->index + 1 }}</td>
                            <td>{{ $row->employee_id }}</td>
                            <td>{{ $row->fullname }}</td>
                            <td>{{ $row->date_of_joining }}</td>
                            <td>{{ $row->employee_type }}</td>
                            <td>{{ $unitWithoutBrackets }}</td>
                            <td>{{ $desgWithoutBrackets }}</td>
                            <td>{{ $row->job_level }}</td>
                            <td>{{ $row->contribution_level_code }}</td>
                            <td>{{ $row->office_area }}</td>
                            <td>{{ $row->group_company }}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-warning mb-1" title="Update" onclick="showEditModal({{ json_encode($row) }})">
                                    <i class="ri-edit-box-line"></i>
                                </button>
                                <a class="btn btn-sm btn-danger mb-1" title="Terminated" onclick="handleDeleteEmployeePA(this)" data-id="{{ $row->employee_id }}"><i class="ri-delete-bin-line"></i></a>
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
                            {{-- <input type="text" class="form-control" id="editUnit" name="unit" required> --}}
                            <select class="form-control" id="editUnit" name="unit" required>
                                @foreach($departments as $department)
                                    <option value="{{ $department->department_name }}">
                                        {{ $department->department_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editDesignationName" class="form-label">Designation</label>
                            {{-- <input type="text" class="form-control" id="editDesignationName" name="designation_name" required> --}}
                            <select class="form-control" id="editDesignationName" name="designation_name" required>
                                @foreach($designations as $designation)
                                    <option value="{{ $designation->job_code }}">
                                        {{ $designation->designation_name }} ({{ $designation->job_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editJobLevel" class="form-label">Job Level</label>
                            {{-- <input type="text" class="form-control" id="editJobLevel" name="job_level" required> --}}
                            <select class="form-control" id="editJobLevel" name="job_level" required>
                                @foreach ($jobLevel as $level)
                                    <option value="{{ $level->job_level }}">{{ $level->job_level }}</option>  
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editOfficeArea" class="form-label">Office Area</label>
                            {{-- <input type="text" class="form-control" id="editOfficeArea" name="office_area" required> --}}
                            <select class="form-control" id="editOfficeArea" name="office_area" required>
                                @foreach($locations as $location)
                                    <option value="{{ $location->work_area }}">
                                        {{ $location->area }} ({{ $location->company_name }})
                                    </option>
                                @endforeach
                            </select>
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