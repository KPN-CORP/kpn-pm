import bootstrap from "bootstrap/dist/js/bootstrap.bundle.min.js";

/**
 * Popover dengan event delegation — SATU instance untuk seluruh halaman.
 *
 * Sebelumnya beberapa file memanggil:
 *
 *     document.querySelectorAll('[data-bs-toggle="popover"]')
 *         .forEach(el => new bootstrap.Popover(el));
 *
 * Pada report Goal itu berarti 1.423 objek Popover dibuat sekaligus, dan
 * fungsinya dipanggil sampai tiga kali per pemuatan report (sekali pada konten
 * LAMA sebelum AJAX, lalu dua kali di callback success) — sekitar 4.270
 * instance untuk satu kali muat. Di halaman On Behalf bahkan dipasang ulang
 * pada setiap "draw" DataTables, jadi tiap ganti halaman/urut/cari
 * membangun ulang semuanya.
 *
 * Bootstrap mendukung delegasi lewat opsi `selector`: satu instance dipasang
 * di <body>, dan instance untuk elemen anak baru dibuat saat benar-benar
 * di-hover. Konten HTML yang di-inject lewat AJAX otomatis ikut bekerja tanpa
 * perlu inisialisasi ulang.
 *
 * Semua popover di aplikasi ini memakai data-bs-trigger="hover focus"
 * (satu memakai "hover" saja), jadi trigger delegasinya disetel sama.
 */

let delegatedPopover = null;

export function initializePopovers() {
    if (delegatedPopover) {
        return delegatedPopover;
    }

    if (!document.body) {
        return null;
    }

    delegatedPopover = new bootstrap.Popover(document.body, {
        selector: '[data-bs-toggle="popover"]',
        trigger: "hover focus",
    });

    return delegatedPopover;
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializePopovers);
} else {
    initializePopovers();
}

// Beberapa view/skrip lama memanggilnya lewat global.
window.initializePopovers = initializePopovers;

export default initializePopovers;
