import $ from 'jquery';

import numeral from 'numeral';

import bootstrap from "bootstrap/dist/js/bootstrap.bundle.min.js";

document.addEventListener('DOMContentLoaded', function () {
    // Initialize all dropdowns on the page
    const dropdownTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
    const dropdownList = dropdownTriggerList.map(function (dropdownTriggerEl) {
        return new bootstrap.Dropdown(dropdownTriggerEl);
    });
});

function checkEmptyFields() {
    const alertField = $(".mandatory-field");
    alertField.html(`
        <div id="alertField" class="alert alert-danger alert-dismissible fade" role="alert" hidden>
            `+ errorMessages +`
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `);
    var requiredInputs = document.querySelectorAll(
        "input[required], select[required], textarea[required]"
    );
    for (var i = 0; i < requiredInputs.length; i++) {
        if (requiredInputs[i].value.trim() === "") {
            Swal.fire({
                title: errorAlertMessages,
                confirmButtonColor: "#3e60d5",
                icon: "error",
                didClose: () => {
                    // Show the alert field after the SweetAlert2 modal is closed
                    var alertField = $("#alertField");
                    alertField.removeAttr("hidden").addClass("show");
                },
            });
            return false; // Prevent form submission
        }
    }
    return true; // All required fields are filled
}

function validate() {
    var weight = document.querySelectorAll('input[name="weightage[]"]');
    var sum = 0;
    for (var i = 0; i < weight.length; i++) {
        sum += parseInt(weight[i].value) || 0; // Parse input value to integer, default to 0 if NaN
    }

    if (sum != 100) {
        Swal.fire({
            title: "Submission failed",
            html: `Your current weightage is ${sum}%, <br>Please adjust to reach the total weightage of 100%`,
            confirmButtonColor: "#3e60d5",
            icon: "error",
            // If confirmed, proceed with form submission
        });
        return false; // Prevent form submission
    }

    return true; // Allow form submission
}

function validateWeightage() {
    // Get all input elements with name="weightage[]"
    var weightageInputs = document.getElementsByName("weightage[]");

    // Iterate through each input element
    for (var i = 0; i < weightageInputs.length; i++) {
        var input = weightageInputs[i];

        // Get the value of the input (convert to number)
        var value = parseFloat(input.value);

        // Check if value is below 5%
        if (value < 5) {
            // Display alert message
            Swal.fire({
                title: "The weightage cannot lower than 5%",
                confirmButtonColor: "#3e60d5",
                icon: "error",
                // If confirmed, proceed with form submission
            });
            weightageInputs.focus();
            return false; // Prevent form submission
        }
    }

    return true; // All weightages are valid
}

function confirmAprroval() {
    if (!checkEmptyFields()) {
        return false; // Stop submission if required fields are empty
    }
    if (!validateWeightage()) {
        return false; // Stop submission if required fields are empty
    }
    if (!validate()) {
        return false; // Stop submission if required fields are empty
    }

    let title1;
    let title2;
    let text;
    let confirmText;

    const submitButton = $("#submitButton");
    const spinner = submitButton.find(".spinner-border");

    title1 = "Do you want to submit?";
    title2 = "KPI submitted successfuly!";
    text = "You won't be able to revert this!";
    confirmText = "Submit";

    Swal.fire({
        title: title1,
        text: text,
        showCancelButton: true,
        confirmButtonColor: "#3e60d5",
        cancelButtonColor: "#f15776",
        confirmButtonText: confirmText,
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            // Disable submit button
            submitButton.prop("disabled", true);
            submitButton.addClass("disabled");

            // Remove d-none class from spinner if it exists
            if (spinner.length) {
                spinner.removeClass("d-none");
            }

            document.getElementById("goalApprovalForm").submit();
            Swal.fire({
                title: title2,
                icon: "success",
                showConfirmButton: false,
                // If confirmed, proceed with form submission
            });
        }
    });

    return false; // Prevent default form submission
}

function confirmAprrovalAdmin() {
    let title1;
    let title2;
    let text;
    let confirmText;

    const submitButton = $("#submitButton");
    const spinner = submitButton.find(".spinner-border");

    title1 = "Do you want to submit?";
    title2 = "KPI submitted successfuly!";
    text = "You won't be able to revert this!";
    confirmText = "Submit";

    Swal.fire({
        title: title1,
        text: text,
        showCancelButton: true,
        confirmButtonColor: "#3e60d5",
        cancelButtonColor: "#f15776",
        confirmButtonText: confirmText,
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            // Disable submit button
            submitButton.prop("disabled", true);
            submitButton.addClass("disabled");

            // Remove d-none class from spinner if it exists
            if (spinner.length) {
                spinner.removeClass("d-none");
            }
            document.getElementById("goalApprovalAdminForm").submit();
            Swal.fire({
                title: title2,
                icon: "success",
                showConfirmButton: false,
                // If confirmed, proceed with form submission
            });
        }
    });

    return false; // Prevent default form submission
}

window.confirmAprrovalAdmin = confirmAprrovalAdmin;

function sendBack(id, nik, name) {
    let msg = $(`#messages${id}`);

    $("#request_id").val(id);
    $("#sendto").val(nik);

    const approver = $("#approver").val();

    let title1 = "Do you want to sendback?";
    let title2 = "KPI sendback successfuly!";
    let text = `This form will sendback to ${name}`;
    let confirmText = "Submit";

    Swal.fire({
        title: title1,
        text: text,
        showCancelButton: true,
        confirmButtonColor: "#3e60d5",
        cancelButtonColor: "#f15776",
        confirmButtonText: confirmText,
        reverseButtons: true,
        input: "textarea",
        nputLabel: "Message",
        inputPlaceholder: "Type your message here...",
        inputAttributes: {
            "aria-label": "Type your message here",
        },
        inputValidator: (value) => {
            if (!value) {
                return "Message cannot be empty"; // Display error message if input is empty
            }
        },
    }).then((result) => {
        if (result.isConfirmed) {
            const message = result.value; // Get the input message value
            if (message.trim() !== "") {
                document.getElementById(
                    "sendback_message"
                ).value = `${approver} : ${message}`;
                // Menggunakan Ajax untuk mengirim data ke Laravel
                document.getElementById("goalSendbackForm").submit();
                Swal.fire({
                    title: title2,
                    icon: "success",
                    showConfirmButton: false,
                    // If confirmed, proceed with form submission
                });
            }
        }
    });

    return false;
}

window.sendBack = sendBack;

// Function to calculate and display the sum of weightage inputs
function updateWeightageSummary() {
    // Get all input elements with name="weightage[]"
    var weightageInputs = document.getElementsByName("weightage[]");
    var totalSum = 0;

    // Iterate through each input element
    for (var i = 0; i < weightageInputs.length; i++) {
        var input = weightageInputs[i];

        // Get the value of the input (convert to number)
        var value = parseFloat(input.value);

        // Check if the value is a valid number and within the allowed range
        if (!isNaN(value) && value >= 5 && value <= 100) {
            totalSum += value; // Add valid value to total sum
        }
    }

    // Display the total sum in a summary element
    var summaryElement = document.getElementById("totalWeightage");

    if (totalSum != 100) {
        summaryElement.classList.remove("text-success");
        summaryElement.classList.add("text-danger"); // Add text-danger class
        // Add or update a sibling element to display the additional message
        if (summaryElement) {
            summaryElement.textContent = totalSum.toFixed(0) + "% of 100%";
        }
    } else {
        summaryElement.classList.remove("text-danger"); // Remove text-danger class
        summaryElement.classList.add("text-success"); // Remove text-danger class
        // Hide the message element if totalSum is 100
        if (summaryElement) {
            summaryElement.textContent = totalSum.toFixed(0) + "%";
        }
    }
}

// Add event listener for keyup event on all weightage inputs
var weightageInputs = document.getElementsByName("weightage[]");
for (var i = 0; i < weightageInputs.length; i++) {
    weightageInputs[i].addEventListener("keyup", updateWeightageSummary);
}

function validateDigits(input, index) {
    // Remove non-digit characters to get the plain number string
    let numericValue = input.value.replace(/[^0-9]/g, '');

    // If the input length exceeds 20 digits, truncate it
    if (numericValue.length > 20) {
        numericValue = numericValue.slice(0, 20);
    }

    // Store the plain numeric value in the hidden input field
    document.getElementById('target'+index).value = numericValue;

    // Format the numeric value with Numeral.js for display
    input.value = numeral(numericValue).format('0,0'); // Format with commas
}

window.validateDigits = validateDigits;

function changeCategory(val) {
    $("#filter_category").val(val);

    const form = $("#onbehalf_filter");
    const contentOnBehalf = $("#contentOnBehalf");
    const customsearch = $("#customsearch");
    const formData = form.serialize();
    
    function initializePopovers() {
        // Initialize all popovers on the page
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }

    showLoader();

    $.ajax({
        url: "/admin/onbehalf/content", // Endpoint URL to fetch report content
        method: "POST",
        data: formData, // Send serialized form data
        success: function (data) {
            //alert(data);
            contentOnBehalf.html(data); // Update report content

            const onBehalfTable = $("#onBehalfTable").DataTable({
                dom: "lrtip",
                stateSave: true,
                fixedColumns: {
                    leftColumns: 0,
                    rightColumns: 1
                },
                paging: false,
                scrollCollapse: true,
                scrollX: true,
                pageLength: 25,
            });
            
            onBehalfTable.on('draw', function () {
                    initializePopovers();
                });
                
            customsearch.keyup(function () {
                onBehalfTable.search($(this).val()).draw();
            });

            $(".filter-btn").on("click", function () {
                const filterValue = $(this).data("id");

                if (filterValue === "all") {
                    onBehalfTable.search("").draw(); // Clear the search for 'All Task'
                } else {
                    onBehalfTable.search(filterValue).draw();
                }
            });

            initializePopovers();

            hideLoader();
        },
        error: function (xhr, status, error) {
            console.error("Error fetching report content:", error);
            // Optionally display an error message to the user
            contentOnBehalf.html("");
        },
    });
    return; // Prevent default form submission
}

window.changeCategory = changeCategory;

document.addEventListener("DOMContentLoaded", function () {
    const form = $("#onbehalf_filter");
    const contentOnBehalf = $("#contentOnBehalf");
    const customsearch = $("#customsearch");

    function initializePopovers() {
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }
    
    // Submit form event handler
    form.on("submit", function (event) {
        event.preventDefault(); // Prevent default form submission behavior
        const formData = form.serialize();
        showLoader();

        // Send AJAX request to fetch and display report content
        $.ajax({
            url: "/admin/onbehalf/content", // Endpoint URL to fetch report content
            method: "POST",
            data: formData, // Send serialized form data
            success: function (data) {
                contentOnBehalf.html(data); // Update report content

                const onBehalfTable = $("#onBehalfTable").DataTable({
                    dom: "lrtip",
                    stateSave: true,
                    fixedColumns: {
                        leftColumns: 0,
                        rightColumns: 1
                    },
                    paging: false,
                    scrollCollapse: true,
                    scrollX: true,
                    pageLength: 25,
                });
                
                onBehalfTable.on('draw', function () {
                    initializePopovers();
                });
                
                customsearch.on("keyup", function () {
                    onBehalfTable.search($(this).val()).draw();
                });

                $(".filter-btn").on("click", function () {
                    const filterValue = $(this).data("id");

                    if (filterValue === "all") {
                        onBehalfTable.search("").draw(); // Clear the search for 'All Task'
                    } else {
                        onBehalfTable.search(filterValue).draw();
                    }
                });
                
                initializePopovers();
                
                hideLoader();

                $("#offcanvas-cancel").click();
            },
            error: function (xhr, status, error) {
                console.error("Error fetching report content:", error);
                // Optionally display an error message to the user
                contentOnBehalf.html(
                    "Error fetching report content. Please try again."
                );
            },
        });
    });
});