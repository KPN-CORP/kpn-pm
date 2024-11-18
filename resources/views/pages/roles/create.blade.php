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
                <option value="{{ $location->work_area_code }}">{{ $location->office_area.' ('.$location->group_company.')' }}</option>
              </select>
            </div>
          </div>
        </div>
      </div>
      <div class="col-auto">
        <div class="mb-2 text-end">
          <button type="submit" id="submitButtonRole" class="btn btn-primary"><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>Create Role</button>
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
                      @php
                          $onbehalfsPermissions = $permissions->where('group_name', 'onbehalfs');
                      @endphp
                      @foreach($onbehalfsPermissions as $permission)
                          <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="{{ $permission->name }}" value="{{ $permission->id }}" name="{{ $permission->name }}">
                            <label class="form-check-label" for="{{ $permission->name }}">
                              {{ $permission->display_name }}
                            </label>
                          </div>
                      @endforeach
                    </div>
                  </div>
                  <div class="tab-pane fade" id="list-report" role="tabpanel" aria-labelledby="list-report-list">
                    <ul class="nav">
                      <li class="nav-item">
                        <a class="nav-link" id="report-accessibility" data-bs-toggle="list" href="#list-report-accessibility" role="tab" aria-controls="report">Accessibility</a>
                      </li>
                    </ul>
                    <div class="tab-pane fade p-3 active show" id="list-report-accessibility" role="tabpanel" aria-labelledby="report-accessibility">
                      @php
                          $onbehalfsPermissions = $permissions->where('group_name', 'report');
                      @endphp
                      @foreach($onbehalfsPermissions as $permission)
                          <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="{{ $permission->name }}" value="{{ $permission->id }}" name="{{ $permission->name }}">
                            <label class="form-check-label" for="{{ $permission->name }}">
                              {{ $permission->display_name }}
                            </label>
                          </div>
                      @endforeach
                    </div>
                  </div>
                  <div class="tab-pane fade" id="list-guide" role="tabpanel" aria-labelledby="list-guide-list">
                    <ul class="nav">
                      <li class="nav-item">
                        <a class="nav-link" id="guide-accessibility" data-bs-toggle="list" href="#list-guide-accessibility" role="tab" aria-controls="guide">Accessibility</a>
                      </li>
                    </ul>
                    <div class="tab-pane fade p-3 active show" id="list-guide-accessibility" role="tabpanel" aria-labelledby="guide-accessibility">
                      @php
                          $onbehalfsPermissions = $permissions->where('group_name', 'guideline');
                      @endphp
                      @foreach($onbehalfsPermissions as $permission)
                          <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="{{ $permission->name }}" value="{{ $permission->id }}" name="{{ $permission->name }}">
                            <label class="form-check-label" for="{{ $permission->name }}">
                              {{ $permission->display_name }}
                            </label>
                          </div>
                      @endforeach
                    </div>
                  </div>
                  <div class="tab-pane fade" id="list-setting" role="tabpanel" aria-labelledby="list-setting-list">
                    <ul class="nav">
                      <li class="nav-item">
                        <a class="nav-link" id="setting-accessibility" data-bs-toggle="list" href="#list-setting-accessibility" role="tab" aria-controls="setting">Accessibility</a>
                      </li>
                    </ul>
                    <div class="tab-pane fade p-3 active show" id="list-setting-accessibility" role="tabpanel" aria-labelledby="setting-accessibility">
                      @php
                          $onbehalfsPermissions = $permissions->filter(function($permission) {
                              return stripos($permission->group_name, 'settings') !== false;
                          });
                          $previousGroup = null;
                      @endphp
                      @foreach($onbehalfsPermissions as $permission)
                          @if($previousGroup != $permission->group_name)
                              <div class="form-check mb-3 bg-dark-subtle">
                                  <label class="form-check-label" for="{{ $permission->group_name }}">
                                    <strong>{{ explode('_', $permission->group_name)[1] }}</strong>
                                  </label>
                              </div>
                              @php
                                  $previousGroup = $permission->group_name;
                              @endphp
                          @endif
                          <div class="form-check mb-3">
                              <input class="form-check-input" type="checkbox" id="{{ $permission->name }}" value="{{ $permission->id }}" name="{{ $permission->name }}">
                              <label class="form-check-label" for="{{ $permission->name }}">
                                  {{ $permission->display_name }} <i class="ri-information-line" title="{{ $permission->desc }}"></i>
                              </label>
                          </div>
                      @endforeach
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