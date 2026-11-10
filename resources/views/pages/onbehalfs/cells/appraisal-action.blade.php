@can('approvalonbehalf')
<div class="btn-group dropstart">
  @php
    $hasCalibration = $row->calibration && $row->calibration->isNotEmpty();
    $canAct = $row->status != 'Sendback' && $row->appraisal->form_status != 'Draft';
    $selfData = auth()->user()->id === $row->employee->id;
  @endphp

  @if (!$selfData)
    @if ($canAct && $hasCalibration)
      <button class="btn btn-sm btn-light px-1 rounded" type="button"
        onclick="Swal.fire({ icon: 'info', title: 'Calibration Ongoing', text: 'Employee already in Calibration process.', confirmButtonText: 'OK', confirmButtonColor: '#AB2F2B' })">
        Action
      </button>
    @else
      <button class="btn btn-sm btn-primary px-1 rounded" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bs-offset="0,10">
        Action
      </button>
      <div class="dropdown-menu dropdown-menu-animated">
        @if ($row->status === 'Pending')
          <a class="dropdown-item" href="{{ route('admin.create.approval.appraisal', [encrypt($row->employee_id), 'onbehalf']) }}">Approve</a>

          {{-- REVOKE AJAX --}}
          <button type="button"
                  class="dropdown-item js-revoke"
                  data-url="{{ route('admin.revoke.approval.appraisal', [encrypt($row->employee_id), 'onbehalf']) }}"
                  data-emp="{{ $row->employee_id }}">
            Revoke
          </button>
        @else
          <a class="dropdown-item" href="{{ route('admin.create.approval.appraisal', [encrypt($row->employee_id), 'onbehalf']) }}">Revise</a>
        @endif
      </div>
    @endif
  @else
    <button class="btn btn-sm btn-light px-1 rounded disabled" type="button">Action</button>
  @endif
</div>
@else
  {{ "-" }}
@endcan
