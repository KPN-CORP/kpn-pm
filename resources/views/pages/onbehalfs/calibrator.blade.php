<div class="row">
    <div class="col-md-12">
      <div class="card shadow mb-4">
        <div class="card-header">
          <div class="row rounded">
            <div class="col-md-auto text-center">
                <button class="btn btn-outline-primary btn-sm px-2 my-1 me-1 filter-btn" data-id="all">{{ __('All Task') }}</button>
                <button class="btn btn-outline-primary btn-sm px-2 my-1 me-1 filter-btn" data-id="draft">Draft</button>
                <button class="btn btn-outline-primary btn-sm px-2 my-1 me-1 filter-btn" data-id="{{ __('Pending') }}">{{ __('Pending') }}</button>
                <button class="btn btn-outline-primary btn-sm px-2 my-1 me-1 filter-btn" data-id="{{ __('Approved') }}">{{ __('Approved') }}</button>
            </div>
          </div>
        </div>
        <div class="card-body">
            <table class="table table-sm table-hover align-middle activate-select dt-responsive nowrap w-100 fs-14" id="onBehalfTable">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th>Calibrator Name</th>
                        <th>Designation</th>
                        <th class="sorting_1">Action</th>
                    </tr>
                </thead>
                <tbody>
                  @foreach ($data as $row)
                    <tr>
                      <td>{{ $row->fullname .' ('.$row->employee_id.')'}}</td>
                      <td>{{ $row->designation_name }}</td>
                      <td>
                        <a href="{{ route('admin.onbehalfs.rating', $row->employee_id) }}" class="btn btn-sm btn-primary px-1 rounded" type="button">
                            Action
                        </a>
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
                {{-- @foreach ($data as $row)
                  @include('pages.onbehalfs.calibrator_list', ['row' => $row])
                @endforeach --}}
              </div>
            </div>
    </div>
</div>
     
