import $ from 'jquery';

$(document).ready(function() {
    $('#tableAppraisalTeam').DataTable({
        stateSave: true,
        fixedColumns: {
            leftColumns: 0,
            rightColumns: 1
        },
        scrollCollapse: true,
        scrollX: true,
        paging: false
    });

    
    $('#tableAppraisal360').DataTable({
        stateSave: true,
        fixedColumns: {
            leftColumns: 0,
            rightColumns: 1
        },
        scrollCollapse: true,
        scrollX: true,
        paging: false
    });
});

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
