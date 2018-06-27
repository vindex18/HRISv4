<?php

// Slim middleware
use Tuupola\Middleware\JwtAuthentication;

$app->add(new JwtAuthentication([
   "secret"=>"Q!w12x2512g1c2a4Wa23Kpb752x&95z*",
   "callback"=>function ($request,$response, $options){
       $app->jwt=$options["decoded"];

   },
   "error" => function ($response, $arguments) {
       $data["status"] = "error";
       $data["message"] = $arguments["message"];
       return $response
           ->withHeader("Content-Type", "application/json")
           ->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
   },
   "rules" => [        new Tuupola\Middleware\JwtAuthentication\RequestPathRule([
       "path" => "/",
       "ignore" => ['/authorization/validatecredentials']
   ]),
   new Tuupola\Middleware\JwtAuthentication\RequestMethodRule([
       "ignore" => ["OPTIONS"]
   ])]
]));