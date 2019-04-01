<?php

class Validator
{
    /** @var array $item Item to run validations against. */
    private $item;


    /** @var array $validations Validations to run against item. */
    private $validations = [];


    /** @var array $errors Array of errors for each field in item, if validations failed. */
    private $errors = [];


    /**
     * Store item on object instance, parse validations and store
     * parsed validations for each field in item on object instance.
     *
     * @param object $item Model instance whose properties are to be validated.
     * @param array $validations Array of validations to run against model instance.
     */
    public function __construct(object $item, array $validations)
    {
        // Store item on object.
        $this->item = $item;

        // $validations as ['username' => 'min:6|max:10']
        foreach ($validations as $field => $rules) {
            // Split string of rules into array of separate rules.
            // $rules = ['username' => ['min:6', 'max:10']]
            $rules = explode('|', $rules);

            // Split each rule into array whose key is the function to
            // run and whose value is the parameter to pass to the function.
            // $rules as [0 => 'min:6', 1 => 'max:10']
            foreach ($rules as $i => $rule) {
                $exploded = explode(':', $rule); // ['min', '6']
                $name = $exploded[0]; // 'min'
                $param = $exploded[1] ?? null; // '6'
                $rules[$name] = $param; // $rules['min'] = '6'
                unset($rules[$i]); // unset $rules[0]
            }

            // Add rules to validations array for current field.
            // $validations['email'] = ['min' => '6', 'max' => '10']
            $this->validations[$field] = $rules;
        }
    }


    /**
     * Run all validations against item.
     *
     * @return bool True if all validations passed; false otherwise.
     */
    public function run()
    {
        // For all validations, get the field and the rules
        // to validate it with.
        // $validations as ['email'] = ['min' => '6', 'max' => '10']
        foreach ($this->validations as $field => $rules) {
            // Get the name of the validation function to run and
            // the parameters to use.
            // $rules as 'min' => '6'
            foreach ($rules as $name => $param) {
                // Run validation function against the item field.
                // $field = 'email', $param = '6'
                if (!$this->$name($field, $param)) {
                    // If field fails validation, skip to next field
                    // to avoid overwriting error message for that field.
                    break;
                }
            }
        }

        // Return true if all validations passed; false otherwise.
        // If all validations passed, no errors will be empty.
        return empty($this->errors);
    }


    /**
     * Retrieve errors array.
     *
     * @return array List of validation errors.
     */
    public function getErrors()
    {
        return $this->errors;
    }


    /**
     * Ensure field in item is present.
     *
     * @param mixed $field Name of field to check.
     * @return bool True if field is present; false otherwise.
     */
    private function required($field)
    {
        if (empty($this->item->$field)) {
            $this->errors[$field] = 'Required.';
            return false;
        }
        return true;
    }


    /**
     * Ensure field is not already in database unique.
     *
     * @param mixed $field Name of field to check.
     * @param string $table Table to check for record uniqueness.
     * @return bool True if field is unique; false otherwise.
     */
    private function unique($field, string $table)
    {
        $primaryKey = $this->item->getPrimaryKey();
        if (!empty($this->$primaryKey)) {
            $count = DB::table($table)->where([
                [$field, $this->item->$field],
                [$primaryKey, '!=', $this->item->$primaryKey]
            ])->count();
        } else {
            $count = DB::table($table)->where([
                [$field, $this->item->$field]
            ])->count();
        }
        if ($count > 0) {
            $this->errors[$field] = 'Already taken.';
            return false;
        }
        return true;
    }


    /**
     * Ensure field in item has correct format.
     *
     * @param mixed $field Name of field to check.
     * @return bool True if field has correct format; false otherwise.
     */
    private function format($field, string $format)
    {
        $val = $this->item->$field;

        if ($format === 'email' && filter_var($val, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field] = 'Invalid email.';
            return false;
        }
        if ($format === 'date' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
            $this->errors[$field] = 'Invalid date format.';
            return false;
        }
        if ($format === 'numeric' && !is_numeric($val)) {
            $this->errors[$field] = 'Must be an number.';
            return false;
        }
        if ($format === 'int' && (!is_numeric($val) || $val != (int) $val)) {
            $this->errors[$field] = 'Must be an integer.';
            return false;
        }
        if ($format === 'float' && (!is_numeric($val) || $val == (int) $val)) {
            $this->errors[$field] = 'Must be a float.';
            return false;
        }
        return true;
    }


    /**
     * Ensure field in item is not larger than a max value.
     *
     * @param mixed $field Name of field to check.
     * @param mixed $value Max value.
     * @return bool True if field is not larger than max value; false otherwise.
     */
    private function max($field, $value)
    {
        if (strlen($this->item->$field) > $value) {
            $this->errors[$field] = 'Must not be longer than ' . $value . ' characters.';
            return false;
        }
        return true;
    }


    /**
     * Ensure field in item is not smaller than a min value.
     *
     * @param mixed $field Name of field to check.
     * @param mixed $value Min value.
     * @return bool True if field is not smaller than min value; false otherwise.
     */
    private function min($field, $value)
    {
        if (strlen($this->item->$field) < $value) {
            $this->errors[$field] = 'Must not be shorter than ' . $value . ' characters.';
            return false;
        }
        return true;
    }


    /**
     * Ensure field in item matches another field.
     *
     * @param mixed $field Name of field to check.
     * @param mixed $fieldToMatch Name of field to match against.
     * @return bool True if fields match; false otherwise.
     */
    private function matches($field, $fieldToMatch)
    {
        if ($this->item->$field !== $this->item->$fieldToMatch) {
            $this->errors[$field] = 'Must match ' . $fieldToMatch . '.';
            return false;
        }
        return true;
    }
}
