<?php
namespace app\components;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Yii;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Class ExcelHelper
 * @package app\components
 */
class ExcelHelper extends \yii\base\Component
{
    /**
     * Папка источника с документом и конфигом к нему.
     * @var string $dir_source
     */
    public $dir_source = 'documents';
    /**
     * Название документа xls.
     * @var string $file_name
     */
    public $file_name = 'Р21001';
    /**
     * Формат документа.
     * @var string $format
     */
    public $format = 'Xls';
    /**
     * Расширение файла на выходе.
     * @var string $format
     */
    public $file_ext = 'file_ext';
    /**
     * Файл на выходе.
     * @var string $format
     */
    public $file_export = 'Р21001_test.xls';
    /**
     * PhpOffice\PhpSpreadsheet\Spreadsheet.
     * @var Spreadsheet $spreadsheet
     */
    public $spreadsheet;
    
    public $file_map;

    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    public function init(){
        parent::init();
        // проверка и загрузка конфигурационного файла с позициями
        if (file_exists($this->dir_source.'/'. $this->file_name.'.'. $this->file_ext)
            && file_exists($this->dir_source.'/'. $this->file_name.'.yml')) {
            $this->spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->dir_source.'/'.
                $this->file_name.'.'. $this->file_ext);
            $this->file_map = \yaml_parse_file($this->dir_source.'/'. $this->file_name.'.yml') ;
        }
    }

    /**
     * заполнение значений у позиций
     * @param array $data массив данных
     * @param int $number_list номер листа
     * @return mixed
     */
    public function setData($data = [], $number_list = 0) {
        foreach ($data as $key=>$item) {
            $this->file_map[$number_list][$key]['value'] = $item;
        }
    }

    /**
     * удаление листа по индексу
     * @param int $index индекс вкладки
     * @return mixed
     */
    public function removeSheet($index) {
        $this->spreadsheet->removeSheetByIndex($index);
    }

    /**
     * занесение данных в документ Excel
     * @return mixed
     */
    public function execute() {
        foreach ($this->file_map as $number_sheet=>$items) {
            $sheet = $this->spreadsheet->getSheet($number_sheet);
            foreach ($items as $key_item=>$item) {
                if (isset($item['value']) && !empty($item['value'])) {
                    $s = $item['value'];

       //             foreach ($item['lines'] as $line) {
                    $number_line = 0;
                    if (isset($item['lines'][$number_line])) {
                        $line = $item['lines'][$number_line];
                        $l_s = mb_strlen($s);
                        $col = $line['col_start']; $row = $line['row_start'];
                        $col_end = $line['col_end']; $row_end = $line['row_end'];

                        for ($i=0; $i<$l_s; $i++) {
                            $char = mb_substr($s,$i,1);
                            $sheet->setCellValueExplicit($col.$row, $char, DataType::TYPE_STRING);
                            if (($col == $col_end) && ($i<$l_s-1)) {
                                $number_line++;
                                if (isset($item['lines'][$number_line])) {
                                    $line = $item['lines'][$number_line];
                                    $col = $line['col_start']; $row = $line['row_start'];
                                    $col_end = $line['col_end']; $row_end = $line['row_end'];
                                } else {
                                    var_dump($item);
                                    echo 'Строка слишком длинная'; exit;
                                }
                            } else {
                                $col++; $col++; $col++;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * вывод документа в браузер.
     * @param int $type_output тип вывода
     * @param string $file_name имя файла
     * @return mixed
     */
    public function sendToBrowser($type_output, $file_name = 'test.xls') {
        $this->file_export = $file_name;
        switch ($type_output) {
            case 1:
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this->spreadsheet, $this->format);
                break;
            case 2:
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdf($this->spreadsheet);
                $writer->writeAllSheets();
                break;
            case 3:
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf($this->spreadsheet);
                $writer->writeAllSheets();
                break;
            case 4:
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf($this->spreadsheet);
                $writer->writeAllSheets();
                break;
            case 5:
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Html($this->spreadsheet);
                $writer->writeAllSheets();
                break;
        }

        // Выводим HTTP-заголовки
        header ( "Expires: Mon, 1 Apr 1974 05:00:00 GMT" );
        header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
        header ( "Cache-Control: no-cache, must-revalidate" );
        header ( "Pragma: no-cache" );
        if ($type_output == 1) {
            header ( "Content-type: application/vnd.ms-excel" );
        } elseif ($type_output == 5) {
            header('Content-Type: text/html');
        } else {
            header('Content-Type: application/pdf');
        }
        header ( "Content-Disposition: attachment; filename=".$this->file_export );

        ob_end_clean();
        $writer->save('php://output');
    }

}


