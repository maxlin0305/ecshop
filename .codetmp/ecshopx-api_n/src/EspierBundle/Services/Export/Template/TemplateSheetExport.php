<?php

namespace EspierBundle\Services\Export\Template;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// sheet1模板
class TemplateSheetExport implements FromArray, WithTitle, WithHeadings, WithStyles
{
    private $sheetData;

    /*
        $params = [
            'sheetname' => 'sheet名称',
            'list' => [], // 单元格列表，包括头部
        ];
    */
    public function __construct($params)
    {
        $this->sheetData = $params;
    }

    /**
     * 填充单元格数据
     * @return array
     */
    public function array(): array
    {
        return $this->sheetData['list'];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [];
    }

    /**
     * 设置sheet名称
     * @return string
     */
    public function title(): string
    {
        return $this->sheetData['sheetname'];
    }

    public function styles(Worksheet $sheet)
    {
    }
}
