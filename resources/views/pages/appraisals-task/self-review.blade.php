<div class="form-group mb-3">
    <h4 class="mb-3">
        Objektif Kerja
    </h4>
    <input type="hidden" name="formData[{{ $formIndex }}][formName]" value="{{ $name }}">
    @forelse ($goalData as $index => $data)
    <div class="row">
        <div class="card" style="background-color: #F8F9FA">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-5 col-md-12 p-2">
                        <span class="text-muted">KPI {{ $index + 1 }}</span>
                        <p class="mt-1" @style('white-space: pre-line')>{{ $data['kpi'] }}</p>
                    </div>
                    <div class="col-lg col-md-12 p-2">
                        <span class="text-muted">{{ __('Weightage') }}</span>
                        <p class="mt-1">{{ $data['weightage'] }}%</p>
                    </div>
                    <div class="col-lg col-md-12 p-2">
                        <span class="text-muted">{{ __('Type') }}</span>
                        <p class="mt-1">{{ $data['type'] }}</p>
                    </div>
                    <div class="col-lg col-md-12 p-2">
                        <span class="text-muted">{{ __('Target In') }} {{ is_null($data['custom_uom']) ? $data['uom']: $data['custom_uom'] }}</span>
                        <p class="mt-1">{{ $data['target'] }}</p>
                    </div>
                    <div class="col-lg-2 col-md-12 p-2">
                        <span class="text-muted">{{ __('Achievement In') }} {{ is_null($data['custom_uom']) ? $data['uom']: $data['custom_uom'] }}</span>
                        <input type="text" id="achievement-{{ $index + 1 }}" name="formData[{{ $formIndex }}][{{ $index }}][achievement]" placeholder="{{ __('Enter Achievement') }}.." value="{{ isset($data['actual']) ? $data['actual'] : "" }}" class="form-control mt-1" {{ $viewCategory == 'detail' ? 'disabled' : '' }} />
                            <div class="text-danger error-message"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
        <p>No form data available.</p>
    @endforelse
    {{-- <table class="table table-striped table-bordered m-0 mb-4">
        <tbody>
        @forelse ($goalData as $index => $data)
        <tr>
            <td scope="row fs-16">
                <div class="row">
                    <label for="kpi" class="col-md-3 col-6 col-form-label">KPI {{ $index + 1 }}</label>
                    <div class="col-9 col-form-label">
                        <p class="text-muted" @style('white-space: pre-line')>{{ $data['kpi'] }}</p>
                    </div>
                </div>
                <div class="row">
                    <label for="weightage" class="col-md-3 col-6 col-form-label">{{ __('Weightage') }}</label>
                    <div class="col-9 col-form-label">
                        <p class="text-muted">{{ $data['weightage'] }}%</p>
                    </div>
                </div>
                <div class="row">
                    <label for="type" class="col-md-3 col-6 col-form-label">{{ __('Type') }}</label>
                    <div class="col-9 col-form-label">
                        <p class="text-muted">{{ $data['type'] }}</p>
                    </div>
                </div>
                <div class="row">
                    <label for="target" class="col-md-3 col-6 col-form-label">{{ __('Target In') }} {{ is_null($data['custom_uom']) ? $data['uom']: $data['custom_uom'] }}</label>
                    <div class="col-9 col-form-label">
                        <p class="text-muted">{{ $data['target'] }}</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-6">
                        <label for="achievement" class="col-form-label">{{ __('Achievement In') }} {{ is_null($data['custom_uom']) ? $data['uom']: $data['custom_uom'] }}</label>
                    </div>
                    <div class="col-9 col-md-4 col-form-label">
                        <input type="text" id="achievement-{{ $index + 1 }}" name="formData[{{ $formIndex }}][{{ $index }}][achievement]" placeholder="{{ __('Enter Achievement') }}.." value="{{ isset($data['actual']) ? $data['actual'] : "" }}" class="form-control" />
                            <div class="text-danger error-message"></div>
                    </div>
                </div>
            </td>
        </tr>
        @empty
        <p>No form data available.</p>
        @endforelse
        </tbody>
    </table> --}}
</div>