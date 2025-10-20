@extends('layouts_.vertical', ['page_title' => 'Create Reminder'])

@section('css')
    
@endsection

@section('content')
    <div class="container-fluid">
      <div class="d-sm-flex align-items-center justify-content-right">
            <div class="card col-md-6">
                <div class="card-header d-flex bg-white justify-content-between">
                    <h4 class="modal-title" id="viewFormEmployeeLabel"></h4>

                    <a href="{{ route('reminderpaindex') }}" type="button" class="btn btn-close"></a>
                </div>
                <div class="card-body" @style('overflow-y: auto;')>
                    <div class="container-fluid">
                      <form id="reminderForm" method="post" action="{{ route('reminders.update', $reminder->id) }}">
                        @csrf
                        @method('PUT')
                          <div class="mb-3 col-md-12">
                              <label class="form-label" for="name">Title</label>
                              <input type="text" class="form-control" id="reminder_name" name="reminder_name" value="{{ old('reminder_name', $reminder->reminder_name) }}" disabled>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-12">
                                  <div class="mb-2">
                                      <label class="form-label" for="type">Business Unit</label>
                                      <input type="text" class="form-control" id="bisnis_unit" name="bisnis_unit" value="{{ old('bisnis_unit', $reminder->bisnis_unit) }}" disabled>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-12">
                                  <div class="mb-2">
                                      <label class="form-label" for="type">Filter Company:</label>
                                      <input type="text" class="form-control" id="company_filter" name="company_filter" value="{{ old('company_filter', $reminder->company_filter) }}" disabled>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-12">
                                  <div class="mb-2">
                                      <label class="form-label" for="type">Filter Locations:</label>
                                      <input type="text" class="form-control" id="location_filter" name="location_filter" value="{{ old('location_filter', $reminder->location_filter) }}" disabled>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-12">
                                  <div class="mb-2">
                                      <label class="form-label" for="type">Filter Job Level:</label>
                                      <input type="text" class="form-control" id="grade" name="grade" value="{{ old('grade', $reminder->grade) }}" disabled>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-6">
                                  <div class="mb-2">
                                      <label class="form-label" for="start">Start Date</label>
                                      <input type="date" name="start_date" class="form-control" id="start" placeholder="mm/dd/yyyy" value="{{ old('start_date', $reminder->start_date) }}" required>
                                  </div>
                              </div>
                              <div class="col-md-6">
                                  <div class="mb-2">
                                      <label class="form-label" for="end">End Date</label>
                                      <input type="date" name="end_date" class="form-control" id="end" placeholder="mm/dd/yyyy" value="{{ old('end_date', $reminder->end_date) }}" required>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-5">
                                  <div class="form-group">
                                      <div class="custom-control custom-checkbox">
                                          <input type="checkbox" class="custom-control-input" id="includeList" name="includeList" value="1" {{ $reminder->includeList ? 'checked' : '' }}>
                                          <label class="form-label" class="custom-control-label" for="includeList">Attach Detail</label>
                                      </div>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-12">
                                  <div class="mb-2">
                                      <label class="form-label" for="inputState">Repeat On</label>
                                      <div class="btn-group mb-2 d-block d-md-flex" role="group">
                                        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                                            <input type="radio" class="btn-check" name="repeat_days_selected" id="day{{ $day }}"
                                                value="{{ $day }}" {{ $reminder->repeat_days == $day ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary btn-sm" for="day{{ $day }}">{{ $day }}</label>
                                        @endforeach
                                    </div>
                                  </div>
                              </div>
                          </div>
                          <div class="row my-2">
                              <div class="col-md-12">
                                  <div class="mb-2">
                                      <label class="form-label" for="for_messages">Messages</label>
                                      <div id="editor-container-ms" class="form-control" style="height: 200px;">{!! $reminder->messages !!}</div>
                                    <textarea name="messages" id="messages" class="d-none">{{ old('messages', $reminder->messages) }}</textarea>
                                  </div>
                              </div>
                          </div>
                          <div class="row">
                              <div class="col-md d-md-flex justify-content-end text-center">
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
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('reminderForm');
        const dayButtons = document.querySelectorAll('.day-button');
        const repeatDaysSelected = document.getElementById('repeatDaysSelected');
        const selectAllBtn = document.getElementById('select-all');

        var quill = new Quill('#editor-container-ms', {
            theme: 'snow'
        });

        form.addEventListener('submit', function() {
            var messageField = document.getElementById('messages');
            messageField.value = quill.root.innerHTML; // Ambil isi editor (HTML)
        });

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