import $ from 'jquery';

function yearGoal(button) {
    // Get the form
    var form = $(button).closest('form');
    
    // Submit the form
    form.submit();
}

window.yearGoal = yearGoal;

$(document).ready(function () {
    // Initiate button click event
    $('[id^="initiateBtn"]').click(function (event) {
        event.preventDefault(); // Prevent the default link behavior
        
        var employeeId = $(this).data('id');
        var index = $(this).data('index'); // Get the index from data-attribute

        Swal.fire({
            title: 'Are you sure you want to initiate the goal setting?',
            // text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3e60d5',
            cancelButtonColor: '#f15776',
            confirmButtonText: 'Yes, initiate it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to the form using the index for the employee
                window.location.href = `/team-goals/form/${employeeId}`;
            }
        });
    });

});

document.addEventListener('DOMContentLoaded', function () {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const taskContainers = [document.getElementById('task-container-1'), document.getElementById('task-container-2')];

    filterButtons.forEach(button => {
        button.addEventListener('click', function () {
            const filter = this.getAttribute('data-id');            
            
            taskContainers.forEach((taskContainer, index) => {
                const tasks = taskContainer.querySelectorAll('.task-card');
                let visibleTaskCount = 0;
                
                tasks.forEach(task => {
                    const taskStatus = task.getAttribute('data-status');

                    if (filter === 'All Task' || taskStatus === filter) {
                        task.style.display = 'flex';
                        visibleTaskCount++;
                    } else {
                        task.style.display = 'none';
                    }
                });
            });
        });
    });
});

document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("customsearch");
    const taskCards = document.querySelectorAll(".task-card");
    const noDataMessages = [document.getElementById('no-data-1'), document.getElementById('no-data-2')];

    if(searchInput){

        searchInput.addEventListener("input", function() {
            const searchValue = this.value.toLowerCase().trim();
    
            taskCards.forEach(function(card) {
                const cardContent = card.textContent.toLowerCase();
                if (cardContent.includes(searchValue)) {
                    card.style.display = "";
                    $('#report-button').css('display', 'block');
                } else {
                    $('#report-button').css('display', 'none');
                    card.style.display = "none";
                }
            });
    
            // Menampilkan pesan jika tidak ada hasil pencarian
            const noDataMessage = document.getElementById("no-data-2");
            const visibleCards = document.querySelectorAll(".task-card[style='display: block;']");
        });
    }
});