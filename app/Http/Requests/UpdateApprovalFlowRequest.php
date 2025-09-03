<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApprovalFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Dukung route model binding: /approval-flows/{approval_flow}
        $flow = $this->route('approval_flow'); // bisa Model atau null
        $id   = is_object($flow) && method_exists($flow, 'getKey')
            ? $flow->getKey()
            : ($this->route('id') ?? null);

        return [
            'flow_name' => [
                'required','string','max:100',
                Rule::unique('approval_flows','flow_name')
                    ->ignore($id) // abaikan record saat update
                    ->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
            'is_active'   => 'boolean',

            'steps' => 'required|array|min:1',
            'steps.*.step_number' => 'required|integer|min:1',
            // minimal salah satu terisi: role atau user_id
            'steps.*.approver_role'    => 'nullable|array|required_without:steps.*.approver_user_id',
            'steps.*.approver_role.*'  => 'string|max:255',
            'steps.*.approver_user_id' => 'nullable|array|required_without:steps.*.approver_role',
            'steps.*.approver_user_id.*' => 'string|max:255',
            'steps.*.step_name'        => 'nullable|string|max:100',
            'steps.*.allotted_time'    => 'nullable|integer|min:0',

            // settings_json dikirim sebagai string JSON → cukup validasi json
            'steps.*.settings_json' => 'nullable|json',
        ];
    }

    public function messages(): array
    {
        return [
            'flow_name.required' => 'Nama alur persetujuan wajib diisi.',
            'flow_name.unique'   => 'Nama alur persetujuan sudah ada.',

            'steps.required'     => 'Setidaknya harus ada satu langkah persetujuan.',
            'steps.*.step_number.required' => 'Nomor langkah wajib diisi.',
            'steps.*.step_number.integer'  => 'Nomor langkah harus berupa angka.',

            'steps.*.approver_role.required_without'    => 'Isi peran approver atau pilih user approver.',
            'steps.*.approver_user_id.required_without' => 'Pilih user approver atau isi peran approver.',

            'steps.*.allotted_time.integer' => 'Waktu yang dialokasikan harus berupa angka.',
            'steps.*.allotted_time.min'     => 'Waktu yang dialokasikan tidak boleh negatif.',

            'steps.*.settings_json.json'    => 'Settings harus berupa JSON yang valid.',
        ];
    }
}
