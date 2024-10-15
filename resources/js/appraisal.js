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

    $('.submit-btn').click(function() {
        if (validateStep(currentStep)) {
            return true;
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
        let currentValue = $(this).val();
        let numberPart = currentValue.replace(/[^0-9]/g, ''); // Remove non-numeric characters
        $(this).val(numberPart);
    });
});