{{--
    Isi modal detail Goal (On Behalf) untuk SATU karyawan.

    Dulu di-include untuk setiap baris di pages/onbehalfs/goal.blade.php,
    sehingga halaman On Behalf > Goals mencapai 33 MB HTML. Sekarang dimuat
    lewat AJAX (Admin\OnBehalfController::goalDetail).

    Variabel yang diharapkan:
      $row  ApprovalRequest dengan relasi employee + goal
--}}
<div class="container-fluid py-3">
    <form action="" method="post">
        <div class="d-sm-flex align-items-center mb-4">
            <h4 class="me-1">{{ $row->employee->fullname }}</h4> <span class="h4 text-muted">{{ $row->employee->employee_id }}</span>
        </div>
        <div class="container-card">
            @php
                $rawFormData = $row->goal->form_data ?? null;
                $formData = is_string($rawFormData) ? json_decode($rawFormData, true) : ($rawFormData ?? []);
            @endphp
            @if ($formData)
            @foreach ($formData as $index => $data)
                <div class="card col-md-12 mb-2 border border-primary">
                    <div class="card-header bg-white pb-0">
                        <h4>{{ __('Goal') }} {{ $index + 1 }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-5 mb-3">
                                <div class="form-group">
                                    <label class="form-label" for="kpi">KPI</label>
                                    <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['kpi'] }}</p>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="form-group">
                                    <label class="form-label" for="target">{{ __('Target In UoM') }} {{ is_null($data['custom_uom']) ? $data['uom']: $data['custom_uom'] }}</label>
                                    <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['target'] }}</p>
                                </div>
                            </div>
                            <div class="col-lg-2 mb-3">
                                <div class="form-group">
                                    <label class="form-label" for="weightage">{{ __('Weightage') }}</label>
                                    <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['weightage'] }}%</p>
                                </div>
                            </div>
                            <div class="col-lg-2 mb-3">
                                <div class="form-group">
                                    <label class="form-label" for="type">{{ __('Type') }}</label>
                                    <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['type'] }}</p>
                                </div>
                            </div>
                        </div>
                        <hr class="mt-0 mb-2">
                        <div class="row">
                            <div class="col-md mb-2">
                                <div class="form-group">
                                    <label class="form-label" for="description">Description</label>
                                    <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['description'] ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            @else
                <p>No form data available.</p>
            @endif                
        </div>
    </form>
</div>
