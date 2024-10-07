@extends('layouts_.vertical', ['page_title' => 'Roles'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <div class="row">
            <div class="col-auto">
                <div class="mb-2">
                    <a class="btn btn-outline-primary btn-sm {{ $active=='create' ? 'active':'' }}" href="{{ route('roles.create') }}">Create Role</a>
                </div>
            </div>
            <div class="col-auto">
                <div class="mb-2">
                    <a class="btn btn-outline-primary btn-sm {{ $active=='manage' ? 'active':'' }}" href="{{ route('roles.manage') }}">Manage Role</a>
                </div>
            </div>
            <div class="col-auto">
                <div class="mb-2">
                    <a class="btn btn-outline-primary btn-sm {{ $active=='assign' ? 'active':'' }}" href="{{ route('roles.assign') }}">Assign Users</a>
                </div>
            </div>
        </div>
        <!-- Content Row -->
        @if(session('success'))
            <div class="alert alert-success mt-3">
                {{ session('success') }}
            </div>
        @endif
        @yield('subcontent')
        <div id="subContent"></div>
    </div>
@endsection