@extends('layouts_.vertical', ['page_title' => 'Ratings'])

@section('css')
{{-- Tombol Terminate (handleDeleteEmployeePA di resources/js/report.js) membaca token dari sini --}}
<meta name="csrf-token" content="{{ csrf_token() }}">
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
                                  <th>Status</th>
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
                                    @php
                                        $isExcluded = isset($excludedIds[$employee->employee_id]);
                                    @endphp
                                    <td>
                                        @if ($employee->deleted_at)
                                            {{-- Sudah resign di HR, tapi masih punya goal/appraisal di periode berjalan --}}
                                            <span class="badge bg-warning text-dark" title="Masih ikut periode {{ implode(' / ', $periods) }} karena punya goal/appraisal">Resigned {{ \Carbon\Carbon::parse($employee->deleted_at)->format('d M Y') }}</span>
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                        @if ($isExcluded)
                                            <span class="badge bg-secondary" title="Tidak tampil di appraisal periode {{ $excludePeriod }}; periode lain tetap tampil">Removed from {{ $excludePeriod }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center" style="background-color: white;" class="sticky-col">
                                        @if ($isExcluded)
                                            <a class="btn btn-sm btn-success" title="Restore to period {{ $excludePeriod }}" onclick="restoreEmployeePA(this)" data-id="{{ $employee->employee_id }}" data-name="{{ $employee->fullname }}"><i class="ri-arrow-go-back-line"></i></a>
                                        @else
                                            {{-- Bukan handleDeleteEmployeePA: nama itu ditimpa resources/js/report.js (terminate semua periode) --}}
                                            <a class="btn btn-sm btn-danger" title="Terminate" onclick="terminateEmployeePA(this)" data-id="{{ $employee->employee_id }}" data-name="{{ $employee->fullname }}"><i class="ri-delete-bin-line"></i></a>
                                        @endif
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
    // Dipanggil saat klik (setelah app.js memuat Swal), jadi aman di inline script.
    function postEmployeePA(url, data) {
        var body = new FormData();
        Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
        return fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: body
        }).then(function (res) { return res.json(); });
    }

    function showEmployeePAResult(data) {
        Swal.fire({
            icon: data.success ? 'success' : 'error',
            title: data.success ? 'Done' : 'Failed',
            text: data.message
        }).then(function () { if (data.success) window.location.reload(); });
    }

    function terminateEmployeePA(element) {
        var id = element.getAttribute('data-id');
        var name = element.getAttribute('data-name') || id;
        var period = @json($excludePeriod);

        Swal.fire({
            title: 'Terminate ' + name + '?',
            icon: 'warning',
            text: 'Only period ' + period + ': earlier periods still show this employee. All periods: removed from every period and report.',
            input: 'select',
            inputOptions: {
                period: 'Only period ' + period,
                all: 'All periods'
            },
            inputValue: 'period',
            inputValidator: function (value) { if (!value) return 'Choose one option'; },
            showCancelButton: true,
            confirmButtonColor: '#AB2F2B',
            confirmButtonText: 'Terminate',
            reverseButtons: true
        }).then(function (result) {
            if (!result.isConfirmed) return;
            postEmployeePA(@json(route('admemployeeDestroy')), { _method: 'DELETE', id: id, scope: result.value })
                .then(showEmployeePAResult)
                .catch(function () { Swal.fire('Failed', 'Failed to terminate Employee.', 'error'); });
        });
    }

    function restoreEmployeePA(element) {
        var id = element.getAttribute('data-id');
        var name = element.getAttribute('data-name') || id;
        var period = @json($excludePeriod);

        Swal.fire({
            title: 'Restore ' + name + '?',
            text: 'This employee will show again in appraisal period ' + period + '.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Restore',
            reverseButtons: true
        }).then(function (result) {
            if (!result.isConfirmed) return;
            postEmployeePA(@json(route('admemployeeRestore')), { id: id })
                .then(showEmployeePAResult)
                .catch(function () { Swal.fire('Failed', 'Failed to restore Employee.', 'error'); });
        });
    }
</script>
@endpush
