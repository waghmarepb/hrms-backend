<?php

class Validator
{
    private $data;
    private $rules;
    private $errors = [];

    public function __construct($data, $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make($data, $rules)
    {
        return new self($data, $rules);
    }

    public function validate()
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            
            foreach ($rules as $rule) {
                $this->applyRule($field, $rule);
            }
        }
        
        if (!empty($this->errors)) {
            Response::validationError($this->errors);
        }
        
        return true;
    }

    private function applyRule($field, $rule)
    {
        $value = $this->data[$field] ?? null;
        
        // Parse rule parameters (e.g., max:255)
        $params = [];
        if (strpos($rule, ':') !== false) {
            list($rule, $paramString) = explode(':', $rule, 2);
            $params = explode(',', $paramString);
        }
        
        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "The {$field} field is required.");
                }
                break;
                
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The {$field} must be a valid email address.");
                }
                break;
                
            case 'min':
                if ($value && strlen($value) < $params[0]) {
                    $this->addError($field, "The {$field} must be at least {$params[0]} characters.");
                }
                break;
                
            case 'max':
                if ($value && strlen($value) > $params[0]) {
                    $this->addError($field, "The {$field} must not exceed {$params[0]} characters.");
                }
                break;
                
            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->addError($field, "The {$field} must be a number.");
                }
                break;
                
            case 'integer':
                if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "The {$field} must be an integer.");
                }
                break;
                
            case 'string':
                if ($value && !is_string($value)) {
                    $this->addError($field, "The {$field} must be a string.");
                }
                break;
                
            case 'in':
                if ($value && !in_array($value, $params)) {
                    $allowed = implode(', ', $params);
                    $this->addError($field, "The {$field} must be one of: {$allowed}.");
                }
                break;
                
            case 'date':
                if ($value && !strtotime($value)) {
                    $this->addError($field, "The {$field} must be a valid date.");
                }
                break;
        }
    }

    private function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function fails()
    {
        return !empty($this->errors);
    }

    public function errors()
    {
        return $this->errors;
    }
}

