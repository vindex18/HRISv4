<?php 

namespace App\Modules\Employee\Controllers;

use App\Modules\Employee\Service\EmployeeService;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class EmployeeController {
    function __construct(){
        //return "This is the Employee Controller";
    }

    function addEmployee(Request $req, Response $res, $args){
        return $res->withJSON(EmployeeService::addEmployee($req,  $res));
    }

    function updateEmployee(Request $req, Response $res, $args){
        return $res->withJSON(EmployeeService::updateEmployee($req, $res));
    }

    function deleteEmployee(Request $req, Response $res, $args){
        return $res->withJSON(EmployeeService::deleteEmployee($req, $res));
    }

    function getAllEmployee(Request $req, Response $res, $args){
        return $res->withJSON(EmployeeService::getAllEmployee($req, $res));
    }

    function getEmployee(Request $req, Response $res, $args){
        return $res->withJSON(EmployeeService::getEmployee($req, $res, $args));
    }
}