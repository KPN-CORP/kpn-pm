@extends('layouts_.vertical', ['page_title' => 'Appraisal'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-primary border-5 border-start-0 border-end-0">
                    <div class="card-header d-flex justify-content-center">
                        <div class="col-10 text-center">
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
                                        <div class="connector {{ $step > $index + 1 ? 'completed' : '' }} col mx-4"></div>
                                    @endif
                                @endforeach
                            </div>
                                              
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="stepperForm" action="{{ route('appraisal.submit') }}" method="POST">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $goal->employee_id }}">
                        <input type="hidden" class="form-control" name="approver_id" value="{{ $approval->approver_id }}">
                        <input type="hidden" name="formGroupName" value="{{ $formGroupData['name'] }}">
                        @foreach ($filteredFormData as $index => $row)
                        <div class="step" data-step="{{ $step }}"></div>
                            <div class="form-step {{ $step == $index + 1 ? 'active' : '' }}" data-step="{{ $index + 1 }}">
                                <div class="card-title h4 mb-4">{{ $row['title'] }}</div>
                                @include($row['blade'], [
                                'id' => 'input_' . strtolower(str_replace(' ', '_', $row['title'])),
                                'formIndex' => $index,
                                'name' => $row['name'],
                                'data' => $row['data'],
                                ])
                            </div>
                            @endforeach
                            <div class="d-flex justify-content-center py-2">
                                <button type="button" class="btn btn-light border me-3 btn-lg prev-btn" style="display: none;"><i class="ri-arrow-left-line"></i>{{ __('Prev') }}</button>
                                <button type="button" class="btn btn-primary btn-lg next-btn">{{ __('Next') }} <i class="ri-arrow-right-line"></i></button>
                                <button type="submit" class="btn btn-primary btn-lg submit-btn px-md-4" style="display: none;">{{ __('Submit') }}</button>
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