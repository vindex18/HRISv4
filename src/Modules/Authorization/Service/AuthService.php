<?php

namespace App\Modules\Authorization\Service;
use App\Modules\Authorization\Models\AuthModel;
use App\Modules\Employee\Dao\EmployeeDao;
use App\Modules\Attendance\Dao\AttendanceDao;
use Respect\Validation\Validator as v;
use App\Utils\Validator;
use \Datetime;
use \Firebase\JWT\JWT;
use \Tuupola\Base62;
use App\Middleware\Auth;

class AuthService {
    function getUserIdFromToken($req, $res){
        $key = Auth::jwtSecret(); //Get Secret Key
        $payload = $req->getHeader('Authorization')[0];
        $authorization = explode(".", $req->getHeader('Authorization')[0]);
        $jwt = JWT::decode($payload, $key, ['HS256']);
        
        $is_active = EmployeeDao::checkIfUserIsActive(base64_decode($jwt->sid));

        if($is_active['is_active']!=1)
            return $res->withJSON(['message' => 'User Not Found', 'status' => false], 401);
        else
        {
            $rec = EmployeeDao::getEmployee(base64_decode($jwt->sid));
            return ($rec) ? $rec['id'] : false;
        }
    }

    function validatecredentials($req, $res){
        $validator = new Validator();
       
        $validation = $validator->validate($req, [
            'email' => v::noWhitespace()->notEmpty()->email(),
            'password' => v::noWhitespace()->notEmpty() //->length(6, null),
        ]); 
        
        if(!is_null($validation))
        {
            $errors = array();
            foreach($validation as $k => $x){
                for($c=0;$c<count($x);$c++){
                    $errors[] = $x[$c];
                }
            }
            return array('status' => 'val', 'msg' => $errors, 'tk' => '');
        }
        
        $userexists = AuthModel::where('email', '=', strip_tags($req->getParam('email')))->first();

        if(is_null($userexists))
            return array('status' => false, 'msg' => 'Email doesn\'t exists!', 'tk' => '');
        else{
            if($userexists->is_active)
            {
                if(password_verify(strip_tags($req->getParam('password')), $userexists->password)){
                    // $last_punch = AttendanceDao::getLastPunch($userexists->id);
                    // $lastpunch = (empty($last_punch)) ? 1 : $last_punch[0]->type_id;
                    // $lastpunch_dt = (empty($last_punch)) ? "Time-In" : $last_punch[0]->datetime;
                    // $last_pdesc = AttendanceDao::getPunchDescription($lastpunch);
                    // $next_punch = AttendanceDao::getNextPunch($lastpunch);
                    
                    return array('status' => true, 
                                 'msg' => 'Auth Success', 
                                 'tk' => AuthService::generate_jwt($req, $res, $userexists->email, $userexists),
                                 'userdata' => array('firstname' => $userexists->first_name,
                                                    'middlename' => $userexists->middle_name,
                                                    'lastname' => $userexists->last_name,
                                                    'email' => $userexists->email,
                                                    'position' => $userexists->pos_title,
                                ));
                }
                else
                    return array('status' => false, 'msg' => 'Incorrect Email/Password!', 'tk' => '');
            }
            else
                return array('status' => false, 'msg' => 'Account Deactivated! ', 'tk' => ''); 
        }
    }

    function generate_jwt($req, $res, $email, $userexists){
        $now = new DateTime();
        //$future = new DateTime("now +2 hours");
        $future = new DateTime("now +2 months"); //+1 day
        $base62 = new \Tuupola\Base62;

        $payload = [
            'iat' => $now->getTimeStamp(), //issued at
             //'exp' => $now->createFromFormat('d/m/Y H:i:s', '23/05/2013'), 
            'exp' => $future->getTimeStamp(), //expiration
            'jti' => $base62->encode(random_bytes(32)), //json token id
            'subd' => $base62->encode($email), //subject
            'iss' => $base62->encode("invento-hris"), //issuer
            'sid' => base64_encode($userexists->id)
        ];

        $secret = Auth::jwtSecret();
        return JWT::encode($payload, $secret, "HS256");
        /*$jwt = JWT::encode($payload, $secret, "HS256");
        $decoded = JWT::decode($jwt, $secret, array('HS256'));
        print_r($decoded);
        die();*/
    }

}