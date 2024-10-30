<?php

namespace App\Http\Controllers;

use App\Models\FormAppraisal;
use App\Models\FormGroupAppraisal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FormAppraisalController extends Controller
{
    /**
     * Display a listing of form appraisals.
     */
    public function index()
    {
        $forms = FormAppraisal::latest()->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $forms
        ]);
    }

    /**
     * Store a newly created form appraisal.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'data' => 'nullable|array',
            'icon' => 'nullable|string|max:255',
            'blade' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $form = FormAppraisal::create([
                ...$request->validated(),
                'created_by' => Auth()->id() ?? 0
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Form appraisal created successfully',
                'data' => $form
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create form appraisal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified form appraisal.
     */
    public function show(FormAppraisal $formAppraisal)
    {
        return response()->json([
            'status' => 'success',
            'data' => $formAppraisal
        ]);
    }

    /**
     * Update the specified form appraisal.
     */
    public function update(Request $request, FormAppraisal $formAppraisal)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:255',
            'title' => 'nullable|string|max:255',
            'data' => 'nullable|array',
            'icon' => 'nullable|string|max:255',
            'blade' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $formAppraisal->update($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Form appraisal updated successfully',
                'data' => $formAppraisal
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update form appraisal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified form appraisal.
     */
    public function destroy(FormAppraisal $formAppraisal)
    {
        try {
            $formAppraisal->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Form appraisal deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete form appraisal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // return response()->json($formattedData);
}
