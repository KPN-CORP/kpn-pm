<div class="row">
    <div class="col-md-12">
      <div class="card shadow mb-4">
        <div class="card-header">
          <div class="row bg-primary-subtle rounded p-2">
            <div class="col-md-auto text-center">
                <button class="btn btn-outline-primary rounded-pill btn-sm px-2 my-1 me-2 filter-btn" data-id="{{ __('All Task') }}">{{ __('All Task') }}</button>
                <button class="btn btn-outline-primary rounded-pill btn-sm px-2 my-1 me-2 filter-btn" data-id="draft">Draft</button>
                <button class="btn btn-outline-primary rounded-pill btn-sm px-2 my-1 me-2 filter-btn" data-id="{{ __('Waiting For Revision') }}">{{ __('Waiting For Revision') }}</button>
                <button class="btn btn-outline-primary rounded-pill btn-sm px-2 my-1 me-2 filter-btn" data-id="{{ __('Pending') }}">{{ __('Pending') }}</button>
                <button class="btn btn-outline-primary rounded-pill btn-sm px-2 my-1 me-2 filter-btn" data-id="{{ __('Approved') }}">{{ __('Approved') }}</button>
            </div>
          </div>
        </div>
        <div class="card-body">
            <table class="table table-sm table-hover activate-select dt-responsive nowrap w-100" id="onBehalfTable">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th>Employees</th>
                        <th>Approval Status</th>
                        <th>Initiated On</th>
                        <th>{{ __('Initiated By') }}</th>
                        <th>{{ __('Last Updated On') }}</th>
                        <th>Updated By</th>
                        <th class="sorting_1">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $row)
                    <tr>
                        <td>{{ $row->employee->fullname }}</td>
                        <td class="text-center">
                          <a href="javascript:void(0)" data-bs-id="{{ $row->employee_id }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $row->approvalLayer ? 'Manager L'.$row->approvalLayer.' : '.$row->name : $row->name }}" class="badge {{ $row->goal->form_status == 'Draft' || $row->status == 'Sendback' ? 'bg-secondary' : ($row->status === 'Approved' ? 'bg-success' : 'bg-warning')}} rounded-pill ">{{ $row->goal->form_status == 'Draft' ? 'Draft': ($row->status == 'Pending' ? __('Pending') : ($row->status == 'Sendback' ? 'Waiting For Revision' : $row->status)) }}</a></td>
                        <td class="text-center">{{ $row->formatted_created_at }}</td>
                        <td>{{ $row->employee->fullname }}</td>
                        <td class="text-center">{{ $row->formatted_updated_at }}</td>
                        <td class="text-center">{{ $row->updatedBy ? $row->updatedBy->name.' ('.$row->updatedBy->employee_id.')' : '-' }}</td>
                        <td class="text-center sorting_1">
                          @if ( $row->status === 'Pending')
                            @can('approvalonbehalf')
                              <a href="{{ route('admin.create.approval.goal', $row->form_id) }}" class="btn btn-outline-primary btn-sm rounded-pill font-weight-medium">Act</a>
                            @else
                              <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $row->goal->id }}"><i class="ri-file-text-line"></i></a>
                            @endcan
                          @else
                              <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $row->goal->id }}"><i class="ri-file-text-line"></i></a>
                          @endif
                        </td>
                        @if ($data)
                        @include('pages.onbehalfs.detail')
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
      </div>
    </div>
</div>
     
