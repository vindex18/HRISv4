<?php

namespace App\Utils;

use Respect\Validation\Validator as Respect;
use Respect\Validation\Exceptions\NestedValidationException;

class Validator {
    protected $errors;
    public function validate($request, array $rules){ 
        foreach($rules as $field => $rule){
            try {
                $rule->setName(ucfirst($field))->assert($request->getParam($field));
            }catch(NestedValidationException $e){
                $this->errors[$field] = $e->getMessages(); 
            }

            if(!empty($this->errors)) 
                 return $this->errors;
        }
        // var_dump($this->errors); die();
    }
}