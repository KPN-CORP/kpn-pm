<div class="form-group mb-3">
    <h4 class="mb-3">
        Objektif Kerja
    </h4>
    <input type="hidden" name="formData[{{ $formIndex }}][formName]" value="{{ $name }}">
    <table class="table table-striped table-bordered m-0 mb-4">
        <tbody>
            @forelse ($goalData as $index => $data)
                <tr>
                    <td scope="row fs-16">
                        <div class="row">
                            <label for="kpi" class="col-md-3 col-12">KPI {{ $index + 1 }}</label>
                            <div class="col-md-9 col-12">
                                <p class="text-muted" @style('white-space: pre-line')>{{ $data['kpi'] }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 col-5">
                                <div class="row">
                                    <label for="weightage" class="col-md-3 col-12">{{ __('Weightage') }}</label>
                                    <div class="col-md-9 col-12">
                                        <p class="text-muted">{{ $data['weightage'] }}%</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 col-7">
                                <div class="row">
                                    <label for="type" class="col-md-3 col-12">{{ __('Type') }}</label>
                                    <div class="col-md-9 col-12">
                                        <p class="text-muted">{{ $data['type'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 col-5">
                                <div class="row">
                                    <label for="target" class="col-md-3 col-12">{{ __('Target In') }}
                                    </label>
                                    <div class="col-md-9 col-12">
                                        <p class="text-muted">{{ $data['target'] }} {{ is_null($data['custom_uom']) ? $data['uom'] : $data['custom_uom'] }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 col-7">
                                <div class="row">
                                    <label for="achievement" class="col-md-3 col-12 ">{{ __('Achievement In') }}
                                    </label>
                                    <div class="col-md-5 col-12">
                                        <div class="input-group input-group-sm mb-3">
                                            <input type="text" id="achievement-{{ $index + 1 }}"
                                                name="formData[{{ $formIndex }}][{{ $index }}][achievement]"
                                                placeholder="{{ __('Enter Achievement') }}.."
                                                value="{{ isset($data['actual']) ? $data['actual'] : '' }}"
                                                class="form-control w-25" />
                                            <div class="input-group-append ">
                                                <span class="input-group-text px-1"
                                                    id="basic-addon2">{{ is_null($data['custom_uom']) ? $data['uom'] : $data['custom_uom'] }}</span>
                                            </div>
                                            <div class="text-danger error-message"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>   
                    </td>
                </tr>
            @empty
                <p>No form data available.</p>
            @endforelse
        </tbody>
    </table>
</div>
