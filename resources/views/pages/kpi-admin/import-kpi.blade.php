@extends('layouts_.vertical', ['page_title' => 'Import KPI'])

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
                <button type="button" class="btn btn-primary shadow" data-bs-toggle="modal" data-bs-target="#importModal">Import</button>
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
                                  <th>Employee ID</th>
                                  <th>Name</th>
                                  <th>Data KPI</th>
                                  <th>Last Update</th>
                                  <th>Actions</th>
                              </tr>
                            </thead>
                            <tbody>
                                @foreach($achievements as $index => $item)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td class="text-center">{{ $item->employee_id }}</td>
                                        <td>{{ $item->employee->fullname ?? '-' }}</td>
                                        <td>
                                            @php
                                                $kpiData = json_decode($item->data, true);
                                            @endphp
                                            @if(is_array($kpiData))
                                                @foreach($kpiData as $kpi)
                                                    {{ $kpi['month'] }} : {{ $kpi['value'] }}@if(!$loop->last), @endif
                                                    @if($loop->iteration % 7 == 0)
                                                        <br>
                                                    @endif
                                                @endforeach
                                            @else
                                                <span class="text-muted">No Data</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{ $item->updated_at ? $item->updated_at->format('d M Y H:i') : $item->created_at->format('d M Y H:i') }}
                                        </td>
                                        <td class="text-center">
                                            {{-- <a href="{{ route('achievements.edit', $item->id) }}" class="btn btn-sm btn-warning">Edit</a>--}}
                                            <form action="{{ route('achievements.destroy', $item->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Yakin hapus data ini?')"><i class="ri-delete-bin-line"></i></button>
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

      <!-- Modal Pop-Up -->
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel">Import KPI</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <form action="{{ route('importkpisubmit') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col">
                                    <div class="alert alert-info">
                                        <strong>Notes:</strong>
                                        <ul class="mb-0">
                                            <li>Template Import ...</strong></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="file">Upload File</label>
                                <input type="file" name="file" id="file" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')

@endpush