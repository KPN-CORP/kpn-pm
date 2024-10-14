@extends('layouts_.vertical', ['page_title' => 'Ratings'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    {{-- <div class="card-header d-flex bg-white justify-content-between">
                        <h4 class="modal-title" id="viewFormEmployeeLabel">{{ $sublink }}</h4>
                        <a href="{{ route('admratings') }}" type="button" class="btn btn-close"></a>
                    </div> --}}
                    <div class="card-body" @style('overflow-y: auto;')>
                        <div class="container-fluid">
                            <form id="scheduleForm" method="post" action="{{ route('admratings.store') }}">@csrf
                                <div class="row my-2">
                                    <div class="col-md-12">
                                        <div class="mb-2">
                                            <label class="form-label" for="name">Rating Group Name</label>
                                            <input type="text" class="form-control" placeholder="Enter name.." id="rating_group_name" name="rating_group_name" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row my-2">
                                    <div class="col-md-12">
                                        <div class="mb-2">
                                            <label class="form-label" for="name">Parameter</label>
                                            <input type="text" class="form-control" placeholder="Enter Parameter.." id="parameter" name="parameter" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row my-2">
                                    <div class="col-md-12">
                                        <div class="mb-2">
                                            <label class="form-label" for="name">Value</label>
                                            <select name="value_rating" class="form-select" required>
                                                @for($value = 1; $value <= 10; $value++)
                                                    <option value="{{ $value }}">{{ $value }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row my-2">
                                    <div class="col-md-12">
                                        <div class="mb-2">
                                            <label class="form-label" for="name">Description (IDN)</label>
                                            <textarea class="form-control" name="description_idn" id="description_idn" cols="15" rows="5" placeholder="Enter Description.."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row my-2">
                                    <div class="col-md-12">
                                        <div class="mb-2">
                                            <label class="form-label" for="name">Description (ENG)</label>
                                            <textarea class="form-control" name="description_eng" id="description_eng" cols="15" rows="5" placeholder="Enter Description.."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row my-2">
                                    <div class="col-md-12">
                                        <div class="mb-2">
                                            <input type="checkbox" class="custom-control-input" id="add_range" name="add_range" value="1">
                                            <label class="form-label" class="custom-control-label" for="add_range">Add Range</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label" for="name">Range</label>
                                        <input type="text" class="form-control bg-light" id="min_range" name="min_range" readonly> 
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label" for="name">&nbsp;&nbsp;</label>
                                        <input type="text" class="form-control bg-light" id="max_range" name="max_range" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md d-md-flex justify-content-end text-center">
                                        <button type="submit" class="btn btn-primary shadow px-4">Submit Rating</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex bg-white justify-content-between">
                        <h4 class="modal-title" id="viewFormEmployeeLabel">Rating Group Name</h4>
                        {{-- <a href="{{ route('admratings') }}" type="button" class="btn btn-close"></a> --}}
                    </div>
                    <div class="card-body" @style('overflow-y: auto;')>
                        <div class="container-fluid">
                            <table class="table table-hover dt-responsive nowrap" width="100%" cellspacing="0">
                                <thead class="thead-light">
                                    <tr class="text-center">
                                        <th>No</th>
                                        <th>Parameter</th>
                                        <th>Value</th>
                                        <th>Description (IDN)</th>
                                        <th>Description (ENG)</th>
                                        <th>Range</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
    
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="pb-2 row">
                        <div class="col-md d-md-flex justify-content-end text-center">
                            <input type="hidden" name="repeat_days_selected" id="repeatDaysSelected">
                            <a href="{{ route('admratings') }}" type="button" class="btn btn-outline-secondary shadow px-4 me-2">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
<script>
    document.getElementById('add_range').addEventListener('change', function() {
    var minRange = document.getElementById('min_range');
    var maxRange = document.getElementById('max_range');
    
    if (this.checked) {
        minRange.removeAttribute('readonly');
        maxRange.removeAttribute('readonly');
        minRange.classList.remove('bg-light');
        maxRange.classList.remove('bg-light');
        minRange.value = 0;
        maxRange.value = 0;
    } else {
        minRange.setAttribute('readonly', true);
        maxRange.setAttribute('readonly', true);
        minRange.classList.add('bg-light');
        maxRange.classList.add('bg-light');
        minRange.value = "";
        maxRange.value = "";
    }
});
</script>
@endpush