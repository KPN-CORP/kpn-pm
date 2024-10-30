import $ from 'jquery';

$(document).ready(function() {
    // Initialize DataTable for Team Appraisal
    var tableTeam = $('#tableAppraisalTeam').DataTable({
        stateSave: true,
        autoWidth: false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csvHtml5',
                text: '<i class="ri-download-cloud-2-line fs-16 me-1"></i>Download Report',
                className: 'btn btn-sm btn-outline-success',
                title: 'My Appraisal Team',
                exportOptions: {
                    columns: ':not(:first-child):not(:last-child)'
                },
                customize: function(csv) {
                    // Split CSV into rows
                    let csvRows = csv.split('\n');
                    
                    // Get data from the DataTable
                    let dt = $('#tableAppraisalTeam').DataTable();
                    let data = dt.data().toArray();
                    
                    // Add new headers
                    csvRows[0] = csvRows[0].slice(0, -1) + ',KPI Score,Culture Score,Leadership Score,Total Score';
                    
                    // Process each data row
                    for(let i = 1; i < csvRows.length; i++) {
                        if(csvRows[i]) {
                            let rowData = data[i-1];
                            let scores = getScores(rowData);
                            
                            // Add the scores to the CSV row
                            csvRows[i] = csvRows[i].slice(0, -1)
                            + scores.kpiScore + '"' + scores.cultureScore + '",' + '"' + scores.leadershipScore + '",' +
                            '"' + scores.totalScore + '"';
                        }
                    }
                    
                    return csvRows.join('\n');
                }
            }
        ],
        fixedColumns: {
            leftColumns: 0,
            rightColumns: 1
        },
        scrollCollapse: true,
        scrollX: true,
        paging: false,
        ajax: {
            url: '/appraisals-task/teams-data',
            type: 'GET',
            dataSrc: ''
        },
        columns: [
            {   
                className: 'dt-control',
                orderable: false,
                data: null,
                defaultContent: ''
             },
            { data: 'employee.employee_id' },
            { data: 'employee.fullname' },
            { data: 'employee.designation' },
            { data: 'employee.office_area' },
            { data: 'employee.group_company' },
            { data: 'approval_date', className: 'text-end' },
            { data: 'action', className: 'sorting_1 text-center' }
        ]
    });

    // Add event listener for both tables
    addChildRowToggle(tableTeam, '#tableAppraisalTeam');

});

$(document).ready(function() {
    
    // Initialize DataTable for 360 Appraisal
    var table360 = $('#tableAppraisal360').DataTable({
        stateSave: true,
        autoWidth: false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csvHtml5',
                text: '<i class="ri-download-cloud-2-line fs-16 me-1"></i>Download Report',
                className: 'btn btn-sm btn-outline-success',
                title: 'My Appraisal 360',
                exportOptions: {
                    columns: ':not(:first-child):not(:last-child)'
                },
                customize: function(csv) {
                    // Split CSV into rows
                    let csvRows = csv.split('\n');
                    
                    // Get data from the DataTable
                    let dt = $('#tableAppraisal360').DataTable();
                    let data = dt.data().toArray();
                    
                    // Add new headers
                    csvRows[0] = csvRows[0].slice(0, -1) + ',KPI Score,Culture Score,Leadership Score,Total Score';
                    
                    // Process each data row
                    for(let i = 1; i < csvRows.length; i++) {
                        if(csvRows[i]) {
                            let rowData = data[i-1];
                            let scores = getScores(rowData);
                            
                            // Add the scores to the CSV row
                            csvRows[i] = csvRows[i].slice(0, -1)
                                + scores.kpiScore + '"' + scores.cultureScore + '",' + '"' + scores.leadershipScore + '",' +
                                '"' + scores.totalScore + '"';
                        }
                    }
                    
                    return csvRows.join('\n');
                }
            }
        ],
        fixedColumns: {
            leftColumns: 0,
            rightColumns: 1
        },
        scrollCollapse: true,
        scrollX: true,
        paging: false,
        ajax: {
            url: '/appraisals-task/360-data',
            type: 'GET',
            dataSrc: ''
        },
        columns: [
            {
                className: 'dt-control',
                orderable: false,
                data: null,
                defaultContent: ''
            },
            { data: 'employee.employee_id' },
            { data: 'employee.fullname' },
            { data: 'employee.designation' },
            { data: 'employee.office_area' },
            { data: 'employee.group_company' },
            { data: 'approval_date', className: 'text-end' },
            { data: 'employee.category' },
            { data: 'action', className: 'sorting_1 text-center' }
        ]
    });
    
    // Add event listener for both tables
    addChildRowToggle(table360, '#tableAppraisal360');

});

// Function to get formatted scores for export
function getScores(rowData) {
    let scores = {
        totalScore: 'N/A',
        kpiScore: '","N/A",',
        cultureScore: 'N/A',
        leadershipScore: 'N/A'
    };

    if (rowData.kpi && rowData.kpi.kpi_status) {
        scores.totalScore = rowData.kpi.total_score || 'N/A';
        scores.kpiScore = ',"' + rowData.kpi.kpi_score + '",' || 'N/A';
        scores.cultureScore = rowData.kpi.culture_score || 'N/A';
        scores.leadershipScore = rowData.kpi.leadership_score || 'N/A';
    }

    return scores;
}

// Function to add child row toggle functionality
function addChildRowToggle(table, tableId, speed = 250) {
    $(tableId + ' tbody').on('click', 'td.dt-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row(tr);

        if (row.child.isShown()) {
            // Close the row with animation
            $('div.slider', row.child()).slideUp(speed, function () {
                row.child.hide(); // After the slide-up animation, hide the row
                tr.removeClass('shown');
            });
        } else {
            // Format and show the child row but initially hide it with display:none
            row.child('<div class="slider" style="display:none;">' + formatChildRow(row.data()) + '</div>').show();
            // Then slide it down to make it visible with animation
            $('div.slider', row.child()).slideDown(speed);
            tr.addClass('shown');
        }
    });
}


// Function to format child row content
function formatChildRow(rowData) {
    let kpiContent = '<div>No scores available</div>';
    let totalScoreContent = '';
    let kpiScoreContent = '';
    let cultureScoreContent = '';
    let leadershipScoreContent = '';

    if (rowData.kpi && rowData.kpi.kpi_status) {
        if (rowData.kpi.total_score) {
            totalScoreContent = `<div class="row">
                <div class="col-2">
                    <div class="mb-1 border-bottom border-secondary"><strong>Total Score</strong></div>
                </div>
                <div class="col-auto">
                    <div class="mb-1 border-bottom border-secondary"><strong>: ${rowData.kpi.total_score}</strong></div>
                </div>
            </div>`;
        }

        if (rowData.kpi.kpi_score) {
            kpiScoreContent = `<div class="row">
                <div class="col-2">
                    <div class="mb-1">KPI</div>
                </div>
                <div class="col">
                    <div class="mb-1">: ${rowData.kpi.kpi_score}</div>
                </div>
            </div>`;
        }

        if (rowData.kpi.culture_score) {
            cultureScoreContent = `<div class="row">
                <div class="col-2">
                    <div class="mb-1">Culture</div>
                </div>
                <div class="col">
                    <div class="mb-1">: ${rowData.kpi.culture_score}</div>
                </div>
            </div>`;
        }

        if (rowData.kpi.leadership_score) {
            leadershipScoreContent = `<div class="row">
                <div class="col-2">
                    <div class="mb-1">Leadership</strong></div>
                </div>
                <div class="col">
                    <div class="mb-1">: ${rowData.kpi.leadership_score}</div>
                </div>
            </div>`;
        }

        kpiContent = `${totalScoreContent}${kpiScoreContent}${cultureScoreContent}${leadershipScoreContent}`;
    }

    return kpiContent;
}

$(document).ready(function() {
    let currentStep = $('.step').data('step');
    const totalSteps = $('.form-step').length;

    function updateStepper(step) {
        // Update circles
        $('.circle').removeClass('active completed');
        $('.circle').each(function(index) {
            if (index < step - 1) {
                $(this).addClass('completed');
            } else if (index == step - 1) {
                $(this).addClass('active');
            }
        });
        
        $('.label').removeClass('active');
        $('.label').each(function(index) {
            if (index < step - 1) {
                $(this).addClass('active');
            } else if (index == step - 1) {
                $(this).addClass('active');
            }
        });

        // Update connectors
        $('.connector').each(function(index) {
            if (index < step - 1) {
                $(this).addClass('completed');
            } else {
                $(this).removeClass('completed');
            }
        });

        // Update form steps visibility
        $('.form-step').removeClass('active').hide();
        $(`.form-step[data-step="${step}"]`).addClass('active').fadeIn();

        // Update navigation buttons
        if (step === 1) {
            $('.prev-btn').hide();
        } else {
            $('.prev-btn').show();
        }

        if (step === totalSteps) {
            $('.next-btn').hide();
            $('.submit-btn').show();
        } else {
            $('.next-btn').show();
            $('.submit-btn').hide();
        }
    }

    function validateStep(step) {
        let isValid = true;
        let firstInvalidElement = null;
    
        $(`.form-step[data-step="${step}"] .form-select, .form-step[data-step="${step}"] .form-control`).each(function() {
            if (!$(this).val()) {
                $(this).siblings('.error-message').text(errorMessages);
                $(this).addClass('border-danger');
                isValid = false;
                if (firstInvalidElement === null) {
                    firstInvalidElement = $(this);
                }
            } else {
                $(this).removeClass('border-danger');
                $(this).siblings('.error-message').text('');
            }
        });
    
        // Focus the first invalid element if any
        if (firstInvalidElement) {
            firstInvalidElement.focus();
        }
    
        return isValid;
    }
    

    $('.next-btn').click(function() {
        if (validateStep(currentStep)) {
            currentStep++;
            updateStepper(currentStep);
        }
    });

    $('.submit-btn').click(function () {
        let submitType = $(this).data('id');
        document.getElementById("submitType").value = submitType; 
        if (validateStep(currentStep)) {
            let title1;
            let title2;
            let text;
            let confirmText;
    
            const spinner = $(this).find(".spinner-border");
    
            if (submitType === "submit_form") {
                title1 = "Submit From?";
                text = "This can't be revert";
                title2 = "Appraisal submitted successfully!";
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
                        $(this).prop("disabled", true);
                        $(this).addClass("disabled");
        
                        // Show spinner if it exists
                        if (spinner.length) {
                            spinner.removeClass("d-none");
                        }
        
                        document.getElementById("appraisalForm").submit();
        
                        // Show success message
                        Swal.fire({
                            title: title2,
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1500, // Optional: Auto close the success message after 1.5 seconds
                        });
                    }
                });
            }
    
            return false; // Prevent default form submission
        }
    });    

    $('.prev-btn').click(function() {
        currentStep--;
        updateStepper(currentStep);
    });

    updateStepper(currentStep);
});


$(document).ready(function() {
    $('[id^="achievement"]').on('input', function() {
        let $this = $(this); // Cache the jQuery object
        let currentValue = $this.val();
        let validNumber = currentValue.replace(/[^0-9.-]/g, ''); // Allow digits, decimal points, and negative signs

        // Ensure only one decimal point and one negative sign at the start
        if (validNumber.indexOf('-') > 0) {
            validNumber = validNumber.replace('-', ''); // Remove if negative sign is not at the start
        }
        if ((validNumber.match(/\./g) || []).length > 1) {
            validNumber = validNumber.replace(/\.+$/, ''); // Remove extra decimal points
        }

        $this.val(validNumber);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    var $window = $(window);
    var $stickyElement = $('.detail-employee');
    if ($stickyElement.length > 0) {
        var stickyOffset = $stickyElement.offset().top;

        function handleScroll() {
            if ($window.width() > 768) { // Check if viewport width is greater than 768px
                if ($window.scrollTop() > stickyOffset) {
                    $stickyElement.addClass('sticky-top');
                    $stickyElement.addClass('sticky-padding');
                } else {
                    $stickyElement.removeClass('sticky-top');
                    $stickyElement.removeClass('sticky-padding');
                }
            } else {
                $stickyElement.removeClass('sticky-top');
                $stickyElement.removeClass('sticky-padding');
            }
        }

        // Run on scroll and resize events
        $window.on('scroll', handleScroll);
        $window.on('resize', function() {
            // Update the stickyOffset on resize
            stickyOffset = $stickyElement.offset().top;
            handleScroll();
        });

        // Initial check
        handleScroll();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var teamTab = document.getElementById('team-tab');
    var reviewTab = document.getElementById('360-review-tab');

    // Event listeners for 'shown' event when the tab becomes active
    teamTab.addEventListener('shown.bs.tab', function () {
        teamTab.classList.remove('btn-outline-secondary');
        teamTab.classList.add('btn-outline-primary');

        reviewTab.classList.remove('btn-outline-primary');
        reviewTab.classList.add('btn-outline-secondary');
    });

    reviewTab.addEventListener('shown.bs.tab', function () {
        reviewTab.classList.remove('btn-outline-secondary');
        reviewTab.classList.add('btn-outline-primary');

        teamTab.classList.remove('btn-outline-primary');
        teamTab.classList.add('btn-outline-secondary');
    });
});
