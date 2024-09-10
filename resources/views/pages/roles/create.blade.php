@extends('pages.roles.app') <!-- Extend the main layout -->

@section('subcontent')
@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif
<div class="card">
  <div class="card-body">

    <form id="roleForm" action="{{ route('roles.store') }}" method="POST">
      @csrf
      <div class="row">
        <div class="col-md">
      <div class="row">
        <div class="col-md-4">
          <div class="mb-3">
              <label for="roleName">Role Name</label>
              <input class="form-control" type="text" name="roleName" placeholder="Enter role name.." required></div>
        </div>
      </div>
        <div class="row mb-3">
          <div class="col-md-8">
            <div class="form-group">
              <label for="roleName">Restrict Group Company (Keeping blank means no restrictions)</label>
              <select class="form-control select2" name="group_company[]" multiple="multiple">
                @foreach ($groupCompanies as $groupCompany)
                  <option value="{{ $groupCompany }}">{{ $groupCompany }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-8">
            <div class="form-group">
              <label for="roleName">Restrict Company (Keeping blank means no restrictions)</label>
              <select class="form-control select2" name="contribution_level_code[]" multiple="multiple">
                @foreach ($companies as $company)
                  <option value="{{ $company->contribution_level_code }}">{{ $company->contribution_level.' ('.$company->contribution_level_code.')' }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-8">
            <div class="form-group">
              <label for="roleName">Restrict Location (Keeping blank means no restrictions)</label>
              <select class="form-control select2" name="work_area_code[]" multiple="multiple">
                @foreach ($locations as $location)
                  <option value="{{ $location->work_area }}">{{ $location->area.' ('.$location->company_name.')' }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>
      <div class="col-auto">
        <div class="mb-2 text-end">
          <button type="submit" id="submitButton" class="btn btn-primary rounded-pill"><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>Create Role</button>
        </div>
      </div>
    </div>
      </div>
</div>
<hr class="mt-3 mb-3">
      <div class="row mb-4">
        <div class="col-md-3 mb-3">
          <div class="list-group" id="list-tab" role="tablist">
            <a class="list-group-item list-group-item-action active" id="list-onBehalf-list" data-bs-toggle="list" href="#list-onBehalf" role="tab" aria-controls="onBehalf">On Behalfs</a>
            <a class="list-group-item list-group-item-action" id="list-report-list" data-bs-toggle="list" href="#list-report" role="tab" aria-controls="report">{{ __('Report') }}</a>
            <a class="list-group-item list-group-item-action" id="list-guide-list" data-bs-toggle="list" href="#list-guide" role="tab" aria-controls="guide">Guideline</a>
            <a class="list-group-item list-group-item-action" id="list-setting-list" data-bs-toggle="list" href="#list-setting" role="tab" aria-controls="setting">Settings</a>
          </div>
        </div>
        <div class="col-md-9">
          <div class="card">
            <div class="card-body">
              <div class="row">
                <div class="tab-content" id="nav-tabContent">
                  <div class="tab-pane fade active show" id="list-onBehalf" role="tabpanel" aria-labelledby="list-onBehalf-list">
                    <ul class="nav">
                      <li class="nav-item">
                        <a class="nav-link" id="onBehalf-accessibility" data-bs-toggle="list" href="#list-onBehalf-accessibility" role="tab" aria-controls="onBehalf">Accessibility</a>
                      </li>
                    </ul>
                    <div class="tab-pane fade p-3 active show" id="list-onBehalf-accessibility" role="tabpanel" aria-labelledby="onBehalf-accessibility">
                      <div class="form-check mb-3">
                        <input type="hidden" name="adminMenu" value="{{ 9 }}">
                        <input class="form-check-input" type="checkbox" id="onBehalfView" value="{{ $permissions[0]->id }}" name="onBehalfView">
                        <label class="form-check-label" for="onBehalfView">
                          View On Behalfs
                        </label>
                      </div>
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="onBehalfApproval" value="{{ $permissions[1]->id }}" name="onBehalfApproval">
                        <label class="form-check-label" for="onBehalfApproval">
                          Initiate Approval
                        </label>
                      </div>                                                
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="onBehalfSendback" value="{{ $permissions[2]->id }}" name="onBehalfSendback">
                        <label class="form-check-label" for="onBehalfSendback">
                          Sendback Approval
                        </label>
                      </div>
                    </div>
                  </div>
                  <div class="tab-pane fade" id="list-report" role="tabpanel" aria-labelledby="list-report-list">
                    <ul class="nav">
                      <li class="nav-item">
                        <a class="nav-link" id="report-accessibility" data-bs-toggle="list" href="#list-report-accessibility" role="tab" aria-controls="report">Accessibility</a>
                      </li>
                    </ul>
                    <div class="tab-pane fade p-3 active show" id="list-report-accessibility" role="tabpanel" aria-labelledby="report-accessibility">
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="reportView" value="{{ $permissions[3]->id }}" name="reportView">
                        <label class="form-check-label" for="reportView">
                          View Reports
                        </label>
                      </div>
                    </div>
                  </div>
                  <div class="tab-pane fade" id="list-guide" role="tabpanel" aria-labelledby="list-guide-list">
                    <ul class="nav">
                      <li class="nav-item">
                        <a class="nav-link" id="guide-accessibility" data-bs-toggle="list" href="#list-guide-accessibility" role="tab" aria-controls="guide">Accessibility</a>
                      </li>
                    </ul>
                    <div class="tab-pane fade p-3 active show" id="list-guide-accessibility" role="tabpanel" aria-labelledby="guide-accessibility">
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="addGuide" value="{{ $permissions[9]->id }}" name="addGuide">
                        <label class="form-check-label" for="addGuide">
                          Add file
                        </label>
                      </div>
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="removeGuide" value="{{ $permissions[10]->id }}" name="removeGuide">
                        <label class="form-check-label" for="removeGuide">
                          Remove file
                        </label>
                      </div>
                    </div>
                  </div>
                  <div class="tab-pane fade" id="list-setting" role="tabpanel" aria-labelledby="list-setting-list">
                    <ul class="nav">
                      <li class="nav-item">
                        <a class="nav-link" id="setting-accessibility" data-bs-toggle="list" href="#list-setting-accessibility" role="tab" aria-controls="setting">Accessibility</a>
                      </li>
                    </ul>
                    <div class="tab-pane fade p-3 active show" id="list-setting-accessibility" role="tabpanel" aria-labelledby="setting-accessibility">
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="settingView" value="{{ $permissions[4]->id }}" name="settingView">
                        <label class="form-check-label" for="settingView">
                          View Settings
                        </label>
                      </div>
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="scheduleView" value="{{ $permissions[5]->id }}" name="scheduleView">
                        <label class="form-check-label" for="scheduleView">
                          Schedule Settings
                        </label>
                      </div>                                                
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="layerView" value="{{ $permissions[6]->id }}" name="layerView">
                        <label class="form-check-label" for="layerView">
                          Layer Settings
                        </label>
                      </div>
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="roleView" value="{{ $permissions[7]->id }}" name="roleView">
                        <label class="form-check-label" for="roleView">
                          Role Permission Settings
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection