import { log } from 'handlebars';
import $ from 'jquery';

$(document).ready(function() {
    $('#tableNotInitiated').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csvHtml5',
                text: '<i class="ri-download-cloud-2-line fs-16 me-1"></i>Download',
                className: 'btn btn-outline-success',
                title: 'Employee Data'
            },
        ],
        scrollX: true,
        paging: false,
    });
});


function updateRatingTable(selectElement) {
    const level = selectElement.dataset.id;
    
    // Get all rating selects for this level
    const ratingSelects = document.querySelectorAll(`select[data-id="${level}"]`);
    
    // Count occurrences of each rating text
    const ratingCounts = {};
    ratingSelects.forEach(select => {
        const selectedOption = select.options[select.selectedIndex];
        if (selectedOption) {
            const text = selectedOption.textContent.trim();
            if (text && text !== 'Please Select') {
                ratingCounts[text] = (ratingCounts[text] || 0) + 1;
            }
        }
    });
    
    // Get all unique keys (ratings) from the table
    const keys = Array.from(document.querySelectorAll(`.key-${level}`)).map(el => el.textContent.trim());
    
    // Update each row in the table
    keys.forEach(key => {
        const count = ratingCounts[key] || 0;
        const countCell = document.querySelector(`.suggested-rating-count-${key}-${level}`);
        const percentageCell = document.querySelector(`.suggested-rating-percentage-${key}-${level}`);
        
        if (countCell && percentageCell) {
            countCell.textContent = count;
            const percentage = ((count / ratingSelects.length) * 100).toFixed(2);
            percentageCell.textContent = `${percentage}%`;
        }
    });
    
    // Update total count and percentage
    const totalCountCell = document.querySelector(`.rating-total-count-${level}`);
    const totalPercentageCell = document.querySelector(`.rating-total-percentage-${level}`);
    
    if (totalCountCell && totalPercentageCell) {
        const totalRated = Object.values(ratingCounts).reduce((sum, count) => sum + count, 0);
        totalCountCell.textContent = totalRated;
        const totalPercentage = totalRated > 0 ? (totalRated / ratingSelects.length) * 100 : 0;
        totalPercentageCell.textContent = `${totalPercentage.toFixed(2)}%`;
    }
}

// Add event listeners to all rating select elements
document.addEventListener('DOMContentLoaded', () => {
    const ratingSelects = document.querySelectorAll('.rating-select');
    ratingSelects.forEach(select => {
        select.addEventListener('change', (event) => updateRatingTable(event.target));
    });
});

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get all the forms
    const forms = document.querySelectorAll('form[id^="formRating"]');

    forms.forEach(form => {
        const level = form.id.replace('formRating', '');
        const submitButton = document.querySelector(`button[data-id="${level}"]`);

        submitButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const selects = form.querySelectorAll('select[id^="rating"]');
        
            const allSelectsDisabled = Array.from(selects).some(select => select.disabled);
        
            if (allSelectsDisabled) {
                Swal.fire({
                    title: 'Submit Not Allowed',
                    text: 'Some employees are still incomplete. Please reach out to the pending employees.',
                    icon: 'warning',
                    confirmButtonColor: "#3e60d5",
                    confirmButtonText: 'OK'
                });
                return; // Stop further execution
            }
        
            let isValid = true;
            const ratings = {};
        
            selects.forEach(select => {
                if (select.disabled) return;
                
                if (select.value === '') {
                    isValid = false;
                    select.classList.add('is-invalid');
                } else {
                    select.classList.remove('is-invalid');
                    const employeeName = select.closest('.card').querySelector('.fw-medium').textContent.split('(')[0].trim();
                    ratings[employeeName] = select.value;
                }
            });
        
            // Validate table data
            const table = document.querySelector(`table:has(.key-${level})`);
            const rows = table.querySelectorAll('tbody tr:not(:last-child)');
            let tableIsValid = true;
            let mismatchedRatings = [];
        
            // Remove previous table-danger classes
            table.querySelectorAll('.table-danger').forEach(el => {
                el.classList.remove('table-danger');
            });

            const totalRatingCell = parseInt(table.querySelector(`td.rating-total-count-${level}`).textContent);
            const firstKey = table.querySelector(`td.key-${level}`).textContent.trim();
            
            rows.forEach(row => {
                const key = row.querySelector(`td.key-${level}`).textContent;
                const ratingCell = row.querySelector('td.rating');
                const suggestedRatingCell = row.querySelector(`td.suggested-rating-count-${key}-${level}`);
                const ratingCount = parseInt(ratingCell.textContent);
                const suggestedRatingCount = parseInt(suggestedRatingCell.textContent);
                        
                if (totalRatingCell === 1) {
                    // Allow mismatch only if key is not 'A'
                    if (key === firstKey && ratingCount !== suggestedRatingCount) {
                        tableIsValid = false;
                        mismatchedRatings.push(`${key}: Expected ${ratingCount}, Got ${suggestedRatingCount}`);
                        suggestedRatingCell.classList.add('table-danger');
                    }
                } else if (totalRatingCell === 2) {
                    // Allow mismatch if key has no more than 1 unique rating value
                    if (suggestedRatingCount > 1 && ratingCount !== suggestedRatingCount) {
                        tableIsValid = false;
                        mismatchedRatings.push(`${key}: Maximum Expected 1, Got ${suggestedRatingCount}`);
                        suggestedRatingCell.classList.add('table-danger');
                    }
                } else if (ratingCount !== suggestedRatingCount) {
                    // General case: Handle any other mismatch
                    tableIsValid = false;
                    mismatchedRatings.push(`${key}: Expected ${ratingCount}, Got ${suggestedRatingCount}`);
                    suggestedRatingCell.classList.add('table-danger');
                }
            });
        
            if (!isValid) {
                Swal.fire({
                    title: 'Ratings are Empty!',
                    text: 'Please select a rating for all employees.',
                    icon: 'error',
                    confirmButtonColor: "#3e60d5",
                    confirmButtonText: 'OK'
                });
            } else if (!tableIsValid) {
                Swal.fire({
                    title: 'Rating Mismatch!',
                    html: `
                        <p>The following ratings do not match the expected values:</p>
                        <pre>${mismatchedRatings.join('\n')}</pre>
                        <p>Please adjust your ratings to match the expected distribution. Mismatched cells are highlighted in the table.</p>
                    `,
                    icon: 'warning',
                    confirmButtonColor: "#3e60d5",
                    confirmButtonText: 'OK'
                });
            } else {
                let ratingsList = '';
                for (const [employee, rating] of Object.entries(ratings)) {
                    ratingsList += `${employee}: ${rating}\n`;
                }
        
                Swal.fire({
                    title: "Submit Ratings?",
                    text: "This can't be reverted.",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#3e60d5",
                    cancelButtonColor: "#f15776",
                    confirmButtonText: "Yes, submit",
                    cancelButtonText: "No, cancel",
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Here you can submit the form or send the data to the server
                        form.submit();
                    }
                });
            }
        });
        
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = document.querySelectorAll('.search-input');

    function createEmptyState(level) {
        const emptyState = document.createElement('div');
        emptyState.id = `emptyState-${level}`;
        emptyState.className = 'row';
        emptyState.innerHTML = `
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">No Employees Found</h5>
                        <p class="card-text">There are no employees matching your search criteria.</p>
                    </div>
                </div>
            </div>
        `;
        return emptyState;
    }

    searchInputs.forEach(searchInput => {
        searchInput.addEventListener('input', function() {
            const level = this.dataset.id;
            const searchTerm = this.value.toLowerCase();
            const employeeList = document.getElementById(`employeeList-${level}`);
            const employeeRows = employeeList.querySelectorAll('.employee-row');
            let visibleRows = 0;

            employeeRows.forEach(row => {
                const employeeName = row.querySelector('.fw-medium').textContent.toLowerCase();
                const employeeId = row.querySelector('.text-muted.ms-1').textContent.toLowerCase();
                const jobLevel = row.querySelector('.col:nth-child(2) .fw-medium').textContent.toLowerCase();
                const designation = row.querySelector('.col:nth-child(3) .fw-medium').textContent.toLowerCase();
                const unit = row.querySelector('.col:nth-child(4) .fw-medium').textContent.toLowerCase();

                if (employeeName.includes(searchTerm) || employeeId.includes(searchTerm) || 
                    jobLevel.includes(searchTerm) || designation.includes(searchTerm) || 
                    unit.includes(searchTerm)) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });

            const existingEmptyState = document.getElementById(`emptyState-${level}`);

            if (visibleRows === 0 && !existingEmptyState) {
                employeeList.appendChild(createEmptyState(level));
            } else if (visibleRows > 0 && existingEmptyState) {
                existingEmptyState.remove();
            }
        });
    });
});

$('#importRatingButton').on('click', function(e) {
    e.preventDefault();
    const form = $('#importRating').get(0);
    const submitButton = $(this);
    const spinner = submitButton.find(".spinner-border");

    if (form.checkValidity()) {
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
        // If the form is not valid, trigger HTML5 validation messages
        form.reportValidity();
    }
});