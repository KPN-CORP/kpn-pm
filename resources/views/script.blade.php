
{{-- <script src="{{ asset('js/report.js') }}"></script> --}}
{{-- Google Translate dihapus: callback googleTranslateElementInit tidak pernah di-expose ke window dan tidak ada elemen #google_translate_element, jadi script sinkron ini cuma menambah request pihak ketiga di setiap halaman. --}}

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