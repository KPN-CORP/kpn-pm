@extends('layouts_.vertical', ['page_title' => 'Guides'])

@section('css')
<style>
        .pop {
            transform: translateX(30%);
            opacity: 0;
            position: fixed;
            display: none;
        }
        .pop.show {
            transition: transform 0.2s ease, opacity 0.2s ease;
            transform: translateX(0);
            opacity: 1;
            position: relative;
            display: inline;
        }
        .pop.hide {
            transition: transform 0.2s ease, opacity 0.2s ease;
            transform: translateX(30%);
            opacity: 0;
            position: relative;
            display: inline;
        }
</style>
@endsection

@section('content')
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success mt-3">
                {{ session('success') }}
            </div>
        @endif
        <div class="row">
            <div class="col-lg">
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="card-widgets">
                            @can('addguide')
                                <button class="btn btn-primary btn-sm shadow me-1" data-bs-toggle="modal" data-bs-target="#add-guide-modal"><i class="ri-add-line col-md d-sm-none"></i> Add <span class="col-md d-none d-sm-inline">new Guideline</span></button>
                            @endcan
                        </div>
                        <h4 class="card-title mb-4">User Guide</h4>
                        @can('removeguide')
                        @if (!$dataUser->isEmpty() || !$dataAdmin->isEmpty())
                        <div class="row align-items-center text-end">
                            <div class="col">
                                <div class="mb-2">
                                    <div class="form-check form-check-inline">
                                        <input type="checkbox" class="form-check-input" id="deleteToggle">
                                        <label class="form-check-label" for="deleteToggle">Delete File</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        @endcan
                        <div class="row">
                            @if (!$datas->isEmpty())
                                @foreach ($dataUser as $user)
                                <div class="card mb-1 shadow-none border">
                                    <div class="p-2">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <div class="avatar-sm">
                                                    <span class="avatar-title bg-danger-subtle text-danger rounded">
                                                        PDF
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col ps-0">
                                                <a href="{{ asset('storage/' . $user->file_path) }}" class="text-muted fw-bold">{{ $user->name }}</a>
                                                <a href="javascript:void(0)" data-bs-id="{{ $user->id }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $user->description ? $user->description : 'no description.' }}" class="text-muted fs-16"><i class="ri-information-line"></i></a>
                                                <p class="mb-0">{{ number_format($user->file_size / 1048576, 2) }} MB</p>
                                            </div>
                                            <div class="col-auto">
                                                <!-- Button -->
                                                <a href="{{ asset('storage/' . $user->file_path) }}" class="btn btn-link fs-16 text-muted">
                                                    <i class="ri-download-line"></i>
                                                </a>
                                                <form id="delete-form-{{ $user->id }}" class="d-inline" action="{{ route('delete.guide', $user->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger fs-16 pop deleteBtn"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                @if (!auth()->user()->roles->isEmpty())  
                                @foreach ($dataAdmin as $admin)
                                <div class="card mb-1 shadow-none border">
                                    <div class="p-2">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <div class="avatar-sm">
                                                    <span class="avatar-title bg-danger-subtle text-danger rounded">
                                                        PDF
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col ps-0">
                                                <a href="{{ asset('storage/' . $admin->file_path) }}" class="text-muted fw-bold">{{ $admin->name.' ('.$admin->category.')' }}</a>
                                                <a href="javascript:void(0)" data-bs-id="{{ $admin->id }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="{{ $admin->description ? $admin->description : 'no description.' }}" class="text-muted fs-16"><i class="ri-information-line"></i></a>
                                                <p class="mb-0">{{ number_format($admin->file_size / 1048576, 2) }} MB</p>
                                            </div>
                                            <div class="col-auto">
                                                <!-- Button -->
                                                <a href="{{ asset('storage/' . $admin->file_path) }}" class="btn btn-link fs-16 text-muted">
                                                    <i class="ri-download-line"></i>
                                                </a>
                                                <form id="delete-form-{{ $admin->id }}" class="d-inline" action="{{ route('delete.guide', $admin->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger fs-16 pop deleteBtn"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                @endif
                            @else
                            <p>No data available.</p>
                            @endif
                        </div>
                        <div class="row">
                        </div>
                        
                    </div>
                    <!-- end card body-->
                </div>
                <!-- end card -->
            </div>
        </div>
        
    </div>
    <!-- file preview template -->
    <div class="d-none" id="uploadPreviewTemplate">
        <div class="card mt-1 mb-0 shadow-none border">
            <div class="p-2">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <img data-dz-thumbnail src="#" class="avatar-sm rounded bg-light" alt="">
                    </div>
                    <div class="col ps-0">
                        <a href="javascript:void(0);" class="text-muted fw-bold" data-dz-name></a>
                        <p class="mb-0" data-dz-size></p>
                    </div>
                    <div class="col-auto">
                        <!-- Button -->
                        <a href="" class="btn btn-link btn-lg text-danger" data-dz-remove>
                            <i class="ri-close-line"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="add-guide-modal" class="modal fade" role="dialog" data-bs-backdrop="static"  aria-labelledby="guide-modalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="guide-modalLabel">New Guideline</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                        <form id="guide-form" action="{{ route('upload.guide') }}" method="post" enctype="multipart/form-data">
                         @csrf
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label class="form-label" for="fileName">Name</label>
                                    <input type="text" id="fileName" name="fileName" class="form-control" placeholder="Enter guidline name..." required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label class="form-label" for="description">Description</label>
                                    <textarea class="form-control" name="description" id="description" placeholder="Enter guidline descriptions..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <div class="form-check form-check-inline">
                                        <input type="checkbox" class="form-check-input" id="category" name="category">
                                        <label class="form-check-label" for="category">The file only for Admin</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-2">
                                    <label class="form-label" for="files">Choose your file</label>
                                    <input class="form-control" type="file" name="files" id="files" required>
                                </div>
                            </div>
                        </div>
                    </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="button" id="submit" class="btn btn-primary"><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>Save Guideline</button>
                    </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
@endsection
@vite('resources/js/guide.js')
