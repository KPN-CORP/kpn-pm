@extends('layouts_.vertical', ['page_title' => 'Reports'])

@section('css')
<style>

  :root {
    --kpn-primary: #AB2F2B;
    --kpn-primary-hover: #8f2623;
    --kpn-primary-soft: #fdf2f2;
  }

  .text-primary { color: var(--kpn-primary) !important; }
  .bg-primary { background-color: var(--kpn-primary) !important; color: white !important; }
  .bg-primary-soft { background-color: var(--kpn-primary-soft) !important; }
  .bg-primary-subtle { background-color: #f8d7d6 !important; }

.mini-progress {
    width:100%;
    height:18px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 0px;
}

.mini-progress-text{
    position:absolute;
    inset:0;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:.65rem;
    color:#fff;
    z-index:2;

    text-shadow:
        -1px -1px 0 #9e2a2b,
         1px -1px 0 #9e2a2b,
        -1px  1px 0 #9e2a2b,
         1px  1px 0 #9e2a2b,

         0 0 3px rgba(0,0,0,.35);
}

.mini-progress-bar.bg-primary { height: 100%; border-radius: 10px; background: linear-gradient(90deg, var(--kpn-primary) 25%, #d96865 50%, var(--kpn-primary) 75%); background-size: 200% 100%; animation: progressFlow 1.5s linear infinite; }
@keyframes progressFlow { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
</style>
@endsection
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Begin Page Content -->
    <div class="container-fluid">
      <div class="card">
        <div class="card-body pb-0">
          <div class="row">
            <div class="col-lg">
              <div class="row">
                <div class="col-md-auto">
                  <div class="mb-3">
                    <label class="form-label" for="report_type">Select Report:</label>
                    <select class="form-select border-dark-subtle" id="reportType" onchange="adminReportType(this.value)">
                    <option value="">- select -</option>
                    <option value="Goal">Detailed Goals</option>
                    <option value="Employee">Goal Menu Access</option>
                    @if(auth()->check())
                      @can('employeepa')
                        <option value="EmployeePA">Employee PA</option>
                      @endcan
                    @endif
                    <option value="Achievement">Achievement</option>
                    </select>
                  </div>
                </div>
              </div>
            <div class="row">
              <div class="col-md-auto">
                <div class="d-md-block d-none mb-2">
                  <button class="input-group-text bg-white border-dark-subtle" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><i class="ri-filter-line me-1"></i>Filters</button>
                </div>
              </div>
              <div class="col-md-auto">
                <div class="mb-3">
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-white border-dark-subtle"><i class="ri-search-line"></i></span>
                    </div>
                    <input type="text" name="customsearch" id="customsearch" class="form-control  border-dark-subtle border-left-0" placeholder="search.." aria-label="search" aria-describedby="search">
                    <div class="d-md-none input-group-append">
                      <button class="input-group-text bg-white border-dark-subtle" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><i class="ri-filter-line"></i></button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
        </div>
        <div class="col-lg-auto">
          <div class="mb-2 text-end">
            <form id="exportForm" action="{{ route('admin.export') }}" method="POST">
              @csrf
              <input type="hidden" name="export_report_type" id="export_report_type">
              <input type="hidden" name="export_group_company" id="export_group_company">
              <input type="hidden" name="export_company" id="export_company">
              <input type="hidden" name="export_location" id="export_location">
              <input type="hidden" name="export_period" id="export_period">
              <button type="button" id="exportBtn" onclick="exportExcel()" 
                  class="btn btn-outline-secondary px-4 shadow">
                  <i class="ri-arrow-circle-down-line"></i> Download
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
      <div id="report_content">
        <div class="row">
          <div class="col-md-12">
          <div class="card shadow mb-4">
              <div class="card-body">
                  {{ __('No Report Found. Please Select Report') }}
              </div>
          </div>
          </div>
        </div>
      </div>

      <div class="offcanvas offcanvas-end" tabindex="-1"  id="offcanvasRight" aria-labelledby="offcanvasRightLabel" aria-modal="false" role="dialog">
          <div class="offcanvas-header">
              <h5 id="offcanvasRightLabel">Filters</h5>
              <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
          </div> <!-- end offcanvas-header-->

          <div class="offcanvas-body">
            <form id="admin_report_filter" action="{{ url('admin/get-report-content') }}" method="POST">
              @csrf
              <input type="hidden" id="report_type" name="report_type">
                  <div class="row">
                      <div class="col">
                          <div class="mb-3">
                            @php
                                $filterYear = request('filterYear');
                            @endphp
                              <label class="form-label" for="filterYear">{{ __('Year') }}</label>
                              <select name="filterYear" id="filterYear" class="form-select" @style('width: 120px')>
                                  <option value="{{ $period }}" {{ $period == $filterYear ? 'selected' : '' }}>{{ $period }}</option>
                                  @foreach ($selectYear as $year)
                                      <option value="{{ $year->period }}" {{ $year->period == $filterYear ? 'selected' : '' }}>{{ $year->period }}</option>
                                  @endforeach
                              </select>
                          </div>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col">
                          <div class="mb-3">
                              <label class="form-label" for="group_company">Group Company</label>
                              <select class="form-select select2" name="group_company[]" id="group_company" multiple>
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
                              <select class="form-select select2" name="company[]" id="company" multiple>
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
                              <select class="form-select select2" name="location[]" id="location" multiple>
                                  @foreach ($locations as $location)
                                  <option value="{{ $location->work_area }}">{{ $location->area.' ('.$location->company_name.')' }}</option>
                                  @endforeach
                              </select>
                          </div>
                      </div>
                  </div>
            </form>
          </div> <!-- end offcanvas-body-->
          <div class="offcanvas-footer p-3 text-end">
            <button type="button" id="offcanvas-cancel" class="btn btn-outline-secondary me-2" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
            <button type="submit" class="btn btn-primary" form="admin_report_filter">Apply</button>
          </div>
      </div>
    </div>
    <!-- Content -->
    <div id="loading-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center; color:white; flex-direction:column;">
    <div style="width: 50%; background: #ddd; border-radius: 10px;">
        <div id="progress-bar" style="width: 0%; height: 20px; background: #4CAF50; border-radius: 10px; transition: width 0.3s;"></div>
    </div>
    <p>Sedang menyiapkan laporan, mohon tunggu...</p>
</div>
@endsection
@push('scripts')
@if(session('triggerFunction'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const reportSelect = document.getElementById('reportType');
            const triggerValue = "{{ session('triggerFunction') }}";

            // Set the select value to 'EmployeePA' and trigger the onchange event
            if (triggerValue) {
                reportSelect.value = triggerValue;
                adminReportType(triggerValue);
            }
        });
    </script>
@endif
<script>
function initMiniProgress(scope = document){

    scope.querySelectorAll('.mini-progress-bar')
        .forEach(function(el){

            el.style.width = '0%';

            requestAnimationFrame(() => {
                el.style.transition = 'width .8s ease';

                setTimeout(() => {
                    el.style.width = el.dataset.width || '0%';
                },100);
            });

        });
}

document.addEventListener(
    "DOMContentLoaded",
    () => initMiniProgress()
);
</script>
@endpush