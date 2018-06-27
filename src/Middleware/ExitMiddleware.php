<?php

namespace App\Middleware;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class ExitMiddleware {
    function __invoke(Request $req, Response $res, $next){
        //var_dump("Exit Middleware Invoked!!!!");
        return $next($req, $res);                        
    }
}