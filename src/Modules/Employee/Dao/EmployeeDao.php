<?php 

namespace App\Modules\Employee\Dao;
use App\Modules\Employee\Models\EmployeeModel;
use Illuminate\Database\Capsule\Manager as DB;

class EmployeeDao {
    function getAllEmployee($accstat){
        return EmployeeModel::select('first_name', 'middle_name', 'last_name', 'phone', 'email', 'address', 'pos_title', 'is_admin', 'created_at','updated_at', 'is_active')->whereRaw($accstat)->orderBy('created_at', 'DESC')->get();
    }

    function addEmployee($arr){
        return EmployeeModel::firstOrcreate($arr)->save();
    }

    function checkEmailExistsOnUpdateEmployee($email, $emp_id){
        return EmployeeModel::select('email')->where('email', '=', strip_tags($email))->where('emp_id', '<>', $emp_id)
        ->first();        
    }
       
    function checkEmailExistsOnAddEmployee($email){
        return EmployeeModel::select('first_name', 'middle_name', 'last_name', 'email', 'password', 'is_admin', 'is_active')
                        ->where('email', '=', strip_tags($email))->where('is_active', '=', 1)->first();
    }

    function deleteEmployee($emp_id){
        $emp = EmployeeDao::getEmployee($emp_id);
        $name = $emp['last_name'].", ".$emp['first_name']." ".$emp['middle_name'];
        $qry = EmployeeModel::where('id', $emp_id)->delete();
        return ($qry) ? array('msg' => 'Employee Deleted Successfully! ('.$name.')', 'status' => true) : array('msg' => 'Error Encountered on Employee Deletion! ('.$name.')', 'status' => false);
    }

    function checkIfUserIsActive($emp_id){
        return EmployeeModel::select('is_active')
                            ->where('id', '=', $emp_id)
                            ->where('is_active', '=', 1)
                            ->first()
                            ->toArray();
    }

    function getUpdatedAt($emp_id){
       // echo $emp_id; die();
        //DB::connection()->enableQueryLog();
        return EmployeeModel::select('updated_at')
                            ->where('id', '=', $emp_id)
                            ->first()
                            ->toArray();
        //$queries = DB::getQueryLog(); var_dump($queries); die("End of Query");
    }

    function getEmployee($emp_id){
         //DB::connection()->enableQueryLog();
         return EmployeeModel::select('id', 'first_name', 'middle_name', 'last_name', 'phone', 'email', 'address', 'pos_title', 'is_admin', 'created_at','updated_at', 'is_active')->where('id', $emp_id)->first()->toArray();
         //var_dump($data); die();
         //$queries = DB::getQueryLog(); var_dump($queries); die("End of Query");
    }

    function getMinimumDTOfEmployeeAttendance(){
        $qry = DB::table('attendance')
               ->orderBy('id', 'DESC')
               ->limit(1)
               ->select('datetime')
               ->get();

        if(is_null($qry))
            return date('Y-m-d 00:00:00');
    }
}