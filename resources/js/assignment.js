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
        initDataTable('#tableAssignment', window.tableUrl, [
            { 
                data: null, 
                name: 'rownum', 
                orderable: false, 
                searchable: false,
                render: function (data, type, row, meta) {
                    // nomor urut sesuai halaman
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'name', name: 'name' },
            { data: 'created_at', name: 'created_at' },
            { data: 'updated_at', name: 'updated_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]);
    }
});


$(document).ready(function() {
    // Initialize select2 for all existing rows
    $('#attributes-container').find('select').each(function() {
        initializeSelect2(this);
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-delete').forEach(function (button) {
        button.addEventListener('click', function () {
            const form = this.closest('form');

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});

// Fungsi untuk menginisialisasi Select2 pada sebuah elemen
function initializeSelect2(element) {
    $(element).select2({
        theme: 'bootstrap-5',
        placeholder: 'Select an option',
        allowClear: true,
        closeOnSelect: false
    });
}

// 2. FUNGSI UTAMA UNTUK MENGUPDATE KOLOM "VALUE"
function updateValueColumn(row) {
    const attributeSelect = row.querySelector('.attribute-select');
    const valueCell = row.querySelector('.value-cell');
    const selectedAttribute = attributeSelect.value;
    const rowIndex = row.dataset.index;

    // Hancurkan instance Select2 yang mungkin ada sebelum mengosongkan sel
    if ($(valueCell).find('.select2-hidden-accessible').length > 0) {
        $(valueCell).find('select').select2('destroy');
    }
    valueCell.innerHTML = ''; // Selalu kosongkan sel value

    if (selectedAttribute && attributeValueMap[selectedAttribute]) {
        const values = attributeValueMap[selectedAttribute];
        const valueSelect = document.createElement('select');
        valueSelect.name = `attributes[${rowIndex}][value][]`;
        valueSelect.className = 'form-select form-select-sm value-select';
        valueSelect.required = true;
        valueSelect.multiple = true;
        valueSelect.dataset.placeholder = 'Select a value';
        
        // Tambahkan opsi berdasarkan atribut yang dipilih
        valueSelect.innerHTML = '<option></option>'; // Opsi kosong untuk placeholder
        values.forEach(value => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            valueSelect.appendChild(option);
        });

        valueCell.appendChild(valueSelect);
        initializeSelect2(valueSelect);
    }
}

// 3. FUNGSI UNTUK MEMPERBARUI NOMOR URUT
function updateRowNumbers() {
    const container = document.getElementById('attributes-container');
    const rows = container.querySelectorAll('.attribute-row');
    rows.forEach((row, index) => {
        row.querySelector('.row-number').textContent = index + 1;
        row.dataset.index = index;
        row.querySelector('.attribute-select').name = `attributes[${index}][name]`;
        const valueSelect = row.querySelector('.value-select');
        if (valueSelect) {
            valueSelect.name = `attributes[${index}][value][]`;
        }
    });
}

// 4. EVENT LISTENER MENGGUNAKAN JQUERY (FIXED)
$(document).ready(function() {
    const container = $('#attributes-container');

    // Inisialisasi Select2 untuk baris pertama yang sudah ada
    initializeSelect2(container.find('.attribute-select'));

    // Listener untuk perubahan pada dropdown atribut (Event Delegation dengan jQuery)
    container.on('change', '.attribute-select', function() {
        const row = this.closest('.attribute-row');
        updateValueColumn(row);
    });

    // Listener untuk klik pada tombol hapus (Event Delegation dengan jQuery)
    container.on('click', '.delete-row-btn', function() {
        $(this).closest('.attribute-row').remove();
        updateRowNumbers();
    });

    // Listener untuk tombol "Add Another Attribute"
    $('#add-attribute-btn').on('click', function() {
        const newIndex = container.find('.attribute-row').length;

        let attributeOptionsHtml = '<option></option>'; // Opsi kosong untuk placeholder
        for (const attributeName in attributeValueMap) {
            attributeOptionsHtml += `<option value="${attributeName}">${attributeName}</option>`;
        }

        const newRowHtml = `
            <tr class="attribute-row" data-index="${newIndex}">
                <td><span class="row-number">${newIndex + 1}</span></td>
                <td>
                    <select name="attributes[${newIndex}][name]" class="form-select form-select-sm attribute-select" required data-placeholder="Select an attribute">
                        ${attributeOptionsHtml}
                    </select>
                </td>
                <td class="value-cell"></td>
                <td class="action-cell text-center">
                    <button type="button" class="btn btn-sm btn-danger delete-row-btn p-1">
                        <i class="ri-delete-bin-line px-1"></i>
                    </button>
                </td>
            </tr>
        `;

        const newRow = $(newRowHtml);
        container.append(newRow);

        // Inisialisasi Select2 pada dropdown atribut yang baru dibuat
        initializeSelect2(newRow.find('.attribute-select'));
    });
});