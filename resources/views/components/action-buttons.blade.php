@forelse ($team->contributors as $contributor)
    <a href="{{ route('appraisals-task.detail', $contributor->id) }}" type="button" class="btn btn-outline-info btn-sm">{{ __('Details') }}</a>
@empty
    @if ($team->layer_type === 'manager' && empty(json_decode($team->approvalRequest, true)))
        <a href="{{ route('appraisals-task.initiate', $team->employee->employee_id) }}" type="button" class="btn btn-outline-primary btn-sm">{{ __('Initiate') }}</a>
    @else
        <a href="{{ route('appraisals-360.review', $team->employee->employee_id) }}" type="button" class="btn btn-outline-warning btn-sm">{{ __('Review') }}</a>
    @endif
@endforelse