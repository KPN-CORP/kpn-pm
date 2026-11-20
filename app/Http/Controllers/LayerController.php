<?php

namespace App\Http\Controllers;

use App\Exports\InvalidApprovalAppraisalImport;
use App\Imports\ApprovalLayerAppraisalImport;
use Illuminate\Http\Request;
use App\Models\ApprovalLayer; 
use App\Models\ApprovalRequest;
use App\Models\Approval;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ApprovalLayerImport;
use App\Models\Appraisal;
use App\Models\AppraisalContributor;
use App\Models\ApprovalLayerAppraisal;
use App\Models\ApprovalLayerAppraisalBackup;
use Illuminate\Support\Facades\Log;
use App\Models\ApprovalLayerBackup;
use App\Models\Calibration;
use App\Models\EmployeeAppraisal;
use App\Models\Goal;
use App\Services\AppService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class LayerController extends Controller
{
    protected $category;
    protected $user;
    protected $appService;

    public function __construct(AppService $appService)
    {
        $this->appService = $appService;
        $this->user = Auth::user()->employee_id;
        $this->category = 'Goals';
    }

    function layer(Request $request) {
        // Baris tabel dimuat lewat layerData (server-side DataTables). Dulu
        // semua baris (±6.400) di-render ke HTML sehingga halaman ±13 MB.
        // Halaman pertama ikut dikirim di HTML supaya tabel langsung terisi.

        // The Employee model has no SoftDeletes trait, so deleted employees must be
        // excluded explicitly or they show up as selectable superiors.
        // Dipakai dropdown superior di modal Edit (layer.js).
        $employees = Employee::select('employee_id', 'fullname')
        // ->whereNotIn('job_level', ['2A', '2B', '2C', '2D', '3A', '3B','4A'])
        ->whereNull('deleted_at')
        ->orderBy('fullname', 'asc')
        ->get();

        return view('pages.layers.layer', [
            'parentLink' => 'Settings',
            'link' => 'Layers',
            'employees' => $employees,
            'initialData' => $this->dataTableInitialPage($request, self::LAYER_COLUMNS, fn ($sub) => $this->layerData($sub), [[0, 'asc']]),
        ]);
    }

    /** Kolom tabel Layer (Goals), sama urutannya dengan layer.js. */
    private const LAYER_COLUMNS = [
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'searchable' => 'false', 'orderable' => 'true'],
        ['data' => 'employee_id', 'name' => 'employee_id', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'fullname', 'name' => 'fullname', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'contribution_level_code', 'name' => 'contribution_level_code', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'office_area', 'name' => 'office_area', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'group_company', 'name' => 'group_company', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'superior', 'name' => 'superior', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'action', 'name' => 'action', 'searchable' => 'false', 'orderable' => 'false'],
    ];

    /**
     * Daftar layer (Goals) per karyawan sesuai restriction role user login.
     */
    private function layerQuery()
    {
        $roles = Auth::user()->roles;

        $restrictionData = [];
        if(!is_null($roles)){
            $restrictionData = json_decode($roles->first()->restriction, true);
        }

        $criteria = [
            'work_area_code' => $restrictionData['work_area_code'] ?? [],
            'group_company' => $restrictionData['group_company'] ?? [],
            'contribution_level_code' => $restrictionData['contribution_level_code'] ?? [],
        ];

        foreach ($criteria as $key => $value) {
            if (!is_array($value)) {
                $criteria[$key] = (array) $value;
            }
        }

        // Satu baris per karyawan yang punya layer. Agregasi superior
        // (GROUP_CONCAT) TIDAK dilakukan di sini: dulu seluruh ±18 rb baris
        // approval_layers di-group (±0,5 dtk) untuk setiap count & halaman.
        // Superior hanya dihitung untuk baris yang tampil (layerSuperiorsFor).
        // Left join dipertahankan: layer yang karyawannya tidak ada di
        // employees tetap muncul (nama kosong), sama seperti sebelumnya.
        return DB::query()
        ->fromSub(DB::table('approval_layers')->select('employee_id')->distinct(), 'al')
        ->select('al.employee_id', 'emp.fullname', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area')
        ->leftJoin('employees as emp', 'emp.employee_id', '=', 'al.employee_id')
        ->whereNull('emp.deleted_at') // Add condition to check if deleted_at is null
        ->when(!empty($criteria), function ($query) use ($criteria) {
            $query->where(function ($query) use ($criteria) {
            foreach ($criteria as $key => $values) {
                if (!empty($values)) {
                $query->whereIn("emp.$key", $values);
                }
            }
            });
        });
    }

    /**
     * Endpoint server-side DataTables untuk tabel Layer (Goals).
     * Pencarian bebas mencakup semua kolom yang tampil, termasuk nama
     * superior (L1..Ln), per kata seperti smart search client-side dulu.
     */
    public function layerData(Request $request)
    {
        // Urutan kolom Superior = teks nama superior per layer (dulu sort
        // client-side atas teks yang sama). Hanya dihitung saat diminta.
        $superiorSort = DB::raw("(SELECT GROUP_CONCAT(COALESCE(es.fullname, '') ORDER BY ls.layer ASC SEPARATOR '|')
            FROM approval_layers ls
            LEFT JOIN employees es ON es.employee_id = ls.approver_id AND es.deleted_at IS NULL
            WHERE ls.employee_id = al.employee_id)");

        $orderable = [
            0 => 'emp.fullname', // "#" dulu = urutan nama
            1 => 'al.employee_id',
            2 => 'emp.fullname',
            3 => 'emp.contribution_level_code',
            4 => 'emp.office_area',
            5 => 'emp.group_company',
            6 => $superiorSort,
        ];

        // Superior per karyawan hanya untuk baris yang tampil (maks. 25),
        // di-cache karena dipakai kolom Superior dan Actions.
        $superiors = [];
        $superiorOf = function ($row) use (&$superiors) {
            $id = (string) $row->employee_id;
            if (!array_key_exists($id, $superiors)) {
                $superiors[$id] = $this->layerSuperiorsFor([$id])[$id]
                    ?? (object) ['layers' => '', 'approver_ids' => '', 'approver_names' => ''];
            }
            return $superiors[$id];
        };

        return DataTables::query($this->layerQuery())
            ->addIndexColumn()
            ->filter(function ($q) use ($request) {
                $words = preg_split('/\s+/', trim((string) $request->input('search.value', '')), -1, PREG_SPLIT_NO_EMPTY);
                foreach ($words as $word) {
                    $like = '%' . addcslashes($word, '%_\\') . '%';
                    $q->where(function ($w) use ($like) {
                        foreach (['al.employee_id', 'emp.fullname', 'emp.contribution_level_code', 'emp.office_area', 'emp.group_company'] as $col) {
                            $w->orWhere($col, 'like', $like);
                        }
                        // nama superior aktif di salah satu layer
                        $w->orWhereExists(function ($s) use ($like) {
                            $s->selectRaw('1')
                                ->from('approval_layers as al2')
                                ->join('employees as e2', 'e2.employee_id', '=', 'al2.approver_id')
                                ->whereColumn('al2.employee_id', 'al.employee_id')
                                ->whereNull('e2.deleted_at')
                                ->where('e2.fullname', 'like', $like);
                        });
                    });
                }
            })
            ->order(function ($q) use ($request, $orderable) {
                $col = $orderable[(int) $request->input('order.0.column', 0)] ?? 'emp.fullname';
                $q->orderBy($col, $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc')
                    ->orderBy('al.employee_id');
            })
            ->addColumn('superior', function ($row) use ($superiorOf) {
                $sup    = $superiorOf($row);
                $layers = explode('|', (string) $sup->layers);
                $names  = explode('|', (string) $sup->approver_names);
                $lines  = [];
                foreach ($layers as $i => $layer) {
                    $name = $names[$i] ?? '';
                    $lines[] = e('L' . $layer) . ' : ' . ($name !== '' ? e($name) : 'No approver available');
                }

                return implode('<br>', $lines);
            })
            ->addColumn('action', function ($row) use ($superiorOf) {
                $sup = $superiorOf($row);

                return '<button type="button" class="btn btn-sm btn-outline-warning open-edit-modal mb-1"'
                    . ' data-bs-employee-id="' . e($row->employee_id) . '"'
                    . ' data-bs-fullname="' . e($row->fullname) . '"'
                    . ' data-bs-app="' . e($sup->approver_ids) . '"'
                    . ' data-bs-layer="' . e($sup->layers) . '"'
                    . ' data-bs-app-name="' . e($sup->approver_names) . '"'
                    . ' title="Edit"><i class="ri-edit-box-line"></i></button>'
                    . ' <button type="button" class="btn btn-sm btn-outline-secondary open-view-modal mb-1" title="History"'
                    . ' onclick="viewHistory(\'' . e($row->employee_id) . '\')"><i class="ri-history-line"></i></button>';
            })
            ->only(['DT_RowIndex', 'employee_id', 'fullname', 'contribution_level_code', 'office_area', 'group_company', 'superior', 'action'])
            ->rawColumns(['superior', 'action'])
            ->toJson();
    }

    /**
     * Layer (Goals) per karyawan: nomor layer, id & nama superior, urut
     * per layer, dipisah '|'.
     *
     * @return array<string, object>  employee_id => {layers, approver_ids, approver_names}
     */
    private function layerSuperiorsFor(array $employeeIds): array
    {
        if (empty($employeeIds)) {
            return [];
        }

        return DB::table('approval_layers as al')
            ->select('al.employee_id')
            ->selectRaw("GROUP_CONCAT(al.layer ORDER BY al.layer ASC SEPARATOR '|') AS layers")
            ->selectRaw("GROUP_CONCAT(al.approver_id ORDER BY al.layer ASC SEPARATOR '|') AS approver_ids")
            // COALESCE keeps the position of every layer in the list: GROUP_CONCAT drops
            // NULLs, so an approver that no longer resolves to an active employee would
            // otherwise shift the remaining names onto the wrong layer.
            ->selectRaw("GROUP_CONCAT(COALESCE(emp1.fullname, '') ORDER BY al.layer ASC SEPARATOR '|') AS approver_names")
            // Only active approvers are resolved; a soft deleted one is rendered as
            // "No approver available" instead of showing an inactive employee.
            ->leftJoin('employees as emp1', function ($join) {
                $join->on('emp1.employee_id', '=', 'al.approver_id')
                    ->whereNull('emp1.deleted_at');
            })
            ->whereIn('al.employee_id', $employeeIds)
            ->groupBy('al.employee_id')
            ->get()
            ->keyBy(fn ($r) => (string) $r->employee_id)
            ->all();
    }

    /**
     * Respons DataTables untuk tampilan awal tabel, dibuat lewat endpoint
     * yang sama agar hasilnya identik dengan AJAX. yajra membaca
     * app('request'), jadi request diganti sementara. null = gagal (tabel
     * tetap bisa memuat lewat AJAX).
     */
    private function dataTableInitialPage(Request $request, array $columns, callable $endpoint, array $order = []): ?array
    {
        $params = [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'columns' => $columns,
            'search' => ['value' => '', 'regex' => 'false'],
        ];
        foreach ($order as $i => [$column, $dir]) {
            $params['order'][$i] = ['column' => $column, 'dir' => $dir];
        }

        $sub = Request::create($request->url(), 'GET', $params);
        $sub->setUserResolver($request->getUserResolver());

        $previous = app('request');
        app()->instance('request', $sub);
        app()->forgetInstance('datatables.request');

        try {
            $json = $endpoint($sub)->getData(true);
            // Info debug yajra (SQL, input) tidak perlu ikut ke HTML.
            unset($json['queries'], $json['input']);

            return $json;
        } catch (\Throwable $e) {
            Log::warning('DataTables initial page failed: ' . $e->getMessage());
            return null;
        } finally {
            app()->instance('request', $previous);
            app()->forgetInstance('datatables.request');
        }
    }

    function updatelayer(Request $req) {
        $employeeId = $req->input('employee_id');
        $nikApps = $req->input('nik_app');
        $jumlahNikApp = count($nikApps);
        $userId = Auth::id();

        $approvalRequest = Goal::select('id')->where('employee_id', $employeeId)->first();

        $approvalLayersToDelete = ApprovalLayer::where('employee_id', $employeeId)->get();

        foreach ($approvalLayersToDelete as $layer) {
            ApprovalLayerBackup::create([
                'employee_id' => $layer->employee_id,
                'approver_id' => $layer->approver_id,
                'layer' => $layer->layer,
                'updated_by' => $layer->updated_by,
                'created_at' => $layer->created_at,
                'updated_at' => $layer->updated_at,
            ]);
        }

        ApprovalLayer::where('employee_id', $employeeId)->delete();

        //$cek="";
        $layer=1;
        for ($jml=0; $jml < $jumlahNikApp; $jml++) {
            $approverId = $nikApps[$jml];

            if($approverId<>''){
                ApprovalLayer::create([
                    'employee_id' => $employeeId,
                    'approver_id' => $approverId,
                    'layer' => $layer,
                    'updated_by' => $userId
                ]);    

                if($layer===1){
                    $cekApprovalRequest = ApprovalRequest::where('category', $this->category)
                                         ->where('employee_id', $employeeId)
                                         ->where('status', ['pending', 'sendback'])
                                         ->get();

                    if ($cekApprovalRequest->isNotEmpty()) {
                        $approvalRequestIds = $cekApprovalRequest->pluck('id');
                
                        DB::transaction(function() use ($employeeId, $approverId, $approvalRequestIds) {
                            ApprovalRequest::where('category', $this->category)
                                           ->where('employee_id', $employeeId)
                                           ->where('status', ['pending', 'sendback'])
                                           ->update([
                                               'current_approval_id' => $approverId,
                                               'updated_by' => null,
                                               'updated_at' => null
                                           ]);
                
                            Approval::whereIn('request_id', $approvalRequestIds)
                                    ->delete();
                        });
                    }
                }
            }
            $layer++;
        }

        Alert::success('Success');
        return redirect()->intended(route('layer', absolute: false));
    }

    public function importLayer(Request $request)
    {
        $request->validate([
            'excelFile' => 'required|mimes:xlsx,xls,csv'
        ]);
        
        // Muat file Excel ke dalam array
        $rows = Excel::toArray([], $request->file('excelFile'));
        $data = $rows[0]; // Ambil sheet pertama
        $employeeIds = [];

        // Mulai dari indeks 1 untuk mengabaikan header
        for ($i = 1; $i < count($data); $i++) {
            $employeeIds[] = $data[$i][0];
        }

        $employeeIds = array_unique($employeeIds);

        // Ambil employee_ids dari data
        //$employeeIds = array_unique(array_column($data, 'employee_id'));

        if (!empty($employeeIds)) {
            // Backup data sebelum menghapus
            $approvalLayersToDelete = ApprovalLayer::whereIn('employee_id', $employeeIds)->get();

            foreach ($approvalLayersToDelete as $layer) {
                ApprovalLayerBackup::create([
                    'employee_id' => $layer->employee_id,
                    'approver_id' => $layer->approver_id,
                    'layer' => $layer->layer,
                    'updated_by' => $layer->updated_by,
                    'created_at' => $layer->created_at,
                    'updated_at' => $layer->updated_at,
                ]);
            }
            // Hapus data lama
            ApprovalLayer::whereIn('employee_id', $employeeIds)->delete();
        }
        $userId = Auth::id();
        // Import data baru
        //Excel::import(new ApprovalLayerImport, $request->file('excelFile'));
        // Excel::import(new ApprovalLayerImport($userId), $request->file('excelFile'));

        // return back()->with('success', 'Data imported successfully.');
        $import = new ApprovalLayerImport($userId);
        Excel::import($import, $request->file('excelFile'));

        // Ambil ID karyawan yang memiliki layer lebih dari 6
        $invalidEmployees = $import->getInvalidEmployees();

        // Format pesan umpan balik
        $message = 'Data imported successfully.';
        if (!empty($invalidEmployees)) {
            $message .= '\nThe following employee IDs have layers greater than 6 and were not imported: \n' . implode(', ', $invalidEmployees);
        }

        return back()->with('success', $message);
    }

    public function show(Request $request)
    {
        $employeeId = $request->input('employee_id');
        
        $approvalLayers1 = DB::table('approval_layer_backups as al')
        ->select('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area', 'al.updated_by', 'al.updated_at', 'usr.name')
        ->selectRaw("GROUP_CONCAT(al.layer ORDER BY al.layer ASC SEPARATOR '|') AS layers")
        ->selectRaw("GROUP_CONCAT(al.approver_id ORDER BY al.layer ASC SEPARATOR '|') AS approver_ids")
        ->selectRaw("GROUP_CONCAT(emp1.fullname ORDER BY al.layer ASC SEPARATOR '|') AS approver_names")
        ->selectRaw("GROUP_CONCAT(emp1.job_level ORDER BY al.layer ASC SEPARATOR '|') AS approver_job_levels")
        ->leftJoin('employees as emp', 'emp.employee_id', '=', 'al.employee_id')
        ->leftJoin('employees as emp1', 'emp1.employee_id', '=', 'al.approver_id')
        ->leftJoin('users as usr', 'usr.id', '=', 'al.updated_by')
        ->groupBy('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area', 'al.updated_by', 'al.updated_at', 'usr.name')
        ->orderBy('al.updated_at', 'desc')
        ->where('al.employee_id', $employeeId)
        ->get();

        $approvalLayers2 = DB::table('approval_layers as al')
        ->select('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area', 'al.updated_by', 'al.updated_at', 'usr.name')
        ->selectRaw("GROUP_CONCAT(al.layer ORDER BY al.layer ASC SEPARATOR '|') AS layers")
        ->selectRaw("GROUP_CONCAT(al.approver_id ORDER BY al.layer ASC SEPARATOR '|') AS approver_ids")
        ->selectRaw("GROUP_CONCAT(emp1.fullname ORDER BY al.layer ASC SEPARATOR '|') AS approver_names")
        ->selectRaw("GROUP_CONCAT(emp1.job_level ORDER BY al.layer ASC SEPARATOR '|') AS approver_job_levels")
        ->leftJoin('employees as emp', 'emp.employee_id', '=', 'al.employee_id')
        ->leftJoin('employees as emp1', 'emp1.employee_id', '=', 'al.approver_id')
        ->leftJoin('users as usr', 'usr.id', '=', 'al.updated_by')
        ->groupBy('al.employee_id', 'emp.fullname', 'emp.job_level', 'emp.contribution_level_code', 'emp.group_company', 'emp.office_area', 'al.updated_by', 'al.updated_at', 'usr.name')
        ->orderBy('al.updated_at', 'desc')
        ->where('al.employee_id', $employeeId)
        ->get();

        $approvalLayers = $approvalLayers2->merge($approvalLayers1);

        return response()->json($approvalLayers);
    }

    function layerAppraisal(Request $request) {
        // Baris tabel dimuat lewat layerAppraisalData (server-side DataTables).
        // Dulu seluruh employees_pa (±6.400 baris) di-render ke HTML plus
        // di-dump lagi sebagai JSON, sehingga halaman ±8 MB.
        //
        // Halaman pertama (25 baris, tanpa search/urutan) ikut dikirim di HTML
        // supaya tabel langsung terisi tanpa menunggu request AJAX kedua.
        return view('pages.layers.layer-appraisal', [
            'parentLink' => 'Settings',
            'link' => 'Layers',
            'initialData' => $this->dataTableInitialPage($request, self::LAYER_APPRAISAL_COLUMNS, fn ($sub) => $this->layerAppraisalData($sub)),
        ]);
    }

    /** Kolom tabel Layer Appraisal, sama urutannya dengan layer-appraisal.js. */
    private const LAYER_APPRAISAL_COLUMNS = [
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'searchable' => 'false', 'orderable' => 'false'],
        ['data' => 'employee', 'name' => 'fullname', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'company_name', 'name' => 'company_name', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'office_area', 'name' => 'office_area', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'group_company', 'name' => 'group_company', 'searchable' => 'true', 'orderable' => 'true'],
        ['data' => 'action', 'name' => 'action', 'searchable' => 'false', 'orderable' => 'false'],
    ];

    /**
     * Daftar karyawan Layer Appraisal sesuai restriction role user login
     * (group_company / contribution_level_code / work_area_code).
     */
    private function layerAppraisalQuery()
    {
        $roles = Auth::user()->roles;

        $restrictionData = [];
        if(!is_null($roles)){
            $restrictionData = json_decode($roles->first()->restriction, true);
        }

        $criteria = [
            'work_area_code' => $restrictionData['work_area_code'] ?? [],
            'group_company' => $restrictionData['group_company'] ?? [],
            'contribution_level_code' => $restrictionData['contribution_level_code'] ?? [],
        ];

        $query = EmployeeAppraisal::query();

        $query->where(function ($query) use ($criteria) {
            foreach ($criteria as $key => $value) {
                if ($value !== null && !empty($value)) {
                    $query->whereIn($key, $value);
                }
            }
        });

        return $query->whereNull('deleted_at');
    }

    /**
     * Endpoint server-side DataTables untuk tabel Layer Appraisal.
     * Pencarian bebas mencakup semua kolom yang tampil (nama, employee id,
     * company, office location, business unit), per kata seperti smart
     * search DataTables client-side sebelumnya.
     */
    public function layerAppraisalData(Request $request)
    {
        $period = $this->appService->appraisalPeriod();

        $searchable = ['fullname', 'employee_id', 'company_name', 'office_area', 'group_company'];
        $orderable  = [1 => 'fullname', 2 => 'company_name', 3 => 'office_area', 4 => 'group_company'];

        $query = $this->layerAppraisalQuery()
            ->select('id', 'fullname', 'employee_id', 'group_company', 'company_name', 'office_area')
            ->withCount(['calibration as approved_calibration_count' => function ($q) use ($period) {
                $q->where('status', 'Approved')->where('period', $period);
            }]);

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->filter(function ($q) use ($request, $searchable) {
                $words = preg_split('/\s+/', trim((string) $request->input('search.value', '')), -1, PREG_SPLIT_NO_EMPTY);
                foreach ($words as $word) {
                    $like = '%' . addcslashes($word, '%_\\') . '%';
                    $q->where(function ($w) use ($searchable, $like) {
                        foreach ($searchable as $col) {
                            $w->orWhere($col, 'like', $like);
                        }
                    });
                }
            })
            ->order(function ($q) use ($request, $orderable) {
                $col = $orderable[(int) $request->input('order.0.column', -1)] ?? null;
                if ($col) {
                    $q->orderBy($col, $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc');
                }
                $q->orderBy('id');
            })
            ->addColumn('employee', fn ($row) => e($row->fullname) . ' <span class="text-muted">' . e($row->employee_id) . '</span>')
            ->addColumn('action', function ($row) {
                $edit = $row->approved_calibration_count
                    ? '<button type="button" onclick="alert(\'Cannot changes layer, This employee already on calibration proccess.\')" class="btn btn-sm rounded btn-light me-1"><i class="ri-edit-box-line fs-16"></i></button>'
                    : '<a href="' . e(route('layer-appraisal.edit', $row->employee_id)) . '" class="btn btn-sm rounded btn-outline-warning me-1"><i class="ri-edit-box-line fs-16"></i></a>';

                return $edit . '<button type="button" class="btn btn-sm rounded btn-outline-info me-1" data-bs-toggle="modal" data-bs-target="#detailModal" data-bs-id="' . e($row->employee_id) . '"><i class="ri-eye-line fs-16"></i></button>';
            })
            ->only(['DT_RowIndex', 'employee', 'company_name', 'office_area', 'group_company', 'action'])
            ->rawColumns(['employee', 'action'])
            ->toJson();
    }

    function layerAppraisalEdit(Request $request) {

        $roles = Auth::user()->roles;

        $restrictionData = [];
        if(!is_null($roles)){
            $restrictionData = json_decode($roles->first()->restriction, true);
        }
        
        $permissionGroupCompanies = $restrictionData['group_company'] ?? [];
        $permissionCompanies = $restrictionData['contribution_level_code'] ?? [];
        $permissionLocations = $restrictionData['work_area_code'] ?? [];

        $criteria = [
            'work_area_code' => $permissionLocations,
            'group_company' => $permissionGroupCompanies,
            'contribution_level_code' => $permissionCompanies,
        ];

        $parentLink = 'Settings';
        $link = 'Layers';

        foreach ($criteria as $key => $value) {
            if (!is_array($value)) {
                $criteria[$key] = (array) $value;
            }
        }

        $datas = EmployeeAppraisal::select('fullname', 'employee_id', 'date_of_joining', 'group_company', 'company_name', 'unit', 'designation', 'office_area')->with(['appraisalLayer' => function($query) {
            $query->with(['approver' => function($subquery) {
                $subquery->select('fullname', 'employee_id', 'designation');
            }])->select('employee_id', 'approver_id', 'layer_type', 'layer');
        }])
        ->where('employee_id', $request->id)
        ->first();

        if ($datas) {
            $datas->formattedDoj = $this->appService->formatDate($datas->date_of_joining);
        }

        $groupLayers = $datas->appraisalLayer->groupBy('layer_type')->map(function ($layers) {
            return $layers->sortBy('layer')->values();
        });

        $calibratorCount = isset($groupLayers['calibrator']) ? $groupLayers['calibrator']->count() : 1;

        // Approver tersimpan per jenis & nomor layer (peers[2] = Peers 2),
        // supaya tiap dropdown menampilkan layer yang benar walau ada yang
        // kosong di tengah. Manager hanya satu.
        $selectedLayers = [
            'manager' => [1 => optional($groupLayers['manager'] ?? collect())->first()?->approver_id],
        ];
        foreach (['peers', 'subordinate'] as $type) {
            foreach ($groupLayers[$type] ?? [] as $layer) {
                $selectedLayers[$type][(int) $layer->layer] = $layer->approver_id;
            }
        }

        // Dropdown memakai Select2 AJAX ke /search-employee (tabel employees),
        // jadi cukup opsi yang sedang terpilih. Dulu tiap dropdown berisi
        // semua employees_pa, sehingga approver yang dipilih lewat pencarian
        // tapi tidak ada di employees_pa (mis. karyawan baru) tampil kosong
        // saat halaman dibuka lagi, dan hilang bila disimpan ulang.
        $approverIds = $datas->appraisalLayer->pluck('approver_id')->filter()->map('strval')->unique()->values();
        $approverNames = Employee::whereIn('employee_id', $approverIds)->pluck('fullname', 'employee_id')->all()
            + EmployeeAppraisal::withTrashed()->whereIn('employee_id', $approverIds)->pluck('fullname', 'employee_id')->all();

        return view('pages.layers.layer-appraisal-edit', [
            'parentLink' => $parentLink,
            'link' => $link,
            'datas' => $datas,
            'calibratorCount' => $calibratorCount,
            'groupLayers' => $groupLayers,
            'selectedLayers' => $selectedLayers,
            'approverNames' => $approverNames,
        ]);
    }

    public function layerAppraisalUpdate(Request $request)
    {
        $userId = Auth::id();
        $period = $this->appService->appraisalPeriod();

        // Define validation rules
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|string',
            'manager' => 'nullable|string|exists:employees,employee_id',
            'peers' => 'nullable|array',
            'peers.*' => 'nullable|string|exists:employees,employee_id', // Validate each peer ID
            'subs' => 'nullable|array',
            'subs.*' => 'nullable|string|exists:employees,employee_id', // Validate each subordinate ID
            'calibrators' => 'nullable|array',
            'calibrators.*' => 'nullable|string|exists:employees,employee_id', // Validate each calibrator ID
        ]);

        // Check if the validation fails
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput(); // Return validation errors
        }

        // Retrieve validated data
        $validated = $validator->validated();

        // Manager data
        $manager = $validated['manager'] ?? null;
        $peers = $validated['peers'] ?? [];
        $subs = $validated['subs'] ?? [];
        $calibrators = $validated['calibrators'] ?? [];

        $currentLayers = ApprovalLayerAppraisal::with(['approver'])->where('employee_id', $validated['employee_id'])
                                       ->whereIn('layer_type', ['manager', 'peers', 'subordinate'])
                                       ->get();
        $currentPeers = $currentLayers->where('layer_type', 'peers');

        // Get the manager (assuming there is only one)
        $currentManager = $currentLayers->where('layer_type', 'manager')->first();

        // Get all peers (already limited by the database to a max of 3)
        $currentPeers = $currentLayers->where('layer_type', 'peers')->pluck('approver.employee_id')->toArray();

        $peersToDelete = array_diff($currentPeers, $peers);
        
        // Get all subordinates (already limited by the database to a max of 3)
        $currentSub = $currentLayers->where('layer_type', 'subordinate')->pluck('approver.employee_id')->toArray();
        $subsToDelete = array_diff($currentSub, $subs);

        // return response()->json($peersToDelete);

        
        $approvalRequest = ApprovalRequest::where('employee_id', $validated['employee_id'])->where('category', 'Appraisal')->where('period', $period)->first();

        // $firstNonNullValue = reset(array_filter($calibrators));

        $filteredCalibrator = array_filter($calibrators, fn($value) => !is_null($value) && $value !== '');

        // Now reset the filtered array to get the first non-null, non-empty value
        $firstNonNullCalibrator = reset($filteredCalibrator);

        if ($approvalRequest) {
            $checkCalibration = Calibration::where('appraisal_id', $approvalRequest->form_id)->where('created_by', $currentManager->approver->id)->where('status', 'Pending')->first();

            // Check if a record was found, then update `approver_id` and `updated_by` fields
            if ($checkCalibration && $checkCalibration->approver_id != $firstNonNullCalibrator) {
                $checkCalibration->approver_id = $firstNonNullCalibrator; // Assign the new approver ID
                $checkCalibration->updated_by = Auth::id(); // Set the current authenticated user as `updated_by`
                $checkCalibration->save(); // Save changes to the database
            }
        }
        
        if ($currentManager) {
            if($currentManager->approver_id != $manager){
                // Check if the employee record exists
                if ($approvalRequest) {

                    $appraisal = Appraisal::where('id', $approvalRequest->form_id)->where('created_by', $currentManager->approver->id)->first();
        
                    if ($approvalRequest->created_by == $currentManager->approver->id) {
                        // Soft delete the record
                        $approvalRequest->delete();

                        if ($appraisal) {
                            // Soft delete the record
                            $appraisal->delete();
                            
                        }
                        
                    } else {
                        // Update the current_approval_id if not created by the current user
                        $approvalRequest->update([
                            'current_approval_id' => $manager,
                            'status' => 'Pending'
                        ]);
                        
                    }
        
                    $calibration = Calibration::where('appraisal_id', $approvalRequest->form_id)->where('created_by', $currentManager->approver->id)->where('status', 'Pending')->first();
        
                    if ($calibration) {
                        // Soft delete the record
                        $calibration->delete();
                        
                    }
        
                    $contributor = AppraisalContributor::where('appraisal_id', $approvalRequest->form_id)->where('contributor_id', $currentManager->approver_id)->first();
        
                    if ($contributor) {
                        // Soft delete the record
                        $contributor->delete();
                        
                    }
        
                }
            }
        }

        if ($currentManager && $approvalRequest) {
            if($approvalRequest->created_by == $currentManager->approver->id){

            AppraisalContributor::where('appraisal_id', $approvalRequest->form_id)
                                ->whereIn('contributor_type', ['peers', 'subordinate'])
                                ->delete();

            }else{
                if (!empty($peersToDelete)) {
                    foreach ($peersToDelete as $peerId) {
                        // Find the record in the AppraisalContributor table based on appraisal_id and contributor_id
                        $contributor = AppraisalContributor::where('appraisal_id', $approvalRequest->form_id)
                            ->where('contributor_id', $peerId)
                            ->where('contributor_type', 'peers')
                            ->first();
                
                        // If a record is found, perform a soft delete
                        if ($contributor) {
                            $contributor->delete();
                        }
                    }
                }
        
                if (!empty($subsToDelete)) {
                    // Iterate through each peer that needs to be deleted
                    foreach ($subsToDelete as $subId) {
                        // Find the record in the AppraisalContributor table based on appraisal_id and contributor_id
                        $contributor = AppraisalContributor::where('appraisal_id', $approvalRequest->form_id)
                            ->where('contributor_id', $subId)
                            ->where('contributor_type', 'subordinate')
                            ->first();
                
                        // If a record is found, perform a soft delete
                        if ($contributor) {
                            $contributor->delete();
                        }
                    }
                }
            }
        }

        
        // Delete existing records for the employee_id
        ApprovalLayerAppraisal::where('employee_id', $validated['employee_id'])->delete();

        // Update manager
        if (!is_null($manager)) {
            ApprovalLayerAppraisal::create(
                [
                    'layer_type' => 'manager',
                    'employee_id' => $validated['employee_id'],
                    'approver_id' => $manager,
                    'created_by' => $userId
                ]
            );
        }

        // Update peers, ignoring null entries
        if (is_array($peers)) {
            foreach ($peers as $index => $peer) {
                if (!is_null($peer)) {
                    ApprovalLayerAppraisal::create(
                        [
                            'layer_type' => 'peers',
                            'layer' => $index + 1,
                            'employee_id' => $validated['employee_id'],
                            'approver_id' => $peer,
                            'created_by' => $userId
                        ]
                    );
                }
            }
        }

        // Update subs, ignoring null entries
        if (is_array($subs)) {
            foreach ($subs as $index => $sub) {
                if (!is_null($sub)) {
                    ApprovalLayerAppraisal::create(
                        [
                            'layer_type' => 'subordinate',
                            'layer' => $index + 1,
                            'employee_id' => $validated['employee_id'],
                            'approver_id' => $sub,
                            'created_by' => $userId
                        ]
                    );
                }
            }
        }

        // Update calibrators, ignoring null entries
        if (is_array($calibrators)) {
            foreach ($calibrators as $index => $calibrator) {
                if (!is_null($calibrator)) {
                    ApprovalLayerAppraisal::create(
                        [
                            'layer_type' => 'calibrator',
                            'layer' => $index + 1,
                            'employee_id' => $validated['employee_id'],
                            'approver_id' => $calibrator,
                            'created_by' => $userId
                        ]
                    );
                }
            }
        }

        // Redirect back to layer-appraisal page
        return redirect()->route('layer-appraisal')->with('success', 'Appraisal layers updated successfully.');
    }

    public function layerAppraisalImport(Request $request)
    {
        $period = $this->appService->appraisalPeriod();

        try {
            $request->validate([
                'excelFile' => 'required|mimes:xlsx,xls,csv'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        try {
            // Get the ID of the currently authenticated user
            $userId = Auth::id();
    
            // Initialize the import process with the user ID and period
            $import = new ApprovalLayerAppraisalImport($userId, $period);
    
            // Perform the import
            Excel::import($import, $request->file('excelFile'));
    
            // Check for any invalid employees
            $invalidEmployees = $import->getInvalidEmployees();
            $message = 'Data imported successfully.';
    
            if (!empty($invalidEmployees)) {
                session()->put('invalid_employees', $invalidEmployees);
                $message .= ' With some errors. <a href="' . route('export.invalid.layer.appraisal') . '">Click here to download the list of errors.</a>';
            }
    
            return redirect()->back()->with('success', $message);
    
        } catch (ValidationException $e) {
            // Catch the validation exception and redirect back with a custom error message
            return redirect()->back()->with('error', $e->errors()['error'][0]);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return redirect()->back()->with('error', 'An unexpected error occurred during the import process. Please try again.');
        }

    }

    public function exportInvalidLayerAppraisal()
    {
        // Retrieve the invalid employees from the session or another source
        $invalidEmployees = session('invalid_employees');

        if (empty($invalidEmployees)) {
            return redirect()->back()->with('success', 'No invalid employees to export.');
        }

        // Export the invalid employees to an Excel file
        return Excel::download(new InvalidApprovalAppraisalImport($invalidEmployees), 'errors_layer_import.xlsx');
    }

    public function getEmployeeLayerDetails($employeeId)
    {
        try {
            // Validate employee ID format (assuming numeric for this example)
            if (!is_numeric($employeeId)) {
                return response()->json(['error' => 'Invalid employee ID format'], 400);
            }

            // Cache the employee data to reduce database queries
            $employee = Cache::remember("employee_{$employeeId}", 60, function () use ($employeeId) {
                return EmployeeAppraisal::where('employee_id', $employeeId)->firstOrFail();
            });

            $doj = Carbon::parse($employee->date_of_joining);

            // Fetch history (you can also cache this if necessary)
            $history = ApprovalLayerAppraisal::with(['approver', 'createBy', 'updateBy'])->where('employee_id', $employeeId)->get();

            // Build the response data structure
            $data = [
                'fullname' => $employee->fullname,
                'employee_id' => $employee->employee_id,
                'formattedDoj' => $doj->format('d M Y'),
                'group_company' => $employee->group_company,
                'company_name' => $employee->company_name,
                'unit' => $employee->unit,
                'designation' => $employee->designation_name,
                'office_area' => $employee->office_area,
                'history' => $history->map(function($entry) {
                    return [
                        'layer_type' => $entry->layer_type,
                        'layer' => $entry->layer,
                        'fullname' => $entry->approver->fullname,
                        'employee_id' => $entry->approver->employee_id,
                        'updated_by' => $entry->createBy ? $entry->createBy->fullname.' ('. $entry->createBy->employee_id .')' : ($entry->updateBy ? $entry->updateBy->fullname.' ('. $entry->updateBy->employee_id .')' : 'System') ,
                        'updated_at' => $entry->updated_at ? $entry->updated_at->format('Y-m-d H:i:s') : null,
                    ];
                }),
            ];

            return response()->json($data);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Employee not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }

}