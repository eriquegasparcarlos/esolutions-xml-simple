<?php

namespace ESolutions\XmlSimple\Validation\Rules;

use ESolutions\XmlSimple\Results\ValidationError;
use ESolutions\XmlSimple\Results\ValidationResult;

class BusinessRulesValidator
{
    /**
     * @var array<int, callable|object>
     * Cada regla puede ser:
     * - callable(array $doc, string $type): ValidationError[]|array
     * - objeto con método validate(array $doc, string $type): ValidationError[]|array
     */
    protected $rules = [];

    public function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    public function validate(string $normalizedType, array $doc): ValidationResult
    {
        if (empty($this->rules)) {
            return ValidationResult::ok();
        }

        $errors = [];

        foreach ($this->rules as $rule) {
            $result = [];

            if (is_callable($rule)) {
                $result = $rule($doc, $normalizedType);
            } elseif (is_object($rule) && method_exists($rule, 'validate')) {
                $result = $rule->validate($doc, $normalizedType);
            }

            if (is_array($result)) {
                foreach ($result as $e) {
                    if ($e instanceof ValidationError) {
                        $errors[] = $e;
                    } elseif (is_string($e) && $e !== '') {
                        $errors[] = new ValidationError($e, 'BUSINESS_RULE');
                    }
                }
            }
        }

        return empty($errors) ? ValidationResult::ok() : ValidationResult::fail($errors);
    }
}
