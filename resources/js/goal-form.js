import $ from 'jquery';

import numeral from 'numeral';

import bootstrap from "bootstrap/dist/js/bootstrap.bundle.min.js";

import select2 from "select2"
select2(); 

document.addEventListener('DOMContentLoaded', function () {
    // Initialize all popovers on the page
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

$(document).ready(function() {
    // Initialize Select2 on the select elements
    $('.select-uom').select2({
         theme : "bootstrap-5",
    });

    // Event listener for select element with Select2
    $(document).on('change', 'select.select-uom', function (event) {
        let index = $(this).data('id');

        const uomSelect = $(this).val();
        // Event listener for select element
        const inputField = $("#custom_uom" + index);
        if (uomSelect === "Other") {
            // Display input field
            inputField.show(); // Show the input field
            inputField.prop("required", true); // Set input as required
        } else {
            inputField.hide(); // Hide the input field
            inputField.val("");
            inputField.prop("required", false); // Remove required attribute
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    // Get the value of the hidden input
    var managerId = $('input[name="manager_id"]').val();

    // Check if managerId is empty or not assigned
    if (managerId == "") {
        // Show SweetAlert alert
        Swal.fire({
            title: "No direct manager is assigned!",
            text: "Please contact admin to assign your manager",
            icon: "error",
            closeOnClickOutside: false, // Prevent closing by clicking outside alert
        }).then(function () {
            // Redirect back
            window.history.back();
        });
    }
});

// Function to fetch UoM data and populate select element
function populateUoMSelect(select) {
    fetch("/units-of-measurement")
        .then((response) => response.json())
        .then((data) => {
            Object.keys(data.UoM).forEach((category) => {
                const optgroup = $("<optgroup></optgroup>").attr(
                    "label",
                    category
                );
                data.UoM[category].forEach((unit) => {
                    var option = $("<option></option>")
                        .attr("value", unit)
                        .text(unit);
                    optgroup.append(option);
                });
                $(select).append(optgroup);
            });
        })
        .catch((error) => {
            console.error("Error fetching units of measurement:", error);
        });
}

document.addEventListener("DOMContentLoaded", function () {
    var x = 1;
    var count = $("#count").val();
    var wrapper = $(".container-card"); // Fields wrapper
    var index = $("#count").val();

    function addField(val) {
        var max_fields = val === "input" ? 9 : 10 - count; // maximum input boxes allowed

        if (x <= max_fields) {
            // max input box allowed
            x++; // text box increment
            index++; // text box increment

            $(wrapper).append(
                '<div class="card border-primary border col-md-12 mb-3 bg-primary-subtle">' +
                    "<div class='card-body'><div class='row align-items-end'><div class='col'><h5 class='card-title fs-16 mb-0 text-primary'>Goal " +
                    (index ? index : x) +
                    "</h5></div>" +
                    "<div class='col-auto'><a class='btn-close btn-sm remove_field' type='button'></a></div></div>" +
                    '<div class="row mt-2">' +
                    '<div class="col-md">' +
                    '<div class="mb-3 position-relative">' +
                    '<textarea name="kpi[]" id="kpi" class="form-control overflow-hidden kpi-textarea" placeholder="input your goals.." required style="padding-right: 40px; resize: none"></textarea>'+
                    '<div class="invalid-feedback">' + textMandatory + '</div>' +
                    "</div>" +
                    "</div>" +
                    "</div>" +
                    '<div class="row">  ' +
                    '<div class="col-md">' +
                    '<label class="form-label text-primary" for="kpi-description">Goal Descriptions</label>' +
                    '<div class="mb-3 position-relative">' +
                    '<textarea name="description[]" id="kpi-description" class="form-control overflow-hidden kpi-descriptions" rows="2" placeholder="Input goal descriptions.." style="padding-right: 40px; resize: none"></textarea>' +
                    "</div>" +
                    "</div>" +
                    "</div>" +
                    '<div class="row">' +
                    '<div class="col-md mb-3">' +
                    '<label class="form-label text-primary" for="target">Target</label><input type="text" oninput="validateDigits(this, '
                    + index +
                    ')" class="form-control" required>' +
                    '<input type="hidden" name="target[]" id="target'
                    + index +'">' +
                    '<div class="invalid-feedback">' + textMandatory + '</div>' +
                    "</div>" +
                    '<div class="col-md mb-3">' +
                    '<label class="form-label text-primary" for="uom">'+ uom +'</label>' +
                    '<select class="form-select select2 select-uom" name="uom[]" id="uom' +
                    index +
                    '" data-id="' +
                    index +
                    '" title="Unit of Measure" required>' +
                    '<option value="">- Select -</option>' +
                    '</select><input type="text" name="custom_uom[]" id="custom_uom' +
                    index +
                    '" class="form-control mt-2" placeholder="Enter UoM" style="display: none" placeholder="Enter UoM">' +
                    '<div class="invalid-feedback">' + textMandatory + '</div>' +
                    "</div>" +
                    '<div class="col-md mb-3">' +
                    '<label class="form-label text-primary" for="type">'+ type +'</label>' +
                    '<select class="form-select select-type" name="type[]" id="type' +
                    index +
                    '" required>' +
                    '<option value="">- Select -</option>' +
                    '<option value="Higher Better">Higher Better</option>' +
                    '<option value="Lower Better">Lower Better</option>' +
                    '<option value="Exact Value">Exact Value</option>' +
                    "</select>" +
                    '<div class="invalid-feedback">' + textMandatory + '</div>' +
                    "</div>" +
                    '<div class="col-6 col-md-2 mb-3">' +
                    '<label class="form-label text-primary" for="weightage">'+ weightage +'</label>' +
                    '<div class="input-group">' +
                    '<input type="number" min="5" max="100" class="form-control" name="weightage[]" required>' +
                    '<span class="input-group-text">%</span>' +
                    '<div class="invalid-feedback">' + textMandatory + '</div>' +
                    "</div>" +
                    "</div>" +
                    "</div>" +
                    "</div>" +
                    "</div>"
            );
             // add input box
             // Reinitialize auto-resize and character counter for new textareas
            initializeTextareaEvents();

            // Populate UoM select for the newly added field
            var newSelect = $("#uom" + index); // Assuming your select has an ID like "uom1", "uom2", ...
            populateUoMSelect(newSelect);

            $('.select-uom').select2({
                theme: "bootstrap-5",
            });

            var weightageInputs = document.getElementsByName("weightage[]");
            for (var i = 0; i < weightageInputs.length; i++) {
                weightageInputs[i].addEventListener(
                    "keyup",
                    updateWeightageSummary
                );
            }
        } else {
            Swal.fire({
                title: "Oops, you've reached the maximum number of KPI",
                icon: "error",
                confirmButtonColor: "#3e60d5",
                confirmButtonText: "OK",
            });
        }
    }

    $(wrapper).on("click", ".remove_field", function (e) {
        e.preventDefault();

        // Find the last card within the wrapper and remove it
        $(wrapper).children(".card").last().remove();
        // Select the last (most recently added) card

        x--; // Decrement the text box count
        index--;

        // updateWeightageSummary;
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
    });

    var addButton = document.getElementById("addButton");
    if(addButton){
        addButton.addEventListener("click", function () {
            var dataId = addButton.getAttribute("data-id");
            addField(dataId); // Add an empty input field
        });
    }
});

var firstSelect = document.getElementById("uom"); // Assuming your first select has an ID "uom1"
populateUoMSelect(firstSelect);

function checkEmptyFields(submitType) {
    const alertField = $(".mandatory-field");
    alertField.html(`
        <div id="alertField" class="alert alert-danger alert-dismissible fade" role="alert" hidden>
            `+ errorMessages +`
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `);
    if (submitType === "submit_form") {
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
                        document.getElementById("goalForm").classList.add("was-validated");
                    },
                });
                return false; // Prevent form submission
            }
        }
        return true; // All required fields are filled
    }
    return true; // All required fields are filled
}

function validate(submitType) {
    var weight = document.querySelectorAll('input[name="weightage[]"]');
    var sum = 0;
    for (var i = 0; i < weight.length; i++) {
        sum += parseInt(weight[i].value) || 0; // Parse input value to integer, default to 0 if NaN
    }

    if (sum != 100 && submitType === "submit_form") {
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

function validateWeightage(submitType) {
    // Get all input elements with name="weightage[]"
    var weightageInputs = document.getElementsByName("weightage[]");

    // Iterate through each input element
    for (var i = 0; i < weightageInputs.length; i++) {
        var input = weightageInputs[i];

        // Get the value of the input (convert to number)
        var value = parseFloat(input.value);

        // Check if value is below 5%
        if (value < 5 && submitType === "submit_form") {
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

$(document).on('click', '#submitButton', function (event) {
    event.preventDefault();

    let submitType = $(this).data('id');

    document.getElementById("submitType").value = submitType; // Set the value of the hidden input field
    // Now you can call the confirmSubmission() function to show the confirmation dialog
    // Check for empty required fields
    if (!checkEmptyFields(submitType)) {
        return false; // Stop submission if required fields are empty
    }
    if (!validateWeightage(submitType)) {
        return false; // Stop submission if required fields are empty
    }
    if (!validate(submitType)) {
        return false; // Stop submission if required fields are empty
    }
    return confirmSubmission(submitType);
});

function confirmSubmission(submitType) {
    let title1;
    let title2;
    let text;
    let confirmText;

    const submitButton = $("#submitButton");
    const spinner = submitButton.find(".spinner-border");

    if (submitType === "save_draft") {
        title1 = "Do you want to save this form?";
        title2 = "Form saved successfuly!";
        text = "Your data will be saved as draft";
        confirmText = "Save";
    } else {
        title1 = "Do you want to submit?";
        title2 = "KPI submitted successfuly!";
        text =
            "You can still change it as long as the manager hasn't approved it yet";
        confirmText = "Submit";
    }

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

            document.getElementById("goalForm").submit();
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
    let numericValue = input.value.replace(/[^0-9.]/g, '');

    // If the input length exceeds 20 digits, truncate it
    if (numericValue.length > 20) {
        numericValue = numericValue.slice(0, 20);
    }

    // Store the plain numeric value in the hidden input field
    document.getElementById('target'+index).value = numericValue;    

    if (numericValue.indexOf('.') == -1) {
        input.value = numeral(numericValue).format('0,0'); // Format with commas
    }
    // Format the numeric value with Numeral.js for display
}

window.validateDigits = validateDigits;

function initializeTextareaEvents() {
    const textareas = document.querySelectorAll(".kpi-textarea, .kpi-descriptions");

    textareas.forEach(textarea => {
        const counter = document.createElement("small");
        counter.classList.add("text-muted",  "position-absolute", "bottom-0", "end-0", "pe-1", "char-counter");
        counter.textContent = "0/1000";

        textarea.parentNode.appendChild(counter); // Tambahkan counter ke parent div

        function updateCounter() {
            counter.textContent = textarea.value.length + "/1000";
        }

        textarea.addEventListener("input", function () {
            if (this.value.length > 1000) {
                this.value = this.value.substring(0, 1000); // Batasi ke 1000 karakter
            }
            updateCounter();
        });

        updateCounter(); // Setel jumlah karakter awal

        function adjustHeight() {
            textarea.style.height = "auto"; // Reset tinggi dulu
            textarea.style.height = textarea.scrollHeight + "px"; // Sesuaikan tinggi dengan konten tanpa batas
        }

        textarea.addEventListener("input", function () {
            if (this.value.length > 1000) {
                this.value = this.value.substring(0, 1000); // Batasi ke 1000 karakter
            }
            adjustHeight();
        });

        window.addEventListener("resize", adjustHeight); // Sesuaikan saat layar berubah ukuran

        // Setel tinggi awal
        adjustHeight();
    });
};

// Run initialization when page loads
document.addEventListener("DOMContentLoaded", initializeTextareaEvents);
