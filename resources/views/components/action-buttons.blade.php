@forelse ($team->contributors as $contributor)
    @if (!$team->calibrationCheck)
        <a href="{{ route('appraisals-360.review', encrypt($team->employee->employee_id)) }}" title="{{ __('Revise') }}" type="button" class="btn btn-outline-info btn-sm m-1"><i class="ri-edit-line"></i></a>
    @endif
    <a href="{{ route('appraisals-task.detail', encrypt($contributor->id)) }}" title="{{ __('Details') }}" type="button" class="btn btn-outline-secondary btn-sm"><i class="ri-file-text-line"></i></a>
@empty
    @if ($team->layer_type === 'manager' && empty(json_decode($team->approvalRequest, true)))
        <a href="{{ route('appraisals-task.initiate', encrypt($team->employee->employee_id)) }}" type="button" class="btn btn-outline-primary btn-sm">{{ __('Initiate') }}</a>
    @else
        <a href="{{ route('appraisals-360.review', encrypt($team->employee->employee_id)) }}" type="button" class="btn btn-outline-warning btn-sm">{{ __('Review') }}</a>
    @endif
@endforelse