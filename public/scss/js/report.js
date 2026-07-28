document.addEventListener('DOMContentLoaded', function () {
    // Initialize all popovers on the page
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

function adminReportType(val) {
    $("#report_type").val(val);
    const reportForm = $("#admin_report_filter");
    const exportButton = $("#export");
    const reportContentDiv = $("#report_content");
    const customsearch = $("#customsearch");
    const formData = reportForm.serialize();
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
                pageLength: 50,
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
        exportButton.addClass("disabled"); // Enable export button
    }
    $.ajax({
        url: "/get-report-content", // Endpoint URL to fetch report content
        method: "POST",
        data: formData, // Send serialized form data
        success: function (data) {
            reportContentDiv.html(data); // Update report content

            const reportGoalsTable = $("#reportGoalsTable").DataTable({
                dom: "lrtip",
                pageLength: 50,
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
