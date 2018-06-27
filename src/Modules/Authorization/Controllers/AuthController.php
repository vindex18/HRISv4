<?php

namespace App\Modules\Authorization\Controllers;
use App\Modules\Authorization\Service\AuthService;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class AuthController {
    public function __construct($container){
        //var_dump("\nAuthController Constructed!");
        //var_dump($container); die();
    }

    public function validatecredentials(Request $req, Response $res){
        //return $res->withJSON(AuthService::validatecredentials($req, $res), 201);
        
        return $res->withJSON(AuthService::validatecredentials($req, $res));
    }
}