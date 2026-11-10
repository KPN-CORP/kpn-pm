import $ from 'jquery';

import numeral from 'numeral';

import bootstrap from "bootstrap/dist/js/bootstrap.bundle.min.js";

$('.select2').each(function() {
    var isModified = $(this).hasClass('is-modified');
    
    $(this).select2({
        width: '100%',
        // Menambahkan class ke container pembungkus select2
        containerCssClass: isModified ? 'bg-primary-subtle fw-medium' : ''
    });
});

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
                confirmButtonColor: "#AB2F2B",
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
        sum += parseFloat(weight[i].value) || 0; // Parse input value to float, default to 0 if NaN
    }

    if (sum != 100) {
        Swal.fire({
            title: "Submission failed",
            html: `Your current weightage is ${sum}%, <br>Please adjust to reach the total weightage of 100%`,
            confirmButtonColor: "#AB2F2B",
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
                confirmButtonColor: "#AB2F2B",
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
        confirmButtonColor: "#AB2F2B",
        
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

window.confirmAprroval = confirmAprroval;

function confirmAprrovalAchievement() {
    
    let title1;
    let title2;
    let text;
    let confirmText;

    const submitButton = $("#submitButton");
    const spinner = submitButton.find(".spinner-border");

    title1 = "Do you want to submit?";
    title2 = "Achievement submitted successfuly!";
    text = "You won't be able to revert this!";
    confirmText = "Submit";

    Swal.fire({
        title: title1,
        text: text,
        showCancelButton: true,
        confirmButtonColor: "#AB2F2B",
        
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

            document.getElementById("achievementApprovalForm").submit();
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

window.confirmAprrovalAchievement = confirmAprrovalAchievement;

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
        confirmButtonColor: "#AB2F2B",
        
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

    let title1 = "Confirm you want to send back?";
    let title2 = "KPI sendback successfuly!";
    let text = `The goals will be sent back to ${name}`;
    let confirmText = "Submit";

    Swal.fire({
        title: title1,
        text: text,
        showCancelButton: true,
        confirmButtonColor: "#AB2F2B",
        
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

async function sendBackAchievement(id, nik, name) {

    $("#request_id").val(id);
    $("#sendto").val(nik);
    let title2;
    title2 = "Achievement sendbacked successfuly!";

    const approver = $("#approver").val();

    const result = await Swal.fire({
        title: 'Confirm Send Back',
        text: `The achievement will be sent back to ${name}`,
        icon: 'warning',

        showCancelButton: true,

        confirmButtonText: 'Submit',
        confirmButtonColor: '#AB2F2B',
        reverseButtons: true,

        input: 'textarea',

        inputLabel: 'Message',

        inputPlaceholder: 'Type your message here...',

        inputValidator: (value) => {
            if (!value?.trim()) {
                return 'Message cannot be empty';
            }
        }
    });

    if (!result.isConfirmed) {
        return;
    }

    console.log(result.value.trim());
    

    $("#sendback_message").val(
        `${approver} : ${result.value.trim()}`
    );

    $("#achievementApprovalForm").submit();
    Swal.fire({
        title: title2,
        icon: "success",
        showConfirmButton: false,
        // If confirmed, proceed with form submission
    });
}

window.sendBackAchievement = sendBackAchievement;

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
            summaryElement.textContent = totalSum + "% of 100%";
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

// ---------------------------------------------------------------------------
// On Behalf (admin) – load the content per category.
// Goals & Appraisal use server-side DataTables (#onBehalfTable[data-server-side]);
// Rating still renders all rows and uses a client-side table.
// ---------------------------------------------------------------------------
let onBehalfStatus = "all";
let onBehalfSearchTimer = null;

function csrfToken() {
    return $('meta[name="csrf-token"]').attr("content");
}

function initializeOnBehalfPopovers() {
    document
        .querySelectorAll('#contentOnBehalf [data-bs-toggle="popover"]')
        .forEach((el) => bootstrap.Popover.getOrCreateInstance(el));
}

function setActiveFilterTab(button) {
    const $btn = $(button);
    $btn.closest(".card-header")
        .find(".filter-btn")
        .removeClass("btn-primary active")
        .addClass("btn-outline-primary")
        .attr("aria-pressed", "false");
    $btn.removeClass("btn-outline-primary")
        .addClass("btn-primary active")
        .attr("aria-pressed", "true");
}

function updateOnBehalfFilterBadge() {
    const count = ["#group_company", "#company", "#location"].reduce(
        (sum, sel) => sum + (($(sel).val() || []).length ? 1 : 0),
        0
    );
    const $badge = $("#onBehalfFilterCount");
    $badge.text(count).toggleClass("d-none", count === 0);
}

function reloadOnBehalfTable() {
    if ($.fn.DataTable.isDataTable("#onBehalfTable")) {
        const table = $("#onBehalfTable").DataTable();
        if (table.init().serverSide) {
            table.ajax.reload(null, false);
            return;
        }
    }
    loadOnBehalfContent();
}

window.reloadOnBehalfTable = reloadOnBehalfTable;

function initServerSideOnBehalfTable() {
    const $table = $("#onBehalfTable");
    const category = $table.data("category");
    onBehalfStatus = "all";

    // Kunci tabel & tab selama data dimuat supaya tombol Action, detail,
    // paging, dan tab tidak bisa diklik pada data yang sedang diganti.
    const $card = $table.closest(".card");
    $table.on("processing.dt", function (e, settings, processing) {
        $card.toggleClass("onbehalf-loading", processing).attr("aria-busy", processing ? "true" : "false");
        if (processing) {
            $card.find('[data-bs-toggle="dropdown"]').each(function () {
                bootstrap.Dropdown.getInstance(this)?.hide();
            });
        }
    });

    $table.DataTable({
        processing: true,
        serverSide: true,
        dom: "lrtip",
        scrollX: true,
        scrollCollapse: true,
        fixedColumns: { leftColumns: 0, rightColumns: 1 },
        pageLength: 25,
        order: [[5, "desc"]],
        search: { search: $("#customsearch").val() || "" },
        language: { processing: "Loading..." },
        ajax: {
            url: $table.data("url"),
            type: "POST",
            headers: { "X-CSRF-TOKEN": csrfToken() },
            data: function (d) {
                d.filter_category = category;
                d.status = onBehalfStatus;
                d.group_company = $("#group_company").val() || [];
                d.company = $("#company").val() || [];
                d.location = $("#location").val() || [];
            },
            error: function (xhr) {
                console.error("Error fetching On Behalf data:", xhr);
                $table.closest(".card").find(".dataTables_processing").hide();
                Swal.fire({
                    icon: "error",
                    title: "Failed to load data",
                    text: (xhr.responseJSON && xhr.responseJSON.message) || "Please try again.",
                });
            },
        },
        columns: [
            { data: "employee", name: "employee" },
            { data: "detail", name: "detail", orderable: false, searchable: false, className: "text-center" },
            { data: "status", name: "status", orderable: false, className: "text-center" },
            { data: "created", name: "created", className: "text-center" },
            { data: "initiated", name: "initiated", orderable: false },
            { data: "updated", name: "updated", className: "text-center" },
            { data: "updated_by", name: "updated_by", orderable: false },
            { data: "action", name: "action", orderable: false, searchable: false, className: "text-center sorting_1 px-1" },
        ],
        drawCallback: initializeOnBehalfPopovers,
    });
}

function initClientSideOnBehalfTable() {
    const onBehalfTable = $("#onBehalfTable").DataTable({
        dom: "lrtip",
        stateSave: true,
        fixedColumns: { leftColumns: 0, rightColumns: 1 },
        scrollCollapse: true,
        scrollX: true,
        pageLength: 25,
        columnDefs: [{ targets: [0], orderable: false }],
    });

    onBehalfTable.on("draw", initializeOnBehalfPopovers);
    onBehalfTable.search($("#customsearch").val() || "").draw();
}

function renderOnBehalfContent(html) {
    const contentOnBehalf = $("#contentOnBehalf");

    if ($.fn.DataTable.isDataTable("#onBehalfTable")) {
        $("#onBehalfTable").DataTable().destroy();
    }
    contentOnBehalf.html(html);

    const $table = $("#onBehalfTable");
    if ($table.data("server-side")) {
        initServerSideOnBehalfTable();
    } else if ($table.length) {
        initClientSideOnBehalfTable();
    }

    // Tandai tab "All" sebagai aktif bila view belum menandai tab apa pun
    const $tabs = contentOnBehalf.find(".filter-btn");
    if ($tabs.length && !$tabs.filter(".active").length) {
        setActiveFilterTab($tabs.first());
    }

    initializeOnBehalfPopovers();
    updateOnBehalfFilterBadge();
}

function loadOnBehalfContent() {
    const contentOnBehalf = $("#contentOnBehalf");
    const category = $("#filter_category").val();

    // Goals/Appraisal: kerangka tabel sudah ada di halaman (<template>),
    // jadi langsung render dan hanya request data tabelnya.
    const template = document.getElementById("onBehalfListTemplate-" + category);
    if (template) {
        renderOnBehalfContent(template.innerHTML);
        return;
    }

    showLoader();

    $.ajax({
        url: "/admin/onbehalf/content",
        method: "POST",
        headers: { "X-CSRF-TOKEN": csrfToken() },
        data: $("#onbehalf_filter").serialize(),
        success: function (html) {
            renderOnBehalfContent(html);
            hideLoader();
        },
        error: function (xhr, status, error) {
            console.error("Error fetching report content:", error);
            contentOnBehalf.html(
                '<div class="alert alert-danger">Error fetching report content. Please try again.</div>'
            );
            hideLoader();
        },
    });
}

function changeCategory(val) {
    $("#filter_category").val(val);
    loadOnBehalfContent();
}

window.changeCategory = changeCategory;

// Tab status: tandai yang aktif; untuk tabel server-side, filter lewat server.
$(document).on("click", "#contentOnBehalf .filter-btn", function () {
    setActiveFilterTab(this);

    const $table = $("#onBehalfTable");
    if ($table.data("server-side") && $.fn.DataTable.isDataTable($table)) {
        onBehalfStatus = $(this).data("status") || "all";
        $table.DataTable().ajax.reload();
    } else if ($.fn.DataTable.isDataTable($table)) {
        const filterValue = $(this).data("id");
        $table.DataTable().search(filterValue === "all" ? "" : filterValue).draw();
    }
});

// Pencarian bebas (debounce untuk server-side)
$(document).on("keyup", "#customsearch", function () {
    const value = $(this).val();
    const $table = $("#onBehalfTable");
    if (!$.fn.DataTable.isDataTable($table)) return;

    clearTimeout(onBehalfSearchTimer);
    onBehalfSearchTimer = setTimeout(
        () => $table.DataTable().search(value).draw(),
        $table.data("server-side") ? 400 : 0
    );
});

// Detail Goals/Appraisal dimuat saat diklik, bukan dirender untuk setiap baris
$(document).on("click", "#contentOnBehalf .js-onbehalf-detail", function () {
    const holder = $("#onBehalfDetailHolder");
    showLoader();

    $.get($(this).data("url"))
        .done(function (html) {
            holder.html(html);
            const modalEl = holder.find(".modal")[0];
            if (!modalEl) return;

            modalEl.addEventListener("hidden.bs.modal", function () {
                bootstrap.Modal.getInstance(modalEl)?.dispose();
                holder.empty();
            });
            modalEl.addEventListener("shown.bs.modal", function () {
                modalEl.querySelectorAll("textarea[readonly]").forEach(autoResize);
                modalEl
                    .querySelectorAll('[data-bs-toggle="popover"]')
                    .forEach((el) => bootstrap.Popover.getOrCreateInstance(el));
            });
            new bootstrap.Modal(modalEl).show();
        })
        .fail(function (xhr) {
            Swal.fire({
                icon: "error",
                title: "Failed to load detail",
                text: (xhr.responseJSON && xhr.responseJSON.message) || "Please try again.",
            });
        })
        .always(hideLoader);
});

document.addEventListener("DOMContentLoaded", function () {
    $("#onbehalf_filter").on("submit", function (event) {
        event.preventDefault();
        const offcanvas = document.getElementById("offcanvasRight");
        if (offcanvas) bootstrap.Offcanvas.getOrCreateInstance(offcanvas).hide();

        // Tabel server-side: cukup reload data (tab yang dipilih tetap)
        const $table = $("#onBehalfTable");
        if ($table.data("server-side") && $.fn.DataTable.isDataTable($table)) {
            updateOnBehalfFilterBadge();
            $table.DataTable().ajax.reload();
            return;
        }
        loadOnBehalfContent();
    });
});

function autoResize(textarea) {
    // Reset the height to auto to calculate the new height correctly
    textarea.style.height = 'auto';
    // Set the height to the scrollHeight to fit the content
    textarea.style.height = textarea.scrollHeight + 'px';
}

// Automatically resize on page load
document.querySelectorAll('textarea[readonly]').forEach(textarea => {
    autoResize(textarea);
});

function revokeGoal(button) {
    const goalId = button.getAttribute('data-id');
    const employee = button.getAttribute('data-name');

    Swal.fire({
        title: 'Are you sure?',
        html: `You are about to grant access for<br><strong>${employee}</strong><br>to revise their goals.`, // Ensures <br> works correctly
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: "#AB2F2B",
        
        confirmButtonText: "Yes, grant access!",
        cancelButtonText: "Cancel",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/admin/goals-revoke', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id: goalId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Revise Granted!', 'The employee now can revise their goals.', 'success');
                    $(button).addClass('d-none'); // Hide button after successful action
                    
                    reloadOnBehalfTable();
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'An error occurred while revoking the goal.', 'error');
            });
        }
    });
}

window.revokeGoal = revokeGoal;
