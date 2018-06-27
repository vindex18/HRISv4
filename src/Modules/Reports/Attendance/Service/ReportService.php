<?php

namespace App\Modules\Reports\Attendance\Service;
use PhpOffice\PhpSpreadsheet\Spreadsheet as Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory as IOFactory;
use App\Modules\Attendance\Dao\AttendanceDao;
use App\Utils\MyCustomStylesLibrary as MyStyles;
use \DateTime;

class ReportService {
    function generateEmployeeAttendanceExcelReport($data, $req){
         //var_dump($data); die();
         $filename = "Invento_Employee_Attendance_".date('Y-m-d_H-i-s');
         /**TEMP **/
         $dtfrom = date('Y-m-d H:i:s', strip_tags($req->getAttribute('dtfrom')));
         $dtto = date('Y-m-d H:i:s', strip_tags($req->getAttribute('dtto')));
         $accstat = strip_tags($req->getAttribute('accstat'));
        
         if(empty($dtfrom))
             $dtfrom = AttendanceDao::getMinimumDTOfEmployeeAttendance($accstat);
             
         if(empty($dtto))
             $dtto = AttendanceDao::getMaximumDTOfEmployeeAttendance($accstat);
 
        if($accstat==1)
            $accstat = "Active";
        elseif($accstat==2)
            $accstat = "Deactivated";
        else
            $accstat = "All Account Status";
        /**TEMP **/

        //Global Font Used
        $Default_Style = array(
            'font'  => array(
                'size'  => 8,
                'name'  => 'Arial Narrow'
            )
        );

        $ss = new Spreadsheet();  /*----Spreadsheet object-----*/
        $Excel_writer = new Xlsx($ss);  /*----- Excel (Xlsx) Object*/

        $first_col = 'A'; //65 - A
        $last_col = 'P';
        $row = 7;

        //Init Custom Styling
        $mystyle = new MyStyles();

        //Initial Config
        $ss->setActiveSheetIndex(0)->setTitle("Employee Attendance");
        $ss->getDefaultStyle()->applyFromArray($Default_Style);
        $ss->getActiveSheet()->getSheetView()->setZoomScale(160);
        ReportService::setHeaderForEmployeeAttendanceReport($ss, $mystyle, $dtfrom, $dtto, $accstat);
       
        //Populating the Data
        for($c=0;$c<count($data);$c++){
            //number
            $ss->getActiveSheet()->setCellValue('A'.$row, $c+1)->getStyle('A'.$row)->applyFromArray($mystyle->alignment_horiz_right())->applyFromArray($mystyle->border_thin('FF573300'));
           
            //name
            $ss->getActiveSheet()->setCellValue('F'.$row, $data[$c]->last_name.", ".$data[$c]->first_name);
            $ss->getActiveSheet()->mergeCells('F'.$row.':H'.$row)->getStyle('F'.$row.':H'.$row)->applyFromArray($mystyle->alignment_horiz_left())->applyFromArray($mystyle->border_thin('FF573300'));
            
            //Punch type
            $ss->getActiveSheet()->setCellValue('D'.$row, $data[$c]->description.' - '.$data[$c]->code);
            $ss->getActiveSheet()->mergeCells('D'.$row.':E'.$row)->getStyle('D'.$row.':E'.$row)->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
            
            //date Time
            $ss->getActiveSheet()->setCellValue('B'.$row, date('M d, Y g:i A', strtotime($data[$c]->datetime)));
            $ss->getActiveSheet()->mergeCells('B'.$row.':C'.$row)->getStyle('B'.$row.':C'.$row)->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
            
            //position
            $ss->getActiveSheet()->setCellValue('I'.$row, $data[$c]->pos_title);
            $ss->getActiveSheet()->mergeCells('I'.$row.':L'.$row)->getStyle('G'.$row.':L'.$row)->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
            
            //Acc stat
            if($accstat=="All Account Status")
            {
                if($data[$c]->is_active==true)
                    $ss->getActiveSheet()->setCellValue('M'.$row, 'Active')->getStyle('M'.$row)->applyFromArray($mystyle->fill_solid_color('002EFE37'));
                else
                    $ss->getActiveSheet()->setCellValue('M'.$row, 'Deactivated')->getStyle('M'.$row)->applyFromArray($mystyle->fill_solid_color('00F82200'))->getFont()->getColor()->setARGB('00FFFFFF');

                $ss->getActiveSheet()->mergeCells('M'.$row.':P'.$row)->getStyle('M'.$row.':P'.$row)->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('00FF5733'));
            }
            else
                $ss->getActiveSheet()->mergeCells('M'.$row.':P'.$row)->getStyle('M'.$row.':P'.$row)->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));                
   
            $row++;
            //var_dump($data); die();
        }

        //Excel Output to Browser
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"'); /*-- $filename is  xsl filename ---*/
        header('Cache-Control: max-age=0');
        $Excel_writer->save('php://output');
    }

    function setHeaderForEmployeeAttendanceReport($ss, $mystyle, $dtfrom, $dtto, $accstat){
            //Start Instructions
            foreach (range('A', 'B') as $column_id) {
                $ss->getActiveSheet()->getColumnDimension($column_id)->setAutoSize(true);
            }
            
            $ss->getActiveSheet()->getRowDimension('1')->setRowHeight(30);

            $ss->getActiveSheet()->setCellValue('B1', 'Invento Software Solutions Inc.')->getStyle('B1')->getFont()->setUnderline(true)->setBold(true)->setSize(14);
            $ss->getActiveSheet()->mergeCells('B1:P1')->getStyle('B1:P1')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
    
            $ss->getActiveSheet()->setCellValue('B2', 'Unit 2105 Richville Corporate Tower, 1107 Alabang Zapote Road, Madrigal Bus. Park, Alabang Muntinlupa 1780, Metro Manila, Philippines')->getStyle('B2')->getFont()->setItalic(true);
            $ss->getActiveSheet()->mergeCells('B2:P2')->getStyle('B2:P2')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
    
            $ss->getActiveSheet()->setCellValue('B3', 'EMPLOYEE ATTENDANCE REPORT')->getStyle('B3')->getFont()->setBold(true)->setSize(12);
            $ss->getActiveSheet()->mergeCells('B3:P3')->getStyle('B3:P3')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
        
            $ss->getActiveSheet()->setCellValue('B4', 'From: '.date('F d, Y', strtotime($dtfrom)).' at '.date('g:i A', strtotime($dtfrom)))->getStyle('B4')->getFont()->setBold(true);
            $ss->getActiveSheet()->mergeCells('B4:E4')->getStyle('B4:E4')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
    
            $ss->getActiveSheet()->setCellValue('F4', 'From: '.date('F d, Y', strtotime($dtto)).' at '.date('g:i A', strtotime($dtto)))->getStyle('F4')->getFont()->setBold(true);
            $ss->getActiveSheet()->mergeCells('F4:I4')->getStyle('F4:I4')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
    
            $dtfrom = new DateTime($dtfrom);
            $dtto = new DateTime($dtto);
            $diff = date_diff($dtfrom, $dtto);
    
            $ss->getActiveSheet()->setCellValue('J4', 'Duration: '.$diff->format('%y Years %m Months %d Days %h Hours %i Minutes %s Seconds'))->getStyle('J4')->getFont()->setBold(true);
            $ss->getActiveSheet()->mergeCells('J4:P4')->getStyle('J4:P4')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
    
            $ss->getActiveSheet()->setCellValue('B5', 'Open Window Time: 9:00am - 1:00pm')->getStyle('B5')->getFont()->setBold(true);
            $ss->getActiveSheet()->mergeCells('B5:E5')->getStyle('B5:E5')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
    
            $ss->getActiveSheet()->setCellValue('F5', 'Account Status: '.$accstat)->getStyle('F5')->getFont()->setBold(true);
            $ss->getActiveSheet()->mergeCells('F5:I5')->getStyle('F5:I5')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
            
            $ss->getActiveSheet()->setCellValue('J5', '')->getStyle('J5')->getFont()->setBold(true);
            $ss->getActiveSheet()->mergeCells('J5:P5')->getStyle('J5:P5')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
            
            $ss->getActiveSheet()->getStyle('A6')->applyFromArray($mystyle->border_thin('FF573300'));
            
            $ss->getActiveSheet()->setCellValue('B6', 'Date & Time')->getStyle('B6')->getFont()->setBold(true);
            $ss->getActiveSheet()->mergeCells('B6:C6')->getStyle('B6:C6')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
            
            $ss->getActiveSheet()->setCellValue('D6', 'Punch Type')->getStyle('D6')->getFont()->setBold(true);
            $ss->getActiveSheet()->mergeCells('D6:E6')->getStyle('D6:E6')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
    
            $ss->getActiveSheet()->setCellValue('F6', 'Name')->getStyle('F6')->getFont()->setBold(true);
            $ss->getActiveSheet()->mergeCells('F6:H6')->getStyle('F6:H6')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
          
            $ss->getActiveSheet()->setCellValue('I6', 'Position')->getStyle('I6')->getFont()->setBold(true);
            $ss->getActiveSheet()->mergeCells('I6:L6')->getStyle('I6:L6')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
    
            if($accstat=="All Account Status")
            {
                $ss->getActiveSheet()->setCellValue('M6', 'Account Status')->getStyle('M6')->getFont()->setBold(true);
                $ss->getActiveSheet()->mergeCells('M6:P6')->getStyle('M6:P6')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));
            }
            else
                $ss->getActiveSheet()->mergeCells('M6:P6')->getStyle('M6:P6')->applyFromArray($mystyle->alignment_horiz_center())->applyFromArray($mystyle->border_thin('FF573300'));                
    }
}