function yearGoal() {
    $("#formYearGoal").submit();
}

window.yearGoal = yearGoal;

document.addEventListener('DOMContentLoaded', function () {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const taskContainers = [document.getElementById('task-container-1'), document.getElementById('task-container-2')];
    const noDataMessages = [document.getElementById('no-data-1'), document.getElementById('no-data-2')];

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

                if (visibleTaskCount === 0) {
                    noDataMessages[index].style.display = 'block';
                } else {
                    noDataMessages[index].style.display = 'none';
                }
            });
        });
    });
});

document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("customsearch");
    const taskCards = document.querySelectorAll(".task-card");
    const noDataMessages = [document.getElementById('no-data-1'), document.getElementById('no-data-2')];

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
});