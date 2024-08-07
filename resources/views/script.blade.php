<!-- Select2 JS -->
<script src="{{ asset('vendor/select2/dist/js/select2.min.js') }}?v={{ config('app.version') }}"></script>

<!-- Core plugin JavaScript-->
<!-- Page level plugins -->
<script src="{{ asset('vendor/datatables/dataTables.min.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}?v={{ config('app.version') }}"></script>

<script src="{{ asset('vendor/datatables.net-fixedcolumns/js/dataTables.fixedColumns.min.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('vendor/datatables.net-fixedcolumns-bs5/js/fixedColumns.bootstrap5.min.js') }}?v={{ config('app.version') }}"></script>

<script src="{{ asset('assets/js/popper.min.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('assets/js/tippy.min.js') }}?v={{ config('app.version') }}"></script>

<script src="{{ asset('js/script.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('js/quill.min.js') }}?v={{ config('app.version') }}"></script>

<script src="{{ asset('js/report.js') }}?v={{ config('app.version') }}"></script>

@if(Session::has('toast'))
<script>
    const toastData = {!! json_encode(Session::get('toast')) !!};

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
    });

    Toast.fire({
        icon: toastData.type,
        title: toastData.message
    });
</script>
@endif