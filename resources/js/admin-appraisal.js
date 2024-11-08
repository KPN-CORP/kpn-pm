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
                className: 'btn btn-sm btn-outline-success',
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
                text: '<i class="ri-file-excel-line fs-16 me-1"></i>Download Excel Report',
                className: 'btn btn-sm btn-outline-success',
                action: function (e, dt, node, config) {
                    // Get headers from DataTable (excluding the last column if needed)
                    let headers = dt.columns(':not(:last-child)').header().toArray().map(header => $(header).text().trim());
            
                    // Get all row nodes for DOM access and data content
                    let rowNodes = dt.rows({ filter: 'applied' }).nodes().toArray(); // Access row nodes for DOM manipulation
                    let rowData = dt.rows({ filter: 'applied' }).data().toArray(); // Get data content for each row
            
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
            
                    if (combinedData.length > 0) {
                        // Send a POST request with the headers and combined data
                        fetch('/export-appraisal-detail', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ headers: headers, data: combinedData })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.blob(); // Treat the response as a Blob for file download
                        })
                        .then(blob => {
                            // Create a download link for the Excel file
                            const url = window.URL.createObjectURL(blob);
                            const link = document.createElement('a');
                            link.href = url;
                            link.download = 'appraisal_report.xlsx';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to download the report.');
                        });
                    } else {
                        alert('No employees found in the current table view.');
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