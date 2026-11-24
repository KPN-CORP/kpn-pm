<?php

return [
    /*
     * Siapa yang boleh import KPI Unit lewat import Layer Appraisal.
     *  - 'adminemails' : hanya user dengan email di admin_emails; user lain
     *                    tidak bisa import KPI Unit dan template tanpa kolom KPI Unit.
     *  - kosong/lainnya: semua user yang bisa import layer (perilaku lama).
     */
    'import_mode' => env('IMPORT_KPI_UNIT'),

    'admin_emails' => array_values(array_filter(array_map(
        fn ($email) => strtolower(trim($email)),
        explode(',', (string) env('IMPORT_KPI_UNIT_ADMIN_EMAILS', ''))
    ))),
];
