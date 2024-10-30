<x-app-layout>
    @section('title', 'Employee')
    <x-slot name="content">
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-end mb-4">
            <a href="{{ route('export.reportemp') }}" id="export" class="btn btn-primary px-4 shadow">Download Employee</a>
        </div>
        <div class="form-group" style="width: 50%">
            <label for="locationFilter">Filter Locations:</label>
            <select class="form-control select2" id="locationFilter">
                <option value="">Select location...</option>
                @foreach($locations as $location)
                    <option value="{{ $location->area." (".$location->company_name.")" }}">{{ $location->area." (".$location->company_name.")" }}</option>
                @endforeach
            </select>
        </div>
        
        
        <!-- Content Row -->
        <div class="row">
          <div class="col-md-12">

            <div class="card shadow mb-4">
              <div class="card-body">
                  <div class="table-responsive">
                      <table class="table table-hover" id="employeeTable" width="100%" cellspacing="0">
                          <thead class="thead-light">
                              <tr class="text-center">
                                <th>#</th>
                                <th>NIK</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>DOJ</th>
                                <th>{{ __('Type') }}</th>
                                <th>Unit</th>
                                <th>Job</th>
                                <th>Grade</th>
                                <th>PT</th>
                                <th>Locations</th>
                                <th>BU</th>
                                <th>Email</th>
                                <th>Menu Goals</th>
                              </tr>
                          </thead>
                          <tbody>

                            @foreach($employees as $employee)
                              <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $employee->employee_id }}</td>
                                    <td>{{ $employee->fullname }}</td>
                                    <td>{{ $employee->gender }}</td>
                                    <td>{{ $employee->date_of_joining }}</td>
                                    <td>{{ $employee->employee_type }}</td>
                                    <td>{{ $employee->unit }}</td>
                                    <td>{{ $employee->designation_name }}</td>
                                    <td>{{ $employee->job_level }}</td>
                                    <td>{{ $employee->contribution_level_code }}</td>
                                    <td>{{ $employee->office_area." (".$employee->group_company.")" }}</td>
                                    <td>{{ $employee->group_company }}</td>
                                    <td>{{ $employee->email }}</td>
                                    <td></td>
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
    </x-slot>
</x-app-layout>
<script>
    // Periksa apakah ada pesan sukses
    var successMessage = "{{ session('success') }}";

    // Jika ada pesan sukses, tampilkan sebagai alert
    if (successMessage) {
        alert(successMessage);
    }
</script>
<script>
        function handleDelete(element) {
            if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                var scheduleId = element.getAttribute('data-id');

                fetch('/schedule/' + scheduleId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Terjadi kesalahan saat menghapus data.');
                    }
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus data.');
                });
            }
        }
    
</script>
<script>
$(document).ready(function() {

    // Apply filter when location dropdown value changes
    $('#locationFilter').on('change', function() {
        applyLocationFilter(table);
    });

    // Apply filter when table is redrawn (e.g., when navigating to next page)
    table.on('draw.dt', function() {
        applyLocationFilter(table);
    });
});

function applyLocationFilter(table) {
    var locationId = $('#locationFilter').val().toUpperCase();

    // Filter table based on location
    table.column(10).search(locationId).draw(); // Adjust index based on your table structure
}

</script>
