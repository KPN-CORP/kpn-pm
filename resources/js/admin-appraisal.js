import $ from 'jquery';

import Swal from "sweetalert2";
window.Swal = Swal;

$(document).ready(function() {
    $('#adminAppraisalTable').DataTable({
        stateSave: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csvHtml5',
                text: '<i class="ri-download-cloud-2-line fs-16 me-1"></i>Download Report',
                className: 'btn btn-sm btn-outline-success',
                title: 'Employee Data',
                exportOptions: {
                    columns: ':not(:last-child)', // Excludes the last column (Details)
                    format: {
                        body: function(data, row, column, node) {
                            // Check if the <td> has a 'data-id' attribute and use that for the export
                            var dataId = $(node).attr('data-id');
                            return dataId ? dataId : data; // Use the data-id value if available, else fallback to default text
                        }
                    }
                }
            }
        ],
        fixedColumns: {
            leftColumns: 0,
            rightColumns: 1
        },
        scrollCollapse: true,
        scrollX: true
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const appraisalId = document.getElementById('appraisal_id').value;
    const typeButtons = document.querySelectorAll('.type-button');
    const detailContent = document.getElementById('detailContent');
    const loadingSpinner = document.getElementById('loadingSpinner');

    typeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const contributorId = this.dataset.id
            const id = contributorId + '_' + appraisalId;

            console.log(id);

            // Check if id is null or undefined
            if (!contributorId) {
                detailContent.innerHTML =  `
                            <div class="alert alert-secondary" role="alert">
                                No data available for this item.
                            </div>
                        `;
                return; // Exit the function early if id is null or invalid
            }

            // Show loading spinner
            loadingSpinner.classList.remove('d-none');
            detailContent.innerHTML = '';

            // Make AJAX request
            fetch(`/admin-appraisal/get-detail-data/${id}`)
            .then(response => {
                // Hide the loading spinner
                loadingSpinner.classList.add('d-none');
                
                // Check if the response is successful (status code 200-299)
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response.text();
            })
            .then(html => {
                // Check if the response is empty
                if (!html.trim()) {
                    detailContent.innerHTML = `
                        <div class="alert alert-secondary" role="alert">
                            No data available for this item.
                        </div>
                    `;
                } else {
                    detailContent.innerHTML = html;
                }
            })
            .catch(error => {
                // Handle any errors, including network errors and non-OK responses
                loadingSpinner.classList.add('d-none');
                detailContent.innerHTML = `
                    <div class="alert alert-secondary" role="alert">
                        No data available for this item.
                    </div>
                `;
                console.error('Error:', error);
            });

        });
    });

});