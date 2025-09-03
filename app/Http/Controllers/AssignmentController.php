<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class AssignmentController extends Controller
{
    public function index()
    {
        $datas = Assignment::all(); // Ambil semua alur persetujuan

        $parentLink = __('Assignments');
        $link = __('List');

        return view('pages.assignments.app', compact('datas', 'parentLink', 'link'));
    }

    public function data()
    {
        $assignments = Assignment::select(['id', 'name', 'updated_at', 'created_at']);

        return DataTables::of($assignments)
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d M Y');
            })
            ->editColumn('updated_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d M Y');
            })
            ->addColumn('action', function ($item) {
                return view('components.flow-table-action-buttons', [
                    'editRoute'   => 'assignments.edit',
                    'deleteRoute' => 'assignments.destroy',
                    'id'          => $item->id,
                    'type'        => 'circle'
                ])->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Menampilkan form untuk membuat alur persetujuan baru.
     */
    public function create()
    {

        // Ambil nilai unik dari setiap kolom
        $businessUnits = Employee::select('group_company')->distinct()->pluck('group_company')->filter()->values();
        $designations  = Employee::select('designation_name')->distinct()->pluck('designation_name')->filter()->values();
        $companies     = Employee::select('company_name')->distinct()->pluck('company_name')->filter()->values();
        $jobLevels     = Employee::select('job_level')->distinct()->orderBy('job_level', 'ASC')->pluck('job_level')->filter()->values();
        $employeeTypes = Employee::select('employee_type')->distinct()->pluck('employee_type')->filter()->values();

        // Gabungkan fullname dan employee_id
        $employeeNames = Employee::select('fullname', 'employee_id')
            ->where('employee_id', '!=', 'DBOX')
            ->get()
            ->map(fn($emp) => "{$emp->fullname} ({$emp->employee_id})");

        $attributeData = [
            'Business Unit'   => $businessUnits->toArray(),
            'Designation Name'=> $designations->toArray(),
            'Company Name'    => $companies->toArray(),
            'Job Level'       => $jobLevels->toArray(),
            'Employee Type'   => $employeeTypes->toArray(),
            'Employee Name'   => $employeeNames->toArray()
        ];


        $parentLink = __('Assignments');
        $link = __('Create');

        return view('pages.assignments.create', compact(
            'parentLink', 
            'link',
            'attributeData'
        ));
    }

    public function edit($id)
    {
        $assignment = Assignment::findOrFail($id);

        // Decode restriction to array
        $restriction = json_decode($assignment->restriction, true) ?? [];

        // Build attribute data from Employee model (same as create)
        $attributeData = [
            'Business Unit'     => Employee::select('group_company')->distinct()->orderBy('group_company')->pluck('group_company')->filter()->values(),
            'Designation Name'  => Employee::select('designation_name')->distinct()->orderBy('designation_name')->pluck('designation_name')->filter()->values(),
            'Company Name'      => Employee::select('company_name')->distinct()->orderBy('company_name')->pluck('company_name')->filter()->values(),
            'Job Level'         => Employee::select('job_level')->distinct()->orderBy('job_level')->pluck('job_level')->filter()->values(),
            'Employee Type'     => Employee::select('employee_type')->distinct()->orderBy('employee_type')->pluck('employee_type')->filter()->values(),
            'Employee'          => Employee::select(DB::raw("CONCAT(fullname, ' (', employee_id, ')') AS name"))
                                            ->orderBy('fullname')
                                            ->pluck('name')
                                            ->filter()
                                            ->values(),
        ];

        // Map restriction key to attribute label for displaying selected values
        $attributesName = [
            'group_company'     => 'Business Unit',
            'designation_name'  => 'Designation Name',
            'company_name'      => 'Company Name',
            'job_level'         => 'Job Level',
            'employee_type'     => 'Employee Type',
            'employee'          => 'Employee',
        ];

        // Prepare array to pass to Blade
        $selectedAttributes = [];
        foreach ($restriction as $key => $values) {
            if (isset($attributesName[$key])) {
                $selectedAttributes[] = [
                    'name'  => $attributesName[$key],
                    'value' => $values,
                ];
            }
        }

        $parentLink = __('Assignments');
        $link = __('Edit');

        return view('pages.assignments.edit', compact('parentLink', 'link', 'assignment', 'attributeData', 'selectedAttributes'));
    }

    /**
     * Menyimpan alur persetujuan baru ke database.
     */
    public function store(Request $request)
    {
        $attributes = $request->input('attributes', []);

        $attributesName = [
            'group_company'     => 'Business Unit',
            'designation_name'  => 'Designation Name',
            'company_name'      => 'Company Name',
            'job_level'         => 'Job Level',
            'employee_type'     => 'Employee Type',
            'employee'          => 'Employee',
        ];

        $restriction = [
            'group_company'     => null,
            'designation_name'  => null,
            'company_name'      => null,
            'job_level'         => null,
            'employee_type'     => null,
            'employee'          => null,
        ];

        foreach ($attributes as $attr) {
            $key = array_search($attr['name'], $attributesName);
            if ($key !== false) {
                $restriction[$key] = is_array($attr['value']) ? $attr['value'] : [$attr['value']];
            }
        }

        $restriction = array_filter($restriction, fn($val) => !is_null($val));

        try {
            DB::beginTransaction();

            $request->validate([
                'name' => [
                    'required',
                    Rule::unique('assignments')->where(function ($query) {
                        return $query->whereNull('deleted_at');
                    }),
                ],
            ]);

            $assignment = new Assignment;
            $assignment->name = $request->name;
            $assignment->restriction = json_encode($restriction);
            $assignment->created_by = Auth::id();
            $assignment->save();

            DB::commit();

            Log::info('Assignment successfully created', [
                'data'       => json_encode([
                    'name'        => $request->name,
                    'restriction' => $restriction,
                ]),
                'created_by' => Auth::id(),
            ]);

            return redirect()
                ->route('assignments.index')
                ->with('success', 'Assignment successfully created');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to save assignment: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $attributes = $request->input('attributes', []);

        $attributesName = [
            'group_company'     => 'Business Unit',
            'designation_name'  => 'Designation Name',
            'company_name'      => 'Company Name',
            'job_level'         => 'Job Level',
            'employee_type'     => 'Employee Type',
            'employee'          => 'Employee',
        ];

        // Initialize restriction keys with null
        $restriction = array_fill_keys(array_keys($attributesName), null);

        // Map submitted attributes to restriction keys
        foreach ($attributes as $attr) {
            $key = array_search($attr['name'], $attributesName);
            if ($key !== false) {
                $restriction[$key] = is_array($attr['value']) ? $attr['value'] : [$attr['value']];
            }
        }

        // Remove null values if not needed
        $restriction = array_filter($restriction, fn($val) => !is_null($val));

        try {
            DB::beginTransaction();

            // Validate unique name but exclude current record and check soft delete
            $request->validate([
                'name' => [
                    'required',
                    Rule::unique('assignments')->ignore($id)->where(function ($query) {
                        return $query->whereNull('deleted_at');
                    }),
                ]
            ]);

            $assignment = Assignment::findOrFail($id);
            $assignment->name = $request->name;
            $assignment->restriction = json_encode($restriction);
            $assignment->updated_by = Auth::id();
            $assignment->save();

            DB::commit();

            Log::info('Assignment successfully updated', [
                'data' => json_encode([
                    'assignment_id' => $assignment->id,
                    'name' => $request->name,
                    'restriction' => $restriction
                ]),
                'updated_by' => Auth::id(),
            ]);

            return redirect()
                ->route('assignments.index')
                ->with('success', 'Assignment successfully updated');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update assignment: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'An error occurred while updating assignment.');
        }
    }

    public function destroy(Request $request)
    {
        $assignment = Assignment::findOrFail($request->id);

        try {
            DB::beginTransaction();

            $assignment->updated_by = Auth::id();
            $assignment->save();

            $assignment->delete();

            DB::commit();

            Log::info('Assignment successfully deleted', [
                'id' => $request->id,
                'deleted_by' => Auth::id(),
            ]);

            return redirect()
                ->route('assignments.index')
                ->with('success', 'Assignment successfully deleted');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete assignment: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'An error occurred while deleting assignment.');
        }
    }
}
