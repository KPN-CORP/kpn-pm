<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApprovalFlowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Sesuaikan dengan logika otorisasi Anda (misalnya, hanya admin yang boleh membuat alur)
        return true; // Untuk contoh ini, kita izinkan semua
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'flow_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('approval_flows', 'flow_name')
                    ->whereNull('deleted_at'),
            ],
            // 'module_transaction_type' tidak ada di form final yang diberikan
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'steps' => 'required|array|min:1',
            'steps.*.step_number' => 'required|integer|min:1',
            'steps.*.approver_role' => 'nullable|array', // Multi-select untuk peran
            'steps.*.approver_role.*' => 'string|max:255',
            'steps.*.approver_user_id' => 'nullable|array', // Multi-select untuk user ID
            'steps.*.approver_user_id.*' => 'string|max:255',
            'steps.*.step_name' => 'nullable|string|max:100',
            // 'required_action' tidak ada di form final yang diberikan
            'steps.*.allotted_time' => 'nullable|integer|min:0',
            // Validasi untuk Additional Settings, sekarang per langkah
            'steps.*.settings_json' => 'nullable|string', // Akan divalidasi sebagai string JSON
            // Validasi untuk konten JSON di dalam settings_json (opsional, bisa dilakukan setelah json_decode di controller)
            // 'steps.*.settings_json.hide_stage_from' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.form_visibility' => 'sometimes|nullable|array',
            // 'steps.*.settings_json.form_visibility.*' => 'sometimes|string',
            // 'steps.*.settings_json.confidential' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.approver_context' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.assignee_context' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.re_route' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.skip_for' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.skip_settings' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.sia_settings' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.replace_buttons' => 'sometimes|boolean',
            // 'steps.*.settings_json.allow_send_back' => 'sometimes|boolean',
            // 'steps.*.settings_json.button_aliases.approve' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.button_aliases.reject' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.button_aliases.delegate' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.button_aliases.save_as_draft' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.button_aliases.view_details' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approver_assigned.template' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approver_assigned.default_recipients' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approver_assigned.additional_recipients' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approver_assigned.cc_users' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approver_assigned.cc_roles' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approvers_approve.template' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approvers_approve.default_recipients' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approvers_approve.additional_recipients' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approvers_approve.cc_users' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approvers_approve.cc_roles' => 'sometimes|nullable|string',

            // 'steps.*.settings_json.email_approvers_reject.template' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approvers_reject.default_recipients' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approvers_reject.additional_recipients' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approvers_reject.cc_users' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_approvers_reject.cc_roles' => 'sometimes|nullable|string',

            // 'steps.*.settings_json.email_due_date_completed.template' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_due_date_completed.default_recipients' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_due_date_completed.additional_recipients' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_due_date_completed.cc_users' => 'sometimes|nullable|string',
            // 'steps.*.settings_json.email_due_date_completed.cc_roles' => 'sometimes|nullable|string',
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'flow_name.required' => 'Nama alur persetujuan wajib diisi.',
            'flow_name.unique' => 'Nama alur persetujuan sudah ada.',
            'steps.required' => 'Setidaknya harus ada satu langkah persetujuan.',
            'steps.*.step_number.required' => 'Nomor langkah wajib diisi.',
            'steps.*.step_number.integer' => 'Nomor langkah harus berupa angka.',
            'steps.*.approver_role.required' => 'Pilih setidaknya satu peran pemberi persetujuan.',
            'steps.*.approver_user_id.required' => 'Pilih setidaknya satu karyawan pemberi persetujuan.',
            'steps.*.allotted_time.integer' => 'Waktu yang dialokasikan harus berupa angka.',
            'steps.*.allotted_time.min' => 'Waktu yang dialokasikan tidak boleh negatif.',
        ];
    }
}
