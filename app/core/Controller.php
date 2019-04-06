<?php

/**
 * Core controller class.
 */
class Controller
{
    /**
     * Run validations on item.
     *
     * Runs all specified validations against $_POST and $_FILES. If all valiations passed,
     * the validated property on the controller instance is changed to true. Otherwise,
     * a list of valiations errors are saved on the controller instance.
     *
     * @param array $validations Array of validations to run against model instance.
     */
    public function validate(array $validations)
    {
        $validator = new Validator($validations);
        if (!$validator->run()) {
            request()->save();
            Session::set('errors', $validator->getErrors());
            back();
        }
    }
}
