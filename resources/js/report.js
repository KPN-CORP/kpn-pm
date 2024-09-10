import $ from 'jquery';

function hideLoader() {
    $("#preloader").hide();
}

import bootstrap from "bootstrap/dist/js/bootstrap.bundle.min.js";

function adminReportType(val) {
    $("#report_type").val(val);
    const reportForm = $("#admin_report_filter");
    const exportButton = $("#export");
    const reportContentDiv = $("#report_content");
    const customsearch = $("#customsearch");
    const formData = reportForm.serialize();

    initializePopovers();

    showLoader();
    if (val) {
        exportButton.removeClass("disabled"); // Enable export button
    } else {
        exportButton.addClass("disabled"); // Enable export button
    }
    $.ajax({
        url: "/admin/get-report-content", // Endpoint URL to fetch report content
        method: "POST",
        data: formData, // Send serialized form data
        success: function (data) {
            //alert(data);
            reportContentDiv.html(data); // Update report content

            const reportGoalsTable = $("#adminReportTable").DataTable({
                dom: "lrtip",
                stateSave: true,
                pageLength: 50,
                scrollCollapse: true,
                scrollX: true
            });
            reportGoalsTable.on('draw', function () {
                initializePopovers();
            });
            customsearch.keyup(function () {
                reportGoalsTable.search($(this).val()).draw();
            });

            $(".filter-btn").on("click", function () {
                const filterValue = $(this).data("id");

                if (filterValue === "all") {
                    reportGoalsTable.search("").draw(); // Clear the search for 'All Task'
                } else {
                    reportGoalsTable.search(filterValue).draw();
                }
            });

            initializePopovers();

            hideLoader();
        },
        error: function (xhr, status, error) {
            console.error("Error fetching report content:", error);
            // Optionally display an error message to the user
            reportContentDiv.html("");
        },
    });
    return; // Prevent default form submission
}

window.adminReportType = adminReportType;

document.addEventListener("DOMContentLoaded", function () {
    const reportForm = $("#admin_report_filter");
    const exportButton = $("#export");
    const reportContentDiv = $("#report_content");
    const customsearch = $("#customsearch");

    // Submit form event handler
    reportForm.on("submit", function (event) {
        event.preventDefault(); // Prevent default form submission behavior
        const formData = reportForm.serialize(); // Serialize form data
        showLoader();

        // Send AJAX request to fetch and display report content
        $.ajax({
            url: "/admin/get-report-content", // Endpoint URL to fetch report content
            method: "POST",
            data: formData, // Send serialized form data
            success: function (data) {
                reportContentDiv.html(data); // Update report content
                exportButton.removeClass("disabled"); // Enable export button

                const reportGoalsTable = $("#adminReportTable").DataTable({
                    dom: "lrtip",
                    stateSave: true,
                    pageLength: 50,
                    scrollCollapse: true,
                    scrollX: true
                });
                
                customsearch.on("keyup", function () {
                    reportGoalsTable.search($(this).val()).draw();
                });

                $(".filter-btn").on("click", function () {
                    const filterValue = $(this).data("id");

                    if (filterValue === "all") {
                        reportGoalsTable.search("").draw(); // Clear the search for 'All Task'
                    } else {
                        reportGoalsTable.search(filterValue).draw();
                    }
                });

                initializePopovers();

                hideLoader();

                $("#offcanvas-cancel").click();
            },
            error: function (xhr, status, error) {
                console.error("Error fetching report content:", error);
                // Optionally display an error message to the user
                reportContentDiv.html(
                    "Error fetching report content. Please try again."
                );
            },
        });
    });

    // Optional: Add event listener for exportButton if needed
    exportButton.on("click", function () {
        const reportContent = reportContentDiv.html();
        // Code here to handle exporting the report content
    });
});

function initializePopovers() {
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

}

function reportType(val) {
    $("#report_type").val(val);
    const reportForm = $("#report_filter");
    const exportButton = $("#export");
    const reportContentDiv = $("#report_content");
    const customsearch = $("#customsearch");
    const formData = reportForm.serialize();
    
    showLoader();
    if (val) {
        exportButton.removeClass("disabled"); // Enable export button
    } else {
        exportButton.addClass("disabled"); // Disable export button
    }

    $.ajax({
        url: "/get-report-content", // Endpoint URL to fetch report content
        method: "POST",
        data: formData, // Send serialized form data
        success: function (data) {
            reportContentDiv.html(data); // Update report content

            const reportGoalsTable = $("#reportGoalsTable").DataTable({
                dom: "lrtip",
                stateSave: true,
                pageLength: 50,
                scrollCollapse: true,
                scrollX: true
            });

            // Reinitialize popovers after the table is drawn
            reportGoalsTable.on('draw', function () {
                initializePopovers();
            });

            // Initialize popovers for the newly loaded content
            initializePopovers();

            customsearch.keyup(function () {
                reportGoalsTable.search($(this).val()).draw();
            });

            $(".filter-btn").on("click", function () {
                const filterValue = $(this).data("id");

                if (filterValue === "all") {
                    reportGoalsTable.search("").draw(); // Clear the search for 'All Task'
                } else {
                    reportGoalsTable.search(filterValue).draw();
                }
            });

            hideLoader();
        },
        error: function (xhr, status, error) {
            console.error("Error fetching report content:", error);
            // Optionally display an error message to the user
            reportContentDiv.html("");
            hideLoader();
        },
    });

    return; // Prevent default form submission
}

window.reportType = reportType;

document.addEventListener("DOMContentLoaded", function () {
    const reportForm = $("#report_filter");
    const exportButton = $("#export");
    const reportContentDiv = $("#report_content");
    const customsearch = $("#customsearch");

    // Submit form event handler
    reportForm.on("submit", function (event) {
        event.preventDefault(); // Prevent default form submission behavior

        const formData = reportForm.serialize(); // Serialize form data

        showLoader();

        // Send AJAX request to fetch and display report content
        $.ajax({
            url: "/get-report-content", // Endpoint URL to fetch report content
            method: "POST", // Use POST method
            data: formData, // Send serialized form data
            success: function (data) {
                reportContentDiv.html(data); // Update report content with the returned HTML
                exportButton.removeClass("disabled"); // Enable export button

                const reportGoalsTable = $("#reportGoalsTable").DataTable({
                    dom: "lrtip",
                    stateSave: true,
                    pageLength: 50,
                    scrollCollapse: true,
                    scrollX: true
                });
                customsearch.on("keyup", function () {
                    reportGoalsTable.search($(this).val()).draw();
                });

                $(".filter-btn").on("click", function () {
                    const filterValue = $(this).data("id");

                    if (filterValue === "all") {
                        reportGoalsTable.search("").draw(); // Clear the search for 'All Task'
                    } else {
                        reportGoalsTable.search(filterValue).draw();
                    }
                });
                initializePopovers();
                hideLoader();

                $("#offcanvas-cancel").click();
            },
            error: function (xhr, status, error) {
                console.error("Error fetching report content:", error);
                // Optionally display an error message to the user
                reportContentDiv.html(
                    "Error fetching report content. Please try again."
                );
            },
        });
    });

    // Optional: Add event listener for exportButton if needed
    exportButton.on("click", function () {
        const reportContent = reportContentDiv.html();
        // Code here to handle exporting the report content
    });
});

function exportExcel() {
    const exportForm = $("#exportForm");
    const reportType = $("#report_type").val();
    const groupCompany = $("#group_company").val();
    const company = $("#company").val();
    const location = $("#location").val();
    
    $("#export_report_type").val(reportType);
    $("#export_group_company").val(groupCompany);
    $("#export_company").val(company);
    $("#export_location").val(location);
    
    // Submit the form
    exportForm.submit();
}

window.exportExcel = exportExcel;
