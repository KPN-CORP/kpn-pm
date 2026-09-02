@php
    $typeStyles = [
        'submitted'        => ['icon' => 'ri-send-plane-line',    'dot' => 'bg-primary',   'badge' => 'bg-primary-subtle text-primary'],
        'approved'         => ['icon' => 'ri-check-line',         'dot' => 'bg-success',   'badge' => 'bg-success-subtle text-success'],
        'reset'            => ['icon' => 'ri-history-line',       'dot' => 'bg-secondary', 'badge' => 'bg-light text-secondary border'],
        'sendback'         => ['icon' => 'ri-arrow-go-back-line', 'dot' => 'bg-warning',   'badge' => 'bg-warning-subtle text-dark'],
        'sendback-current' => ['icon' => 'ri-feedback-line',      'dot' => 'bg-warning',   'badge' => 'bg-warning-subtle text-dark'],
        'pending'          => ['icon' => 'ri-time-line',          'dot' => 'bg-warning',   'badge' => 'bg-warning-subtle text-dark'],
        'final'            => ['icon' => 'ri-shield-check-line',  'dot' => 'bg-success',   'badge' => 'bg-success-subtle text-success'],
    ];
@endphp

<div class="px-4 py-3 border-bottom bg-light">
    <div class="fw-bold text-dark">{{ $employeeName }}</div>
    <div class="text-muted small-text">
        {{ $approvalRequest->employee_id }} &middot; {{ __('Period') }} {{ $approvalRequest->period }}
    </div>
</div>

@if ($autoApproved && $approvalRequest->status === 'Pending')
    <div class="alert alert-info border-0 rounded-0 mb-0 py-2 px-4" style="font-size: 0.85rem;">
        <i class="ri-information-line me-1"></i>
        {{ __('Goals were auto-approved after the PA form was submitted, so no further approval step is recorded.') }}
    </div>
@endif

<div class="p-4">
    <div class="position-relative ps-4" style="border-left: 2px solid #e9ecef;">
        @foreach ($events as $event)
            @php $style = $typeStyles[$event['type']] ?? $typeStyles['approved']; @endphp
            <div class="position-relative pb-4 {{ $loop->last ? 'pb-0' : '' }}">
                <span class="position-absolute rounded-circle {{ $style['dot'] }} d-flex align-items-center justify-content-center text-white"
                      style="width: 22px; height: 22px; left: -33px; top: 0; font-size: 0.7rem;">
                    <i class="{{ $style['icon'] }}"></i>
                </span>

                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <span class="badge {{ $style['badge'] }} rounded-pill py-1 px-3 fw-medium">{{ $event['title'] }}</span>
                    <span class="text-muted small-text">
                        {{ $event['date'] ? $event['date']->format('d M Y H:i') : __('In progress') }}
                    </span>
                </div>

                @if ($event['actor'])
                    <div class="fw-semibold text-dark lh-sm">{{ $event['actor'] }}</div>
                    @if ($event['role'])
                        <div class="text-muted small-text">{{ $event['role'] }}</div>
                    @endif
                @endif

                @if ($event['messages'])
                    <div class="mt-2 p-2 bg-light border rounded text-secondary" style="font-size: 0.82rem; white-space: pre-line;">
                        {{ $event['messages'] }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
