<?php

namespace Zeretei\PHPCore\Http\Traits;

use \Zeretei\PHPCore\Application;
use \Zeretei\PHPCore\Http\Request;

trait Validator
{
    /**
     * Default error message templates.
     * Placeholders are wrapped in curly braces: {field}, {min}, {max}, {key}.
     *
     * @var array<string, string>
     */
    protected static array $ERROR_MESSAGES = [
        'required' => 'The {field} is required.',
        'string'   => 'The {field} must be a string.',
        'email'    => 'Invalid email address.',
        'min'      => 'The {field} is too short (minimum {min} characters).',
        'max'      => 'The {field} is too long (maximum {max} characters).',
        'same'     => 'The {field} and {key} did not match.',
        'confirm'  => 'The {field} confirmation did not match.',
    ];

    /**
     * The current field name being validated (matches the HTML input name attribute).
     */
    protected string $input = '';

    /**
     * Validate an associative map of field-name => rule(s).
     *
     * Rules may be a pipe-delimited string ("required|min:6|max:255") or an array.
     * Returns the validated key-value pairs on success, or redirects back with
     * session errors on failure (never returns null on a validation error).
     *
     * @param array<string, string|list<string>> $fields
     * @return array<string, mixed>|never
     */
    public function validate(array $fields): ?array
    {
        $validated = [];

        foreach ($fields as $key => $rules) {
            if (!is_string($key)) {
                $rulesStr = is_array($rules) ? implode('|', $rules) : (string) $rules;
                throw new \Exception(
                    sprintf('Missing field name for validation rules: "%s".', $rulesStr)
                );
            }

            $this->input = $key;
            $value = Request::request($key);

            if (is_null($value)) {
                throw new \Exception(
                    sprintf('Request field "%s" is not present in the submitted data.', $key)
                );
            }

            // Normalize string rules to an array: "required|min:6" → ['required', 'min:6']
            $ruleList = is_string($rules) ? explode('|', $rules) : $rules;

            foreach ($ruleList as $rule) {
                // execute() returns true when a rule fails; short-circuit remaining rules.
                if ($this->execute($rule, $value)) {
                    break;
                }
            }

            $validated[$key] = $value;
        }

        $hasErrors = count(Application::get('session')->errorBag()) > 0;

        return $hasErrors
            ? Application::get('router')->back()
            : $validated;
    }

    /**
     * Rule: the field must be non-empty.
     * Returns true (failure) when the value is empty, false (pass) otherwise.
     */
    protected function required(string $request): bool
    {
        if ($request === '') {
            $this->error('required', ['field' => $this->input]);
            return true;
        }
        return false;
    }

    /**
     * Rule: the field must be a string.
     * Since all HTTP input is a string, this guards against programmatic misuse.
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
     * Rule: the field must be at least $value characters long.
     *
     * @throws \Exception when $value is not a positive integer string.
     */
    protected function min(string $request, string $value): bool
    {
        if (!$this->isNumeric($value)) {
            throw new \Exception('The "min" rule value must be a positive integer.');
        }

        if (strlen($request) < (int) $value) {
            $this->error('min', ['field' => $this->input, 'min' => $value]);
            return true;
        }

        return false;
    }

    /**
     * Rule: the field must not exceed $value characters.
     *
     * @throws \Exception when $value is not a positive integer string.
     */
    protected function max(string $request, string $value): bool
    {
        if (!$this->isNumeric($value)) {
            throw new \Exception('The "max" rule value must be a positive integer.');
        }

        if (strlen($request) > (int) $value) {
            $this->error('max', ['field' => $this->input, 'max' => $value]);
            return true;
        }

        return false;
    }

    /**
     * Rule: the field must be a valid email address.
     */
    protected function email(string $request): bool
    {
        if (!$this->isEmail($request)) {
            $this->error('email');
            return true;
        }
        return false;
    }

    /**
     * Rule: the field value must equal the value of another field ($key).
     */
    protected function same(string $request, string $key): bool
    {
        if (Request::request($key) !== $request) {
            $this->error('same', ['field' => $this->input, 'key' => $key]);
            return true;
        }
        return false;
    }

    /**
     * Rule: the field value must equal its auto-named confirmation field
     * (e.g., "password" must match "password_confirmation").
     */
    protected function confirm(string $request): bool
    {
        $key = $this->input . '_confirmation';

        if (Request::request($key) !== $request) {
            $this->error('confirm', ['field' => $this->input]);
            return true;
        }
        return false;
    }

    /**
     * Return true when $value is a non-empty string of digits.
     */
    public function isNumeric(string $value): bool
    {
        return $value !== '' && ctype_digit($value);
    }

    /**
     * Return true when $email passes both PHP's email filter and a basic regex check.
     */
    public function isEmail(string $email): bool
    {
        $sanitized = (string) filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($sanitized, FILTER_VALIDATE_EMAIL) !== false
            && preg_match('/^[\w.]{3,50}@\w{2,12}\.\w{2,8}$/', $sanitized) === 1;
    }

    /**
     * Store a validation error in the session error bag for the current field.
     *
     * @param array<string, string> $params Template placeholder replacements.
     */
    protected function error(string $key, array $params = []): void
    {
        $message = static::$ERROR_MESSAGES[$key] ?? '';

        foreach ($params as $placeholder => $replacement) {
            $message = str_replace("{{$placeholder}}", $replacement, $message);
        }

        Application::get('session')->setErrorFlash($this->input, $message);
    }

    /**
     * Execute a single validation rule against $value.
     *
     * Returns true when the rule fails (signals the caller to short-circuit).
     * Returns false when the rule passes.
     *
     * @throws \Exception when the rule name does not correspond to a known method.
     */
    protected function execute(string $rule, mixed $value): bool
    {
        [$ruleName, $parameter] = [...explode(':', $rule, 2), null];

        if (!method_exists($this, $ruleName)) {
            throw new \Exception(sprintf('Validation rule "%s" does not exist.', $ruleName));
        }

        return $this->{$ruleName}($value, $parameter);
    }
}
