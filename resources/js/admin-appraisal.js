import $ from 'jquery';

import Swal from "sweetalert2";
window.Swal = Swal;

$(document).ready(function() {
    $('#adminAppraisalTable').DataTable({
        stateSave: true,
        buttons: [
            'copy', 'csv'
        ],
        fixedColumns: {
            leftColumns: 0,
            rightColumns: 1
        },
        scrollCollapse: true,
        scrollX: true // Enable horizontal scrolling for large tables
    });
});