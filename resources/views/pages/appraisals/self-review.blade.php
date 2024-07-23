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
                    <label for="kpi" class="col-md-3 col-6 col-form-label">KPI {{ $index + 1 }}</label>
                    <div class="col-9 col-form-label">
                        <p class="text-muted">{{ $data['kpi'] }}</p>
                    </div>
                </div>
                <div class="row">
                    <label for="weightage" class="col-md-3 col-6 col-form-label">Weightage</label>
                    <div class="col-9 col-form-label">
                        <p class="text-muted">{{ $data['weightage'] }}%</p>
                    </div>
                </div>
                <div class="row">
                    <label for="type" class="col-md-3 col-6 col-form-label">Type</label>
                    <div class="col-9 col-form-label">
                        <p class="text-muted">{{ $data['type'] }}</p>
                    </div>
                </div>
                <div class="row">
                    <label for="target" class="col-md-3 col-6 col-form-label">Target</label>
                    <div class="col-9 col-form-label">
                        <p class="text-muted">{{ $data['target'] }} {{ is_null($data['custom_uom']) ? $data['uom']: $data['custom_uom'] }}</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-6">
                        <label for="achievement" class="col-form-label">Achievement ({{ is_null($data['custom_uom']) ? $data['uom']: $data['custom_uom'] }})</label>
                    </div>
                    <div class="col-9 col-md-4 col-form-label">
                        <input type="text" id="achievement-{{ $index + 1 }}" name="formData[{{ $formIndex }}][{{ $index }}][achievement]" placeholder="Enter achievement.." class="form-control w-75" />
                            <div class="text-danger error-message"></div>
                    </div>
                </div>
            </td>
        </tr>
        @empty
        <p>No form data available.</p>
        @endforelse
        </tbody>
    </table>
    @if (auth()->user()->employee_id != $goal->employee_id)
    <div class="row">
        <div class="col-md-5">
            <label for="messages">Komentar</label>
            <textarea class="form-control" name="messages" id="messages" rows="3" placeholder="masukkan komentar anda.."></textarea>
        </div>
    </div>
    @endif
</div>