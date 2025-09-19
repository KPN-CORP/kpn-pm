import $ from 'jquery';

import bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';

function initDataTable(selector, ajaxUrl, columns) {
    $(selector).DataTable({
        serverSide: true,
        processing: false,
        ajax: {
            url: ajaxUrl,
            type: 'GET',
            beforeSend: function () {
                let colspan = $(selector + ' thead th').length;
                $(selector + ' tbody').html(`
                    <tr>
                        <td colspan="${colspan}" class="text-center p-2">
                            <div class="spinner-border text-info" style="width: 1.75rem; height: 1.75rem;" role="status" aria-hidden="true">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                `);
            }
        },
        columns: columns,
        responsive: true,
        pageLength: 10,
        dom: '<"top"f>rt<"bottom"ip><"clear">',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search..."
        }
    });
}


document.addEventListener('DOMContentLoaded', function () {
    if (window.tableUrl) {
        initDataTable('#tableApprovalFlow', window.tableUrl, [
            { data: 'flow_name', name: 'flow_name' },
            { data: 'description', name: 'description' },
            { data: 'is_active', name: 'is_active' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]);
    }
});

document.addEventListener("DOMContentLoaded", function () {
    // Inisialisasi Select2
    function initializeSelect2(element) {
        $(element).select2({
            placeholder: "Please select",
            allowClear: true,
            theme: 'bootstrap-5',
            dropdownParent: element.closest('.approvers-cell') // Penting untuk modal/dinamis
        });
    }


    $(document).ready(function() {
        $('.select360').each(function () {
            initializeSelect2(this);
            this.classList.remove('select360');
        });
    });    


    const stepsContainer = document.getElementById('steps-container');
    const addStepButton = document.getElementById('add-step');
    const additionalSettingsModal = new bootstrap.Modal(document.getElementById('additionalSettingsModal'));
    const modalCurrentStepIndexInput = document.getElementById('modal-current-step-index');
    const modalStepDisplay = document.getElementById('modal-step-display');
    const saveModalSettingsButton = document.getElementById('saveModalSettings');

    // Helper untuk mendapatkan data settings dari old input
    function getOldStepSettings(stepIndex) {
        if (window.oldSteps && window.oldSteps[stepIndex] && window.oldSteps[stepIndex]['settings_json']) {
            try {
                // Settings disimpan sebagai JSON string, jadi perlu di-parse
                return JSON.parse(window.oldSteps[stepIndex]['settings_json']);
            } catch (e) {
                return {};
            }
        }
        return {};
    }

    // Fungsi untuk mereset form modal
    function resetModalForm() {
        const modal = document.getElementById('additionalSettingsModal');
        $(modal).find('input[type="text"], input[type="number"], textarea').val('');
        $(modal).find('select').prop('selectedIndex', 0);
        $(modal).find('input[type="checkbox"]').prop('checked', false);
        $(modal).find('select[multiple]').val([]).trigger('change');
    }

    // Fungsi untuk mengumpulkan data dari modal dan menyimpannya ke input tersembunyi
    function saveModalFormDataToRow() {
        const stepIndex = modalCurrentStepIndexInput.value;
        const currentStepRow = stepsContainer.querySelector(`tr[data-index="${stepIndex}"]`);
        if (!currentStepRow) return;

        let settingsData = {};
        // Kumpulkan data dari semua field di modal
        // Contoh untuk beberapa field:
        settingsData.hide_stage_from = $('#modal_settings_hide_stage_from').val();
        settingsData.form_visibility = $('#modal_settings_form_visibility').val();
        settingsData.confidential = $('#modal_settings_confidential').val();
        settingsData.replace_buttons = $('#modal_settings_replace_buttons').is(':checked');
        settingsData.allow_send_back = $('#modal_settings_allow_send_back').is(':checked');
        settingsData.button_aliases = {
            approve: $('#modal_settings_alias_approve').val(),
            reject: $('#modal_settings_alias_reject').val(),
        };
        // Lanjutkan untuk semua field lainnya...

        // Buat atau update input tersembunyi di dalam baris
        let hiddenInput = currentStepRow.querySelector(`input[name="steps[${stepIndex}][settings_json]"]`);
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = `steps[${stepIndex}][settings_json]`;
            // Tempatkan di dalam salah satu sel, misal sel terakhir
            currentStepRow.querySelector('td:last-child').appendChild(hiddenInput);
        }
        hiddenInput.value = JSON.stringify(settingsData);
        console.log("Saved settings for step " + stepIndex, hiddenInput.value);
    }

    // Fungsi untuk memperbarui nomor urut dan nama input
    const updateStepNumbersAndNames = () => {
        const rows = stepsContainer.querySelectorAll('tr');
        rows.forEach((row, index) => {
            row.dataset.index = index;
            row.querySelector('.step-number-display').textContent = index + 1;
            row.querySelector('td:nth-child(2)').textContent = `R${index + 1}`;

            // Update nama semua input, select, textarea
            row.querySelectorAll('input, select, textarea').forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
                }
                const id = input.getAttribute('id');
                 if (id) {
                    input.setAttribute('id', id.replace(/-\d+-/, `-${index}-`));
                    // Update label 'for' attribute
                    const label = row.querySelector(`label[for="${id}"]`);
                    if(label) {
                        label.setAttribute('for', input.getAttribute('id'));
                    }
                }
            });

            // Update index pada tombol settings
            const settingsBtn = row.querySelector('.additional-settings-btn');
            if (settingsBtn) {
                settingsBtn.dataset.stepIndex = index;
            }
        });
    };

    // Event listener untuk menambah baris baru
    addStepButton.addEventListener('click', function () {
        const newIndex = stepsContainer.querySelectorAll('tr').length;
        const newRow = document.createElement('tr');
        newRow.classList.add('align-middle');
        newRow.dataset.index = newIndex;

        // Buat HTML untuk opsi approver roles
        let approverOptionsHtml = '<option></option>';
        for (const [id, name] of Object.entries(approverData)) {
            approverOptionsHtml += `<option value="${name}">${name}</option>`;
        }

        let employeeOptionsHtml = '<option></option>';
        for (const [id, item] of Object.entries(employeeData)) {
            employeeOptionsHtml += `<option value="${item.id}">${item.value}</option>`;
        }

        newRow.innerHTML = `
            <td><span class="step-number-display">${newIndex + 1}</span></td>
            <td>R${newIndex + 1}</td>
            <td>
                <input type="text" name="steps[${newIndex}][step_name]" class="form-control form-control-sm">
                <input type="hidden" name="steps[${newIndex}][step_number]" value="${newIndex + 1}" class="form-control form-control-sm">
            </td>
            <td class="approvers-cell">
                <div class="form-group mb-2">
                    <label for="steps-${newIndex}-approver_role" class="form-label">Select Approvers</label>
                    <select multiple name="steps[${newIndex}][approver_role][]" class="form-select form-select-sm mb-1 select360" id="steps-${newIndex}-approver_role" required data-placeholder="Select approver">
                        ${approverOptionsHtml}
                    </select>
                </div>
                <div class="form-group">
                    <label for="steps-${newIndex}-approver_user_id" class="form-label">Select Employee (Optional)</label>
                    <select multiple name="steps[${newIndex}][approver_user_id][]" class="form-select form-select-sm mb-1 select360" id="steps-${newIndex}-approver_user_id" data-placeholder="Select employee">
                        ${employeeOptionsHtml}
                    </select>
                </div>
            </td>
            <td class="d-none">
                <input type="number" name="steps[${newIndex}][allotted_time]" class="form-control form-control-sm">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-light d-none rounded py-1 px-2 m-1 fs-14 additional-settings-btn" data-bs-toggle="modal" data-bs-target="#additionalSettingsModal" data-step-index="${newIndex}">
                    <i class="ri-more-2-fill"></i>
                </button>
                <button type="button" class="btn btn-sm btn-light rounded py-1 px-2 fs-14 remove-step"><i class="ri-delete-bin-line"></i></button>
            </td>
        `;
        stepsContainer.appendChild(newRow);
        
        // Inisialisasi select2 pada baris baru
        newRow.querySelectorAll('.select360').forEach(el => {
            initializeSelect2(el);
            el.classList.remove('select360');
        });

        updateStepNumbersAndNames();
    });

    // Event listener untuk menghapus baris dan membuka modal (delegasi event)
    stepsContainer.addEventListener('click', function (event) {
        const removeBtn = event.target.closest('.remove-step');
        if (removeBtn) {
            if (stepsContainer.children.length > 1) {
                removeBtn.closest('tr').remove();
                updateStepNumbersAndNames();
            } else {
                alert('At least one approval step is required.');
            }
        }

        const settingsBtn = event.target.closest('.additional-settings-btn');
        if (settingsBtn) {
            const stepIndex = settingsBtn.dataset.stepIndex;
            modalCurrentStepIndexInput.value = stepIndex;
            modalStepDisplay.textContent = parseInt(stepIndex) + 1;

            // Isi modal dengan data yang ada
            resetModalForm();
            const existingSettings = getOldStepSettings(stepIndex);
            
            const hiddenInput = stepsContainer.querySelector(`tr[data-index="${stepIndex}"] input[name="steps[${stepIndex}][settings_json]"]`);
            let currentSettings = {};
            if(hiddenInput && hiddenInput.value) {
                currentSettings = JSON.parse(hiddenInput.value);
            }
            
            const finalSettings = {...existingSettings, ...currentSettings};

            // Isi setiap field di modal berdasarkan finalSettings
            $('#modal_settings_hide_stage_from').val(finalSettings.hide_stage_from);
            $('#modal_settings_form_visibility').val(finalSettings.form_visibility);
            // ... dan seterusnya untuk semua field
            
            additionalSettingsModal.show();
        }
    });

    // Event listener untuk tombol simpan di modal
    saveModalSettingsButton.addEventListener('click', function() {
        saveModalFormDataToRow();
        additionalSettingsModal.hide();
    });

    // Panggil sekali saat load untuk memastikan data old('settings_json') dimuat
    function loadInitialSettings() {
        const rows = stepsContainer.querySelectorAll('tr');
        rows.forEach((row, index) => {
            const settings = getOldStepSettings(index);
            if (Object.keys(settings).length > 0) {
                 let hiddenInput = row.querySelector(`input[name="steps[${index}][settings_json]"]`);
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = `steps[${index}][settings_json]`;
                    row.querySelector('td:last-child').appendChild(hiddenInput);
                }
                hiddenInput.value = JSON.stringify(settings);
            }
        });
    }
    
    loadInitialSettings();
});