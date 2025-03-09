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
                        <input type="text" id="achievement-{{ $index + 1 }}" name="formData[{{ $formIndex }}][{{ $index }}][achievement]" placeholder="{{ __('Enter Achievement') }}.." value="{{ isset($data['actual']) ? $data['actual'] : "" }}" class="form-control mt-1" />
                            <div class="text-danger error-message"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
        <p>No form data available.</p>
    @endforelse
</div>