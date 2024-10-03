@if ($roleId)
<div class="card">
  <div class="card-body">

    <form id="assignForm" action="{{ route('assign.user', [], true) }}" method="POST">
      @csrf
      <input type="hidden" name="role_id" value="{{ $roleId }}">
      <div class="row mb-3">
        <div class="col">
          <label class="form-label" for="granted">Granted To:</label>
          <select class="form-control select2" id="granted" name="users_id[]" multiple="multiple">
            @foreach ($users as $user)
              @php
                $isSelected = false;
                foreach ($roles as $role) {
                    if ($role->model_id == $user->id) {
                        $isSelected = true;
                        break;
                    }
                }
              @endphp
              <option value="{{ $user->id }}" {{ $isSelected ? 'selected' : '' }}>{{ $user->fullname.' ('.$user->employee_id.') - '.$user->designation }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-auto text-end">
          <button type="submit" id="submitButton" class="btn btn-primary px-3"><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>Save</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endif
