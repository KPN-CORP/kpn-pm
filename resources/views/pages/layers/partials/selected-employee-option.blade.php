{{-- Opsi terpilih untuk dropdown karyawan (Select2 AJAX /search-employee).
     Nama diambil dari tabel employees, sama dengan sumber pencarian. --}}
@if (!empty($id))
<option value="{{ $id }}" selected>{{ $approverNames[$id] ?? $id }} {{ $id }}</option>
@endif
