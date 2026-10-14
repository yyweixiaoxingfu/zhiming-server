<?php

namespace app\common\library;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * excel 文件导入导出
 * Class ExcelServer
 * @package app\common\library
 */
class ExcelServer
{
    /**
     * excel导出
     * 调用示例 ExcelService::export_old('会员表',[['name','会员'],['phone','手机号']],[['name'=>'xiaolan','phone'=>'13500135000'],['name'=>'小白','phone'=>'13800138000']]);
     * @param string $expTitle 文件命名
     * @param array $expCellName 数据列名称定义
     * @param array $expTableData 原始数据
     * @param bool $set_first 设置是否在第一行显示工作表名称
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function export_old($expTitle, $expCellName, $expTableData, $set_first = true, $options = []){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName =$xlsTitle . date('_YmdHis').'.xlsx';//or $xlsTitle 文件名称可根据自己情况设定
        $expCellName = array_values($expCellName);
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle($expTitle);//设置工作表标题名称

        // 设置单元格样式
        $styleArray = [
            //'font' => ['bold' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,],
        ];
        $styleArrayBody = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '666666'],
                ],
            ],
            'alignment' => [
                //'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        //表头   设置单元格内容
        $titleRow = 1;
        if($set_first){
            $worksheet->setCellValueByColumnAndRow(1, 1, $expTitle.date('Y-m-d H:i:s'));
            $worksheet->mergeCells('A1:'.$cellName[$cellNum-1].'1');
            $titleRow ++;
            $worksheet->getStyle('A1')->applyFromArray($styleArray)->getFont()->setSize(14);
           
        }
        for ($i = 0; $i < $cellNum; $i++){
            $worksheet->setCellValueByColumnAndRow($i+1, $titleRow, $expCellName[$i][1]);
        }
        $worksheet->getStyle('A'.$titleRow.':'.$cellName[$cellNum-1].$titleRow)->applyFromArray($styleArray)->getFont()->setSize(12);
        $worksheet->getStyle('A'.$titleRow.':'.$cellName[$cellNum-1].$titleRow)->getAlignment()->setWrapText(TRUE);
        $worksheet->getStyle('A'.$titleRow.':'.$cellName[$cellNum-1].$titleRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        if(isset($options['download']) && $options['download']=='car_import'){
            $worksheet->getstyle('F1:F1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $worksheet->getColumnDimension('A')->setWidth(10);
            $worksheet->getColumnDimension('B')->setWidth(25);
            $worksheet->getColumnDimension('C')->setWidth(17);
            $worksheet->getColumnDimension('D')->setWidth(25);
            $worksheet->getColumnDimension('E')->setWidth(15);
            $worksheet->getColumnDimension('F')->setWidth(50);
            $worksheet->getColumnDimension('G')->setWidth(30);
            $worksheet->getColumnDimension('H')->setWidth(10);
            $worksheet->getColumnDimension('I')->setWidth(10);
        }
       
        $row = $titleRow + 1;
        foreach ($expTableData as $item){
            for ($j = 0; $j < $cellNum; $j++){
                $column_name = $expCellName[$j][0];
                if (stripos($column_name, '.')) {
                    $allKey = explode('.', $column_name);
                    $value = $item;
                    foreach ($allKey as $k) {
                        $value = $value[$k] ?? null;
                    }
                } else {
                    if ($column_name == 'index'){
                        $value = $row - $titleRow;
                    }else{
                        $value = $item[$column_name] ?? '';
                    }
                }
                $worksheet->setCellValueByColumnAndRow($j + 1, $row, (string)$value);
            }
            $row ++;
        }
        $total_rows = $dataNum + $titleRow;
        $worksheet->getStyle('A1:'.$cellName[$cellNum-1].$total_rows)->applyFromArray($styleArrayBody);

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        if(isset($options['savePath'])){
            $dir = dirname($options['savePath']);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            if(file_exists($options['savePath'])){
                unlink($options['savePath']);
            }
            $writer->save($options['savePath']);
            exit;
        }else{
            header('Content-Disposition: attachment;filename="'.$fileName.'"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;
        }
    }

    

    public static function export($expTitle,$exportData,$options = []){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName =$xlsTitle . date('_YmdHis').'.xlsx';//or $xlsTitle 文件名称可根据自己情况设定
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle($expTitle);//设置工作表标题名称
        self::setData($spreadsheet,$exportData,$expTitle,$cellName);
        $spreadsheet->setActiveSheetIndex(0);
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        if(isset($options['savePath'])){
            $dir = dirname($options['savePath']);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $writer->save($options['savePath']);
        }else{
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$fileName.'"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
        }
        exit();
    }


    protected static function setData(Spreadsheet $spreadsheet,$exportData,$expTitle,$cellName){
        foreach ($exportData as $row => $v) {
            if (isset($v['sheet'])){
                if($row >0){
                    $spreadsheet->createSheet($row);
                    $spreadsheet->setActiveSheetIndex($row);
                }
                self::setData($spreadsheet,$v['data'],$v['sheet'],$cellName);
            }else{
                $worksheet = $spreadsheet->getActiveSheet();
                $worksheet->setTitle($expTitle);//设置工作表标题名称
                foreach ($v as $k => $item) {
                    $worksheet->setCellValueExplicit($cellName[$k] . ($row + 1), (string)$item,DataType::TYPE_STRING);
                }
            }
        }
    }

    /**
     * excel导入
     * 调用示例 ExcelService::import('file',['name','phone']);
     * @param mixed $file 上传excel文件接收字段 或者 excel文件绝对路径
     * @param array $data_fields 获取表格数据索引定义
     * @param int $first_row 表格数据从第几行开始读取
     * @param int $is_upload 上传文件 1 ，文件路径 0
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public static function import($file,$data_fields,$first_row = 2,$is_upload = 1){
        if($is_upload){
            //获取表格的大小，限制上传表格的大小5M
            $file_size = $_FILES[$file]['size'];
            if ($file_size > 5 * 1024 * 1024) {
                return ['code' => 0,'msg' => '文件大小不能超过5M'];
            }
            if (is_uploaded_file($_FILES[$file]['tmp_name'])) {
                $filename = $_FILES[$file]['tmp_name'];
            }else{
                return ['code' => 0,'msg' => '文件上传失败'];
            }
            //限制上传表格类型
            $fileExtendName = substr(strrchr($_FILES[$file]["name"], '.'), 1);
        }else{
            if(!file_exists($file)){
                return ['code' => 0,'msg' => '文件不存在'];
            }
            $filename = $file;
            $fileExtendName = substr(strrchr($filename, '.'), 1);
        }
        //application/vnd.ms-excel  为xls文件类型
        if ($fileExtendName != 'xls' && $fileExtendName != 'xlsx') {
            return ['code' => 0,'msg' => '必须为excel表格，且必须为xls或xlsx格式！'];
        }
        // 有Xls和Xlsx格式两种
        if($fileExtendName == 'xls'){
            $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
        }else{
            $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        }
        $objPHPExcel = $objReader->load($filename);  //$filename可以是上传的表格，或者是指定的表格
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        if ($highestRow > 10001) {
            return ['code' => 0,'msg' => '至多一次导入10000条数据'];
        }
        // $highestColumn = $sheet->getHighestColumn();   // 取得总列数

        $excelData = [];
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        //循环读取excel表格，整合成数组。
        for ($j = $first_row; $j <= $highestRow; $j++) {
            foreach ($data_fields as $key => $field){
                $value = $objPHPExcel->getActiveSheet()->getCell($cellName[$key] . $j)->getValue();
                $excelData[$j-$first_row][$field] = trim($value);
            }
        }
        return ['code'=>1,'data'=>$excelData];
    }

    /**
     * excel导入
     * 调用示例 ExcelService::import('file',['name','phone']);
     * @param mixed $file 上传excel文件接收字段 或者 excel文件绝对路径
     * @param int $first_row 表格数据从第几行开始读取
     * @param int $is_upload 上传文件 1 ，文件路径 0
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public static function loadFile($file,$first_row = 2,$is_upload = 1){
        if($is_upload){
            //获取表格的大小，限制上传表格的大小5M
            $file_size = $_FILES[$file]['size'];
            if ($file_size > 5 * 1024 * 1024) {
                return ['code' => 0,'msg' => '文件大小不能超过5M'];
            }
            if (is_uploaded_file($_FILES[$file]['tmp_name'])) {
                $filename = $_FILES[$file]['tmp_name'];
            }else{
                return ['code' => 0,'msg' => '文件上传失败'];
            }
            //限制上传表格类型
            $fileExtendName = substr(strrchr($_FILES[$file]["name"], '.'), 1);
        }else{
            if(!file_exists($file)){
                return ['code' => 0,'msg' => '文件不存在'];
            }
            $filename = $file;
            $fileExtendName = substr(strrchr($filename, '.'), 1);
        }
        //application/vnd.ms-excel  为xls文件类型
        if ($fileExtendName != 'xls' && $fileExtendName != 'xlsx') {
            return ['code' => 0,'msg' => '必须为excel表格，且必须为xls或xlsx格式！'];
        }
        // 有Xls和Xlsx格式两种
        if($fileExtendName == 'xls'){
            $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
        }else{
            $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        }
        $objPHPExcel = $objReader->load($filename);  //$filename可以是上传的表格，或者是指定的表格
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        if ($highestRow > 10001) {
            return ['code' => 0,'msg' => '至多一次导入10000条数据'];
        }
        $highestColumn = $sheet->getHighestColumn();   // 取得总列数
        $column_indexs = Coordinate::columnIndexFromString($highestColumn);
        //循环读取excel文件,读取一条,插入一条
        $data = array();
        //从第一行开始读取数据
        for ($j = 1; $j <= $highestRow; $j++) {
            //从A列读取数据
            for ($k = 1; $k <= $column_indexs; $k++) {
                $col_name = Coordinate::stringFromColumnIndex($k);
                // 读取单元格
                $data[$j][] = $objPHPExcel->getActiveSheet()->getCell("$col_name$j")->getValue();
            }
        }

        return ['code'=>1,'data'=>$data];
    }

    public function getWordData($type,$path){
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($type);
        $objPHPExcel = $objReader->load($path); 
        print_r($objPHPExcel);die;
        
    }

}