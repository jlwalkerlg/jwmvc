<?php

class Validator
{
    /** @var Request $request Request instance. */
    private $request;


    /** @var array $validations Validations to run against input. */
    private $validations = [];


    /** @var array $errors Array of errors for each field in input, if validations failed. */
    private $errors = [];


    /**
     * Parse validations and store parsed validations for each field.
     *
     * @param array $validations Array of validations to run against model instance.
     */
    public function __construct(array $validations)
    {
        // Store request on object.
        $this->request = request();

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
                $params = $exploded[1] ?? null; // '6'
                $rules[$name] = explode(',', $params); // $rules['min'] = '6'
                unset($rules[$i]); // unset $rules[0]
            }

            // Add rules to validations array for current field.
            // $validations['email'] = ['min' => '6', 'max' => '10']
            $this->validations[$field] = $rules;
        }
    }


    /**
     * Run all validations against input.
     *
     * @return bool True if all validations passed; false otherwise.
     */
    public function run()
    {
        // For all validations, get the field and the rules
        // to validate it with.
        // $validations as ['email'] = ['min' => '6', 'max' => '10']
        foreach ($this->validations as $field => $rules) {
            // Store variable indicating if the field is required.
            // If not, first check if the input has been submitted.
            // If it was not submitted, don't run the other validations
            // for the current field; instead, skip to the next field.
            $required = array_key_exists('required', $rules);
            if (!$required) {
                $present = $this->required($field);
                if (!$present) {
                    // Required method will add to errors array; however,
                    // if the field was not required and is not present, there
                    // should not be an error.
                    unset($this->errors[$field]);
                    continue;
                }
            }
            // Get the name of the validation function to run and
            // the parameters to use.
            // $rules as 'min' => '6'
            foreach ($rules as $name => $params) {
                // Run validation function against the input field.
                // $field = 'email', $param = '6'
                if (!$this->$name($field, ...$params)) {
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
     * Ensure field in input is present.
     *
     * @param mixed $field Name of field to check.
     * @return bool True if field is present; false otherwise.
     */
    private function required($field)
    {
        $input = $this->request->input($field);

        if (is_null($input)) {
            $this->errors[$field] = 'Required.';
            return false;
        }

        if ($this->request->file($field)) {
            if (!$this->request->file($field)->checkRequired()) {
                $this->errors[$field] = $this->request->file($field)->getError('required');
                return false;
            }
        }
        else {
            if (is_array($input)) {
                if (empty($input)) {
                    $this->errors[$field] = 'Required.';
                    return false;
                }
            }
            elseif (trim($input) === '') {
                $this->errors[$field] = 'Required.';
                return false;
            }
        }

        return true;
    }


    /**
     * Ensure field is not already in database unique.
     *
     * @param mixed $field Name of field to check.
     * @param string $table Table to check for record uniqueness.
     * @param string $primaryKey Primary key of column to check
     * @return bool True if field is unique; false otherwise.
     */
    private function unique($field, string $table, string $primaryKey = null, $ignoreKey = false)
    {
        if ($ignoreKey !== false) {
            $count = DB::table($table)->where([
                [$field, $this->request->input($field)],
                [$primaryKey, '!=', $ignoreKey]
            ])->count();
        } else {
            $count = DB::table($table)->where([
                [$field, $this->request->input($field)]
            ])->count();
        }
        if ($count > 0) {
            $this->errors[$field] = 'Already taken.';
            return false;
        }
        return true;
    }


    /**
     * Ensure field in input has correct format.
     *
     * @param mixed $field Name of field to check.
     * @return bool True if field has correct format; false otherwise.
     */
    private function format($field, string $format)
    {
        $val = $this->request->input($field);

        if ($format === 'email' && filter_var($val, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field] = 'Invalid email.';
            return false;
        }
        if ($format === 'date' && !preg_match('/^[0-9]{4}-(0[0-9]|1[0-2])-(0[0-9]|[12][0-9]|3[01])$/', $val)) {
            $this->errors[$field] = 'Invalid date format (YYYY-MM-DD).';
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
        if ($format === 'url' && filter_var($val, FILTER_VALIDATE_URL) === false) {
            $this->errors[$field] = 'Invalid URL.';
            return false;
        }
        return true;
    }


    /**
     * Ensure field in input is not larger than a max value.
     *
     * @param mixed $field Name of field to check.
     * @param mixed $value Max value.
     * @return bool True if field is not larger than max value; false otherwise.
     */
    private function max($field, $value)
    {
        if ($file = $this->request->file($field)) {
            $file->setOptions(['maxSize' => $value]);
            if (!$file->checkMaxSize()) {
                $this->errors[$field] = $file->getError('maxSize');
                return false;
            }
        } else {
            $val = $this->request->input($field);

            if (is_numeric($val)) {
                if (floatval($val) > $value) {
                    $this->errors[$field] = 'Must not be greater than ' . $value . '.';
                    return false;
                }
            }
            elseif(is_array($val)) {
                if (count($val) > $value) {
                    $this->errors[$field] = 'At most ' . $value . ' inputs must be selected.';
                    return false;
                }
            }
            else {
                if (strlen($this->request->input($field)) > $value) {
                    $this->errors[$field] = 'Must not be longer than ' . $value . ' characters.';
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * Ensure field in input is not smaller than a min value.
     *
     * @param mixed $field Name of field to check.
     * @param mixed $value Min value.
     * @return bool True if field is not smaller than min value; false otherwise.
     */
    private function min($field, $value)
    {
        $val = $this->request->input($field);

        if (is_numeric($val)) {
            if (floatval($val) < $value) {
                $this->errors[$field] = 'Must not be less than ' . $value . '.';
                return false;
            }
        }
        elseif(is_array($val)) {
            if (count($val) < $value) {
                $this->errors[$field] = 'At least ' . $value . ' inputs must be selected.';
                return false;
            }
        }
        else {
            if (strlen($this->request->input($field)) < $value) {
                $this->errors[$field] = 'Must not be shorter than ' . $value . ' characters.';
                return false;
            }
        }
        return true;
    }


    /**
     * Ensure field in input matches another field.
     *
     * @param mixed $field Name of field to check.
     * @param mixed $fieldToMatch Name of field to match against.
     * @return bool True if fields match; false otherwise.
     */
    private function matches($field, $fieldToMatch)
    {
        if ($this->request->input($field) !== $this->request->input($fieldToMatch)) {
            $this->errors[$field] = 'Must match ' . $fieldToMatch . '.';
            return false;
        }
        return true;
    }


    /**
     * Check extensions and MIME type of uploaded file.
     *
     * @param mixed $field Name of field to check.
     * @return bool True if file extensions and MIME type is permitted; false otherwise.
     */
    private function types($field, ...$extensions) {
        $file = $this->request->file($field);
        $file->setOptions(['types' => $extensions]);
        if (!$file->checkType()) {
            $this->errors[$field] = $this->request->input($field)->getError('type');
            return false;
        }
        return true;
    }
}
