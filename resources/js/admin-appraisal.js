import $ from 'jquery';

import Swal from "sweetalert2";
window.Swal = Swal;

$(document).ready(function() {
    $('#adminAppraisalTable').DataTable({
        stateSave: true,
        buttons: [
            'copy', 'csv'
        ],
        fixedColumns: {
            leftColumns: 0,
            rightColumns: 1
        },
        scrollCollapse: true,
        scrollX: true // Enable horizontal scrolling for large tables
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
                .then(response => response.text())
                .then(html => {
                    // Hide the loading spinner
                    loadingSpinner.classList.add('d-none');

                    // Check if the response is empty
                    if (!html.trim()) {
                        detailContent.innerHTML = `
                            <div class="alert alert-info" role="alert">
                                No data available for this item.
                            </div>
                        `;
                    } else {
                        detailContent.innerHTML = html;
                    }
                }).catch(error => {
                    // Handle any errors
                    loadingSpinner.classList.add('d-none');
                    detailContent.innerHTML = `
                        <div class="alert alert-danger" role="alert">
                            Error loading data. Please try again.
                        </div>
                    `;
                    console.error('Error:', error);
                });
        });
    });

});