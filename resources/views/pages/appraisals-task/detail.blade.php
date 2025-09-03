@extends('layouts_.vertical', ['page_title' => 'Appraisal'])

@section('css')
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="detail-employee">
            <div class="row">
                <div class="col-12 fs-14">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md">
                                    <div class="row mb-1">
                                        <div class="col-lg-4 col-md-5">
                                            <span class="text-muted">Employee ID</span>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->first()->employee->employee_id }}
                                        </div>
                                    </div>
                                    <div class="row mb-1">
                                        <div class="col-lg-4 col-md-5">
                                            <span class="text-muted">Employee Name</span>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->first()->employee->fullname }}
                                        </div>
                                    </div>
                                    <div class="row mb-1">
                                        <div class="col-lg-4 col-md-5">
                                            <span class="text-muted">Job Level</span>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->first()->employee->job_level }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="row mb-1">
                                        <div class="col-lg-4 col-md-5">
                                            <span class="text-muted">Business Unit</span>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->first()->employee->group_company }}
                                        </div>
                                    </div>
                                    <div class="row mb-1">
                                        <div class="col-lg-4 col-md-5">
                                            <span class="text-muted">Division</span>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->first()->employee->unit }}
                                        </div>
                                    </div>
                                    <div class="row mb-1">
                                        <div class="col-lg-4 col-md-5">
                                            <span class="text-muted">Designation</span>
                                        </div>
                                        <div class="col">
                                            : {{ $datas->first()->employee->designation_name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- @if ($row->request->employee->group_company == 'Cement') --}}
        <div class="card-body m-0 py-2">
            @php
                // Contoh data bulanan
                $achievements = [
                    ["month" => "January", "value" => "B"],
                    ["month" => "February", "value" => "B"],
                    ["month" => "March", "value" => "A"],
                    ["month" => "April", "value" => "A"],
                    ["month" => "May", "value" => "B"],
                    ["month" => "June", "value" => "B"],
                    ["month" => "July", "value" => "C"],
                    ["month" => "August", "value" => "C"],
                    ["month" => "September", "value" => "B"],
                    ["month" => "October", "value" => "A"],
                    ["month" => "November", "value" => "B"],
                    ["month" => "December", "value" => "A"],
                ];
            @endphp

            <div class="rounded mb-2 p-3 bg-white text-primary align-items-center">
                <div class="row mb-2">
                    <span class="fs-16 mx-1">
                        Achievements
                    </span>      
                </div>                         
                <div class="row">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 text-center align-middle">
                            <thead class="bg-primary-subtle">
                                <tr>
                                    @foreach ($achievements as $item)
                                        <th>{{ substr($item['month'], 0, 3) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr>
                                    @foreach ($achievements as $item)
                                        <td>{{ $item['value'] }}</td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        {{-- @endif --}}
        <div class="row">
            <div class="col">
                <div class="card">
                    @if($datas->first()->appraisal?->file && $formData['formGroupName'] != "Appraisal Form 360")
                    <div class="card-body m-0 py-3">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="attachment" class="form-label">Supporting documents :</label>
                                <div class="d-flex align-items-center gap-1">
                                    <a href="{{ asset($datas->first()->appraisal->file) }}" target="_blank" class="badge rounded-pill text-bg-warning px-2 py-1" style="font-size: 0.75rem">
                                        attachment <i class="ri-file-text-line"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <div class="mb-2 text-primary fw-semibold fs-16 {{ $formData['kpiScore'] ? '' : 'd-none'}}">
                                    Total Score : {{ round($formData['totalScore'], 2) }}
                                </div>
                            </div>
                        </div>
                        @forelse ($appraisalData['formData'] as $indexItem => $item)
                        <div class="row">
                            <button class="btn rounded mb-2 py-2 bg-secondary-subtle bg-opacity-10 text-primary align-items-center d-flex justify-content-between" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $indexItem }}" aria-expanded="false" aria-controls="collapse-{{ $indexItem }}">
                                <span class="fs-16 ms-1">
                                    {{ $item['formName'] }} 
                                    | Score : {{ $item['formName'] === 'KPI' ? $appraisalData['kpiScore'] : ($item['formName'] === 'Culture' ? $appraisalData['cultureScore'] : $appraisalData['leadershipScore']) }}
                                </span>  
                                <span>
                                    <p class="d-none d-md-inline me-1">Details</p><i class="ri-arrow-down-s-line"></i>
                                </span>                               
                            </button>
                            @if ($item['formName'] == 'Leadership')
                            <div class="collapse" id="collapse-{{ $indexItem }}">
                                <div class="card card-body mb-3">
                                    @forelse($formData['formData'] as $form)
                                        @if($form['formName'] === 'Leadership')
                                            @foreach($form as $key => $item)
                                                @if(is_numeric($key))
                                                <div class="{{ $loop->last ? '':'border-bottom' }} mb-3">
                                                    @if(isset($item['title']))
                                                        <h5 class="mb-3"><u>{!! $item['title'] !!}</u></h5>
                                                    @endif
                                                    @foreach($item as $subKey => $subItem)
                                                        @if(is_array($subItem))
                                                        <ul class="ps-3">
                                                            <li>
                                                                <div>
                                                                    @if(isset($subItem['formItem']))
                                                                        <p class="mb-1">{!! $subItem['formItem'] !!}</p>
                                                                    @endif
                                                                    @if(isset($subItem['score']))
                                                                        <p><strong>Score:</strong> {{ $subItem['score'] }}</p>
                                                                    @endif
                                                                </div>
                                                            </li>
                                                        </ul>
                                                        @endif
                                                    @endforeach
                                                </div>
                                                @endif
                                            @endforeach
                                        @endif
                                    @empty
                                        <p>No Data</p>
                                    @endforelse
                                </div>
                            </div>
                            @elseif($item['formName'] == 'Culture')
                            <div class="collapse" id="collapse-{{ $indexItem }}">
                                <div class="card card-body mb-3">
                                    @forelse($formData['formData'] as $form)
                                        @if($form['formName'] === 'Culture')
                                            @foreach($form as $key => $item)
                                                @if(is_numeric($key))
                                                <div class="{{ $loop->last ? '':'border-bottom' }} mb-3">
                                                    @if(isset($item['title']))
                                                        <h5 class="mb-3"><u>{!! $item['title'] !!}</u></h5>
                                                    @endif
                                                    @foreach($item as $subKey => $subItem)
                                                        @if(is_array($subItem))
                                                        <ul class="ps-3">
                                                            <li>
                                                                <div>
                                                                    @if(isset($subItem['formItem']))
                                                                        <p class="mb-1">{!! $subItem['formItem'] !!}</p>
                                                                    @endif
                                                                    @if(isset($subItem['score']))
                                                                        <p><strong>Score:</strong> {{ $subItem['score'] }}</p>
                                                                    @endif
                                                                </div>
                                                            </li>
                                                        </ul>
                                                        @endif
                                                    @endforeach
                                                </div>
                                                @endif
                                            @endforeach
                                        @endif
                                    @empty
                                        <p>No Data</p>
                                    @endforelse
                                </div>
                            </div>
                            @else 
                            <div class="collapse" id="collapse-{{ $indexItem }}">
                                <div class="card card-body mb-3 py-0">
                                    @forelse ($formData['formData'] as $form)
                                    @if ($form['formName'] === 'KPI')
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>KPI</th>
                                                    <th>{{ __('Type') }}</th>
                                                    <th>{{ __('Weightage') }}</th>
                                                    <th>Target</th>
                                                    <th>{{ __('Actual') }}</th>
                                                    <th>{{ __('Achievement') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ($form as $key => $data)
                                                @if (is_array($data))
                                                <tr>
                                                    <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                        <p class="mt-1 mb-0">{{ $key + 1 }}</p>
                                                    </td>
                                                    <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                        <p class="mt-1 mb-0 text-muted" @style('white-space: pre-line')>{{ $data['kpi'] }}</p>
                                                    </td>
                                                    <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                        <p class="mt-1 mb-0 text-muted">{{ $data['type'] }}</p>
                                                    </td>
                                                    <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                        <p class="mt-1 mb-0 text-muted">{{ $data['weightage'] }}%</p>
                                                    </td>
                                                    <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                        <p class="mt-1 mb-0 text-muted">{{ $data['target'] }} {{ is_null($data['custom_uom']) ? $data['uom']: $data['custom_uom'] }}</p>
                                                    </td>
                                                    <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                        <p class="mt-1 mb-0 text-muted">{{ $data['achievement'] }} {{ is_null($data['custom_uom']) ? $data['uom']: $data['custom_uom'] }}</p>
                                                    </td>
                                                    <td class="{{ $loop->last ? 'border-0' : 'border-bottom-2 border-dashed' }}">
                                                        <p class="mt-1 mb-0 text-muted">{{ isset($data['percentage']) ? round($data['percentage']) . '%' : '0%' }}</p>
                                                    </td>
                                                </tr>
                                                @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endif
                                    @empty
                                    <p>No form data available.</p>
                                    @endforelse
                                </div>
                            </div>
                            @endif
                        </div>
                        @empty
                            No Data
                        @endforelse
                        
                    </div> <!-- end card-body-->
                </div>
            </div>
        </div>
    </div>
@endsection
