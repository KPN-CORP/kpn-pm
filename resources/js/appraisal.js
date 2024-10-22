import $ from 'jquery';

import Swal from "sweetalert2";
window.Swal = Swal;

function yearAppraisal() {
    $("#formYearAppraisal").submit();
}

window.yearAppraisal = yearAppraisal;

$(document).ready(function() {
    let currentStep = $('.step').data('step');
    const totalSteps = $('.form-step').length;

    function updateStepper(step) {
        $('.circle').removeClass('active completed');
        $('.circle').each(function(index) {
            if (index < step - 1) {
                $(this).addClass('completed');
            } else if (index == step - 1) {
                $(this).addClass('active');
            }
        });

        $('.form-step').removeClass('active').hide();
        $(`.form-step[data-step="${step}"]`).addClass('active').fadeIn();

        if (step === 1) {
            $('.prev-btn').hide();
        } else {
            $('.prev-btn').show();
        }

        if (step === totalSteps) {
            $('.next-btn').hide();
            $('.submit-user').show();
        } else {
            $('.next-btn').show();
            $('.submit-user').hide();
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

    $('.submit-user').click(function () {
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
                text = "You can still change it as long as the manager hasn't approved it yet";
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
        
                        document.getElementById("formAppraisalUser").submit();
        
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