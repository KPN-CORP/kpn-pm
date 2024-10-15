@extends('layouts_.vertical', ['page_title' => 'Ratings'])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible border-0 fade show" role="alert">
                <button type="button" class="btn-close btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <strong>Success - </strong> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <div class="row">
            <div class="col-10">
            <div class="col-md-auto">
              <div class="mb-3">
                <div class="input-group" style="width: 30%;">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-dark-subtle"><i class="ri-search-line"></i></span>
                  </div>
                  <input type="text" name="customsearch" id="customsearch" class="form-control  border-dark-subtle border-left-0" placeholder="search.." aria-label="search" aria-describedby="search">
                </div>
              </div>
            </div>
            </div>
            <div class="col-2" style="text-align:right">
                <a href="{{ route('pages.rating-admin.create') }}" class="btn btn-primary shadow">Set Ratings</a>
            </div>
        </div>
        
        <div class="row">
          <div class="col-md-12">
            <div class="card shadow mb-4">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="card-title"></h3>
                    
                </div>
                  <div class="table-responsive">
                      <table class="table table-hover dt-responsive nowrap" id="scheduleTable" width="100%" cellspacing="0">
                          <thead class="thead-light">
                              <tr class="text-center">
                                  <th>No</th>
                                  <th>Rating Group Name</th>
                                  <th>Detail</th>
                                  <th>Created By</th>
                                  <th>Action</th>
                              </tr>
                          </thead>
                          <tbody>

                            @foreach($ratings as $rating)
                              <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td style="width: 20%; word-wrap: break-word; white-space: normal;">{!! $rating->rating_group_name !!}</td>
                                    <td style="width: 60%; word-wrap: break-word; white-space: normal; overflow-wrap: break-word; word-break: break-word;">
                                        {!! $rating->detail !!}
                                    </td>
                                    <td>{{ $rating->created_by_name }}</td>
                                    <td class="text-center">
                                        @if($userId == $rating->userId)
                                        <a href="{{ route('pages.rating-admin.update', Crypt::encryptString($rating->id_rating_group)) }}" class="btn btn-sm btn-outline-warning" title="Edit" ><i class="ri-edit-box-line"></i></a>
                                        <a class="btn btn-sm btn-danger" title="Delete" onclick="handleDeleteMasterRating(this)" data-id="{{ $rating->id_rating_group }}"><i class="ri-delete-bin-line"></i></a>
                                        @endif
                                    </td>
                              </tr>
                              @endforeach
                          </tbody>
                      </table>
                  </div>
              </div>
            </div>
          </div>
      </div>
    </div>
@endsection

@push('scripts')
<script>
    function handleDeleteMasterRating(element) {
        var id = element.getAttribute('data-id');
        var deleteUrl = "{{ route('rating-admin-destroy', ':id') }}";
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
</script>
@endpush