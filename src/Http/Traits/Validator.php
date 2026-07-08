<?php

namespace Zeretei\PHPCore\Http\Traits;

use \Zeretei\PHPCore\Application;
use \Zeretei\PHPCore\Http\Request;

trait Validator
{
    /**
     * Rules error message
     */
    protected static $ERROR_MESSAGES = [
        'required' => 'The {field} is required.',
        'string' => 'The {field} must be a string.',
        'email' => 'Invalid Email Address.',
        'min' => 'Your {field} is too short, min of {min}.',
        'max' => 'Your {field} is too long, max of {max}.',
        'same' => 'The {field} and {key} did not match.',
        'confirm' => 'The {field} and confirm {field} did not match.',
    ];

    /**
     * input name (<input name="" />)
     */
    protected $input;

    // private const PASSED = true;
    // private const FAILED = false;

    /**
     * Validate input fields with rules
     */
    public function validate(array $fields): ?array
    {
        $validated = [];

        foreach ($fields as $key => $rules) {
            if (!is_string($key)) {
                $rulesStr = is_array($rules) ? json_encode($rules) : (string)$rules;
                throw new \Exception(
                    sprintf('Request is missing a field or a rule for validation key: "%s"', $rulesStr)
                );
            }

            $value = Request::request($key);

            $this->input = $key;

            if (is_null($value)) {
                throw new \Exception(
                    sprintf('request field "%s" does not exist.', $this->input)
                );
            }

            // if "required|min:6|max:255"  (string with `|`)
            // convert to ["required", "min:6", "max:255"]
            $rules = !is_string($rules) ? $rules : explode("|", $rules);

            foreach ($rules as $rule) {
                //! change to false (tests should pass)
                if ($this->execute($rule, $value)) break;
            }

            $validated[$key] = $value;
        }

        $hasError = count(Application::get('session')->errorBag()) > 0;

        return (!$hasError) ? $validated : Application::get('router')->back();
    }


    /**
     * Check if the request is set and has a value
     */
    protected function required(string $request): bool
    {
        if (!isset($request) || empty($request)) {
            $this->error('required', ['field' => $this->input]);
            return true;
        }
        return false;
    }

    /**
     * Check if the Request is a string
     */
    protected function string(string $request): bool
    {
        if (!is_string($request)) {
            $this->error('string', ['field' => $this->input]);
            return true;
        }

        return false;
    }

    /**
     * Check if the rule minimum value passed
     */
    protected function min(string  $request, string $value)
    {
        if (!$this->isNumeric($value)) {
            throw new \Exception('Rule "min" must not contain a non numerical value.');
        }

        if (strlen($request) < (int) $value) {
            $this->error('min', ['field' => $this->input, 'min' => $value]);
            return true;
        }

        return false;
    }


    /**
     * Check if the rule maximum value didn't exceed
     */
    protected function max(string $request, string $value): bool
    {
        if (!$this->isNumeric($value)) {
            throw new \Exception('Rule "max" must not contain a non numerical value.');
        }

        if (strlen($request) > (int) $value) {
            $this->error('max', ['field' => $this->input, 'max' => $value]);
            return true;
        }

        return false;
    }

    /**
     * Check if the request is a valid email address
     */
    protected function email($request): bool
    {
        if (!$this->isEmail($request)) {
            $this->error('email');
            return true;
        }

        return false;
    }

    /**
     * Check if the 2 inputs matched
     */
    protected function same(string $request, string $key)
    {
        if (Request::request($key) !== $request) {
            $this->error('same', ['field' => $this->input, 'key' => $key]);
            return true;
        }

        return false;
    }

    /**
     * Check if the input and the confirm input matched
     */
    protected function confirm(string $request)
    {
        $key = $this->input . '_confirmation';

        if (Request::request($key) !== $request) {
            $this->error('confirm', ['field' => $this->input]);
            return true;
        }

        return false;
    }

    /**
     * Check if string $value only contains numeric value
     */
    public function isNumeric(string $value): bool
    {
        return $value !== '' && ctype_digit($value);
    }

    /**
     * Check if $value is an email
     */
    public function isEmail(string $email): bool
    {
        $sanitizedEmail = filter_var($email, FILTER_SANITIZE_EMAIL);
        $isFilterValidEmail = filter_var($sanitizedEmail, FILTER_VALIDATE_EMAIL);
        $isRegexValidEmail = preg_match('/^[\w\.]{3,50}@\w{2,12}\.\w{2,8}$/', $sanitizedEmail);
        return (bool) ($isFilterValidEmail && $isRegexValidEmail);
    }

    /**
     * Set the session errors
     */
    protected function error(string $key, array $params = [])
    {
        $message = $this->getErrors()[$key] ?? '';

        foreach ($params as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }

        Application::get('session')->setErrorFlash($this->input, $message);
    }

    /**
     * All errors
     */
    protected function getErrors(): array
    {
        return static::$ERROR_MESSAGES;
    }

    /**
     * Execute rule
     */
    protected function execute(string $rule, mixed $value): bool
    {
        [$rule, $parameter] = [...explode(':', $rule), null];

        if (!method_exists($this::class, $rule)) {
            throw new \Exception(sprintf('Rule "%s" does not exist', $rule));
        }

        return $this->{$rule}($value, $parameter);
    }
}
