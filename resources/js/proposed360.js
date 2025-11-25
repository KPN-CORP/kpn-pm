import $ from 'jquery';

import Swal from "sweetalert2";

import bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';

(function(){
  function pickValues(form, selector){
    return [...form.querySelectorAll(selector)]
      .map(el => el && el.value ? String(el.value) : null)
      .filter(Boolean)
      .filter((v,i,a) => a.indexOf(v) === i)
      .slice(0,3);
  }

  // ⬇️ APPEND (tanpa clear)
  function appendHiddenList(container, name, values){
    values.forEach(v => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name + '[]';
      input.value = v;
      container.appendChild(input);
    });
  }

  function validateFirstLayer(peers, subs, employeeLabel, hasSubordinates){
    if (!peers[0] || !subs[0]) {
      const who = employeeLabel ? ` untuk ${employeeLabel}` : '';
      if (window.Swal) Swal.fire({icon:'error',title:'Validasi',text:'Minimal pilih Peers 1 dan Subordinate 1' + who + '.'});
      else alert('Minimal pilih Peers 1 dan Subordinate 1' + who + '.');
      return false;
    }
    return true;
  }

  function hasSubordinates(employeeId) {
      const el = document.getElementById(`havingSubs_${employeeId}`);
      
      if (!el) return false;

      const v = (el.value ?? '').toString().trim().toLowerCase();
      // console.log('subs for', employeeId, '=>', v);

      return v === '1' || v === 'true' || v === 'yes';
  }


  document.querySelectorAll('.btn-approve').forEach(btn => {
      btn.addEventListener('click', function(e){
          const approveForm = e.currentTarget.closest('form');
          const cloneArea   = approveForm.querySelector('.js-clone-area');
          const sourceForm  = document.getElementById(e.currentTarget.dataset.sourceForm);

          if (!sourceForm || !cloneArea) {
              approveForm.submit();
              return;
          }

          const employeeId = sourceForm.querySelector('input[name="employee_id"]')?.value ?? '';

          const peers = pickValues(sourceForm, 'select[name="peers[]"]');
          const subs  = pickValues(sourceForm, 'select[name="subordinates[]"]');

          console.log(subs);
          

          // FIXED: hanya blokir jika punya subordinate tapi tidak input nilai
          if ( hasSubordinates(employeeId) && subs.length === 0 ) {
              Swal.fire('Subordinates required', 'Pilih minimal 1 bawahan untuk melanjutkan.', 'warning');
              return;
          }

          cloneArea.innerHTML = '';
          appendHiddenList(cloneArea, 'peers', peers);
          appendHiddenList(cloneArea, 'subordinates', subs);

          const sp = e.currentTarget.querySelector('.spinner-border');
          if (sp) sp.classList.remove('d-none');
          e.currentTarget.disabled = true;

          approveForm.submit();
      });
});

})();

(function(){
  const alertEl = document.getElementById('alertField');

  function showAlert(msg){
    if(!alertEl) return;
    alertEl.hidden = false;
    alertEl.classList.remove('fade');
    const s = alertEl.querySelector('strong'); if (s) s.textContent = msg;
    alertEl.scrollIntoView({behavior:'smooth', block:'center'});
  }
  function clearErrors(box){ box.querySelectorAll('.error-message').forEach(e=>e.textContent=''); }
  function setErr(sel,msg){
    const holder = sel.closest('.col, .mb-2, .mb-3') || sel.parentElement;
    const err = holder && holder.querySelector('.error-message'); if (err) err.textContent = msg;
  }

  // Ambil teks "Nama (employee_id)" dari baris label Employee di kartu
  function getEmployeeLabel(box){
    // kalau ada elemen khusus, pakai itu lebih dulu
    const mark = box.querySelector('[data-employee-label]');
    if (mark && mark.textContent) return mark.textContent.trim();

    // cari baris yang mengandung teks "Employee"
    const rows = box.querySelectorAll('.row');
    for (const r of rows) {
      const ps = Array.from(r.querySelectorAll('p'));
      if (ps.some(p => p.textContent.trim().toLowerCase() === 'employee')) {
        // ambil p terakhir pada baris tsb (kolom nama)
        const lastP = ps[ps.length - 1];
        if (lastP) return lastP.textContent.trim();
      }
    }
    // fallback
    const h = box.closest('.card')?.querySelector('h5')?.textContent?.trim();
    return h || 'employee ini';
  }

  // Validasi kartu: Peers 1 wajib; Subordinate 1 wajib HANYA jika inputnya ada
  function validateCard(box){
    clearErrors(box);
    const peer1 = box.querySelector('select#peer1, select[id^="peer1_"]');
    const sub1  = box.querySelector('select#sub1,  select[id^="sub1_"]');
    let ok = true;

    if (!peer1 || !peer1.value) { ok = false; if (peer1) setErr(peer1,'Peers layer 1 wajib dipilih.'); }
    if (sub1 && !sub1.value)    { ok = false; setErr(sub1,'Subordinate layer 1 wajib dipilih.'); }

    if (!ok) {
      const label = sub1 ? "Minimal pilih 1 Peers (layer 1) & 1 Subordinate (layer 1) untuk " + getEmployeeLabel(box) : "Minimal pilih 1 Peers (layer 1) untuk " + getEmployeeLabel(box);
      showAlert(`${label}.`);
    }
    return ok;
  }

    function getFormFromButton(btn) {
        if (btn.form) return btn.form; // kalau tombol berada di dalam form
        const formId = btn.getAttribute('form'); // tombol di luar form pakai atribut "form"
        if (formId) {
        const f = document.getElementById(formId);
        if (f) return f;
        }
        return btn.closest('form'); // fallback terakhir
    }

    function showAlert(msg){
        const el = document.getElementById('alertField');
        if (!el) return;
        el.hidden = false; el.classList.remove('fade');
        const s = el.querySelector('strong'); if (s) s.textContent = msg;
        el.scrollIntoView({ behavior:'smooth', block:'center' });
    }

    document.querySelectorAll('button[data-submit]').forEach((btn) => {
      btn.addEventListener('click', function (e) {
          e.preventDefault();

          const form = getFormFromButton(btn);
          if (!form) {
              showAlert('Form tidak ditemukan.');
              return;
          }

          const box = form.closest?.('.card-body') || form; // aman walau form null (sudah diguard)
          if (!validateCard(box)) return;

          // Tampilkan SweetAlert2 sebelum submit
          Swal.fire({
              title: "Are you sure?",
              text: "You won't be able to revert this!",
              icon: "warning",
              showCancelButton: true,
              confirmButtonColor: "#3e60d5",
              cancelButtonColor: "#f15776",
              confirmButtonText: "Submit",
              reverseButtons: true,
          }).then((result) => {
              if (result.isConfirmed) {
                  // Logika submit dipindahkan ke sini
                  const sp = btn.querySelector('.spinner-border');
                  if (sp) sp.classList.remove('d-none');
                  btn.disabled = true;
                  form.submit();
              } else {
                  // Jika user membatalkan, pastikan tombol tetap aktif (jika diperlukan)
                  // Di sini, kita tidak perlu melakukan apa-apa,
                  // karena spinner dan disabled hanya diaktifkan saat konfirmasi.
              }
          });
      });
   });

  // Submit via Enter → tetap tervalidasi
  document.querySelectorAll('form[action*="proposed360.store"]').forEach(f=>{
    f.addEventListener('submit', function(e){
      const box = f.closest('.card-body') || document;
      if (!validateCard(box)) e.preventDefault();
    });
  });

  // Approve/Sendback → tervalidasi juga
  document.querySelectorAll('form[action*="proposed360.action"]').forEach(f=>{
    f.addEventListener('submit', function(e){
      const box = f.closest('.card-body') || document;
      if (!validateCard(box)) e.preventDefault();
    });
  });

  // Tooltip bootstrap
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
})();

(function () {
  // Toast kanan atas
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end', // top-right
    showConfirmButton: false,
    timer: 5000,
    timerProgressBar: true
  });
  window.Toast = Toast; // optional: biar bisa dipakai di tempat lain

  // Handler Sendback
  document.querySelectorAll('form.proposed360-sendback .btn-sendback').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const form = btn.closest('form.proposed360-sendback');
      if (!form) return;

      // Popup textarea wajib
      const { value: text, isConfirmed } = await Swal.fire({
        title: 'Sendback message',
        input: 'textarea',
        inputPlaceholder: 'Input your reasons here...',
        inputAttributes: { 'aria-label': 'Sendback message', 'maxlength': '500', 'rows': '4' },
        inputValidator: (v) => !v?.trim() ? 'Field is mandatory.' : undefined,
        showCancelButton: true,
        confirmButtonColor: "#3e60d5",
        cancelButtonColor: "#f15776",
        confirmButtonText: 'Submit',
        reverseButtons: true,
      });

      if (!isConfirmed) return;

      // Set nilai ke hidden input lalu submit via fetch (AJAX)
      form.querySelector('input[name="sendback_message"]').value = text.trim();

      try {
        const fd = new FormData(form);
        const res = await fetch(form.getAttribute('action'), {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (res.ok) {
          Toast.fire({ icon: 'success', title: 'Success' });
          setTimeout(() => window.location.reload(), 800);
        } else {
          const msg = (await res.text()) || 'Gagal mengirim sendback.';
          Swal.fire('Gagal', msg.slice(0, 300), 'error');
        }
      } catch (err) {
        Swal.fire('Error', (err?.message || 'Terjadi kesalahan jaringan.'), 'error');
      }
    });
  });
})();