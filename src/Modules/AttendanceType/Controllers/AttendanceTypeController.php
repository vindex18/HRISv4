<?php

namespace App\Modules\Attendancetype\Controllers;
use App\Modules\Attendancetype\Service\AttendancetypeService;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class AttendancetypeController {
    function __construct(){

    }

   function getAllAttendanceType(Request $req, Response $res){
        return var_dump(AttendancetypeService::getAllAttendanceType());
   }
}