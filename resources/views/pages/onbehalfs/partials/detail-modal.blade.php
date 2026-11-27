{{--
    Popup pembungkus detail Goals / Appraisal (On Behalf), dimuat lewat AJAX.

    goal-approval.js mencari elemen .modal di response lalu menampilkannya;
    tanpa pembungkus ini isi detail tertempel langsung di bawah tabel.

    Variabel: $title (judul popup), $view (partial isi), $row (diteruskan).
--}}
<div class="modal fade" tabindex="-1" aria-labelledby="onBehalfDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="onBehalfDetailTitle">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-primary-subtle">
                @include($view, ['row' => $row])
            </div>
        </div>
    </div>
</div>
