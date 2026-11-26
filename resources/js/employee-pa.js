import $ from 'jquery';

// Settings > Employee (/admemployees): server-side DataTables.
// Dulu ~6.400 baris dirender sekaligus di Blade (HTML ~9 MB, ~7 detik).
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('employeePaTable');
    if (!table) {
        return;
    }

    const dataTable = $(table).DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: table.dataset.url,
            type: 'GET',
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'employee_id', name: 'employee_id' },
            { data: 'fullname', name: 'fullname' },
            { data: 'date_of_joining', name: 'date_of_joining' },
            { data: 'contribution_level_code', name: 'contribution_level_code' },
            { data: 'unit', name: 'unit' },
            { data: 'designation_name', name: 'designation_name' },
            { data: 'job_level', name: 'job_level' },
            { data: 'office_area', name: 'office_area' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'sorting_1 text-center' },
        ],
        // Tanpa order awal: server memakai urutan default (lokasi, nama, company).
        order: [],
        dom: 'lrtip',
        pageLength: 50,
        scrollCollapse: true,
        scrollX: true,
        fixedColumns: {
            leftColumns: 0,
            rightColumns: 1,
        },
    });

    // Dipakai tombol Terminate / Restore (inline script di view) untuk
    // me-refresh tabel tanpa reload halaman, sehingga search & halaman tetap.
    window.employeePaTable = dataTable;

    // Kotak search di atas tabel; debounce supaya tidak satu request per huruf.
    let searchTimer = null;
    // 'input' juga menangkap paste / autofill, bukan hanya ketikan.
    $('#customsearch').on('input keyup', function () {
        const value = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            if (dataTable.search() !== value) {
                dataTable.search(value).draw();
            }
        }, 400);
    });
});
