import $ from 'jquery';

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
        showLoader();
        initDataTable('#tableFlow', window.tableUrl, [
            { data: 'flow_name', name: 'flow_name' },
            { data: 'description', name: 'description' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]);
    }
    hideLoader();
});

$(document).ready(function () {

    $('.assignment-select, .module-select').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select an option',
        allowClear: true,
        closeOnSelect: false,
        width: '100%'
    });

    // Hitung row index awal sesuai jumlah row yang ada
    let rowIndex = $('#flows-container tr').length;

    initSelect2();
    updateDeleteButtons();

    $('#add-flow-btn').on('click', function () {
        let lastRow = $('#flows-container tr').last();
        let initiatorVal = lastRow.find('.initiator-select').val();
        let approvalVal = lastRow.find('.approvalflow-select').val();

        lastRow.find('select').removeClass('is-invalid');

        if (!initiatorVal || !approvalVal) {
            if (!initiatorVal) lastRow.find('.initiator-select').addClass('is-invalid');
            if (!approvalVal) lastRow.find('.approvalflow-select').addClass('is-invalid');
            return;
        }

        let newRow = `
            <tr class="flow-row" data-index="${rowIndex}">
                <td><span class="row-number">${rowIndex + 1}</span></td>
                <td>
                    <select name="initiator[${rowIndex}][role]" 
                            class="form-select form-select-sm flow-select initiator-select" 
                            required>
                        <option></option>
                        ${generateRoleOptions(approverRolesData)}
                    </select>
                </td>
                <td>
                    <select name="initiator[${rowIndex}][approval_flow]"
                            class="form-select form-select-sm flow-select approvalflow-select" 
                            required>
                        <option></option>
                        ${generateApprovalOptions(approvalFlowData)}
                    </select>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger delete-row-btn">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#flows-container').append(newRow);

        initSelect2();
        updateDeleteButtons();

        rowIndex++;
    });

    $(document).on('click', '.delete-row-btn', function () {
        $(this).closest('tr').remove();
        updateRowNumbers();
        updateDeleteButtons();
    });

    function initSelect2() {
        $('.flow-select').select2({
            theme: 'bootstrap-5',
            placeholder: "Select...",
            allowClear: true,
            width: '100%'
        });
    }

    function generateRoleOptions(data) {
        let html = '';
        for (const [key, value] of Object.entries(data)) {
            if (!isNaN(key)) {
                html += `<option value="role|${key}|${value}">${value}</option>`;
            } else {
                html += `<option value="state|${key.toLowerCase()}|${value}">${value}</option>`;
            }
        }
        return html;
    }

    function generateApprovalOptions(data) {
        let html = '';
        for (const [id, name] of Object.entries(data)) {
            html += `<option value="${id}|${name}">${name}</option>`;
        }
        return html;
    }

    function updateRowNumbers() {
        $('#flows-container .row-number').each(function (index) {
            $(this).text(index + 1);
        });
    }

    function updateDeleteButtons() {
        let rows = $('#flows-container tr');
        rows.each(function (index) {
            let btn = $(this).find('.delete-row-btn');
            if (index === 0) {
                btn.remove();
            } else {
                if (btn.length === 0) {
                    $(this).find('td:last').html(`
                        <button type="button" class="btn btn-sm btn-outline-danger delete-row-btn">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    `);
                }
            }
        });
    }
});
