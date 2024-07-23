function adminReportType(val) {
    $("#report_type").val(val);
    const reportForm = $("#admin_report_filter");
    const exportButton = $("#export");
    const reportContentDiv = $("#report_content");
    const customsearch = $("#customsearch");
    const formData = reportForm.serialize();

    function initializePopovers() {
        $('[data-bs-toggle="popover"]').popover();
    }

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
function reportType(val) {
    $("#report_type").val(val);
    const reportForm = $("#report_filter");
    const exportButton = $("#export");
    const reportContentDiv = $("#report_content");
    const customsearch = $("#customsearch");
    const formData = reportForm.serialize();
    function initializePopovers() {
        $('[data-bs-toggle="popover"]').popover();
    }
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
