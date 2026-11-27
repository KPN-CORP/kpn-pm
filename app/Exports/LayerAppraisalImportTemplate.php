<?php

namespace App\Exports;

use App\Imports\ApprovalLayerAppraisalImport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Template import Layer Appraisal. Header diambil langsung dari
 * ApprovalLayerAppraisalImport supaya template selalu sama dengan yang
 * divalidasi import (dulu file .xls statis di server).
 *
 * Warna, lebar kolom, border dan perataan meniru template lama
 * (template_import_appraisal_layer_v2.xls): warna header menandai kelompok
 * kolom untuk user. Kolom kpi_unit punya warna sendiri.
 */
class LayerAppraisalImportTemplate implements FromArray, WithHeadings, WithEvents
{
    /** Warna header per kelompok kolom (prefix nama kolom => RGB). */
    private const GROUP_COLORS = [
        'employee_'    => '92D050', // hijau
        'manager_'     => 'FFFF00', // kuning
        'peers_'       => '99CCFF', // biru
        'subordinate_' => 'CCFFCC', // hijau muda
        'calibrator_'  => 'FFFFCC', // kuning muda
    ];

    /** Warna kolom KPI Unit (kpi_unit, keterangan, periode, status_aktif): peach. */
    private const KPI_UNIT_COLOR = 'F8CBAD';

    /** Lebar kolom dari template lama; kolom lain memakai default. */
    private const WIDTHS = [
        'employee_id' => 11.44, 'employee_name' => 14.66, 'layer_period' => 12.0,
        'manager_id_1' => 12.55, 'manager_name_1' => 15.78,
        'peers_id' => 9.89, 'peers_name' => 13.11,
        'subordinate_id' => 15.22, 'subordinate_name' => 18.44,
        'calibrator_id' => 13.44, 'calibrator_name' => 16.55,
        'calibrator_id_10' => 14.44, 'calibrator_name_10' => 17.66,
        'kpi_unit' => 10.0, 'keterangan' => 40.0, 'periode' => 9.0, 'status_aktif' => 12.0,
    ];

    /** Sertakan kolom KPI Unit (hanya untuk user yang boleh import KPI Unit). */
    private bool $withKpiUnit;

    /**
     * Karyawan yang diisi di template (employee_id + nama), kolom layer
     * dibiarkan kosong untuk diisi admin.
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private array $employees;

    /** Periode layer yang diisi di kolom layer_period tiap baris. */
    private ?int $period;

    public function __construct(bool $withKpiUnit = true, iterable $employees = [], ?int $period = null)
    {
        $this->withKpiUnit = $withKpiUnit;
        $this->period = $period;
        $this->employees = collect($employees)
            ->map(fn ($e) => [(string) $e[0], (string) $e[1]])
            ->values()
            ->all();
    }

    public function headings(): array
    {
        return ApprovalLayerAppraisalImport::templateHeaders($this->withKpiUnit);
    }

    public function array(): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->headings() as $i => $header) {
                    $col = Coordinate::stringFromColumnIndex($i + 1);
                    $cell = $col . '1';

                    $color = $this->colorFor($header);
                    $style = $sheet->getStyle($cell);
                    $style->getFont()->setName('Calibri')->setSize(11)->setBold(false);
                    $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    if ($color) {
                        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
                    }
                    // Template lama: kolom subordinate_name_* tidak di-center
                    if (! str_starts_with($header, 'subordinate_name_')) {
                        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    if ($width = $this->widthFor($header)) {
                        $sheet->getColumnDimension($col)->setWidth($width);
                    }
                }

                // employee_id ditulis sebagai teks supaya angka 0 di depan tidak hilang
                $headers = $this->headings();
                $idCol = Coordinate::stringFromColumnIndex(array_search('employee_id', $headers, true) + 1);
                $nameCol = Coordinate::stringFromColumnIndex(array_search('employee_name', $headers, true) + 1);
                $periodCol = Coordinate::stringFromColumnIndex(array_search(ApprovalLayerAppraisalImport::LAYER_PERIOD_HEADER, $headers, true) + 1);
                $sheet->getStyle($idCol . ':' . $idCol)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

                foreach ($this->employees as $i => [$id, $name]) {
                    $row = $i + 2;
                    $sheet->setCellValueExplicit($idCol . $row, $id, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit($nameCol . $row, $name, DataType::TYPE_STRING);
                    if ($this->period) {
                        $sheet->setCellValueExplicit($periodCol . $row, $this->period, DataType::TYPE_NUMERIC);
                    }
                }
            },
        ];
    }

    private function colorFor(string $header): ?string
    {
        if (in_array($header, ApprovalLayerAppraisalImport::KPI_UNIT_HEADERS, true)) {
            return self::KPI_UNIT_COLOR;
        }

        if ($header === ApprovalLayerAppraisalImport::LAYER_PERIOD_HEADER) {
            return self::GROUP_COLORS['employee_'];
        }

        foreach (self::GROUP_COLORS as $prefix => $rgb) {
            if (str_starts_with($header, $prefix)) {
                return $rgb;
            }
        }

        return null;
    }

    private function widthFor(string $header): ?float
    {
        if (isset(self::WIDTHS[$header])) {
            return self::WIDTHS[$header];
        }

        // peers_id_2 -> peers_id, calibrator_name_3 -> calibrator_name, ...
        $base = preg_replace('/_\d+$/', '', $header);

        return self::WIDTHS[$base] ?? null;
    }
}
