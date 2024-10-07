<div class="modal fade" id="modalFilter" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog mt-3" role="document">
        <div class="modal-content">
            <div class="modal-header">
                  <span class="modal-title h4" id="viewFormEmployeeLabel">Filters</span>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              <div class="input-group-md">
                  <input type="text" id="employee_name" class="form-control" placeholder="Search employee.." hidden>
              </div>
            </div>
            <form id="admin_report_filter" action="" method="POST">
                @csrf
                <input type="hidden" name="report_type" id="report_type">
                <div class="modal-body">
                    <div class="container-fluid py-3">
                        <!-- Content Row -->
                        <div class="container-card">
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label class="form-label" for="group_company">Group Company</label>
                                        <select class="form-select select2" name="group_company" id="group_company">
                                            <option value="">- select group company -</option>
                                            @foreach ($groupCompanies as $groupCompany)
                                            <option value="{{ $groupCompany }}">{{ $groupCompany }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label class="form-label" for="company">Company</label>
                                        <select class="form-select select2" name="company" id="company">
                                            <option value="">- select company -</option>
                                            @foreach ($companies as $company)
                                            <option value="{{ $company->contribution_level_code }}">{{ $company->contribution_level }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label class="form-label" for="location">Location</label>
                                        <select class="form-select select2" name="location" id="location">
                                            <option value="">- select location -</option>
                                            @foreach ($locations as $location)
                                            <option value="{{ $location->work_area }}">{{ $location->area.' ('.$location->company_name.')' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-sm-flex justify-content-end">
                        <a class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                </div>
            </form>
            </div>
        </div>
  </div>