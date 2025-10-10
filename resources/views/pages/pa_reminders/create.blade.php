@extends('layouts_.vertical', ['page_title' => 'Create Reminder'])

@section('css')
    
@endsection

@section('content')
    <div class="container-fluid">
      <div class="d-sm-flex align-items-center justify-content-right">
            <div class="card col-md-6">
                <div class="card-header d-flex bg-white justify-content-between">
                    <h4 class="modal-title" id="viewFormEmployeeLabel"></h4>
                    {{-- {{ $sublink }} --}}

                    <a href="{{ route('reminderpaindex') }}" type="button" class="btn btn-close"></a>
                </div>
                <div class="card-body" @style('overflow-y: auto;')>
                    <div class="container-fluid">
                      <form id="reminderForm" method="post" action="{{ route('prstore') }}">@csrf
                          <div class="mb-3 col-md-12">
                              <label class="form-label" for="name">Title</label>
                              <input type="text" class="form-control" placeholder="Enter.." id="reminder_name" name="reminder_name" required>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-12">
                                  <div class="mb-2">
                                      <label class="form-label" for="type">Business Unit</label>
                                      <select name="bisnis_unit[]" id="bisnis_unit" class="form-select bg-light select2" multiple required>
                                          @foreach($allowedGroupCompanies as $allowedGroupCompaniy)
                                              <option value="{{ $allowedGroupCompaniy }}">{{ $allowedGroupCompaniy }}</option>
                                          @endforeach
                                      </select>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-12">
                                  <div class="mb-2">
                                      <label class="form-label" for="type">Filter Company:</label>
                                      <select class="form-select bg-light select2" name="company_filter[]" multiple>
                                          <option value="">Select Company...</option>
                                          @foreach($companies as $company)
                                              <option value="{{ $company->contribution_level_code }}">{{ $company->contribution_level_code." (".$company->contribution_level.")" }}</option>
                                          @endforeach
                                      </select>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-12">
                                  <div class="mb-2">
                                      <label class="form-label" for="type">Filter Locations:</label>
                                      <select class="form-select bg-light select2" name="location_filter[]" multiple>
                                          <option value="">Select location...</option>
                                          @foreach($locations as $location)
                                              <option value="{{ $location->work_area }}">{{ $location->area." (".$location->company_name.")" }}</option>
                                          @endforeach
                                      </select>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-12">
                                  <div class="mb-2">
                                      <label class="form-label" for="type">Filter Job Level:</label>
                                      <select class="form-select bg-light select2" name="grade[]" multiple>
                                          <option value="">Select Job Level...</option>
                                          @foreach($listJobLevels as $listJobLevel)
                                              <option value="{{ $listJobLevel }}">{{ $listJobLevel }}</option>
                                          @endforeach
                                      </select>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-6">
                                  <div class="mb-2">
                                      <label class="form-label" for="start">Start Date</label>
                                      <input type="date" name="start_date" class="form-control" id="start" placeholder="mm/dd/yyyy"  required>
                                  </div>
                              </div>
                              <div class="col-md-6">
                                  <div class="mb-2">
                                      <label class="form-label" for="end">End Date</label>
                                      <input type="date" name="end_date" class="form-control" id="end" placeholder="mm/dd/yyyy" required>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-5">
                                  <div class="form-group">
                                      <div class="custom-control custom-checkbox">
                                          <input type="checkbox" class="custom-control-input" id="includeList" name="includeList" value="1">
                                          <label class="form-label" class="custom-control-label" for="includeList">Attach Detail</label>
                                      </div>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-12">
                                  <div class="mb-2">
                                      <label class="form-label" for="inputState">Repeat On</label>
                                      <div class="btn-group mb-2 d-block d-md-flex" role="group" aria-label="Repeat Day">
                                          <input type="radio" class="btn-check" name="repeat_days_selected" id="dayMon" value="Mon" autocomplete="off">
                                          <label class="btn btn-outline-primary btn-sm" for="dayMon">Mon</label>

                                          <input type="radio" class="btn-check" name="repeat_days_selected" id="dayTue" value="Tue" autocomplete="off">
                                          <label class="btn btn-outline-primary btn-sm" for="dayTue">Tue</label>

                                          <input type="radio" class="btn-check" name="repeat_days_selected" id="dayWed" value="Wed" autocomplete="off">
                                          <label class="btn btn-outline-primary btn-sm" for="dayWed">Wed</label>

                                          <input type="radio" class="btn-check" name="repeat_days_selected" id="dayThu" value="Thu" autocomplete="off">
                                          <label class="btn btn-outline-primary btn-sm" for="dayThu">Thu</label>

                                          <input type="radio" class="btn-check" name="repeat_days_selected" id="dayFri" value="Fri" autocomplete="off">
                                          <label class="btn btn-outline-primary btn-sm" for="dayFri">Fri</label>

                                          <input type="radio" class="btn-check" name="repeat_days_selected" id="daySat" value="Sat" autocomplete="off">
                                          <label class="btn btn-outline-primary btn-sm" for="daySat">Sat</label>

                                          <input type="radio" class="btn-check" name="repeat_days_selected" id="daySun" value="Sun" autocomplete="off">
                                          <label class="btn btn-outline-primary btn-sm" for="daySun">Sun</label>
                                      </div>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-12">
                                  <div class="mb-2">
                                      <label class="form-label" for="messages">Messages</label>
                                      <div id="editor-container" class="form-control" style="height: 200px;"></div>
                                      <textarea name="messages" id="messages" class="d-none"></textarea>
                                  </div>
                              </div>
                          </div>
                          <div class="row">
                              <div class="col-md d-md-flex justify-content-end text-center">
                                  {{-- <input type="text" name="repeat_days_selected" id="repeatDaysSelected"> --}}
                                  <a href="{{ route('reminderpaindex') }}" type="button" class="btn btn-outline-secondary shadow px-4 me-2">Cancel</a>
                                  <button type="submit" class="btn btn-primary shadow px-4">Submit</button>
                              </div>
                          </div>
                      </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    var quill = new Quill('#editor-container', {
        theme: 'snow'
    });

    document.getElementById('reminderForm').addEventListener('submit', function() {
        document.querySelector('textarea[name=messages]').value = quill.root.innerHTML;
    });

    // document.getElementById('reminderForm').addEventListener('submit', function() {
    //     var repeatDaysButtons = document.getElementsByName('repeat_days[]');
    //     var repeatDaysSelected = [];
    //     repeatDaysButtons.forEach(function(button) {
    //         if (button.classList.contains('active')) {
    //             repeatDaysSelected.push(button.value);
    //         }
    //     });
    //     document.getElementById('repeatDaysSelected').value = repeatDaysSelected.join(',');
    // });

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('reminderForm');
        const dayButtons = document.querySelectorAll('.day-button');
        const repeatDaysSelected = document.getElementById('repeatDaysSelected');
        const selectAllBtn = document.getElementById('select-all');

        // toggle aktif saat button diklik
        dayButtons.forEach(button => {
            button.addEventListener('click', function () {
                button.classList.toggle('active'); 
            });
        });

        // pilih semua
        selectAllBtn.addEventListener('click', function () {
            dayButtons.forEach(button => button.classList.add('active'));
        });

        // sebelum submit → isi hidden input
        form.addEventListener('submit', function () {
            const selected = Array.from(dayButtons)
                .filter(btn => btn.classList.contains('active'))
                .map(btn => btn.value);
            repeatDaysSelected.value = selected.join(',');
        });
    });

    function validateInput(input) {
        input.value = input.value.replace(/[^0-9]/g, '');
    }

    $(document).ready(function() {
        $('.select2').select2({
            theme: "bootstrap-5",
        });
    });
</script>
@endpush