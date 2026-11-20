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
