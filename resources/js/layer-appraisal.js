import $ from 'jquery';

document.addEventListener("DOMContentLoaded", function () {

    const layerAppraisalTable = $("#layerAppraisalTable").DataTable({
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

    $("#customsearch").on("keyup", function () {
        layerAppraisalTable.search($(this).val()).draw();
    });

    $(document).ready(function() {
        $('.selection2').select2({
            minimumInputLength: 1,
            theme: 'bootstrap-5',
            ajax: {
                url: '/search-employee', // Route for your Laravel search endpoint
                dataType: 'json',
                delay: 250, // Wait 250ms before triggering request (debounce)
                data: function (params) {
                    return {
                        searchTerm: params.term, // Search term entered by the user
                        employeeId: $('#employee_id').val()
                    };
                },
                processResults: function (data) {
                    // Map the data to Select2 format
                    return {
                        results: $.map(data, function (item) {
                            return {
                                id: item.employee_id, // ID field for Select2
                                text: item.fullname + ' ' + item.employee_id // Text to display in Select2
                            };
                        })
                    };
                },
                cache: true
            }
        });
    });    

});

$(document).ready(function(){
    const maxCalibrators = 10;

    $('#add-calibrator').on('click', function() {
        showLoader();
        if (calibratorCount < maxCalibrators) {
            calibratorCount++;
            
            // Create the new calibrator row with dynamic employee options
            let options = '<option value="">- Please Select -</option>';
    
            const newCalibrator = `
                <div class="row mb-2" id="calibrator-row-${calibratorCount}">
                    <div class="col-10">
                        <h5>Calibrator ${calibratorCount}</h5>
                        <select name="calibrators[]" id="calibrator${calibratorCount}" class="form-select selection2">
                            ${options}
                        </select>
                    </div>
                    <div class="col-2 d-flex align-items-end justify-content-end">
                        <div class="mt-1">
                            <a class="btn btn-outline-danger rounded remove-calibrator" data-calibrator-id="${calibratorCount}">
                            <i class="ri-delete-bin-line"></i>
                            </a>
                        </div>
                    </div>
                </div>
            `;
    
            $('#calibrator-container').append(newCalibrator);

            $('.selection2').select2({
                minimumInputLength: 1,
                theme: 'bootstrap-5',
                ajax: {
                    url: '/search-employee', // Route for your Laravel search endpoint
                    dataType: 'json',
                    delay: 250, // Wait 250ms before triggering request (debounce)
                    data: function (params) {
                        return {
                            searchTerm: params.term, // Search term entered by the user
                            employeeId: $('#employee_id').val()
                        };
                    },
                    processResults: function (data) {
                        // Map the data to Select2 format
                        return {
                            results: $.map(data, function (item) {
                                return {
                                    id: item.employee_id, // ID field for Select2
                                    text: item.fullname + ' ' + item.employee_id // Text to display in Select2
                                };
                            })
                        };
                    },
                    cache: true
                }
            });

            // updateRemoveButtons();
        } else {
            Swal.fire({
                title: "Oops!",
                text: "You've reached the maximum number of Calibrator",
                icon: "error",
                confirmButtonColor: "#3e60d5",
                confirmButtonText: "OK",
            });
        }
        hideLoader();
    });

    function updateRemoveButtons() {
        // $('.remove-calibrator').prop('disabled', false); // Enable all remove buttons
        $(`#calibrator-row-${calibratorCount} .remove-calibrator`).prop('disabled', false); // Ensure the latest one is enabled
    }

    $(document).on('click', '.remove-calibrator', function() {
        // Always remove the latest calibrator row
        $(`#calibrator-row-${calibratorCount}`).remove(); 
        calibratorCount--;
        updateRemoveButtons(); // Update buttons visibility
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

document.addEventListener('DOMContentLoaded', function () {
    const detailModal = document.getElementById('detailModal');
    
    detailModal.addEventListener('show.bs.modal', async function (event) {
        const button = event.relatedTarget;
        const employeeId = button.getAttribute('data-bs-id');
        
        // Show loading state before fetching data
        showLoadingState();

        try {
            // Fetch the employee details using async/await
            const data = await fetchEmployeeDetails(employeeId);
            
            // Populate the modal with the retrieved data
            populateModal(data);
            
            // Populate history table
            populateHistoryTable(data.history);
        } catch (error) {
            console.error('Error fetching employee details:', error);
            showErrorMessage('Unable to retrieve employee details. Please try again.');
        } finally {
            // Hide loading state
            hideLoadingState();
        }
    });

    // Function to fetch employee details from the backend
    async function fetchEmployeeDetails(employeeId) {
        const response = await fetch(`/employee-layer-appraisal/details/${employeeId}`);
                
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        return await response.json();
    }

    // Function to show loading indicator (can be a spinner or overlay)
    function showLoadingState() {
        const modalBody = detailModal.querySelector('#historyTable tbody');
        modalBody.innerHTML = '<div class="loading-spinner">Loading...</div>';
    }

    // Function to hide loading indicator
    function hideLoadingState() {
        const loadingSpinner = detailModal.querySelector('.loading-spinner');
        if (loadingSpinner) {
            loadingSpinner.remove();
        }
    }

    // Function to populate modal fields with employee data
    function populateModal(data) {
        detailModal.querySelector('.fullname').textContent = data.fullname || 'N/A';
        detailModal.querySelector('.employee_id').textContent = data.employee_id || 'N/A';
        detailModal.querySelector('.formattedDoj').textContent = data.formattedDoj || 'N/A';
        detailModal.querySelector('.group_company').textContent = data.group_company || 'N/A';
        detailModal.querySelector('.company_name').textContent = data.company_name || 'N/A';
        detailModal.querySelector('.unit').textContent = data.unit || 'N/A';
        detailModal.querySelector('.designation').textContent = data.designation || 'N/A';
        detailModal.querySelector('.office_area').textContent = data.office_area || 'N/A';
    }

    // Function to populate history table with employee history data
    function populateHistoryTable(history) {
        const historyTableBody = detailModal.querySelector('#historyTable tbody');
        historyTableBody.innerHTML = ''; // Clear previous entries

        if (history.length === 0) {
            historyTableBody.innerHTML = '<tr><td colspan="4" class="text-center">No history available.</td></tr>';
            return;
        }

        history.forEach((entry, index) => {
            const row = `<tr>
                            <td>${entry.layer_type + ' ' + entry.layer || 'N/A'}</td>
                            <td>${entry.fullname + ' (' + entry.employee_id + ')' || 'N/A'}</td>
                            <td class="text-center">${entry.updated_by}</td>
                            <td class="text-center">${entry.updated_at || 'N/A'}</td>
                        </tr>`;
            historyTableBody.insertAdjacentHTML('beforeend', row);
        });
    }

    // Function to display an error message inside the modal
    function showErrorMessage(message) {
        const modalBody = detailModal.querySelector('#historyTable tbody');
        modalBody.innerHTML = `<div class="alert alert-danger">${message}</div>`;
    }
});

function applyLocationFilter(table) {
var locationId = $('#locationFilter').val().toUpperCase();

// Filter table based on location
table.column(10).search(locationId).draw(); // Adjust index based on your table structure
}

$('#submitButton').on('click', function(e) {
    e.preventDefault();
    const form = $('#editForm').get(0);
    const submitButton = $('#submitButton');
    const spinner = submitButton.find(".spinner-border");

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
});
$('#importButton').on('click', function(e) {
    e.preventDefault();
    const form = $('#importForm').get(0);
    const submitButton = $('#importButton');
    const spinner = submitButton.find(".spinner-border");

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
});