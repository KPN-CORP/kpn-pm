@extends('layouts_.vertical', ['page_title' => 'Goals'])

@section('css')
<style>
    .freezeCol {
    background-color: #f9f9f9 !important; /* Change this to the desired background color */
    }
</style>
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item">{{ $parentLink }}</li>
                            <li class="breadcrumb-item active">{{ $link }}</li>
                        </ol>
                    </div>
                    <h4 class="page-title">{{ $link }}</h4>
                </div>
            </div>
        </div>
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
        <div class="row mt-3">
            <div class="col-xl">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tableAppraisal360" class="table activate-select dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Office</th>
                                        <th>Business Unit</th>
                                        <th>Initiated Date</th>
                                        <th>Category</th>
                                        <th class="sorting_1">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($contributors as $index => $row)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                {{ $row->employee->fullname }}
                                                <span class="text-muted">{{ '('.$row->employee->employee_id.')' }}</span>
                                            </td>
                                            <td>{{ $row->employee->designation }}</td>
                                            <td>{{ $row->employee->office_area }}</td>
                                            <td>{{ $row->employee->group_company }}</td>
                                            <td>-</td>
                                            <td>{{ $row->layer_type === 'manager' ? 'subordinate' : ($row->layer_type === 'subordinate' ? 'manager' : $row->layer_type ) }}</td>
                                            <td class="sorting_1">
                                                    @if ($row->layer_type === 'manager')
                                                        <a href="{{ route('appraisals-360.initiate', $row->employee->employee_id) }}" type="button" class="btn btn-primary btn-sm rounded-pill">Initiate</a>
                                                    @else
                                                        <a href="{{ route('appraisals-360.review', $row->employee->employee_id) }}" type="button" class="btn btn-info btn-sm rounded-pill">Review</a>    
                                                    @endif

                                                    {{-- <a href="{{ url('appraisals-360/details', $row->id) }}" type="button" class="btn btn-light btn-sm rounded-pill">View Details</a> --}}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div> <!-- end card-body -->
                </div> <!-- end card-->
            </div>
        </div>
    </div>
    @endsection
    @push('scripts')
        <script src="{{ asset('js/goal-approval.js') }}?v={{ config('app.version') }}"></script>
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