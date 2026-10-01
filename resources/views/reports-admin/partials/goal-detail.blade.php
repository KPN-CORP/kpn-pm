{{--
    Isi modal detail Goal untuk SATU karyawan.

    Dulu blok ini di-render inline untuk setiap baris report: pada report Goal
    periode 2026 itu 1.422 modal = 38 MB HTML — praktis seluruh halaman —
    padahal user paling banyak membuka satu. Sekarang dimuat lewat AJAX
    (lihat Admin\ReportController::goalDetail).

    Variabel yang diharapkan:
      $employee  model Employee
      $formData  goal->form_data yang sudah di-decode (array)
--}}
<div class="container-fluid py-3">
    <div class="row">
        <div class="col">
            <div class="d-sm-flex align-items-center mb-2">
                <h4 class="me-1">{{ $employee->fullname }}</h4><span class="text-muted h4">{{ $employee->employee_id }}</span>
            </div>
        </div>
    </div>
    <!-- Content Row -->
    <div class="container-card">
        @if ($formData)
            @foreach ($formData as $index => $data)
                <div class="card mb-2 border border-primary">
                    <div class="card-header pb-0 border-0 bg-white">
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
</div>
