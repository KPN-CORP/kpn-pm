@extends('layouts_.vertical', ['page_title' => 'Ratings'])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body" @style('overflow-y: auto;')>
                        <div class="container-fluid">
                            <form id="scheduleForm" method="post" action="{{ route('admratings.store') }}">@csrf
                                <div class="row my-2">
                                    <div class="col-md-12">
                                        <div class="mb-2">
                                            <label class="form-label" for="name">Rating Group Name</label>
                                            <input type="text" class="form-control bg-light" placeholder="Enter name.." id="rating_group_name" name="rating_group_name" value="{{ $rating->rating_group_name }}" readonly>
                                            <input type="hidden" class="form-control" placeholder="Enter name.." id="id_rating_group" name="id_rating_group" value="{{ $rating->id_rating_group }}" readonly>
                                            <input type="hidden" class="form-control" placeholder="Enter name.." id="id_rating" name="id_rating" value="0" readonly>
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
                                            <select name="value_rating" id="value_rating" class="form-select" required>
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
                                        <input type="number" class="form-control bg-light" id="min_range" name="min_range" readonly> 
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label" for="name">&nbsp;&nbsp;</label>
                                        <input type="number" class="form-control bg-light" id="max_range" name="max_range" readonly>
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
                                    @foreach($ratings as $rating)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $rating->parameter }}</td>
                                        <td>{{ $rating->value }}</td>
                                        <td>{{ $rating->desc_idn }}</td>
                                        <td>{{ $rating->desc_eng }}</td>
                                        <td>@if($rating->add_range <> 0) 
                                                {{ $rating->min_range }} - {{ $rating->max_range }}
                                            @else - @endif
                                        </td>
                                        <td class="text-center">
                                            {{-- <a href="{{ route('pages.rating-admin.update', $rating->id_rating_group) }}" class="btn btn-sm btn-outline-warning" title="Edit" ><i class="ri-edit-box-line"></i></a> --}}
                                            <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning" title="Edit" onclick="editRating(this)" data-id="{{ $rating->id }}" ><i class="ri-edit-box-line"></i></a>
                                            <a class="btn btn-sm btn-danger" title="Delete" onclick="handleDeleteDetailRating(this)" data-id="{{ $rating->id }}"><i class="ri-delete-bin-line"></i></a>
                                        </td>
                                    </tr>
                                    @endforeach
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

    function handleDeleteDetailRating(element) {
        var id = element.getAttribute('data-id');
        var deleteUrl = "{{ route('detail-rating-admin-destroy', ':id') }}";
        deleteUrl = deleteUrl.replace(':id', id);
        
        Swal.fire({
            title: 'Are you sure?',
            text: "This rating will deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Jika dikonfirmasi, buat form dan submit ke server
                var form = document.createElement('form');
                form.action = deleteUrl;
                form.method = 'POST';
                form.innerHTML = `
                    @csrf
                    @method('DELETE')
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function editRating(element) {
        var id = element.getAttribute('data-id');

        fetch('/rating-admin/' + id + '/edit')
            .then(response => response.json())
            .then(data => {
                document.getElementById('id_rating').value = data.id;
                document.getElementById('rating_group_name').value = data.rating_group_name;
                document.getElementById('id_rating_group').value = data.id_rating_group;
                document.getElementById('parameter').value = data.parameter;
                document.getElementById('value_rating').value = data.value;
                document.getElementById('description_idn').value = data.description_idn;
                document.getElementById('description_eng').value = data.description_eng;
                
                if(data.add_range == 1) {
                    document.getElementById('add_range').checked = true;
                    document.getElementById('min_range').value = data.min_range;
                    document.getElementById('max_range').value = data.max_range;
                    document.getElementById('min_range').removeAttribute('readonly');
                    document.getElementById('max_range').removeAttribute('readonly');
                    document.getElementById('min_range').classList.remove('bg-light');
                    document.getElementById('max_range').classList.remove('bg-light');
                } else {
                    document.getElementById('add_range').checked = false;
                    document.getElementById('min_range').value = '';
                    document.getElementById('max_range').value = '';
                    document.getElementById('min_range').setAttribute('readonly', true);
                    document.getElementById('max_range').setAttribute('readonly', true);
                    document.getElementById('min_range').classList.add('bg-light');
                    document.getElementById('max_range').classList.add('bg-light');
                }
            })
            .catch(error => console.error('Error:', error));
    }
</script>
@endpush