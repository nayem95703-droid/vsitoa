<?php

namespace Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $customMessages = [];

    public function __construct(array $data, array $rules, array $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
    }

    /**
     * Validate data against rules
     */
    public function validate(): bool
    {
        foreach ($this->rules as $field => $fieldRules) {
            $rules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                if (!$this->validateRule($field, $value, $rule)) {
                    break; // Stop on first error for this field
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function firstError(): ?string
    {
        $firstError = reset($this->errors);
        return $firstError ? reset($firstError) : null;
    }

    /**
     * Validate single rule
     */
    private function validateRule(string $field, mixed $value, string $rule): bool
    {
        // Parse rule with parameters
        if (strpos($rule, ':') !== false) {
            [$ruleName, $parameter] = explode(':', $rule, 2);
            $parameters = explode(',', $parameter);
        } else {
            $ruleName = $rule;
            $parameters = [];
        }

        // Call validation method
        $method = 'validate' . ucfirst(str_replace('_', '', $ruleName));
        
        if (method_exists($this, $method)) {
            return $this->$method($field, $value, $parameters);
        }

        return true; // Unknown rule, pass
    }

    /**
     * Add error message
     */
    private function addError(string $field, string $rule, array $parameters = []): void
    {
        $message = $this->customMessages["$field.$rule"] ?? 
                  $this->customMessages[$rule] ?? 
                  $this->getDefaultMessage($rule, $parameters);

        // Replace placeholders
        $message = str_replace(':field', $field, $message);
        $message = str_replace(':value', $this->data[$field] ?? '', $message);
        
        foreach ($parameters as $i => $param) {
            $message = str_replace(":param$i", $param, $message);
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Get default error message
     */
    private function getDefaultMessage(string $rule, array $parameters): string
    {
        $messages = [
            'required' => 'The :field field is required',
            'email' => 'The :field must be a valid email address',
            'min' => 'The :field must be at least :param0 characters',
            'max' => 'The :field may not be greater than :param0 characters',
            'numeric' => 'The :field must be a number',
            'integer' => 'The :field must be an integer',
            'positive' => 'The :field must be a positive number',
            'url' => 'The :field must be a valid URL',
            'alpha' => 'The :field may only contain letters',
            'alpha_num' => 'The :field may only contain letters and numbers',
            'alpha_dash' => 'The :field may only contain letters, numbers, dashes and underscores',
            'unique' => 'The :field has already been taken',
            'exists' => 'The selected :field is invalid',
            'confirmed' => 'The :field confirmation does not match',
            'different' => 'The :field and :param0 must be different',
            'same' => 'The :field and :param0 must match',
            'in' => 'The selected :field is invalid',
            'not_in' => 'The selected :field is invalid',
            'regex' => 'The :field format is invalid',
            'date' => 'The :field is not a valid date',
            'date_format' => 'The :field does not match the format :param0',
            'before' => 'The :field must be a date before :param0',
            'after' => 'The :field must be a date after :param0',
            'between' => 'The :field must be between :param0 and :param1',
            'file' => 'The :field must be a file',
            'image' => 'The :field must be an image',
            'mimes' => 'The :field must be a file of type: :param0',
            'max_file' => 'The :field may not be greater than :param0 kilobytes'
        ];

        return $messages[$rule] ?? 'The :field is invalid';
    }

    // Validation Methods

    private function validateRequired(string $field, mixed $value): bool
    {
        if (is_null($value)) {
            $this->addError($field, 'required');
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            $this->addError($field, 'required');
            return false;
        } elseif (is_array($value) && empty($value)) {
            $this->addError($field, 'required');
            return false;
        }

        return true;
    }

    private function validateEmail(string $field, mixed $value): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'email');
            return false;
        }

        return true;
    }

    private function validateMin(string $field, mixed $value, array $parameters): bool
    {
        $min = $parameters[0] ?? 0;

        if (is_string($value)) {
            if (strlen($value) < $min) {
                $this->addError($field, 'min', $parameters);
                return false;
            }
        } elseif (is_numeric($value)) {
            if ($value < $min) {
                $this->addError($field, 'min', $parameters);
                return false;
            }
        } elseif (is_array($value)) {
            if (count($value) < $min) {
                $this->addError($field, 'min', $parameters);
                return false;
            }
        }

        return true;
    }

    private function validateMax(string $field, mixed $value, array $parameters): bool
    {
        $max = $parameters[0] ?? 0;

        if (is_string($value)) {
            if (strlen($value) > $max) {
                $this->addError($field, 'max', $parameters);
                return false;
            }
        } elseif (is_numeric($value)) {
            if ($value > $max) {
                $this->addError($field, 'max', $parameters);
                return false;
            }
        } elseif (is_array($value)) {
            if (count($value) > $max) {
                $this->addError($field, 'max', $parameters);
                return false;
            }
        }

        return true;
    }

    private function validateNumeric(string $field, mixed $value): bool
    {
        if (!is_numeric($value)) {
            $this->addError($field, 'numeric');
            return false;
        }

        return true;
    }

    private function validateInteger(string $field, mixed $value): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, 'integer');
            return false;
        }

        return true;
    }

    private function validatePositive(string $field, mixed $value): bool
    {
        if (!is_numeric($value) || $value <= 0) {
            $this->addError($field, 'positive');
            return false;
        }

        return true;
    }

    private function validateUrl(string $field, mixed $value): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, 'url');
            return false;
        }

        return true;
    }

    private function validateAlpha(string $field, mixed $value): bool
    {
        if (!preg_match('/^[a-zA-Z]+$/', $value)) {
            $this->addError($field, 'alpha');
            return false;
        }

        return true;
    }

    private function validateAlphaNum(string $field, mixed $value): bool
    {
        if (!preg_match('/^[a-zA-Z0-9]+$/', $value)) {
            $this->addError($field, 'alpha_num');
            return false;
        }

        return true;
    }

    private function validateAlphaDash(string $field, mixed $value): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
            $this->addError($field, 'alpha_dash');
            return false;
        }

        return true;
    }

    private function validateUnique(string $field, mixed $value, array $parameters): bool
    {
        $table = $parameters[0] ?? '';
        $column = $parameters[1] ?? $field;
        $exceptId = $parameters[2] ?? null;

        if (empty($table)) {
            return true;
        }

        $sql = "SELECT COUNT(*) FROM $table WHERE $column = ?";
        $params = [$value];

        if ($exceptId) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }

        $count = Database::fetchColumn($sql, $params);

        if ($count > 0) {
            $this->addError($field, 'unique');
            return false;
        }

        return true;
    }

    private function validateExists(string $field, mixed $value, array $parameters): bool
    {
        $table = $parameters[0] ?? '';
        $column = $parameters[1] ?? $field;

        if (empty($table)) {
            return true;
        }

        $count = Database::fetchColumn(
            "SELECT COUNT(*) FROM $table WHERE $column = ?",
            [$value]
        );

        if ($count == 0) {
            $this->addError($field, 'exists');
            return false;
        }

        return true;
    }

    private function validateConfirmed(string $field, mixed $value): bool
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->data[$confirmationField] ?? null;

        if ($value !== $confirmationValue) {
            $this->addError($field, 'confirmed');
            return false;
        }

        return true;
    }

    private function validateDifferent(string $field, mixed $value, array $parameters): bool
    {
        $otherField = $parameters[0] ?? '';
        $otherValue = $this->data[$otherField] ?? null;

        if ($value === $otherValue) {
            $this->addError($field, 'different', $parameters);
            return false;
        }

        return true;
    }

    private function validateSame(string $field, mixed $value, array $parameters): bool
    {
        $otherField = $parameters[0] ?? '';
        $otherValue = $this->data[$otherField] ?? null;

        if ($value !== $otherValue) {
            $this->addError($field, 'same', $parameters);
            return false;
        }

        return true;
    }

    private function validateIn(string $field, mixed $value, array $parameters): bool
    {
        if (!in_array($value, $parameters)) {
            $this->addError($field, 'in');
            return false;
        }

        return true;
    }

    private function validateNotIn(string $field, mixed $value, array $parameters): bool
    {
        if (in_array($value, $parameters)) {
            $this->addError($field, 'not_in');
            return false;
        }

        return true;
    }

    private function validateRegex(string $field, mixed $value, array $parameters): bool
    {
        $pattern = $parameters[0] ?? '';

        if (empty($pattern) || !preg_match($pattern, $value)) {
            $this->addError($field, 'regex');
            return false;
        }

        return true;
    }

    private function validateDate(string $field, mixed $value): bool
    {
        if (strtotime($value) === false) {
            $this->addError($field, 'date');
            return false;
        }

        return true;
    }

    private function validateDateFormat(string $field, mixed $value, array $parameters): bool
    {
        $format = $parameters[0] ?? 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $value);

        if (!$date || $date->format($format) !== $value) {
            $this->addError($field, 'date_format', $parameters);
            return false;
        }

        return true;
    }

    private function validateBefore(string $field, mixed $value, array $parameters): bool
    {
        $compareDate = $parameters[0] ?? '';
        $compareValue = $this->data[$compareDate] ?? $compareDate;

        if (strtotime($value) >= strtotime($compareValue)) {
            $this->addError($field, 'before', $parameters);
            return false;
        }

        return true;
    }

    private function validateAfter(string $field, mixed $value, array $parameters): bool
    {
        $compareDate = $parameters[0] ?? '';
        $compareValue = $this->data[$compareDate] ?? $compareDate;

        if (strtotime($value) <= strtotime($compareValue)) {
            $this->addError($field, 'after', $parameters);
            return false;
        }

        return true;
    }

    private function validateBetween(string $field, mixed $value, array $parameters): bool
    {
        $min = $parameters[0] ?? 0;
        $max = $parameters[1] ?? 0;

        if (is_numeric($value)) {
            if ($value < $min || $value > $max) {
                $this->addError($field, 'between', $parameters);
                return false;
            }
        } elseif (is_string($value)) {
            $length = strlen($value);
            if ($length < $min || $length > $max) {
                $this->addError($field, 'between', $parameters);
                return false;
            }
        }

        return true;
    }

    private function validateFile(string $field, mixed $value): bool
    {
        if (!isset($_FILES[$field]) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
            $this->addError($field, 'file');
            return false;
        }

        return true;
    }

    private function validateImage(string $field, mixed $value): bool
    {
        if (!$this->validateFile($field, $value)) {
            return false;
        }

        $imageInfo = getimagesize($_FILES[$field]['tmp_name']);
        if ($imageInfo === false) {
            $this->addError($field, 'image');
            return false;
        }

        return true;
    }

    private function validateMimes(string $field, mixed $value, array $parameters): bool
    {
        if (!$this->validateFile($field, $value)) {
            return false;
        }

        $allowedMimes = $parameters;
        $fileMime = mime_content_type($_FILES[$field]['tmp_name']);

        if (!in_array($fileMime, $allowedMimes)) {
            $this->addError($field, 'mimes', $parameters);
            return false;
        }

        return true;
    }

    private function validateMaxFile(string $field, mixed $value, array $parameters): bool
    {
        if (!$this->validateFile($field, $value)) {
            return false;
        }

        $maxSize = ($parameters[0] ?? 0) * 1024; // Convert KB to bytes
        $fileSize = $_FILES[$field]['size'];

        if ($fileSize > $maxSize) {
            $this->addError($field, 'max_file', $parameters);
            return false;
        }

        return true;
    }

    /**
     * Static validation method
     */
    public static function make(array $data, array $rules, array $customMessages = []): self
    {
        return new self($data, $rules, $customMessages);
    }

    /**
     * Quick validation with array return
     */
    public static function validateAndReturn(array $data, array $rules, array $customMessages = []): array
    {
        $validator = new self($data, $rules, $customMessages);
        $isValid = $validator->validate();
        
        return [
            'valid' => $isValid,
            'errors' => $validator->errors()
        ];
    }
}
