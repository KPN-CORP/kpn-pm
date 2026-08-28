import $ from 'jquery';

document.addEventListener("DOMContentLoaded", function () {

    const layerTable = $("#layerTable").DataTable({
        dom: "lrtip",
        stateSave: true,
        fixedColumns: {
            leftColumns: 0,
            rightColumns: 1
        },
        pageLength: 25,
        scrollCollapse: true,
        scrollX: true
    });

    $('#customsearch').val(layerTable.search());

    $("#customsearch").on("keyup", function () {
        layerTable.search($(this).val()).draw();
    });

});

$(document).ready(function() {
    $('.open-import-modal').on('click', function() {
        var importModal = document.getElementById('importModal');
        
        // Initialize the Bootstrap modal
        var modal = new bootstrap.Modal(importModal);
        
        modal.show();
    });
});

function viewHistory(employeeId) {
    showLoader();
    fetch('/history-show', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ employee_id: employeeId })
    })
    .then(response => response.json())
    .then(data => {
        // Clear existing rows in the table body
        const tableBody = document.querySelector('#viewModal tbody');
        tableBody.innerHTML = '';

        // Populate table with new data
        data.forEach((item, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${item.fullname}</td>
                <td>
                    ${item.layers.split('|').map((layer, i) => `L${layer}: ${item.approver_names.split('|')[i]}`).join('<br>')}
                </td>
                <td>${item.name}</td>
                <td>${item.updated_at}</td>
            `;
            tableBody.appendChild(row);
        });
        hideLoader();
        var viewModal = document.getElementById('viewModal');
        // Initialize the Bootstrap modal
        var modal = new bootstrap.Modal(viewModal);
        
        modal.show();
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

window.viewHistory = viewHistory;

$(document).ready(function() {

// Apply filter when location dropdown value changes
$('#locationFilter').on('change', function() {
    applyLocationFilter(table);
});

// Apply filter when table is redrawn (e.g., when navigating to next page)
// table.on('draw.dt', function() {
//     applyLocationFilter(table);
// });
});

function applyLocationFilter(table) {
var locationId = $('#locationFilter').val().toUpperCase();

// Filter table based on location
table.column(10).search(locationId).draw(); // Adjust index based on your table structure
}


$(document).on('click', '.open-edit-modal', function() {
    var employeeId = $(this).data('bsEmployee-id');
    
    var fullname = $(this).data('bsFullname');
    var app = $(this).data('bsApp');
    $('#employeeId').text(employeeId);
    
    
    var layer = $(this).data('bsLayer');
    var appname = $(this).data('bsApp-name');
    
    // populateModal(employeeId, fullname, app, layer, appname);
    populateModal(employeeId, fullname, app, layer, appname, employeesData);
});

function populateModal(employeeId, fullName, app, layer, appName, employees) {

    $('#employee_id').val(employeeId);
    $('#employeeId').val(employeeId);
    $('#fullname').val(fullName+' - '+employeeId);

    let apps = [];
    let layers = [];
    let appNames = [];

    // if (typeof app === 'string' && app.indexOf("|") !== -1) {
    if (app.includes('|')) {
        // Jika nilai app mengandung karakter '|', lakukan pemisahan
        apps = app.split('|');
        layers = layer.split('|');
        appNames = appName.split('|');
    } else {
        // Jika tidak mengandung karakter '|', gunakan nilai langsung
        apps = [app]; // Ubah ke array untuk konsistensi
        layers = [layer];
        appNames = [appName];
    }

    $('#viewlayer').empty();
    $('#nikAppInputs').empty();
    var layerIndex = 1;

    if((apps.length+3)>6){
        var maxlayer = 6;
    }else{
        var maxlayer = (apps.length+3);
    }

    for (var i = 0; i < maxlayer; i++) {
        var selectOptions = "<option></option>";
        for (var j = 0; j < employees.length; j++) {
            var selected = (employees[j].employee_id == apps[i]) ? 'selected' : 'Select Employee';
            selectOptions += '<option value="' + employees[j].employee_id + '" ' + selected + '>' + employees[j].fullname + ' - ' + employees[j].employee_id + '</option>';
        }

        var disabled = (i > apps.length) ? 'disabled' : ''; // Disable additional layers initially
        var required = (i == 0) ? 'required' : '';
        $('#viewlayer').append('<div class="row mb-2"><label class="col-md-2 col-form-label">Layer ' + layerIndex + '</label><div class="col"><select name="nik_app[]" class="form-select select2"' + disabled + ' ' + required + '>' + selectOptions + '</select></div></div>');
        layerIndex++;
    }

    // Initialize Select2
    $('.select2').select2({
        dropdownParent: $('#editModal'),
        placeholder: 'Select Layer Name',
        theme: "bootstrap-5",
        width: '100%',
        allowClear: true
    });

    // Remove, from every layer's dropdown, the employees already chosen in the
    // other layers: if User A is picked in Layer 1, User A no longer appears in
    // Layer 2, 3, ... The employee chosen in a layer always stays in its own list.
    //
    // IMPORTANT: select only "select.select2". After Select2 initialises it also
    // inserts a <span class="select2 select2-container"> wrapper, so a bare
    // ".select2" selector matches that span too - writing options into it would
    // wipe out the rendered widget and print the names as plain text.
    // This works incrementally. The employee list holds thousands of entries, so
    // rebuilding every option of every layer would create tens of thousands of
    // DOM nodes on each run and freeze the page ("Page Unresponsive"). Only the
    // handful of options that actually became taken or free are touched.
    function refreshLayerOptions() {
        var $selects = $('#viewlayer select.select2');

        // Snapshot what each layer currently holds.
        var chosen = [];
        $selects.each(function () {
            chosen.push(this.value ? String(this.value) : '');
        });

        $selects.each(function (idx) {
            var select = this;
            var own = chosen[idx];

            // Employees taken by the OTHER layers.
            var taken = {};
            for (var t = 0; t < chosen.length; t++) {
                if (t !== idx && chosen[t]) {
                    taken[chosen[t]] = true;
                }
            }

            // Remove options now taken elsewhere; note which ones remain.
            var present = {};
            for (var o = select.options.length - 1; o >= 0; o--) {
                var v = select.options[o].value;
                if (!v) {
                    continue;
                }
                if (v !== own && taken[v]) {
                    select.remove(o);
                } else {
                    present[v] = true;
                }
            }

            // Re-insert options that became free again, preserving the original
            // ordering by anchoring on the next employee still in the list.
            for (var k = 0; k < employees.length; k++) {
                var eid = String(employees[k].employee_id);
                if (taken[eid] || present[eid]) {
                    continue;
                }

                var anchor = null;
                for (var m = k + 1; m < employees.length; m++) {
                    if (present[String(employees[m].employee_id)]) {
                        anchor = select.querySelector('option[value="' + employees[m].employee_id + '"]');
                        if (anchor) {
                            break;
                        }
                    }
                }

                select.insertBefore(new Option(employees[k].fullname + ' - ' + eid, eid), anchor);
                present[eid] = true;
            }
        });
    }

    // The change handler below cascades (it triggers "change" on the following
    // layers), so coalesce the whole burst into a single run once it settles.
    var exclusionPending = false;
    function scheduleLayerOptions() {
        if (exclusionPending) {
            return;
        }
        exclusionPending = true;
        setTimeout(function () {
            exclusionPending = false;
            refreshLayerOptions();
        }, 0);
    }

    // Add change event listener to enable the next layer only if the current one is selected
    $('#viewlayer .select2').each(function (index) {
        $(this).on('change', function () {
            if ($(this).val() !== '') {
                // Enable the next select element if current selection is not empty
                for (var i = index + 2; i < $('#viewlayer .select2').length; i++) {
                    if(i === index + 2){
                        $('#viewlayer .select2').eq(i).val('').prop('disabled', false).trigger('change');
                    }
                }
            } else {
                // Disable the subsequent select elements if the current one is cleared
                for (var i = index + 2; i < $('#viewlayer .select2').length; i++) {
                    $('#viewlayer .select2').eq(i).val('').prop('disabled', true).trigger('change');
                }
            }

            // Keep the other layers free of already-picked employees.
            scheduleLayerOptions();
        });
    });

    // Apply the exclusion to the values already saved when the modal opens.
    refreshLayerOptions();

    var editModal = document.getElementById('editModal');
    
    // Initialize the Bootstrap modal
    var modal = new bootstrap.Modal(editModal);
    
    modal.show();
}

$('#submitButton').on('click', function(e) {
    e.preventDefault();
    const form = $('#editForm').get(0);
    const submitButton = $('#submitButton');
    const spinner = submitButton.find(".spinner-border");

    if(form){
        if (form && form.checkValidity()) {
        // Disable submit button
        submitButton.prop('disabled', true);
        submitButton.addClass("disabled");
    
        // Remove d-none class from spinner if it exists
        if (spinner.length) {
            spinner.removeClass("d-none");
        }
    
        // Submit form
        form.submit();
        } else {
            // If the form is not valid, trigger HTML5 validation messages
            form.reportValidity();
        }
    }
});
$('#importButton').on('click', function(e) {
    e.preventDefault();
    const form = $('#importForm').get(0);
    const submitButton = $('#importButton');
    const spinner = submitButton.find(".spinner-border");

    if(form){
        if (form.checkValidity()) {
        // Disable submit button
        submitButton.prop('disabled', true);
        submitButton.addClass("disabled");
    
        // Remove d-none class from spinner if it exists
        if (spinner.length) {
            spinner.removeClass("d-none");
        }
    
        // Submit form
        form.submit();
        } else {
            // If the form is not valid, trigger HTML5 validation messages
            form.reportValidity();
        }
    }
});