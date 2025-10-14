@extends('layouts_.vertical', ['page_title' => 'Reminder '])

@section('css')
    
@endsection

@section('content')
    <div class="container-fluid">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible border-0 fade show" role="alert">
                <button type="button" class="btn-close btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <strong>Success - </strong> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible border-0 fade show" role="alert">
                <button type="button" class="btn-close btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <strong>Error - </strong> {{ session('error') }}
            </div>
        @endif
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
                {{-- <button type="button" class="btn btn-primary shadow">Create</button> --}}
                <a href="{{ route('prcreate') }}" type="button" class="btn btn-primary">Create</a>
            </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="card shadow mb-4">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="card-title"></h3>
                </div>
                  <div class="table-responsive">
                      <table class="table table-hover dt-responsive nowrap" id="scheduleTable" width="100%" cellspacing="0">
                            <thead class="thead-light">
                              <tr class="text-center">
                                  <th>No</th>
                                  <th>Title</th>
                                  <th>Start</th>
                                  <th>End</th>
                                  <th>Include</th>
                                  <th>Repeat</th>
                                  <th>Actions</th>
                              </tr>
                            </thead>
                            <tbody>
                                @foreach($schedules as $index => $item)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>{{ $item->reminder_name }}</td>
                                        <td>{{ $item->start_date }}</td>
                                        <td>{{ $item->end_date }}</td>
                                        <td>{{ $item->includeList == 1 ? 'Yes' : 'No' }}</td>
                                        <td>{{ $item->repeat_days }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('reminders.edit', $item->id) }}" 
                                              class="btn btn-sm btn-outline-warning" 
                                              title="Edit">
                                              <i class="ri-edit-box-line"></i>
                                            </a>
                                            <form action="{{ route('reminders.destroy', $item->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete"
                                                    onclick="return confirm('Yakin ingin menghapus reminder ini?')">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
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
@endsection

@push('scripts')

@endpush