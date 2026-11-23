@extends('pages.roles.app') <!-- Extend the main layout -->

@section('subcontent')
<div class="card">
  <div class="card-body">
    <div class="row">
      <div class="col-md-4">
        <div>
            <label class="form-label" for="report_type">Permission Group:</label>
            <select class="form-select select2-searchable" name="permission_name" data-placeholder="Select Permission Group" onchange="getAssignmentData(this.value)">
            <option value="">Select Permission Group</option>
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