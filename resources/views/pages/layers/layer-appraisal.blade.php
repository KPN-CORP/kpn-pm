@extends('layouts_.vertical', ['page_title' => 'Layers'])

@section('css')
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Begin Page Content -->
    <div class="container-fluid"> 
        @if (session('success'))
            <div class="alert alert-success mt-3">
                {!! session('success') !!}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger mt-3">
                {!! session('error') !!}
            </div>
        @endif
        <div class="row">
            <div class="col-lg">
                <div class="mb-3 text-end">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal" title="Import">Import Layer</button>
                </div>
            </div>
        </div>    
        <div class="row">
            <div class="col-md-auto">
              <div class="mb-3">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-dark-subtle"><i class="ri-search-line"></i></span>
                  </div>
                  <input type="text" name="customsearch" id="customsearch" autocomplete="off" class="form-control  border-dark-subtle border-left-0" placeholder="search.." aria-label="search" aria-describedby="search">
                </div>
              </div>
            </div>
        </div>
        <!-- Content Row -->
        <div class="row">
          <div class="col-md-12">
            <div class="card shadow mb-4">
              <div class="card-body">
                {{-- Baris dimuat server-side dari route layer-appraisal.data (lihat layer-appraisal.js) --}}
                <table class="table table-sm activate-select dt-responsive nowrap w-100 fs-14 align-middle" id="layerAppraisalTable" data-url="{{ route('layer-appraisal.data') }}">
                    <thead class="thead-light">
                        <tr class="text-center">
                        <th>#</th>
                        <th>Employee Name</th>
                        <th>Company</th>
                        <th>Office Location</th>
                        <th>Business Unit</th>
                        <th class="sorting_1 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                @if ($initialData)
                {{-- Halaman pertama tabel, dipakai layer-appraisal.js sebagai respons draw pertama --}}
                <script type="application/json" id="layerAppraisalInitialData">@json($initialData)</script>
                @endif
              </div>
            </div>
          </div>
      </div>
    </div>

<!-- importModal -->
<div class="modal fade" id="importModal" role="dialog" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="importModalLabel">Import Layer</h4>
                {{-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> --}}
            </div>
            <div class="modal-body">
                <!-- Notes Section -->
                <div class="row">
                    <div class="col">
                        <div class="alert alert-info">
                            <strong>Notes:</strong>
                            <ul class="mb-0">
                                <li><strong>Managers</strong> are limited to <strong>1 layer</strong>.</li>
                                <li><strong>Peers</strong> and <strong>Subordinates</strong> are allowed a maximum of <strong>3 layers</strong> each.</li>
                                <li><strong>Calibrators</strong> can have up to <strong>10 layers</strong>.</li>
                                @if (\App\Imports\ApprovalLayerAppraisalImport::canImportKpiUnit())
                                <li><strong>KPI Unit</strong> (optional, last columns, peach): <code>kpi_unit</code> = grade from Master Calibration (e.g. <strong>C</strong>), <code>keterangan</code> = note, <code>periode</code> = year (empty = current appraisal period), <code>status_aktif</code> = <strong>T</strong>/<strong>F</strong> (empty = T for a new KPI Unit). Only filled columns are updated; leave all four empty to keep the current KPI Unit.</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
                <form id="importForm" action="{{ route('layer-appraisal.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col">
                            <div class="mb-4 mt-2">
                                <label class="form-label" for="excelFile">Upload Excel File</label>
                                <input type="file" class="form-control" id="excelFile" name="excelFile" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="mb-2">
                                <label class="form-label" for="fullname">Download Templete here : </label>
                                <a href="{{ route('layer-appraisal.template') }}" class="badge-outline-primary p-1"><i class="ri-file-text-line me-1"></i>Import_Excel_Template</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="importButton" class="btn btn-primary"><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>Import Data</button>
            </div>
        </div>
    </div>
</div>

<!-- view detail -->
<div class="modal fade" id="detailModal" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-full-width-md-down" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="detailModalLabel">View Detail</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-2">
                    <div class="col-md">
                        <div class="row">
                            <div class="col-3">
                                <p class="text-muted mb-1">Employee Name</p>
                            </div>
                            <div class="col">
                                : <span class="fullname"></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <p class="text-muted mb-1">Employee ID</p>
                            </div>
                            <div class="col">
                                : <span class="employee_id"></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <p class="text-muted mb-1">Join Date</p>
                            </div>
                            <div class="col">
                                : <span class="formattedDoj"></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <p class="text-muted mb-1">Business Unit</p>
                            </div>
                            <div class="col">
                                : <span class="group_company"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="row">
                            <div class="col-3">
                                <p class="text-muted mb-1">Company</p>
                            </div>
                            <div class="col">
                                : <span class="company_name"></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <p class="text-muted mb-1">Unit</p>
                            </div>
                            <div class="col">
                                : <span class="unit"></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <p class="text-muted mb-1">Designation</p>
                            </div>
                            <div class="col">
                                : <span class="designation"></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <p class="text-muted mb-1">Office Location</p>
                            </div>
                            <div class="col">
                                : <span class="office_area"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md">
                        <table class="table table-sm dt-responsive table-hover table-bordered" id="historyTable" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr class="text-center">
                                    <th>Layer</th>
                                    <th>Employee Name</th>
                                    <th>Latest Updated By</th>
                                    <th>Latest Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows will be added dynamically using JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
@endpush