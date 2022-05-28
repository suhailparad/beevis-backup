<?php

namespace App\Export;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet;

Sheet::macro('styleCells', function (Sheet $sheet, string $cellRange, array $style) {
    $sheet->getDelegate()->getStyle($cellRange)->applyFromArray($style);
});

class StockExport implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithEvents, WithColumnFormatting{

    protected $data;
    protected $count;

    public function __construct(array $data, int $count)
    {
        $this->data = $data;
        $this->count = $count;
    }

    public function title(): string
    {
        return 'Stock';
    }

    public function headings(): array
    {
        return [
            [
                'Barcode',
                'Sku',
                'Product Name',
                'Quantity',
            ]
        ];
    }

    public function array(): array
    {
        return $this->data;
    }

    public function registerEvents(): array
    {
        // $this->CHAR = 'R';

        return [
            AfterSheet::class => function (AfterSheet $event) {

                // $event->sheet->getDelegate()->mergeCells('A1:'.$this->CHAR.'1');
                // $event->sheet->getDelegate()->mergeCells('A2:'.$this->CHAR.'2');
                // $event->sheet->getDelegate()->getStyle('A1:'.$this->CHAR.'1')->getAlignment()->setHorizontal('left');
                // $event->sheet->getDelegate()->getStyle('A2:'.$this->CHAR.'2')->getAlignment()->setHorizontal('left');
                // $event->sheet->getDelegate()->getStyle('A1:'.$this->CHAR.'1')->getFont()->setBold(true);
                // $event->sheet->getDelegate()->getStyle('A2:'.$this->CHAR.'2')->getFont()->setBold(true);
                // $event->sheet->getDelegate()->getStyle('A3:'.$this->CHAR.'3')->getFont()->setBold(true);
                // // $event->sheet->getDelegate()->mergeCells('A1:'.$this->CHAR.'1');
                // $event->sheet->getDelegate()->getStyle("A1")->getFont()->setSize(14);

                // $event->sheet->getDelegate()->getColumnDimension('Q')->setWidth(50);


                // $event->sheet->getDelegate()->getStyle( "A1:$this->CHAR$this->cols")->getAlignment()->setVertical('top');

                // $event->sheet->styleCells(
                //     "A1:$this->CHAR$this->cols",
                //     [
                //         'borders' => [
                //             'allBorders' => [
                //                 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                //                 'color' => array('argb' => '22222200'),

                //             ],
                //         ],

                //     ],
                // );
            },
        ];
    }

    public function columnFormats(): array
    {
        return [
            // 'B' => NumberFormat::FORMAT_NUMBER,
        ];
    }

}
