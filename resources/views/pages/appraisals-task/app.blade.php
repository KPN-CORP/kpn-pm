@extends('layouts_.vertical', ['page_title' => 'Appraisal'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success mt-3">
                {{ session('success') }}
            </div>
        @endif
        <div class="mandatory-field">
            <div id="alertField" class="alert alert-danger alert-dismissible {{ Session::has('error') ? '':'fade' }}" role="alert" {{ Session::has('error') ? '':'hidden' }}>
                <strong>{{ Session::get('error') }}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-pills mb-md-0 mb-3" id="myTab" role="tablist">
                            <li class="nav-item" role="taskbox">
                              <button class="btn btn-outline-primary position-relative active me-2" id="team-tab" data-bs-toggle="tab" data-bs-target="#team" type="button" role="tab" aria-controls="team" aria-selected="true">{{ __('My Team') }}
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $notifDataTeams ? $notifDataTeams : 'd-none' }}">
                                    {{ $notifDataTeams }}
                                </span>
                              </button>
                            </li>
                            <li class="nav-item" role="taskbox">
                                <button class="btn btn-outline-primary position-relative" id="360-review-tab" data-bs-toggle="tab" data-bs-target="#360-review" type="button" role="tab" aria-controls="360-review" aria-selected="false">
                                    {{ __('Appraisal 360') }}
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $notifData360 ? $notifData360 : 'd-none' }}">
                                        {{ $notifData360 }}
                                    </span>
                                </button>
                            </li>
                          </ul>
                          <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="team" role="tabpanel" aria-labelledby="team-tab">
                                <div class="table-responsive">
                                    <table id="tableAppraisalTeam" class="table table-hover w-100">
                                        <caption>List of Team</caption>
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Position</th>
                                                <th>Office</th>
                                                <th>Business Unit</th>
                                                <th>{{ __('Initiated Date') }}</th>
                                                <th>Category</th>
                                                <th class="sorting_1">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($dataTeams as $index => $team)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        {{ $team->employee->fullname }}
                                                        <span class="text-muted">{{ '('.$team->employee->employee_id.')' }}</span>
                                                    </td>
                                                    <td>{{ $team->employee->designation }}</td>
                                                    <td>{{ $team->employee->office_area }}</td>
                                                    <td>{{ $team->employee->group_company }}</td>
                                                    <td class="text-end">{{ $team->approvalRequest->first() ? $team->approvalRequest->first()->formatted_created_at : '-' }}</td>
                                                    <td>{{ $team->layer_type === 'manager' ? 'subordinate' : ($team->layer_type === 'subordinate' ? 'manager' : $team->layer_type ) }}</td>
                                                    <td class="sorting_1 text-center">
                                                        @forelse ($team->contributors as $contributor)
                                                            <a href="{{ route('appraisals-task.detail', $contributor->id) }}" type="button" class="btn btn-outline-info btn-sm">{{ __('View Detail') }}</a>
                                                        @empty
                                                            @if ($team->layer_type === 'manager' && empty(json_decode($team->approvalRequest, true)))
                                                                <a href="{{ route('appraisals-task.initiate', $team->employee->employee_id) }}" type="button" class="btn btn-outline-primary btn-sm">{{ __('Initiate') }}</a>
                                                            @else
                                                                <a href="{{ route('appraisals-360.review', $team->employee->employee_id) }}" type="button" class="btn btn-outline-warning btn-sm">{{ __('Review') }}</a>    
                                                            @endif
                                                        @endforelse
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="360-review" role="tabpanel" aria-labelledby="360-review-tab">
                                <div class="table-responsive">
                                    <table id="tableAppraisal360" class="table table-hover w-100">
                                        <caption>List of 360</caption>
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Position</th>
                                                <th>Office</th>
                                                <th>Business Unit</th>
                                                <th>{{ __('Initiated Date') }}</th>
                                                <th>Category</th>
                                                <th class="sorting_1">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($data360 as $index => $row)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        {{ $row->employee->fullname }}
                                                        <span class="text-muted">{{ '('.$row->employee->employee_id.')' }}</span>
                                                    </td>
                                                    <td>{{ $row->employee->designation }}</td>
                                                    <td>{{ $row->employee->office_area }}</td>
                                                    <td>{{ $row->employee->group_company }}</td>
                                                    <td class="text-end">{{ $row->approvalRequest->first() ? $row->approvalRequest->first()->formatted_created_at : '-' }}</td>
                                                    <td>{{ $row->layer_type === 'manager' ? 'subordinate' : ($row->layer_type === 'subordinate' ? 'manager' : $row->layer_type ) }}</td>
                                                    <td class="sorting_1 text-center">
                                                        @forelse ($row->contributors as $contributor)
                                                            <a href="{{ route('appraisals-task.detail', $contributor->id) }}" type="button" class="btn btn-outline-info btn-sm">{{ __('View Detail') }}</a>
                                                        @empty
                                                            <a href="{{ route('appraisals-360.review', $row->employee->employee_id) }}" type="button" class="btn btn-outline-warning btn-sm">{{ __('Review') }}</a>    
                                                        @endforelse
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                          </div>
                    </div> <!-- end card-body -->
                </div> <!-- end card-->
            </div>
        </div>
    </div>
    @endsection
    @push('scripts')
        @if(Session::has('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {                
                Swal.fire({
                    icon: "error",
                    title: "Cannot initiate appraisal!",
                    text: '{{ Session::get('error') }}',
                    confirmButtonText: "OK",
                });
            });
        </script>
        @endif
    @endpush
