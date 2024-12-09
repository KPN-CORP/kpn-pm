import { log } from 'handlebars';
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
                className: 'btn btn-sm btn-outline-success me-1 mb-1',
                title: 'PA Details',
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
            },
            {
                text: '<i class="ri-download-cloud-2-line fs-16 me-1 download-detail-icon"></i><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>Download Report Details',
                className: 'btn btn-sm btn-outline-success mb-1 report-detail-btn',
                available: function() {
                    return $('#permission-reportpadetail').data('report-pa-detail') === true;
                },
                action: function (e, dt, node, config) {
                    // Get headers from DataTable (excluding the last column if needed)
                    let headers = dt.columns(':not(:last-child)').header().toArray().map(header => $(header).text().trim());
            
                    // Get all row nodes for DOM access and data content
                    let rowNodes = dt.rows({ filter: 'applied' }).nodes().toArray(); // Access row nodes for DOM manipulation
                    let rowData = dt.rows({ filter: 'applied' }).data().toArray(); // Get data content for each row
            
                     // To hold the interval ID so we can stop it
                    let fileName = `appraisal_details_${userID}.xlsx`;
                    
                    // Combine headers with data and data-id for each row
                    let combinedData = rowData.map((row, rowIndex) => {
                        let rowObject = {};
                        
                        // Loop through each cell in the row, excluding the last column
                        row.slice(0, -1).forEach((cellContent, colIndex) => {
                            // Get the corresponding header for this column
                            let header = headers[colIndex];
                            
                            // Get the cell node to access its data-id attribute
                            let cellNode = $(rowNodes[rowIndex]).find('td').eq(colIndex);
                            let dataId = cellNode.attr('data-id'); // Get data-id attribute if present
                            
                            // Set each cell as a key-value pair with header as the key
                            rowObject[header] = {
                                dataId: dataId ? dataId : cellContent // Include dataId if present, otherwise set to null
                            };
                        });
                        
                        return rowObject; // Each row is an object with header keys
                    });

                    let reportDetailButton = document.querySelector('.report-detail-btn');
                    const spinner = reportDetailButton.querySelector(".spinner-border");
                    const icon = reportDetailButton.querySelector(".download-detail-icon");

                    let checkInterval;
            
                    if (combinedData.length > 0) {
                        document.querySelectorAll('.report-detail-btn').forEach(function(button) {
                            button.disabled = true;
                        });
                        spinner.classList.remove("d-none");
                        icon.classList.add("d-none");
                    
                        // Start the export process
                        fetch('/export-appraisal-detail', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ headers: headers, data: combinedData })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.message === 'Export is being processed in the background.') {
                                alert('The export is being processed. Please wait a moment.');
                    
                                // Start checking the file availability
                                startFileCheck(fileName); // Start checking for the file immediately
                            } else {
                                console.error('Unexpected response:', data);
                                alert('Failed to start export.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to start the export process.');
                        });
                    } else {
                        alert('No employees found in the current table view.');
                    }
                    
                    // Function to start checking for the file availability
                    function startFileCheck(file) {
                        let checkInterval;
                        let timeout;
                    
                        // Set a timeout to stop checking after 2 minutes (120 seconds)
                        timeout = setTimeout(() => {
                            clearInterval(checkInterval); // Stop checking after 2 minutes
                            alert('The file was not ready in time. Please try again later.');
                        }, 120000); // 120000 milliseconds = 2 minutes
                    
                        // Start checking for file availability every 10 seconds
                        checkInterval = setInterval(() => {
                            checkFileAvailability(file, checkInterval, timeout); // Pass the interval and timeout for cleanup
                        }, 10000); // 10 seconds interval
                    }
                    
                    // Function to check if the file is available
                    function checkFileAvailability(file, checkInterval, timeout) {
                        fetch('/check-file', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ file: file })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                // If the file exists, trigger the download and stop checking
                                window.location.href = `/appraisal-details/download/${file}`;
                                clearInterval(checkInterval); // Stop the interval once the file is downloaded
                                clearTimeout(timeout); // Clear the timeout if the file is found
                    
                                // Re-enable buttons and reset the UI
                                document.querySelectorAll('.report-detail-btn').forEach(function(button) {
                                    button.disabled = false;
                                });
                                spinner.classList.add("d-none");
                                icon.classList.remove("d-none");
                    
                                // Optionally send a request to delete the file from the server
                                fetch(`/appraisal-details/delete/${file}`, {
                                    method: 'GET', // Assuming DELETE for cleanup
                                    headers: {
                                        'Content-Type': 'application/json',
                                    }
                                })
                                .then(response => response.json()) // Assuming the server responds with JSON
                                .catch(error => {
                                    console.error("Error deleting file:", error);
                                });
                            } else {
                                // File does not exist yet, log and continue checking
                                console.log(`${file} is not available yet. Re-checking...`);
                            }
                        })
                        .catch(error => {
                            console.error('Error checking file:', error);
                            alert('There was an error checking the file.');
                            clearInterval(checkInterval); // Stop checking on error
                            clearTimeout(timeout); // Stop the timeout if there's an error
                        });
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