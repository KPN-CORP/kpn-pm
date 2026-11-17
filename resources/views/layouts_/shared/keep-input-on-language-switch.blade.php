{{--
    Ganti bahasa (topbar) me-reload halaman, sehingga input form yang belum disimpan hilang.
    Form dengan atribut data-keep-input disimpan dulu ke sessionStorage saat link bahasa diklik,
    lalu diisi kembali setelah reload (termasuk step aktif stepper appraisal).

    File yang dipilih tidak bisa disimpan browser, jadi user diberi peringatan untuk memilih ulang.
    Script ini harus jalan sebelum modul Vite (appraisal.js membaca step dari .step[data-step]).
--}}
<script>
(function () {
    const KEY = 'keepInput:' + location.pathname;
    const MAX_AGE = 10 * 60 * 1000; // 10 menit
    const forms = () => Array.from(document.querySelectorAll('form[data-keep-input]'));

    function fieldsOf(form) {
        return Array.from(form.elements).filter(el =>
            el.name && el.name !== '_token' && !el.disabled &&
            !['file', 'submit', 'button', 'reset', 'password'].includes(el.type));
    }

    function snapshot() {
        return forms().map(form => ({
            id: form.id,
            step: (document.querySelector('.form-step.active') || {}).dataset?.step || null,
            fields: fieldsOf(form).map(el => ({
                name: el.name,
                type: el.type,
                value: el.type === 'select-multiple' ? Array.from(el.selectedOptions).map(o => o.value) : el.value,
                checked: el.checked,
            })),
        }));
    }

    function hasSelectedFiles() {
        return forms().some(form => Array.from(form.querySelectorAll('input[type=file]')).some(i => i.files && i.files.length))
            || (document.getElementById('fileCards')?.children.length || 0) > 0;
    }

    // ---- Simpan saat link bahasa diklik ----
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href*="/language/"]');
        if (!link || !forms().length) return;

        const save = () => {
            try { sessionStorage.setItem(KEY, JSON.stringify({ at: Date.now(), forms: snapshot() })); } catch (err) {}
        };

        if (hasSelectedFiles() && window.Swal) {
            e.preventDefault();
            e.stopImmediatePropagation();
            Swal.fire({
                icon: 'warning',
                title: 'Change language?',
                text: 'Your input will be kept, but selected files are not saved yet and must be chosen again.',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonColor: '#AB2F2B',
                confirmButtonText: 'Change language',
            }).then(result => {
                if (!result.isConfirmed) return;
                save();
                if (typeof showLoader === 'function') showLoader();
                location.href = link.href;
            });
            return;
        }
        save();
    }, true);

    // ---- Isi kembali setelah reload ----
    let saved = null;
    try {
        saved = JSON.parse(sessionStorage.getItem(KEY) || 'null');
        sessionStorage.removeItem(KEY);
    } catch (err) {}
    if (!saved || Date.now() - saved.at > MAX_AGE) return;

    saved.forms.forEach(data => {
        const form = (data.id && document.getElementById(data.id)) || forms()[0];
        if (!form) return;

        const byName = {};
        fieldsOf(form).forEach(el => (byName[el.name] = byName[el.name] || []).push(el));

        data.fields.forEach(f => {
            (byName[f.name] || []).forEach(el => {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    if (el.value === String(f.value)) el.checked = f.checked;
                    return;
                }
                if (el.type === 'hidden' && el.value === f.value) return;
                if (el.type === 'select-multiple') {
                    Array.from(el.options).forEach(o => (o.selected = (f.value || []).includes(o.value)));
                } else {
                    el.value = f.value;
                }
            });
        });

        // stepper appraisal membaca step awal dari .step[data-step] pertama
        const stepHolder = document.querySelector('.step[data-step]');
        if (data.step && stepHolder) stepHolder.setAttribute('data-step', data.step);

        // jalankan ulang hitungan / tampilan yang bergantung pada input (mis. preview achievement KPI)
        window.addEventListener('load', function () {
            fieldsOf(form).forEach(el => {
                if (el.type === 'hidden') return;
                el.dispatchEvent(new Event(el.tagName === 'SELECT' || el.type === 'radio' || el.type === 'checkbox' ? 'change' : 'input', { bubbles: true }));
            });
        });
    });
})();
</script>
