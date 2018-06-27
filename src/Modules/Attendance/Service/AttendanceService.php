<?php
 
namespace App\Modules\Attendance\Service;
use App\Modules\Attendance\Models\AttendanceModel;
use App\Modules\Attendance\Dao\AttendanceDao;
use Respect\Validation\Validator as v;
use App\Utils\Validator;
use App\Modules\Authorization\Service\AuthService;

class AttendanceService {
    function getAllEmployeeAttendance($req, $res){
        //Unix Timestamps from GET Param
        //1527782400/1530374400/1
      
        $accstat = strip_tags($req->getAttribute('accstat'));
       
        if(strip_tags($req->getAttribute('dtfrom'))||strip_tags($req->getAttribute('dtfrom'))=="+")
            $dtfrom = AttendanceDao::getMinimumDTOfEmployeeAttendance();
            
        if(strip_tags($req->getAttribute('dtto'))||strip_tags($req->getAttribute('dtto'))=="+")
            $dtto = AttendanceDao::getMaximumDTOfEmployeeAttendance();

        (empty($accstat)) ? $accstat = "e.is_active IS NOT NULL" : $accstat = "e.is_active = ".$accstat;
        $data = AttendanceDao::getAllEmployeeAttendance($dtfrom, $dtto, $accstat);
        $status = (!is_null($data)) ? true:  false;   
        $newdata = array();
        foreach($data as $k){
            $newdata[] = array(
                            'firstname' => $k->first_name,
                            'lastname' => $k->last_name,
                            'is_active' => $k->is_active,
                            'position' => $k->pos_title,
                            'max' => (!is_null($k->max)) ? date('M d, Y g:i:s A', strtotime($k->max)) : null,
                            'id' => $k->emp_id,
                            'code' => $k->code

            );
        }
        //die();
        return array('msg' => $data, 'status' => $status);
    }

    function getTimeLogStatus($req, $res){
        $user_id = AuthService::getUserIdFromToken($req, $res);
        $last_punch = AttendanceDao::getLastPunch($user_id);
        $last_punchdt = (empty($last_punch)) ? null : $last_punch[0]->datetime;
        $last_punch = (empty($last_punch)) ? null : $last_punch[0]->type_id;
        return (!empty($user_id)) ? AttendanceDao::getTimeLogStatus($user_id) : null;
    }

    function addAttendance($req, $res){
        $validator = new Validator();
        $validation = $validator->validate($req, [
            'tag' => v::notEmpty()->numeric()->positive()->between(1, 4)
        ]); 
        
        if(!is_null($validation)){
            $errors = array();
            foreach($validation as $k => $x){
                for($c=0;$c<count($x);$c++){
                    $errors[] = $x[$c]."<br/>";
                }
            }
            return array('status' => 'val', 'msg' => $errors, 'tk' => '');
        }
        
        $emp_id = AuthService::getUserIdFromToken($req, $res);
        $type_id = strip_tags($req->getParam('tag'));
        return (is_numeric($emp_id)) ? AttendanceDao::addAttendance($type_id, $emp_id) : false;
    }

    function getEmployeeAbsences($req, $res){
        //var_dump(AuthService::getUserIdFromToken($req, $res)); die();
        $emp_id = (base64_decode(urldecode($req->getAttribute('emp_id')))!="+") ? base64_decode(urldecode($req->getAttribute('emp_id'))) : AuthService::getUserIdFromToken($req, $res);
        //echo (base64_decode(urldecode($req->getAttribute('emp_id')))!="+") ? "Z" : "C";
        if((is_numeric($emp_id))){
            $dtfrom = (strip_tags($req->getAttribute('dtfrom'))!=" ") ? date('Y-m-d', strip_tags($req->getAttribute('dtfrom'))) : strtotime(AttendanceDao::getMinPunch($emp_id));
            $dtto = (strip_tags($req->getAttribute('dtto'))!=" ") ? date('Y-m-d', strip_tags($req->getAttribute('dtto'))) : strtotime(AttendanceDao::getMaxPunch($emp_id));
            return AttendanceDao::getEmployeeAbsences($dtfrom, $dtto, $emp_id);
        }else{
            return null;
        }
    }

    function getEmployeeAttendance($req, $res){ 
      
        $emp_id = (base64_decode(urldecode($req->getAttribute('emp_id')))!="+") ? base64_decode(urldecode($req->getAttribute('emp_id'))) : AuthService::getUserIdFromToken($req, $res);

        if((is_numeric($emp_id))){
            $dtfrom = (strip_tags($req->getAttribute('dtfrom'))!=" ") ? date('Y-m-d', strip_tags($req->getAttribute('dtfrom'))) : strtotime(AttendanceDao::getMinPunch($emp_id));
            $dtto = (strip_tags($req->getAttribute('dtto'))!=" ") ? date('Y-m-d', strip_tags($req->getAttribute('dtto'))) : strtotime(AttendanceDao::getMaxPunch($emp_id));
            return AttendanceDao::getEmployeeAttendance($dtfrom, $dtto, $emp_id);
        }else{
            return null;
        }
    }

    function deleteEmployeeAttendance($req, $res){
        $att_id = base64_decode(urldecode($req->getAttribute('att_id')));
        //$att_id = strip_tags($req->getAttribute('att_id'));
        return (is_numeric($att_id)) ? AttendanceDao::deleteEmployeeAttendance($att_id) : false;
    }

    function getEmployeeAttendanceSummary($req, $res){
        $emp_id = (base64_decode(urldecode($req->getAttribute('emp_id')))!="+") ? base64_decode(urldecode($req->getAttribute('emp_id'))) : AuthService::getUserIdFromToken($req, $res);
        if((is_numeric($emp_id))){
            $dtfrom = (strip_tags($req->getAttribute('dtfrom'))!=" ") ? date('Y-m-d', strip_tags($req->getAttribute('dtfrom'))) : strtotime(AttendanceDao::getMinPunch($emp_id));
            $dtto = (strip_tags($req->getAttribute('dtto'))!=" ") ? date('Y-m-d', strip_tags($req->getAttribute('dtto'))) : strtotime(AttendanceDao::getMaxPunch($emp_id));
            return (is_numeric($emp_id)) ? AttendanceDao::getEmployeeAttendanceSummary($dtfrom, $dtto, $emp_id) : null;
        }else{
            return null;
        }
    }
}

