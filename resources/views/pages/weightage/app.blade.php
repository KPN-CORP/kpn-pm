@extends('layouts_.vertical', ['page_title' => 'Weightage'])

@section('css')
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Begin Page Content -->
    <div class="container-fluid"> 
        @if (session('success'))
            <div class="alert alert-success mt-3">
                {!! session('success') !!}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger mt-3">
                {!! session('error') !!}
            </div>
        @endif
        <div class="row">
            <div class="col-lg">
                <div class="mb-3 text-end">
                    <a href="{{ route('admin-weightage.create') }}" type="button" class="btn btn-primary" title="create">Create Weightage</a>
                </div>
            </div>
        </div> 
        <!-- Content Row -->
        <div class="row">
          <div class="col-md-12">
            <div class="card shadow mb-4">
              <div class="card-body">
                <table class="table table-sm activate-select dt-responsive nowrap w-100 fs-14 align-middle" id="weightageTable">
                    <thead class="thead-light">
                        <tr class="text-center">
                        <th>#</th>
                        <th>Period</th>
                        <th>Business Unit</th>
                        <th>Job Level</th>
                        <th class="sorting_1 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($datas as $index => $row)   
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row->period }}</td>
                            <td>{{ $row->group_company }}</td>
                            <td>{{ implode(', ', $row->allJobLevels) }}</td>
                            <td class="sorting_1 text-center">
                                @if (!$row->deleted_at)
                                    <a href="{{ route('admin-weightage.edit', $row->id) }}" title="Edit" class="btn btn-sm rounded btn-outline-warning me-1"><i class="ri-edit-box-line fs-16"></i></a>
                                    <a href="{{ route('admin-weightage.detail', $row->id) }}" title="Detail" class="btn btn-sm rounded btn-outline-info me-1"><i class="ri-eye-line fs-16"></i></a>
                                    <button data-id="{{ $row->id }}" title="Archive" class="btn btn-sm rounded btn-outline-dark me-1 archive"><i class="ri-inbox-archive-line fs-16"></i></button> 
                                @else
                                    <a href="{{ route('admin-weightage.archive', $row->id) }}" title="Archived" class="btn btn-sm rounded btn-outline-secondary me-1"><i class="ri-archive-line fs-16"></i></a>
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
@push('scripts')
<script>

</script>
@endpush