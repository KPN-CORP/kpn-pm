$("#submit").on("click", function (event) {
    event.preventDefault();

    const submitButton = $(this).closest(".btn-primary");
    const spinner = submitButton.find(".spinner-border");
    const form = $("#guide-form").get(0);

    if (form.checkValidity()) {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3e60d5",
            cancelButtonColor: "#f15776",
            confirmButtonText: "Yes, Save it!",
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                submitButton.prop("disabled", true);
                submitButton.addClass("disabled");
                if (spinner.length) {
                    spinner.removeClass("d-none");
                }
                form.submit(); // Use the native DOM submit method
            }
        });
    } else {
        // If form is not valid, trigger HTML5 validation messages
        form.reportValidity();
    }
});

document.getElementById("deleteToggle").addEventListener("change", function () {
    var deleteButtons = document.querySelectorAll(".deleteBtn");
    if (this.checked) {
        deleteButtons.forEach(function (deleteBtn) {
            deleteBtn.classList.remove("hide");
            deleteBtn.classList.add("show");
        });
    } else {
        deleteButtons.forEach(function (deleteBtn) {
            deleteBtn.classList.remove("show");
            deleteBtn.classList.toggle("hide");
            setTimeout(function () {
                deleteBtn.classList.remove("hide");
            }, 30);
        });
    }
});

// if (this.checked) {
//     deleteBtn.classList.remove("hide");
//     deleteBtn.classList.add("show");
// } else {
//     deleteBtn.classList.remove("show");
//     deleteBtn.classList.toggle("hide");
//     setTimeout(function () {
//         deleteBtn.classList.remove("hide");
//     }, 30);
// }
