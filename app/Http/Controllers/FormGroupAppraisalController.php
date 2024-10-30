<?php

namespace App\Http\Controllers;

use App\Models\FormGroupAppraisal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FormGroupAppraisalController extends Controller
{
    /**
     * Display a listing of form groups.
     */
    public function index()
    {
        $formGroups = FormGroupAppraisal::with('formAppraisals')->latest()->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $formGroups
        ]);
    }

    /**
     * Store a newly created form group.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'form_number' => 'required|integer',
            'form_names' => 'required|array',
            'form_names.*' => 'required|string',
            'restrict' => 'nullable|array',
            'form_appraisals' => 'required|array',
            'form_appraisals.*.id' => 'required|exists:form_appraisals,id',
            'form_appraisals.*.sort_order' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $formGroup = FormGroupAppraisal::create([
                'name' => $request->name,
                'form_number' => $request->form_number,
                'form_names' => $request->form_names,
                'restrict' => $request->restrict,
                'created_by' => Auth()->id() ?? 0
            ]);

            // Attach forms with sort order
            foreach ($request->form_appraisals as $form) {
                $formGroup->formAppraisals()->attach($form['id'], [
                    'sort_order' => $form['sort_order'],
                    'created_by' => Auth()->id() ?? 0
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Form group created successfully',
                'data' => $formGroup->load('formAppraisals')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create form group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified form group.
     */
    public function show(FormGroupAppraisal $formGroupAppraisal)
    {
        return response()->json([
            'status' => 'success',
            'data' => $formGroupAppraisal->load('formAppraisals')
        ]);
    }

    /**
     * Update the specified form group.
     */
    public function update(Request $request, FormGroupAppraisal $formGroupAppraisal)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'form_number' => 'sometimes|required|integer',
            'form_names' => 'sometimes|required|array',
            'form_names.*' => 'required|string',
            'restrict' => 'nullable|array',
            'form_appraisals' => 'sometimes|required|array',
            'form_appraisals.*.id' => 'required|exists:form_appraisals,id',
            'form_appraisals.*.sort_order' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $formGroupAppraisal->update($request->only([
                'name', 'form_number', 'form_names', 'restrict'
            ]));

            // Update form relationships if provided
            if ($request->has('form_appraisals')) {
                $formGroupAppraisal->formAppraisals()->detach();
                
                foreach ($request->form_appraisals as $form) {
                    $formGroupAppraisal->formAppraisals()->attach($form['id'], [
                        'sort_order' => $form['sort_order'],
                        'created_by' => Auth()->id() ?? 0
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Form group updated successfully',
                'data' => $formGroupAppraisal->load('formAppraisals')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update form group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified form group.
     */
    public function destroy(FormGroupAppraisal $formGroupAppraisal)
    {
        try {
            $formGroupAppraisal->formAppraisals()->detach();
            $formGroupAppraisal->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Form group deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete form group',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
