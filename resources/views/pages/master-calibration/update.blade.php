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
                                        <input type="text" class="form-control bg-light" placeholder="Enter name.." id="calibration_name" name="calibration_name" value="{{ isset($value_calibration_name) ? $value_calibration_name : '' }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label class="form-label" for="name">KPI Unit</label>
                                        <select class="form-control bg-light" name="kpi_unit" id="kpi_unit" readonly>
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
                                        <select class="form-control bg-light" name="individual_kpi" id="individual_kpi" readonly>
                                            <option value="-">-</option>
                                            @foreach($ratings as $rating)
                                                <option value="{{ $rating->id_rating_group }}" {{ (isset($value_indi_kpi) && $value_indi_kpi == $rating->id_rating_group) ? 'selected' : '' }}>{{ $rating->rating_group_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 ">
                                    <div class="mb-2">
                                        <label class="form-label" for="name">&nbsp;&nbsp;</label>
                                        {{-- <button type="submit" class="btn btn-primary form-control" >Set</button>
                                         <a href="{{ route('admcalibrations') }}" type="button" class="btn btn-outline-secondary">Cancel</a> --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
            @if(isset($kpiUnits) && isset($individualKpis))
            <form action="{{ route('updatecalibrations') }}" method="POST">
                @csrf
                <input type="hidden" name="idcalibration" value="{{ $value_calibration_id }}">
                <input type="hidden" name="calibration_name" value="{{ $value_calibration_name }}">
                <input type="hidden" name="kpi_unit" value="{{ $value_kpi_unit }}">
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
                                                    <input type="number" name="Xx[{{ $unit }}][{{ $kpi }}]" class="form-control kpi-input" placeholder="Enter value" value="{{ isset($calibrationPercentages[$unit][$kpi]) ? ($calibrationPercentages[$unit][$kpi])*100 : '' }}" oninput="calculateTotal()">
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
                <div class="col-md-3">
                    <div class="mb-2">
                        {{-- <label class="form-label" for="name">&nbsp;&nbsp;</label> --}}
                        <button type="submit" class="btn btn-primary" >Submit Calibration</button>
                        <a href="{{ route('admcalibrations') }}" type="button" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </form>
            @endif
        
        </div>
    </div>
@endsection
@push('scripts')
<script>
    function calculateTotal() {
        @if(!empty($kpiUnits))
            @foreach($kpiUnits as $unit)
                let total_{{ $unit }} = 0; // Variabel untuk menyimpan total per KPI Unit

                // Cari semua input yang sesuai dengan KPI Unit ini (menggunakan pencarian pola yang spesifik)
                document.querySelectorAll('input[name*="Xx[{{ $unit }}]"]').forEach(function(input) {
                    let value = parseFloat(input.value) || 0; // Jika tidak ada nilai, anggap 0
                    total_{{ $unit }} += value; // Tambahkan nilai ke total
                });

                // Tampilkan hasil di elemen <td> untuk total KPI Unit ini
                document.getElementById('total-V{{ $unit }}').innerText = total_{{ $unit }} + '%';
            @endforeach
        @endif
    }
    document.addEventListener('DOMContentLoaded', function() {
        @foreach($kpiUnits as $unit)
            let total_{{ $unit }} = 0; // Variabel untuk menyimpan total per KPI Unit

            // Cari semua input yang sesuai dengan KPI Unit ini (menggunakan pencarian pola yang spesifik)
            document.querySelectorAll('input[name*="Xx[{{ $unit }}]"]').forEach(function(input) {
                let value = parseFloat(input.value) || 0; // Jika tidak ada nilai, anggap 0
                total_{{ $unit }} += value; // Tambahkan nilai ke total
            });

            // Tampilkan hasil di elemen <td> untuk total KPI Unit ini
            document.getElementById('total-V{{ $unit }}').innerText = total_{{ $unit }} + '%';
        @endforeach
    });
</script>
@endpush