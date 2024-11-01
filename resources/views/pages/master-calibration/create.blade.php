@extends('layouts_.vertical', ['page_title' => 'Calibrations'])

@section('css')
<style>
    table th.bgcolor-silver {
        background-color: #D3D3D3;
        text-align: center;
    }
</style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <form id="scheduleForm" method="post" action="{{ route('showcalibrations') }}">@csrf
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body" @style('overflow-y: auto;')>
                        <div class="container-fluid">
                            <div class="row my-2">
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label class="form-label" for="name">Calibration Name</label>
                                        <input type="text" class="form-control" placeholder="Enter name.." id="calibration_name" name="calibration_name" value="{{ isset($value_calibration_name) ? $value_calibration_name : '' }}" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-2">
                                        <label class="form-label" for="name">Periode</label>
                                        <select class="form-control" id="periode" name="periode" required>
                                            @foreach ($years as $year)
                                                @if (!in_array($year, $periodCalibration))
                                                    <option value="{{ $year }}" 
                                                        @if(isset($value_periode) && $value_periode == $year) selected @endif>
                                                        {{ $year }}
                                                    </option>
                                                @endif
                                            @endforeach
                                            
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label class="form-label" for="name">KPI Unit</label>
                                        <select class="form-control" name="kpi_unit" id="kpi_unit" required>
                                            <option value="-">-</option>
                                            @foreach($ratings as $rating)
                                                <option value="{{ $rating->id_rating_group }}" {{ (isset($value_kpi_unit) && $value_kpi_unit == $rating->id_rating_group) ? 'selected' : '' }}>{{ $rating->rating_group_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label class="form-label" for="name">Individual KPI</label>
                                        <select class="form-control" name="individual_kpi" id="individual_kpi" required>
                                            <option value="-">-</option>
                                            @foreach($ratings as $rating)
                                                <option value="{{ $rating->id_rating_group }}" {{ (isset($value_indi_kpi) && $value_indi_kpi == $rating->id_rating_group) ? 'selected' : '' }}>{{ $rating->rating_group_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="mb-2">
                                        <label class="form-label" for="name">&nbsp;&nbsp;</label>
                                        <button type="submit" class="btn btn-primary form-control" >Set</button>
                                        {{-- <a href="{{ route('admcalibrations') }}" type="button" class="btn btn-outline-secondary">Cancel</a> --}}
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
            @if(isset($kpiUnits) && isset($individualKpis))
            <form action="{{ route('savecalibrations') }}" method="POST">
                @csrf
                <input type="hidden" name="calibration_name" value="{{ $value_calibration_name }}">
                <input type="hidden" name="kpi_unit" value="{{ $value_kpi_unit }}">
                <input type="hidden" name="periode" value="{{ $value_periode }}">
                <input type="hidden" name="individual_kpi" value="{{ $value_indi_kpi }}">
            
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Individual KPI</th>
                                        <th colspan="{{ $jumlahKpiUnits }}" class="bgcolor-silver">KPI Unit</th>
                                    </tr>
                                    <tr>
                                        @foreach($kpiUnits as $unit)
                                            <th class="bgcolor-silver">{{ $unit }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($individualKpis as $kpi)
                                        <tr>
                                            <td style="text-align:center; background-color: #D3D3D3;">{{ $kpi }}</td>
                                            @foreach($kpiUnits as $unit)
                                                <td>
                                                    <input type="number" name="Xx[{{ $unit }}][{{ $kpi }}]" class="form-control kpi-input" placeholder="Enter value" oninput="calculateTotal()">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        @foreach($kpiUnits as $unit)
                                            <td id="total-V{{ $unit }}">0%</td>
                                        @endforeach
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="mb-2 text-end">
                        <a href="{{ route('admcalibrations') }}" type="button" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" >Submit Calibration</button>
                    </div>
                </div>
            </form>
            @endif
        
        </div>
    </div>
@endsection
@push('scripts')
<script>
    @if(!empty($kpiUnits))
        function calculateTotal() {
            @foreach($kpiUnits as $unit)
                var total = 0;
                var lastInput = null;

                document.querySelectorAll(`input[name^="Xx[{{ $unit }}]"]`).forEach(function(input) {
                    var value = parseFloat(input.value);
                    
                    if (document.activeElement === input) {
                        lastInput = input;
                    }

                    if (!isNaN(value)) {
                        total += value;
                    }
                });

                if (total > 100) {
                    alert("Total persentase untuk KPI Unit '{{ $unit }}' tidak boleh lebih dari 100%");
                    
                    if (lastInput !== null) {
                        lastInput.value = 0;
                    }

                    calculateTotal();
                    return;
                }

                document.getElementById(`total-V{{ $unit }}`).textContent = total.toFixed(0) + '%';
            @endforeach
        }

        document.querySelectorAll('.kpi-input').forEach(function(input) {
            input.addEventListener('input', calculateTotal);
        });
    @endif

    // function calculateTotal() {
    //     @if(!empty($kpiUnits))
    //         @foreach($individualKpis as $kpi)
    //             @foreach($kpiUnits as $unit)
    //                 let total_{{ $unit }} = 0;

    //                 document.querySelectorAll('input[name*="Xx[{{ $unit }}]"]').forEach(function(input) {
    //                     let value = parseFloat(input.value) || 0;
    //                     total_{{ $unit }} += value;
    //                     lastInput = input;
    //                     console.log(lastInput);
    //                 });

    //                 if (total_{{ $unit }} > 100) {
    //                     alert("Total untuk KPI Unit '{{ $unit }}' tidak boleh lebih dari 100%");
    //                     lastInput.value = 0;
    //                     total_{{ $unit }} -= parseFloat(lastInput.value) || 0;
    //                 }

    //                 document.getElementById('total-V{{ $unit }}').innerText = total_{{ $unit }} + '%';
    //             @endforeach
    //         @endforeach
    //     @endif
    // }
</script>
@endpush