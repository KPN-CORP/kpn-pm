@extends('layouts_.vertical', ['page_title' => 'Cash Advanced'])

@section('css')
    <!-- Sertakan CSS Bootstrap jika diperlukan -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-beta3/css/bootstrap.min.css">
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('schedules') }}">{{ $parentLink }}</a></li>
                            <li class="breadcrumb-item active">{{ $link }}</li>
                        </ol>
                    </div>
                    <h4 class="page-title">{{ $link }}</h4>
                </div>
            </div>
        </div>
        <div class="d-sm-flex align-items-center justify-content-center">
            <div class="card col-md-8">
                <div class="card-header d-flex bg-white justify-content-between">
                    <h4 class="modal-title" id="viewFormEmployeeLabel">Add Data</h4>
                    <a href="{{ route('cashadvanced') }}" type="button" class="btn btn-close"></a>
                </div>
                <div class="card-body" @style('overflow-y: auto;')>
                    <div class="container-fluid">
                        <form id="scheduleForm" method="post" action="{{ route('save-schedule') }}">@csrf
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="start">Name</label>
                                        <input type="text" name="name" id="name" value="{{ $employee_data->fullname }}" class="form-control bg-light" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="start">Unit</label>
                                        <input type="text" name="unit" id="unit" value="{{ $employee_data->unit }}" class="form-control bg-light" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="start">Grade</label>
                                        <input type="text" name="grade" id="grade" value="{{ $employee_data->job_level }}" class="form-control bg-light" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="name">Costing Company</label>
                                        <select class="form-control select2" id="companyFilter" name="company" required>
                                            <option value="">Select Company...</option>
                                            @foreach($companies as $company)
                                                <option value="{{ $company->contribution_level_code }}">{{ $company->contribution_level." (".$company->contribution_level_code.")" }}</option>
                                            @endforeach
                                        </select>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="name">Destination</label>
                                        <select class="form-control select2" id="locationFilter" name="locationFilter" onchange="toggleOthers()" required>
                                            <option value="">Select location...</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->area." (".$location->company_name.")" }}">{{ $location->area." (".$location->company_name.")" }}</option>
                                            @endforeach
                                            <option value="Others">Others</option>
                                        </select>
                                        <br><input type="text" name="others_location" id="others_location" class="form-control" placeholder="Other Location" style="display: none;"> 
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="name">CA Purposes</label>
                                        <textarea name="ca_needs" id="ca_needs" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="start">Start Date</label>
                                        <input type="date" name="start_date" id="start_date" class="form-control" placeholder="mm/dd/yyyy" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="start">End Date</label>
                                        <input type="date" name="end_date" id="end_date" class="form-control" placeholder="mm/dd/yyyy" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="start">Total Days</label>
                                        <div class="input-group">
                                            <input class="form-control bg-light" id="totaldays" name="totaldays" type="text" min="0" value="0" readonly>
                                            <div class="input-group-append">
                                                <span class="input-group-text">days</span>
                                            </div>
                                        </div>
                                        <input class="form-control" id="perdiem" name="perdiem" type="hidden" value="{{ $perdiem->amount }}" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="start">CA Date Required</label>
                                        <input type="date" name="ca_required" id="ca_required" class="form-control" placeholder="mm/dd/yyyy" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label" for="type">CA Type</label>
                                        <select name="ca_type" id="ca_type" class="form-select" onchange="toggleDivs()" required>
                                            <option value="">-</option>
                                            <option value="ca_nbt">CA Non Business Trip</option>
                                            <option value="ca_e">CA Entertainment</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row" id="ca_nbt" style="display: none;">
                                <div class="col-md-12">
                                    <div class="table-responsive-sm">
                                        <div class="d-flex flex-column gap-2">
                                            <div class="text-bg-danger p-2" style="text-align:center">Estimated Cash Advanced</div>
                                                <div class="card">
                                                    <div class="card-body">
                                                        <div class="mb-2">
                                                            <label class="form-label">Allowance (Perdiem)</label>
                                                            <div class="input-group">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text">Rp</span>
                                                                </div>
                                                                <input class="form-control bg-light" name="allowance" id="allowance" type="text" min="0" value="0" readonly>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label">Transportation</label>
                                                            <div class="input-group">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text">Rp</span>
                                                                </div>
                                                                <input class="form-control" name="transport" type="text" min="0" value="0">
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label">Accommodation</label>
                                                            <div class="input-group">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text">Rp</span>
                                                                </div>
                                                                <input class="form-control" name="accommodation" type="text" min="0" value="0">
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label">Other</label>
                                                            <div class="input-group">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text">Rp</span>
                                                                </div>
                                                                <input class="form-control" name="other" type="text" min="0" value="0">
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label">Total Cash Advanced</label>
                                                            <div class="input-group">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text">Rp</span>
                                                                </div>
                                                                <input class="form-control bg-light" name="totalca" type="text" min="0" value="0" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="ca_e" style="display: none;">
                                <div class="col-md-12">
                                    <div class="table-responsive-sm">
                                        <div class="d-flex flex-column gap-2">
                                            <div class="text-bg-danger p-2" style="text-align:center">Estimated Entertainment</div>
                                                <div class="card">
                                                    <div class="card-body">
                                                        <div class="accordion" id="accordionPanelsStayOpenExample">
                                                            <div class="accordion-item">
                                                                <h2 class="accordion-header" id="panelsStayOpen-headingOne">
                                                                    <button class="accordion-button fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                                                                        Entertainment Detail #1
                                                                    </button>
                                                                </h2>
                                                                <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse show" aria-labelledby="panelsStayOpen-headingOne">
                                                                    <div class="accordion-body">
                                                                        <div class="mb-2">
                                                                            <label class="form-label">Entertainment Type</label>
                                                                            <select name="enter_type_1" id="enter_type_1" class="form-select">
                                                                                <option value="">-</option>
                                                                                <option value="meal_cost">Meal Cost</option>
                                                                                <option value="accommodation">Accommodation</option>
                                                                                <option value="gift">Gift</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label class="form-label">Entertainment Fee Detail</label>
                                                                            <textarea name="enter_fee_1" id="enter_fee_1" class="form-control"></textarea>
                                                                        </div>
                                                                        <div class="input-group">
                                                                            <div class="input-group-append">
                                                                                <span class="input-group-text">Rp</span>
                                                                            </div>
                                                                            <input class="form-control" name="nominal_1" id="nominal_1" type="text" min="0" value="0">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="accordion-item">
                                                                <h2 class="accordion-header" id="panelsStayOpen-headingTwo">
                                                                    <button class="accordion-button fw-medium collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
                                                                        Entertainment Detail #2
                                                                    </button>
                                                                </h2>
                                                                <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingTwo">
                                                                    <div class="accordion-body">
                                                                        <div class="mb-2">
                                                                            <label class="form-label">Entertainment Type</label>
                                                                            <select name="enter_type_2" id="enter_type_2" class="form-select">
                                                                                <option value="">-</option>
                                                                                <option value="meal_cost">Meal Cost</option>
                                                                                <option value="accommodation">Accommodation</option>
                                                                                <option value="gift">Gift</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label class="form-label">Entertainment Fee Detail</label>
                                                                            <textarea name="enter_fee_2" id="enter_fee_2" class="form-control"></textarea>
                                                                        </div>
                                                                        <div class="input-group">
                                                                            <div class="input-group-append">
                                                                                <span class="input-group-text">Rp</span>
                                                                            </div>
                                                                            <input class="form-control" name="nominal_2" id="nominal_2" type="text" min="0" value="0">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="accordion-item">
                                                                <h2 class="accordion-header" id="panelsStayOpen-headingThree">
                                                                    <button class="accordion-button fw-medium collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseThree" aria-expanded="false" aria-controls="panelsStayOpen-collapseThree">
                                                                        Entertainment Detail #3
                                                                    </button>
                                                                </h2>
                                                                <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingThree">
                                                                    <div class="accordion-body">
                                                                        <div class="mb-2">
                                                                            <label class="form-label">Entertainment Type</label>
                                                                            <select name="enter_type_3" id="enter_type_3" class="form-select">
                                                                                <option value="">-</option>
                                                                                <option value="meal_cost">Meal Cost</option>
                                                                                <option value="accommodation">Accommodation</option>
                                                                                <option value="gift">Gift</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label class="form-label">Entertainment Fee Detail</label>
                                                                            <textarea name="enter_fee_3" id="enter_fee_3" class="form-control"></textarea>
                                                                        </div>
                                                                        <div class="input-group">
                                                                            <div class="input-group-append">
                                                                                <span class="input-group-text">Rp</span>
                                                                            </div>
                                                                            <input class="form-control" name="nominal_3" id="nominal_3" type="text" min="0" value="0">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="accordion-item">
                                                                <h2 class="accordion-header" id="panelsStayOpen-headingFour">
                                                                    <button class="accordion-button fw-medium collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseFour" aria-expanded="false" aria-controls="panelsStayOpen-collapseFour">
                                                                        Entertainment Detail #4
                                                                    </button>
                                                                </h2>
                                                                <div id="panelsStayOpen-collapseFour" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingFour">
                                                                    <div class="accordion-body">
                                                                        <div class="mb-2">
                                                                            <label class="form-label">Entertainment Type</label>
                                                                            <select name="enter_type_4" id="enter_type_4" class="form-select">
                                                                                <option value="">-</option>
                                                                                <option value="meal_cost">Meal Cost</option>
                                                                                <option value="accommodation">Accommodation</option>
                                                                                <option value="gift">Gift</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label class="form-label">Entertainment Fee Detail</label>
                                                                            <textarea name="enter_fee_4" id="enter_fee_4" class="form-control"></textarea>
                                                                        </div>
                                                                        <div class="input-group">
                                                                            <div class="input-group-append">
                                                                                <span class="input-group-text">Rp</span>
                                                                            </div>
                                                                            <input class="form-control" name="nominal_4" id="nominal_4" type="text" min="0" value="0">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="accordion-item">
                                                                <h2 class="accordion-header" id="panelsStayOpen-headingFive">
                                                                    <button class="accordion-button fw-medium collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseFive" aria-expanded="false" aria-controls="panelsStayOpen-collapseFive">
                                                                        Entertainment Detail #5
                                                                    </button>
                                                                </h2>
                                                                <div id="panelsStayOpen-collapseFive" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingFive">
                                                                    <div class="accordion-body">
                                                                        <div class="mb-2">
                                                                            <label class="form-label">Entertainment Type</label>
                                                                            <select name="enter_type_5" id="enter_type_5" class="form-select">
                                                                                <option value="">-</option>
                                                                                <option value="meal_cost">Meal Cost</option>
                                                                                <option value="accommodation">Accommodation</option>
                                                                                <option value="gift">Gift</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label class="form-label">Entertainment Fee Detail</label>
                                                                            <textarea name="enter_fee_5" id="enter_fee_5" class="form-control"></textarea>
                                                                        </div>
                                                                        <div class="input-group">
                                                                            <div class="input-group-append">
                                                                                <span class="input-group-text">Rp</span>
                                                                            </div>
                                                                            <input class="form-control" name="nominal_5" id="nominal_5" type="text" min="0" value="0">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-md d-md-flex justify-content-end text-center">
                                    <input type="hidden" name="repeat_days_selected" id="repeatDaysSelected">
                                    <a href="{{ route('schedules') }}" type="button" class="btn btn-danger rounded-pill shadow px-4 me-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary rounded-pill shadow px-4">Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<!-- Tambahkan script JavaScript untuk mengumpulkan nilai repeat_days[] -->
@push('scripts')
<script>
    function toggleDivs() {
        // ca_type ca_nbt ca_e
        var ca_type = document.getElementById("ca_type");
        var ca_nbt = document.getElementById("ca_nbt");
        var ca_e = document.getElementById("ca_e");
        
        if (ca_type.value === "ca_nbt") {
            ca_nbt.style.display = "block";
            ca_e.style.display = "none";
        } else if (ca_type.value === "ca_e"){
            ca_nbt.style.display = "none";
            ca_e.style.display = "block";
        } else{
            ca_nbt.style.display = "none";
            ca_e.style.display = "none";
        }
    }
    function toggleOthers() {
        // ca_type ca_nbt ca_e
        var locationFilter = document.getElementById("locationFilter");
        var others_location = document.getElementById("others_location");
        
        if (locationFilter.value === "Others") {
            others_location.style.display = "block";
        } else{
            others_location.style.display = "none";
            others_location.value = "";
        }
    }
      
    function validateInput(input) {
        //input.value = input.value.replace(/[^0-9,]/g, '');
        input.value = input.value.replace(/[^0-9]/g, '');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const totalDaysInput = document.getElementById('totaldays');
        const perdiemInput = document.getElementById('perdiem');
        const allowanceInput = document.getElementById('allowance');
        const othersLocationInput = document.getElementById('others_location');

        function calculateTotalDays() {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            if (startDate && endDate && !isNaN(startDate) && !isNaN(endDate)) {
                const timeDiff = endDate - startDate;
                const daysDiff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
                const totalDays = daysDiff > 0 ? daysDiff+1 : 0+1;
                totalDaysInput.value = totalDays;

                const perdiem = parseFloat(perdiemInput.value) || 0;
                let allowance = totalDays * perdiem;

                if (othersLocationInput.value.trim() !== '') {
                    allowance *= 1; // allowance * 50%
                }else{
                    allowance *= 0.5;
                }

                allowanceInput.value = allowance;
            } else {
                totalDaysInput.value = 0;
                allowanceInput.value = 0;
            }
        }

        startDateInput.addEventListener('change', calculateTotalDays);
        endDateInput.addEventListener('change', calculateTotalDays);
        othersLocationInput.addEventListener('input', calculateTotalDays);
    });
</script>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: "bootstrap-5",
            
        });
    });
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-beta3/js/bootstrap.min.js"></script>
@endpush
