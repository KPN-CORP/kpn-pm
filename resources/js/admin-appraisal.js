import { log } from "handlebars";
import $ from "jquery";

import Swal from "sweetalert2";
window.Swal = Swal;

$(document).ready(function() {

    function getCurrentDateTime() {
        const now = new Date();
        const weekday = now.toLocaleString('en-US', { weekday: 'long' });
        const day = String(now.getDate()).padStart(2, '0'); // Add leading zero if single digit
        const month = String(now.getMonth() + 1).padStart(2, '0'); // Get month (0-11), so add 1
        const year = now.getFullYear();

        // Get hours and minutes for the time (in 12-hour format)
        const hours = now.getHours() % 12 || 12;  // 12-hour format, 0 becomes 12
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const ampm = now.getHours() >= 12 ? 'PM' : 'AM';  // AM/PM

        return `${weekday}, ${day}/${month}/${year} ${hours}:${minutes} ${ampm}`;
    }

    $('#adminAppraisalTable').DataTable({
        stateSave: true,
        dom: "Bfrtip",
        buttons: [
            {
                extend: "csvHtml5",
                text: '<i class="ri-download-cloud-2-line fs-16 me-1"></i>Download Report',
                className: "btn btn-sm btn-outline-success me-1 mb-1",
                title: "PA Details",
                exportOptions: {
                    columns: ":not(:last-child)", // Excludes the last column (Details)
                    format: {
                        body: function (data, row, column, node) {
                            // Check if the <td> has a 'data-id' attribute and use that for the export
                            var dataId = $(node).attr("data-id");
                            return dataId ? dataId : data; // Use the data-id value if available, else fallback to default text
                        },
                    },
                },
            },
            {
                text: '<i class="ri-download-cloud-2-line fs-16 me-1 download-detail-icon"></i><span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>Download Report Details',
                className:
                    "btn btn-sm btn-outline-success mb-1 report-detail-btn",
                action: function (e, dt, node, config) {
                    let headers = dt
                        .columns(":not(:last-child)")
                        .header()
                        .toArray()
                        .map((header) => $(header).text().trim());
                    let rowNodes = dt
                        .rows({ filter: "applied" })
                        .nodes()
                        .toArray();
                    let rowData = dt
                        .rows({ filter: "applied" })
                        .data()
                        .toArray();
                    const BATCH_SIZE = 500; // Number of rows per Excel file

                    // Calculate number of batches needed
                    const totalBatches = Math.ceil(rowData.length / BATCH_SIZE);

                    let combinedData = rowData.map((row, rowIndex) => {
                        let rowObject = {};
                        row.slice(0, -1).forEach((cellContent, colIndex) => {
                            let header = headers[colIndex];
                            let cellNode = $(rowNodes[rowIndex])
                                .find("td")
                                .eq(colIndex);
                            let dataId = cellNode.attr("data-id");
                            rowObject[header] = {
                                dataId: dataId ? dataId : cellContent,
                            };
                        });
                        return rowObject;
                    });

                    let reportDetailButton =
                        document.querySelector(".report-detail-btn");
                    const spinner =
                        reportDetailButton.querySelector(".spinner-border");
                    const icon = reportDetailButton.querySelector(
                        ".download-detail-icon"
                    );

                    if (combinedData.length > 0) {
                        document
                            .querySelectorAll(".report-detail-btn")
                            .forEach((button) => (button.disabled = true));
                        spinner.classList.remove("d-none");
                        icon.classList.add("d-none");

                        // Split data into batches
                        const batches = [];
                        for (
                            let i = 0;
                            i < combinedData.length;
                            i += BATCH_SIZE
                        ) {
                            batches.push(combinedData.slice(i, i + BATCH_SIZE));
                        }

                        // Start the export process for all batches
                        fetch("/export-appraisal-detail", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document
                                    .querySelector('meta[name="csrf-token"]')
                                    .getAttribute("content"),
                            },
                            body: JSON.stringify({
                                headers: headers,
                                data: combinedData,
                                batchSize: BATCH_SIZE,
                            }),
                        })
                            .then((response) => response.json())
                            .then((data) => {
                                if (
                                    data.message ===
                                    "Export is being processed in the background."
                                ) {
                                    alert(
                                        `The export is being processed in ${totalBatches} file(s). Please wait a moment.`
                                    );
                                    startBatchFileCheck(totalBatches);
                                } else {
                                    console.error("Unexpected response:", data);
                                    alert("Failed to start export.");
                                }
                            })
                            .catch((error) => {
                                console.error("Error:", error);
                                alert("Failed to start the export process.");
                                resetUI();
                            });
                    } else {
                        alert("No employees found in the current table view.");
                    }

                    function startBatchFileCheck(totalBatches) {
                        let checkInterval;
                        let timeout;
                        const userId = window.userID; // Make sure this is defined globally

                        timeout = setTimeout(() => {
                            clearInterval(checkInterval); // Stop checking after 2 minutes
                            alert('Hi there! It seems the Reports Details file is too large to download. Please try grouping it by Business Unit and then download again. Thank you!');
                            document.querySelectorAll('.report-detail-btn').forEach(function(button) {
                                button.disabled = false;
                            });
                            spinner.classList.add("d-none");
                            icon.classList.remove("d-none");
                        }, 1800000); // 300000 milliseconds = 5 minutes

                        // Start checking for file availability every 10 seconds
                        checkInterval = setInterval(() => {
                            checkFileAvailability(file, checkInterval, timeout); // Pass the interval and timeout for cleanup
                        }, 30000); // 10 seconds interval
                    }

                    // Function to check if the file is available
                    function checkFileAvailability(file, checkInterval, timeout) {
                        fetch('/check-file', {
                            method: 'POST',
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document
                                    .querySelector('meta[name="csrf-token"]')
                                    .getAttribute("content"),
                            },
                            body: JSON.stringify({ file: file }),
                        })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.exists) {
                                    downloadAndDelete(
                                        file,
                                        batchNumber,
                                        onComplete
                                    );
                                }
                            })
                            .catch((error) =>
                                console.error("Error checking file:", error)
                            );
                    }

                    function downloadAndDelete(file, batchNumber, onComplete) {
                        fetch(`/appraisal-details/download/${file}`)
                            .then((response) => {
                                if (!response.ok) {
                                    throw new Error(
                                        `HTTP error! status: ${response.status}`
                                    );
                                }
                                return response.blob();
                            })
                            .then((blob) => {
                                const link = document.createElement("a");
                                const url = URL.createObjectURL(blob);
                                link.href = url;
                                link.download = file;
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                                URL.revokeObjectURL(url);

                                // Delete the file after download
                                return fetch(
                                    `/appraisal-details/delete/${file}`
                                );
                            })
                            .then((response) => response.json())
                            .then(() => onComplete())
                            .catch((error) => {
                                console.error(
                                    "Error in download process:",
                                    error
                                );
                                alert(
                                    "There was an error downloading the file. Please try again."
                                );
                                onComplete();
                            });
                    }

                    function resetUI() {
                        document
                            .querySelectorAll(".report-detail-btn")
                            .forEach((button) => (button.disabled = false));
                        spinner.classList.add("d-none");
                        icon.classList.remove("d-none");
                    }
                },
            },
        ],
        fixedColumns: {
            leftColumns: 0,
            rightColumns: 1,
        },
        scrollCollapse: true,
        scrollX: true,
        initComplete: function(settings, json) {
            // Get the current date and time
            const dateTime = getCurrentDateTime();

            // Insert the current date/time below the buttons
            const dateTimeHTML = `<div class="text-right mt-2" id="currentDateTime">${dateTime}</div>`;

            // Append the date and time below the button container
            $(this).closest('.dataTables_wrapper').find('.dt-buttons').after(dateTimeHTML);
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const appraisalId = document.getElementById("appraisal_id").value;
    const typeButtons = document.querySelectorAll(".type-button");
    const detailContent = document.getElementById("detailContent");
    const loadingSpinner = document.getElementById("loadingSpinner");

    typeButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const contributorId = this.dataset.id;
            const id = contributorId + "_" + appraisalId;

            // Check if id is null or undefined
            if (!contributorId) {
                detailContent.innerHTML = `
                            <div class="alert alert-secondary" role="alert">
                                No data available for this item.
                            </div>
                        `;
                return; // Exit the function early if id is null or invalid
            }

            // Show loading spinner
            loadingSpinner.classList.remove("d-none");
            detailContent.innerHTML = "";

            // Make AJAX request
            fetch(`/admin-appraisal/get-detail-data/${id}`)
                .then((response) => {
                    // Hide the loading spinner
                    loadingSpinner.classList.add("d-none");

                    // Check if the response is successful (status code 200-299)
                    if (!response.ok) {
                        throw new Error(
                            `HTTP error! status: ${response.status}`
                        );
                    }

                    return response.text();
                })
                .then((html) => {
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
                .catch((error) => {
                    // Handle any errors, including network errors and non-OK responses
                    loadingSpinner.classList.add("d-none");
                    detailContent.innerHTML = `
                    <div class="alert alert-secondary" role="alert">
                        No data available for this item.
                    </div>
                `;
                    console.error("Error:", error);
                });
        });
    });
});
