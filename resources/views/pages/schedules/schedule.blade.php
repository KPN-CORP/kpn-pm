@extends('layouts_.vertical', ['page_title' => 'Schedule'])

@section('css')
{{-- <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
@endsection

@section('content')
    
    <!-- Begin Page Content -->
    <div class="container-fluid">
        @if (session('success'))
            {{-- <div class="alert alert-success">
                {{ session('success') }}
            </div> --}}
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
        <!-- Page Heading -->
        <div class="pt-1 row">
        </div>
        
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
                <a href="{{ route('schedules.form') }}" class="btn btn-primary shadow">Create Schedule</a>
            </div>
        </div>
        <!-- Content Row -->
        <div class="row">
          <div class="col-md-12">
            <div class="card shadow mb-4">
              <div class="card-body">
                  <div class="table-responsive">
                      <table class="table table-hover dt-responsive nowrap" id="scheduleTable" width="100%" cellspacing="0">
                          <thead class="thead-light">
                              <tr class="text-center">
                                  <th>#</th>
                                  <th>Name</th>
                                  <th>Type</th>
                                  <th>From</th>
                                  <th>To</th>
                                  <th>Reminder</th>
                                  <th>Days</th>
                                  <th>Created By</th>
                                  <th>Actions</th>
                              </tr>
                          </thead>
                          <tbody>

                            @foreach($schedules as $schedule)
                              <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td style="width: 20%; word-wrap: break-word; white-space: normal;">{{ $schedule->schedule_name }}</td>
                                    <td>
                                        @if($schedule->event_type == 'goals'){{ 'Goal Setting' }}
                                        @elseif($schedule->event_type == 'schedulepa'){{ 'PA '.$schedule->schedule_periode }}
                                        @elseif($schedule->event_type == 'masterschedulepa'){{ 'Master PA '.$schedule->schedule_periode }}
                                        @elseif($schedule->event_type == 'masterschedulegoals'){{ 'Master Goal Settings '.$schedule->schedule_periode }}
                                        @endif
                                    </td>
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
                                    <td class="text-center">
                                        @if($schedule->created_by == $userId)
                                            @if($schedule->event_type == 'schedulepa')
                                                @if($schedulemasterpa)
                                                    <a href="{{ route('edit-schedule', \Crypt::encrypt($schedule->id)) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri-edit-box-line"></i></a>
                                                    {{-- <a class="btn btn-sm btn-danger" title="Delete" onclick="handleDelete(this)" data-id="{{ $schedule->id }}"><i class="ri-delete-bin-line"></i></a> --}}
                                                    <a class="btn btn-sm btn-danger" title="Delete" onclick="confirmDelete({{ $schedule->id }})" data-id="{{ $schedule->id }}"><i class="ri-delete-bin-line"></i></a>
                                                @endif
                                            @elseif($schedule->event_type == 'goals')
                                                @if($schedulemastergoals && $schedulegoals->contains($schedule->id))
                                                    <a href="{{ route('edit-schedule', \Crypt::encrypt($schedule->id)) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri-edit-box-line"></i></a>
                                                    <a class="btn btn-sm btn-danger" title="Delete" onclick="confirmDelete({{ $schedule->id }})" data-id="{{ $schedule->id }}"><i class="ri-delete-bin-line"></i></a>
                                                @endif
                                            @else
                                                <a href="{{ route('edit-schedule', \Crypt::encrypt($schedule->id)) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri-edit-box-line"></i></a>
                                                <a class="btn btn-sm btn-danger" title="Delete" onclick="confirmDelete({{ $schedule->id }})" data-id="{{ $schedule->id }}"><i class="ri-delete-bin-line"></i></a>
                                            @endif
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
    <form id="delete-form" action="" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
    
@endsection
@push('scripts')
<script>
    function confirmDelete(scheduleId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This schedule will be deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Atur action pada form tersembunyi dan submit
                var form = document.getElementById('delete-form');
                form.action = '/schedule/' + scheduleId + '/delete';
                form.submit();
            }
        });
    }
</script>
@endpush