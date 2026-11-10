<div class="row">
    <div class="col-md-12">
      <div class="card shadow mb-4">
        <div class="card-header">
          <div class="d-flex flex-wrap gap-1" role="group" aria-label="Status">
            @foreach ($tabs as $key => $label)
              <button type="button"
                      class="btn btn-sm px-2 filter-btn {{ $loop->first ? 'btn-primary active' : 'btn-outline-primary' }}"
                      data-status="{{ $key }}"
                      aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                {{ $label }}
              </button>
            @endforeach
          </div>
        </div>
        <div class="card-body">
            <table class="table table-sm table-hover align-middle nowrap w-100 fs-14" id="onBehalfTable"
                   data-server-side="true"
                   data-category="{{ $category }}"
                   data-url="{{ route('admin.onbehalf.data') }}">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th>Employees</th>
                        <th>{{ $category === 'Goals' ? 'Goals' : 'Appraisal' }}</th>
                        <th>Approval Status</th>
                        <th>Initiated On</th>
                        <th>{{ __('Initiated By') }}</th>
                        <th>{{ __('Last Updated On') }}</th>
                        <th>Updated By</th>
                        <th class="sorting_1">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
      </div>
    </div>
</div>
<div id="onBehalfDetailHolder"></div>
