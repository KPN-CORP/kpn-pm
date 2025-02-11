@extends('layouts_.vertical', ['page_title' => 'Imports'])

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
            <div class="alert alert-danger">
                {{ session('error') }}
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
                <a class="btn btn-outline-info m-1" data-bs-toggle="modal" data-bs-target="#importModal" title="Import Rating"><i class="ri-upload-cloud-2-line d-md-none"></i><span class="d-none d-md-block">Upload Rating</span></a>
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
                      <table class="table table-hover dt-responsive nowrap activate-select" id="scheduleTable" width="100%" cellspacing="0">
                          <thead class="thead-light">
                              <tr class="text-center">
                                  <th>No</th>
                                  <th>Import By</th>
                                  <th>Import Date</th>
                                  <th>Imported File</th>
                                  <th>Period</th>
                                  <th>Error</th>
                                  <th class="sorting_1 text-center"></th>
                              </tr>
                          </thead>
                          <tbody>
                            @foreach($datas as $index => $row)
                              <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row->employee->fullname .' ('.$row->employee->employee_id.')' }}</td>
                                    <td>{{ $row->created_at }}</td>
                                    <td>
                                        <a href="{{ asset('storage/' . $row->path) }}" >
                                            {{ $row->file_name }}
                                        </a>
                                    </td>
                                    <td>{{ $row->period }}</td>
                                    <td>
                                        <a href="{{ asset('storage/' . $row->error_path) }}" >
                                            {{ $row->error_files }}
                                        </a>
                                    </td>
                                    <td class="text-end">
                                        <a href="javascript:void(0)" data-bs-id="" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-title="Description" data-bs-content="{{ $row->desc }}"><i class="ri-more-2-fill"></i></a>
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
      <!-- Modal -->
        <div class="modal fade" id="importModal" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="importModalLabel">Upload Rating <span id="modalLevel"></span></h4>
                    </div>
                    <form id="importRating" action="{{ route('importRating.store') }}" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            @csrf
                            <input type="hidden" name="category" value="Rating">
                            <div class="row">
                                <div class="col-3">
                                    <div class="mb-2">
                                        <label class="form-label" for="period">
                                            Period
                                        </label>
                                        <input class="form-control" type="text" name="period" id="period" placeholder="select" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-2">
                                        <label class="form-label" for="desc">
                                            Description
                                        </label>
                                        <textarea class="form-control" id="desc" name="desc" placeholder="input descriptions.." required></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-4 mt-2">
                                        <label class="form-label" for="excelFile">
                                            Upload Rating File *<code>.xlsx,.csv</code>
                                        </label>
                                        <input type="file" class="form-control" id="excelFile" name="excelFile" accept=".xlsx,.csv" required>
                                        <small class="form-text text-muted">Only Excel (.xlsx) and CSV (.csv) files are allowed.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" id="importRatingButton" class="btn btn-primary">
                                <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection