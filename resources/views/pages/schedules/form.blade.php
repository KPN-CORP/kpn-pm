@extends('layouts_.vertical', ['page_title' => 'Schedule'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-center">
            <div class="card col-md-8">
                <div class="card-header d-flex bg-white justify-content-between">
                    <h4 class="modal-title" id="viewFormEmployeeLabel">Schedule</h4>
                    <a href="{{ route('schedules') }}" type="button" class="btn btn-close"></a>
                </div>
                <div class="card-body" @style('overflow-y: auto;')>
                    <div class="container-fluid">
                        <form id="scheduleForm" method="post" action="{{ route('save-schedule') }}">@csrf
                            <div class="row my-2">
                                <div class="col-md-5">
                                    <div class="mb-2">
                                        <label class="form-label" for="name">Schedule Name</label>
                                        <input type="text" class="form-control bg-light" placeholder="Enter name.." id="name" name="schedule_name" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-5">
                                    <div class="mb-2">
                                        <label class="form-label" for="type">Event Type</label>
                                        <select name="event_type" class="form-select" required>
                                            <option value="" disabled selected>- Please Select -</option>
                                            <option value="goals">Goals</option>
                                            <option value="pa_year_end">Year End</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-10">
                                    <div class="mb-2">
                                        <label class="form-label" for="type">Employee Type</label>
                                        <select name="employee_type[]" class="form-select bg-light select2" multiple>
                                            <option value="Permanent">Permanent</option>
                                            <option value="Contract">Contract</option>
                                            <option value="Probation">Probation</option>
                                            <option value="Service Bond">Service Bond</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-10">
                                    <div class="mb-2">
                                        <label class="form-label" for="type">Bisnis Unit</label>
                                        <select name="bisnis_unit[]" class="form-select bg-light select2" multiple required>
                                            <option value="KPN Corporation">KPN Corporation</option>
                                            <option value="KPN Plantations">KPN Plantations</option>
                                            <option value="Downstream">Downstream</option>
                                            <option value="Property">Property</option>
                                            <option value="Cement">Cement</option>
                                            <option value="KPN Sugar">KPN Sugar</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-10">
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
                                <div class="col-md-10">
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
                                <div class="col-md-5">
                                    <div class="mb-2">
                                        <label class="form-label" for="start">Last Join Date</label>
                                        <input type="date" name="last_join_date" class="form-control bg-light" id="start" placeholder="mm/dd/yyyy" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-5">
                                    <div class="mb-2">
                                        <label class="form-label" for="start">Start Date</label>
                                        <input type="date" name="start_date" class="form-control bg-light" id="start" placeholder="mm/dd/yyyy" required>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="mb-2">
                                        <label class="form-label" for="end">End Date</label>
                                        <input type="date" name="end_date" class="form-control bg-light" id="end" placeholder="mm/dd/yyyy" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="checkbox_reminder" name="checkbox_reminder" value="1">
                                            <label class="form-label" class="custom-control-label" for="checkbox_reminder">Reminder</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="reminders" hidden>
                                <div class="row my-2">
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label" for="inputState">Reminder By</label>
                                            <select id="inputState" name="inputState" class="form-select" onchange="toggleDivs()">
                                                <option value="repeaton" selected>Repeat On</option>
                                                <option value="beforeenddate">Before End Date</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="repeaton">
                                    <div class="row">
                                        <div class="col-12 col-md-auto">
                                            <div class="btn-group mb-2 d-block d-md-flex" role="group" aria-label="Vertical button group">
                                                <button type="button" name="repeat_days[]" value="Mon" class="btn btn-outline-primary btn-sm day-button">Mon</button>
                                                <button type="button" name="repeat_days[]" value="Tue" class="btn btn-outline-primary btn-sm day-button">Tue</button>
                                                <button type="button" name="repeat_days[]" value="Wed" class="btn btn-outline-primary btn-sm day-button">Wed</button>
                                                <button type="button" name="repeat_days[]" value="Thu" class="btn btn-outline-primary btn-sm day-button">Thu</button>
                                                <button type="button" name="repeat_days[]" value="Fri" class="btn btn-outline-primary btn-sm day-button">Fri</button>
                                                <button type="button" name="repeat_days[]" value="Sat" class="btn btn-outline-primary btn-sm day-button">Sat</button>
                                                <button type="button" name="repeat_days[]" value="Sun" class="btn btn-outline-primary btn-sm day-button">Sun</button>
                                            </div>
                                        </div>
                                        <div class="col-md-auto text-end">
                                            <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="select-all">{{ __('select all') }}</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row" id="beforeenddate" style="display: none;">
                                    <div class="col-md-4">
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" name="before_end_date" oninput="validateInput(this)">
                                            <div class="input-group-append">
                                                <span class="input-group-text">Days</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row my-4">
                                    <div class="col-md">
                                        <div class="mb-2">
                                            <label class="form-label" for="messages">Messages</label>
                                            <div id="editor-container" class="form-control bg-light" style="height: 200px;"></div>
                                            <textarea name="messages" id="messages" class="d-none"></textarea>
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md d-md-flex justify-content-end text-center">
                                    <input type="hidden" name="repeat_days_selected" id="repeatDaysSelected">
                                    <a href="{{ route('schedules') }}" type="button" class="btn btn-outline-secondary rounded-pill shadow px-4 me-2">{{ __('Cancel') }}</a>
                                    <button type="submit" class="btn btn-primary rounded-pill shadow px-4">{{ __('Submit') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@vite('resources/js/schedule.js')
