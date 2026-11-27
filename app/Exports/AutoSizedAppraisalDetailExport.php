<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * AppraisalDetailExport dengan lebar kolom otomatis. Dipakai hanya untuk
 * export kecil: auto-size menghitung lebar setiap sel dan jadi bagian
 * termahal penulisan file untuk sheet besar.
 */
class AutoSizedAppraisalDetailExport extends AppraisalDetailExport implements ShouldAutoSize
{
}
