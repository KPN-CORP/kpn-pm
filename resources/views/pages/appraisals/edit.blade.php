@extends('layouts_.vertical', ['page_title' => 'Appraisal'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col">
                <div class="card">
                    <div class="card-header d-flex justify-content-center">
                        <div class="col col-10 text-center">
                            <div class="stepper mt-3 d-flex justify-content-between justify-content-md-around">
                                @foreach ($filteredFormData as $index => $tabs)
                                <div class="step" data-step="{{ $step }}"></div>
                                    <div class="step d-flex flex-column align-items-center" data-step="{{ $index + 1 }}">
                                        <div class="circle {{ $step == $index + 1 ? 'active' : ($step > $index + 1 ? 'completed' : '') }}">
                                            <i class="{{ $tabs['icon'] }}"></i>
                                        </div>
                                        <div class="label">{{ $tabs['name'] }}</div>
                                    </div>
                                    @if ($index < count($filteredFormData) - 1)
                                        <div class="connector {{ $step > $index + 1 ? 'completed' : '' }} col mx-md-4 d-none d-md-block"></div>
                                    @endif
                                @endforeach
                            </div>
                                              
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="formAppraisalUser" action="{{ route('appraisal.update') }}" method="POST">
                        @csrf
                        <input type="hidden" class="form-control" name="id" value="{{ $appraisal->id }}">
                        <input type="hidden" name="employee_id" value="{{ $appraisal->employee_id }}">
                        <input type="hidden" class="form-control" name="approver_id" value="{{ $approval->approver_id }}">
                        <input type="hidden" name="formGroupName" value="{{ $formGroupData['data']['name'] }}">
                        @foreach ($filteredFormData as $index => $row)
                        <div class="step" data-step="{{ $step }}"></div>
                            <div class="form-step {{ $step == $index + 1 ? 'active' : '' }}" data-step="{{ $index + 1 }}">
                                <div class="card-title h4 mb-4">{{ $row['title'] }}</div>
                                @include($row['blade'], [
                                'id' => 'input_' . strtolower(str_replace(' ', '_', $row['title'])),
                                'formIndex' => $index,
                                'name' => $row['name'],
                                'data' => $row['data'],
                                'viewCategory' => $viewCategory
                                ])
                            </div>
                            @endforeach
                            <input type="hidden" name="submit_type" id="submitType" value="">
                            <div class="d-flex justify-content-center py-2">
                                <a type="button" class="btn btn-light border me-3 prev-btn" style="display: none;"><i class="ri-arrow-left-line"></i>{{ __('Prev') }}</a>
                                <a type="button" class="btn btn-primary next-btn">{{ __('Next') }} <i class="ri-arrow-right-line"></i></a>
                                <a data-id="submit_form" class="btn btn-primary submit-user px-md-4" style="display: none;"><span class="spinner-border spinner-border-sm me-1 d-none" aria-hidden="true"></span>{{ __('Submit') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
<script>
    const errorMessages = '{{ __('Empty Messages') }}';
</script>
@endpush