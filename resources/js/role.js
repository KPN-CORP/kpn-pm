import $ from 'jquery';

$('#submitButtonRole').on('click', function(e) {
    // console.log("test 0");
    e.preventDefault();
    const form = $('#roleForm').get(0);
    const submitButton = $('#submitButtonRole');
    const spinner = submitButton.find(".spinner-border");

    if (form.checkValidity()) {
        // console.log("test 1");
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
        // console.log("test 2");
        // If the form is not valid, trigger HTML5 validation messages
        form.reportValidity();
    }
});

function getPermissionData(id) {
    const subContent = $("#subContent");
    // Send AJAX request to fetch and display report content
    $.ajax({
        url: "/admin/roles/get-permission", // Endpoint URL to fetch report content
        method: "GET",
        data: { roleId: id }, // Send serialized form data
        success: function (data) {
            subContent.html(data); // Update report content
            $(".select2").select2({
                theme: "bootstrap-5",
            });
            $("#submitButton").on("click", function (e) {
                e.preventDefault();
                const form = $("#roleForm").get(0);
                const submitButton = $("#submitButton");
                const spinner = submitButton.find(".spinner-border");

                if (form.checkValidity()) {
                    // Disable submit button
                    submitButton.prop("disabled", true);
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
        },
        error: function (xhr, status, error) {
            console.error("Error fetching data:", error);
            // Optionally display an error message to the user
            subContent.html("Error fetching data. Please try again.");
        },
    });
}

window.getPermissionData = getPermissionData;

function deleteRole() {
    const submitButton = $(event.target).closest(".btn-outline-danger");
    const spinner = submitButton.find(".spinner-border");

    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3e60d5",
        cancelButtonColor: "#f15776",
        confirmButtonText: "Yes, delete it!",
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            submitButton.prop("disabled", true);
            submitButton.addClass("disabled");
            if (spinner.length) {
                spinner.removeClass("d-none");
            }
            document.getElementById("delete-role-form").submit();
        }
    });
}

window.deleteRole = deleteRole;

function getAssignmentData(id) {
    const subContent = $("#subContent");
    // Send AJAX request to fetch and display report content
    $.ajax({
        url: "/admin/roles/get-assignment", // Endpoint URL to fetch report content
        method: "GET",
        data: { roleId: id }, // Send serialized form data
        success: function (data) {
            subContent.html(data); // Update report content
            $(".select2").select2({
                theme: "bootstrap-5",
            });
            $("#submitButton").on("click", function (e) {
                e.preventDefault();
                const form = $("#assignForm").get(0);
                const submitButton = $("#submitButton");
                const spinner = submitButton.find(".spinner-border");

                if (form.checkValidity()) {
                    // Disable submit button
                    submitButton.prop("disabled", true);
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
        },
        error: function (xhr, status, error) {
            console.error("Error fetching data:", error);
            // Optionally display an error message to the user
            subContent.html("Error fetching data. Please try again.");
        },
    });
}

window.getAssignmentData = getAssignmentData;
