@can('approvalonbehalf')
<div class="btn-group dropstart">
  <button class="btn btn-sm {{ $row->status != 'Sendback' && $row->goal->form_status != 'Draft' && $row->access ? 'btn-primary' : 'btn-light disabled' }} px-1 rounded" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bs-offset="0,10">
    Action
  </button>
  <div class="dropdown-menu dropdown-menu-animated">
    @if ($row->status != 'Sendback' && $row->goal->form_status != 'Draft')
      @if ($row->status === 'Pending')
        <a class="dropdown-item" href="{{ route('admin.create.approval.goal', $row->form_id) }}">Approve</a>
      @endif
        <a class="dropdown-item" href="javascript:void(0)" id="revoke-btn-{{ $row->goal->id }}" onclick="revokeGoal(this)" data-id="{{ $row->goal->id }}" data-name="{{ $row->employee->fullname . ' - ' . $row->employee->employee_id }}">Revise</a>
    @else
      <a class="dropdown-item disabled" href="javascript:void(0)">{{ __('No Action') }}</a>
    @endif
  </div>
</div>
@else
{{ "-" }}
@endcan
