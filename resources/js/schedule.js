import $ from 'jquery';

import select2 from "select2"
select2(); 

import Quill from "quill";

// function handleDelete(element) {
//     var scheduleId = element.getAttribute('data-id');
//     // console.log(scheduleId);
//     Swal.fire({
//         title: 'Are you sure?',
//         text: "This schedule will deleted!",
//         icon: 'warning',
//         showCancelButton: true,
//         confirmButtonColor: '#3085d6',
//         cancelButtonColor: '#d33',
//         confirmButtonText: 'Yes, delete it!',
//         cancelButtonText: 'Cancel',
//         reverseButtons: true,
//     }).then((result) => {
//         if (result.isConfirmed) {
//             fetch('/schedule/' + scheduleId, {
//                 method: 'DELETE',
//                 headers: {
//                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
//                     'Content-Type': 'application/json'
//                 }
//             })
//             .then(response => {
//                 if (!response.ok) {
//                     throw new Error('An error occurred while deleting the data.');
//                 }
//                 Swal.fire(
//                     'Deleted!',
//                     'Your data has been deleted.',
//                     'success'
//                 ).then(() => {
//                     location.reload();
//                 });
//             })
//             .catch(error => {
//                 console.error('Error:', error);
//                 Swal.fire(
//                     'Error!',
//                     'An error occurred while deleting the data.',
//                     'error'
//                 );
//             });
//         }
//     });
// }

// window.handleDelete = handleDelete;
function confirmDelete(scheduleId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This schedule will be deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Atur action pada form tersembunyi dan submit
            var form = document.getElementById('delete-form');
            form.action = '/schedule/' + scheduleId + '/delete';
            form.submit();
        }
    });
}
window.confirmDelete = confirmDelete;

const toolbarOptions = [['bold', 'italic', 'underline'], [{ 'list': 'ordered'}, { 'list': 'bullet' }], [{ 'indent': '-1'}, { 'indent': '+1' }]];


var editorContainer = document.querySelector('#editor-container');

if (editorContainer) {
    var quill = new Quill('#editor-container', {
        theme: 'snow',
        placeholder: 'Enter messages...',
        modules: {
            toolbar: toolbarOptions
        }
    });
}

if (document.querySelector('#scheduleForm')) {
    document.getElementById('scheduleForm').addEventListener('submit', function() {
        document.querySelector('textarea[name=messages]').value = quill.root.innerHTML;
    });
    
    document.getElementById('scheduleForm').addEventListener('submit', function() {
        var repeatDaysButtons = document.getElementsByName('repeat_days[]');
        var repeatDaysSelected = [];
        repeatDaysButtons.forEach(function(button) {
            if (button.classList.contains('active')) {
                repeatDaysSelected.push(button.value);
            }
        });
        document.getElementById('repeatDaysSelected').value = repeatDaysSelected.join(',');
    });
}

function toggleDivs() {
    var selectBox = document.getElementById("inputState");
    var repeatOnDiv = document.getElementById("repeaton");
    var beforeEndDateDiv = document.getElementById("beforeenddate");
    
    if (selectBox.value === "repeaton") {
        repeatOnDiv.style.display = "block";
        beforeEndDateDiv.style.display = "none";
    } else {
        repeatOnDiv.style.display = "none";
        beforeEndDateDiv.style.display = "block";
    }
}

function validateInput(input) {
    //input.value = input.value.replace(/[^0-9,]/g, '');
    input.value = input.value.replace(/[^0-9]/g, '');
}

$(document).ready(function() {
    $('.select2').select2({
        theme: "bootstrap-5",
    });
});


document.addEventListener("DOMContentLoaded", function () {

    const scheduleTable = $("#scheduleTable").DataTable({
        dom: "lrtip",
        stateSave: true,
        fixedColumns: {
            leftColumns: 0,
            rightColumns: 1
        },
        pageLength: 50,
        scrollCollapse: true,
        scrollX: true
    });

    $("#customsearch").on("keyup", function () {
        scheduleTable.search($(this).val()).draw();
    });

});


// Load the initial content into the Quill editor
// var initialContent = `{!! $model->messages !!}`;
// quill.clipboard.dangerouslyPasteHTML(initialContent);