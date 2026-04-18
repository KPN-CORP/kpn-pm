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


    const addStepButton = document.getElementById('add-step');
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('additionalSettingsModal');

        // ✅ Guard: pastikan element ada
        if (!modalEl) {
            console.warn('Modal #additionalSettingsModal not found');
            return;
        }

        const additionalSettingsModal = new bootstrap.Modal(modalEl);
    });    
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
    document.addEventListener('click', function (e) {
        const addStepButton = e.target.closest('#addStepButton');
        if (!addStepButton) return;

        let stepsContainer = document.getElementById('stepsContainer');

        // ✅ Guard wajib
        if (!stepsContainer) {
            console.warn('stepsContainer not found');
            return;
        }

        const newIndex = stepsContainer.querySelectorAll('tr').length;

        const newRow = document.createElement('tr');
        newRow.classList.add('align-middle');
        newRow.dataset.index = newIndex;

        // ✅ Safe build options
        let approverOptionsHtml = '<option></option>';
        if (typeof approverData === 'object') {
            Object.entries(approverData).forEach(([id, name]) => {
                approverOptionsHtml += `<option value="${name}">${name}</option>`;
            });
        }

        let employeeOptionsHtml = '<option></option>';
        if (typeof employeeData === 'object') {
            Object.entries(employeeData).forEach(([id, item]) => {
                employeeOptionsHtml += `<option value="${item.id}">${item.value}</option>`;
            });
        }

        newRow.innerHTML = `
            <td><span class="step-number-display">${newIndex + 1}</span></td>
            <td>R${newIndex + 1}</td>
            <td>
                <input type="text" name="steps[${newIndex}][step_name]" class="form-control form-control-sm">
                <input type="hidden" name="steps[${newIndex}][step_number]" value="${newIndex + 1}">
            </td>
            <td class="approvers-cell">
                <div class="form-group mb-2">
                    <label class="form-label">Select Approvers</label>
                    <select multiple name="steps[${newIndex}][approver_role][]" 
                        class="form-select form-select-sm mb-1 select360"
                        data-placeholder="Select approver" required>
                        ${approverOptionsHtml}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Select Employee (Optional)</label>
                    <select multiple name="steps[${newIndex}][approver_user_id][]" 
                        class="form-select form-select-sm mb-1 select360"
                        data-placeholder="Select employee">
                        ${employeeOptionsHtml}
                    </select>
                </div>
            </td>
            <td class="d-none">
                <input type="number" name="steps[${newIndex}][allotted_time]" class="form-control form-control-sm">
            </td>
            <td class="text-center">
                <button type="button"
                    class="btn btn-sm btn-light d-none rounded py-1 px-2 m-1 fs-14 additional-settings-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#additionalSettingsModal"
                    data-step-index="${newIndex}">
                    <i class="ri-more-2-fill"></i>
                </button>

                <button type="button"
                    class="btn btn-sm btn-light rounded py-1 px-2 fs-14 remove-step">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </td>
        `;

        stepsContainer.appendChild(newRow);

        // ✅ Init select2 (safe)
        newRow.querySelectorAll('.select360').forEach(el => {
            if (typeof initializeSelect2 === 'function') {
                initializeSelect2(el);
            }
            el.classList.remove('select360');
        });

        // ✅ Update numbering
        if (typeof updateStepNumbersAndNames === 'function') {
            updateStepNumbersAndNames();
        }
    });

    // Event listener untuk menghapus baris dan membuka modal (delegasi event)
    let stepsContainer = document.getElementById('stepsContainer');

    if (stepsContainer) {

        stepsContainer.addEventListener('click', function (event) {

            // =========================
            // REMOVE STEP
            // =========================
            const removeBtn = event.target.closest('.remove-step');
            if (removeBtn) {

                if (stepsContainer.children.length > 1) {
                    const row = removeBtn.closest('tr');
                    if (row) row.remove();

                    if (typeof updateStepNumbersAndNames === 'function') {
                        updateStepNumbersAndNames();
                    }
                } else {
                    alert('At least one approval step is required.');
                }

                return; // stop lanjut ke logic lain
            }

            // =========================
            // OPEN SETTINGS MODAL
            // =========================
            const settingsBtn = event.target.closest('.additional-settings-btn');
            if (!settingsBtn) return;

            const stepIndex = settingsBtn.dataset.stepIndex;

            // ✅ Guard element modal
            if (!stepIndex) return;

            if (typeof modalCurrentStepIndexInput !== 'undefined' && modalCurrentStepIndexInput) {
                modalCurrentStepIndexInput.value = stepIndex;
            }

            if (typeof modalStepDisplay !== 'undefined' && modalStepDisplay) {
                modalStepDisplay.textContent = parseInt(stepIndex) + 1;
            }

            // Reset modal
            if (typeof resetModalForm === 'function') {
                resetModalForm();
            }

            // Ambil existing settings
            let existingSettings = {};
            if (typeof getOldStepSettings === 'function') {
                existingSettings = getOldStepSettings(stepIndex) || {};
            }

            // Ambil dari hidden input
            let currentSettings = {};
            try {
                const hiddenInput = stepsContainer.querySelector(
                    `tr[data-index="${stepIndex}"] input[name="steps[${stepIndex}][settings_json]"]`
                );

                if (hiddenInput && hiddenInput.value) {
                    currentSettings = JSON.parse(hiddenInput.value);
                }
            } catch (err) {
                console.warn('Invalid JSON in settings_json', err);
            }

            const finalSettings = { ...existingSettings, ...currentSettings };

            // =========================
            // SET VALUE KE MODAL (SAFE)
            // =========================
            if (window.$) {
                $('#modal_settings_hide_stage_from').val(finalSettings.hide_stage_from ?? null);
                $('#modal_settings_form_visibility').val(finalSettings.form_visibility ?? null);
                // tambahkan field lain di sini
            }

            // =========================
            // SHOW MODAL (SAFE)
            // =========================
            const modalEl = document.getElementById('additionalSettingsModal');

            if (!modalEl) {
                console.warn('Modal #additionalSettingsModal not found');
                return;
            }

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });

    } else {
        console.warn('stepsContainer not found');
    }

    // Event listener untuk tombol simpan di modal
    document.addEventListener('DOMContentLoaded', function () {

        const saveModalSettingsButton = document.getElementById('saveModalSettingsButton');
        const modalElement = document.getElementById('additionalSettingsModal');

        if (!saveModalSettingsButton || !modalElement) return;

        const additionalSettingsModal = bootstrap.Modal.getOrCreateInstance(modalElement);

        saveModalSettingsButton.addEventListener('click', function () {
            try {
                if (typeof saveModalFormDataToRow === 'function') {
                    saveModalFormDataToRow();
                }

                additionalSettingsModal.hide();

            } catch (error) {
                console.error('Error saving modal settings:', error);
            }
        });

    });

    // Panggil sekali saat load untuk memastikan data old('settings_json') dimuat
    function loadInitialSettings() {

        if (!stepsContainer) return;

        const rows = stepsContainer.querySelectorAll('tr');

        rows.forEach((row, index) => {

            const settings = getOldStepSettings?.(index) || {};

            if (!settings || typeof settings !== 'object') return;

            if (Object.keys(settings).length === 0) return;

            let hiddenInput = row.querySelector(`input[name="steps[${index}][settings_json]"]`);

            // Cari container terakhir dengan fallback
            const containerCell = row.querySelector('td:last-child') || row;

            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = `steps[${index}][settings_json]`;

                containerCell.appendChild(hiddenInput);
            }

            try {
                hiddenInput.value = JSON.stringify(settings);
            } catch (error) {
                console.error('Failed to stringify settings:', error);
            }

        });
    }
    loadInitialSettings();
});