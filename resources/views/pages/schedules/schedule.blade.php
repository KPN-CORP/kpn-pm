@extends('layouts_.vertical', ['page_title' => 'Schedule'])

@section('css')
<style>
    .loader {
    display: none; /* Awalnya loader disembunyikan */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    /* Tambahkan gaya lain untuk loader sesuai kebutuhan */
    }

    .loader.active {
    display: block; /* Loader akan muncul saat class "active" ditambahkan */
    }
</style>
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="mb-2 text-end">
                    <a href="{{ route('schedules.form') }}" class="btn btn-primary rounded-pill shadow">Create Schedule</a>
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
                  <input type="text" name="customsearch" id="customsearch" class="form-control  border-dark-subtle border-left-0" placeholder="search.." aria-label="search" aria-describedby="search">
                </div>
              </div>
            </div>
        </div>
        <!-- Content Row -->
        <div class="row">
          <div class="col-md-12">
            <div class="card shadow mb-4">
              <div class="card-body">
                <table class="table activate-select dt-responsive nowrap w-100" id="scheduleTable">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th>#</th>
                            <th>Name</th>
                            <th>{{ __('Type') }}</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Reminder</th>
                            <th>Days</th>
                            <th>Created By</th>
                            <th class="sorting_1">Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                    @foreach($schedules as $schedule)
                        <tr>
                            <td>{{ $loop->index + 1 }}</td>
                            <td>{{ $schedule->schedule_name }}</td>
                            <td>{{ $schedule->employee_type }}</td>
                            <td>{{ $schedule->start_date }}</td>
                            <td>{{ $schedule->end_date }}</td>
                            <td>@if($schedule->checkbox_reminder == '1') Yes @else No @endif</td>
                            <td>@if($schedule->checkbox_reminder == 1)
                                    @if($schedule->repeat_days <> '')
                                        {{ $schedule->repeat_days }}
                                    @else
                                        {{ $schedule->before_end_date . ' Days Before End Date' }}
                                    @endif
                                @endif
                            </td>
                            <td>{{ isset($schedule->createdBy->name) ? $schedule->createdBy->name : '-' }}</td>
                            <!--<td><span class="badge badge-success badge-pill w-100">Active</span></td>-->
                            <td class="text-center sorting_1">
                                @if($schedule->created_by == $userId)
                                    <a href="{{ route('edit-schedule', $schedule->id) }}" class="btn btn-sm rounded-pill btn-warning" title="Edit" ><i class="ri-edit-box-line"></i></a>
                                    
                                    <a class="btn btn-sm rounded-pill btn-danger" title="Delete" onclick="handleDelete(this)" data-id="{{ $schedule->id }}"><i class="ri-delete-bin-line"></i></a>
                                @else
                                    <span>-</span>
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
@endsection
@vite('resources/js/schedule.js')