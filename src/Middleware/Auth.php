<?php

namespace App\Middleware;

use \Firebase\JWT\JWT;
use \Tuupola\Base62;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\DomainException;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use App\Modules\Employee\Dao\EmployeeDao;

/*
    jwtSecret function holds the secret key
*/

class Auth {
    function __construct(){
        //var_dump('Auth Middleware Constructed!<br>');
        //echo urlencode(base64_encode(1)); die();
    }

    function __invoke(Request $req, Response $res, $next){
        //var_dump('Auth Middleware Invoked!<br>');
        //return $next($req, $res);
        //return ($req->hasHeader('Authorization')) ? $res->withJSON("WOW") : $res->withJSON('OK');
        //return ($route->getPattern() == "/authenticate/token") ? $res->withJSON("WOW") : $res->withJSON('OK');
        //$payload = $req->getHeader('Authorization')[0];
        //return $res->withJSON($req->hasHeader('Authorization'));
        $route = $req->getAttribute("route");
        $token = false;
        //return $res->withJSON($req->getQueryParams());
        //var_dump($route); die("<br>Done");
        //$route->getPattern() == "/exec/book"
        //var_dump($req->getUri()->getPath()); die();
        //if($route->getUri()->getPath()->getPattern() == "/authorization/validatecredentials")
        //return $next($req, $res);
        //return $res->withJSON($req->hasHeader('Accept'));//$req->getQueryParams());
       
        if(isset($route)&&!empty($route)&&$route->getPattern() == "/authorization/validatecredentials") //User trying to login
        {       
            return $next($req, $res);
        }
        elseif(isset($route)&&!empty($route)&&$route->getPattern() == "/authenticate/token"){ //Token Authentication
            if($req->hasHeader('Authorization')&&isset($req->getHeader('Authorization')[0])&&!empty($req->getHeader('Authorization')[0])){
                //return $res->withJSON("BRUSELAS");
                $key = Auth::jwtSecret(); //Get Secret Key
                $payload = $req->getHeader('Authorization')[0];
                //return $res->withJSON($req->getHeader('Authorization')); die();
                $authorization = explode(".", $req->getHeader('Authorization')[0]);
                //return $res->withJSON($authorization);
                switch($authorization[1]){
                    case true: 
                                try {
                                    $jwt = JWT::decode($payload, $key, ['HS256']);
                                    //return $res->withJSON($jwt);
                                    //return $res->withJSON(base64_decode($jwt->sid));
                                    $is_active = EmployeeDao::checkIfUserIsActive(base64_decode($jwt->sid));
                                    //return $res->withJSON($is_active);
                                    //var_dump($is_active); die();
                                    if($is_active['is_active']!=1)
                                        return $res->withJSON(['message' => 'User Deactivated', 'status' => false], 401);
                                    else
                                    {
                                        //die("HERE");
                                        //echo $jwt->sid; die();
                                        //var_dump($jwt); die();
                                        $rec = EmployeeDao::getEmployee(base64_decode($jwt->sid));
                                        //$data
                                        //var_dump($rec); die();
                                        $data = array('firstname' => $rec['first_name'],
                                                      'middlename' => $rec['middle_name'],
                                                      'lastname' => $rec['last_name'],   
                                                      'position' => $rec['pos_title'],
                                                      'email' => $rec['email']
                                                     );
                                        // $data = array('firstname' => $rec[0]);
                                        return $res->withJSON(['message' => $data, 'status' => true], 200);
                                    }
                                } catch (ExpiredException $ex) {
                                    return $res->withJSON(['message' => 'Token expired', 'status' => false], 401);
                                } catch(SignatureInvalidException $ex){
                                    return $res->withJSON(['message' => 'Invalid Signature', 'token' => $token, 'status' => false], 401);
                                } catch(DomainException $e){
                                    return $res->withJSON(['message' => 'Invalid Token', 'token' => $token, 'status' => false], 401);
                                } 
                                break;
                }
            }
        }
        else //Secured Requests 
        {
            //return $res->withJSON($req->hasHeader('Authorization')); 
            //if($req->hasHeader('Authorization')&&isset($req->getHeader('Authorization')[0])&&!empty($req->getHeader('Authorization')[0])){
            if($req->hasHeader('Authorization')&&isset($req->getHeader('Authorization')[0])){
                //return $res->withJSON($req->getHeader('Authorization')[0]);
                //return $res->withJSON($req->getHeader('Authorization'));
                $key = Auth::jwtSecret(); //Get Secret Key
                $payload = $req->getHeader('Authorization')[0];
                //return $res->withJSON($req->getHeader('Authorization'));   
                
                $authorization = explode(".", $req->getHeader('Authorization')[0]);
            
                //Evaluate Authentication
                switch($authorization[1]){
                    case true:  //return $res->withJSON(true);
                                try {
                                   
                                    $jwt = JWT::decode($payload, $key, ['HS256']);
                                 
                                    //var_dump($jwt); die();
                                    //var_dump($jwt->subd); die();
                                    //var_dump(base64_decode(urldecode($jwt->sid))); die();
                                    $is_active = EmployeeDao::checkIfUserIsActive(base64_decode(urldecode($jwt->sid)));
                                   
                                    if($is_active['is_active']!=1)
                                        return $res->withJSON(['message' => 'User Deactivated'], 401);

                                    $authentic = Auth::authenticateToken($jwt);
                                  
                                    if(!$authentic)
                                        return $res->withJSON(['message' => 'Session Timed-out'], 401);

                                    return $next($req, $res);   
                                    //return $res->withJSON($authorization); die();                     
                                } catch (ExpiredException $ex) {
                                    return $res->withJSON(['message' => 'Token expired'], 401);
                                } catch(SignatureInvalidException $ex){
                                    return $res->withJSON(['message' => 'Invalid Signature', 'token' => $token], 401);
                                } catch(DomainException $e){
                                    return $res->withJSON(['message' => 'Invalid Token', 'token' => $token], 401);
                                } 
                                break;

                    case false: return $res->withJSON(false);
                                break;
                }
            }
            //return $res->withJSON("ROIR");
            return $res->withJSON(['message' => 'Roir Request', 'token' => $token, 'status' => false], 401);
        }
        return $res->withJSON("OUTER");
    }
    
    function jwtSecret(){
        return "Q!w12x2512g1c2a4Wa23Kpb752x&95z*";
    }

    function authenticateToken($jwt){
        //$expire_at = $jwt->exp;
        $updated_at = EmployeeDao::getUpdatedAt(base64_decode(urldecode($jwt->sid)));
        // var_dump(date('Y-m-d H:i:s', $jwt->iat)); die();
        // var_dump($updated_at); die();
        return ($jwt->iat<strtotime($updated_at['updated_at'])) ? false : true;
    }

    function validatejwt(Request $req, Response $res){
        //$key = "Q!w12x2512g1c2a4Wa23Kpb752x&95z*";
        //$jwt = JWT::encode($payload, $secret, "HS256");
        //$decoded = JWT::decode($jwt, $key, array('HS256'));
        /*print_r($decoded);
        die();*/
    }
}