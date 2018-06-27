<?php

namespace App\Modules\Reports\Attendance\Controllers;
use App\Modules\Attendance\Service\AttendanceService;
use App\Modules\Reports\Attendance\Service\ReportService;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class ReportController {
    function __construct(){
        //var_dump('ReportController Constructed!');
    }

    function getAllEmployeeAttendanceReport(Request $req, Response $res){
        $data = AttendanceService::getAllEmployeeAttendance($req, $res);
        ReportService::generateEmployeeAttendanceExcelReport($data, $req);
    }
}