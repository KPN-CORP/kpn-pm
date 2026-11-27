@extends('pages.roles.app') <!-- Extend the main layout -->

@section('subcontent')
<div class="card">
  <div class="card-body">
    <div class="row">
      <div class="col-md-4">
        <div>
            <label  for="role_name">Select Permission Role</label>
            <select name="" id="roleName" onchange="getPermissionData(this.value)" class="form-select select2-searchable" data-placeholder="Select Permission Role">
              <option value="">Select</option>
              @foreach ($roles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
              @endforeach
            </select>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection