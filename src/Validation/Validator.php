<?php

namespace Luxid\Nova\Validation;

class Validator
{
  /**
   * Validation rules
   * @var array
   */
  protected array $rules = [];

  /**
   * Validation errors
   * @var array
   */
  protected array $errors = [];

  /**
   * Custom error messages
   * @var array
   */
  protected array $messages = [];

  /**
   * Data being validated
   * @var array
   */
  protected array $data = [];

  /**
   * Available validation rules
   */
  protected array $availableRules = [
    'required',
    'email',
    'min',
    'max',
    'between',
    'confirmed',
    'numeric',
    'integer',
    'string',
    'array',
    'boolean',
    'url',
    'date',
    'alpha',
    'alpha_num',
    'unique',
    'exists',
    'in',
    'not_in',
    'regex',
    'same',
    'different',
    'required_if',
    'required_unless'
  ];

  /**
   * Constructor
   */
  public function __construct(array $data = [], array $rules = [], array $messages = [])
  {
    $this->data = $data;
    $this->rules = $rules;
    $this->messages = $messages;
  }

  /**
   * Validate data against rules
   */
  public function validate(?array $data = null, ?array $rules = null): bool
  {
    if ($data !== null) {
      $this->data = $data;
    }

    if ($rules !== null) {
      $this->rules = $rules;
    }

    $this->errors = [];

    foreach ($this->rules as $field => $fieldRules) {
      $value = $this->getValue($field);
      $rules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

      foreach ($rules as $rule) {
        $this->validateRule($field, $value, $rule);
      }
    }

    return empty($this->errors);
  }

  /**
   * Validate a single rule
   */
  protected function validateRule(string $field, $value, string $rule): void
  {
    // Parse rule with parameters (e.g., min:8)
    $ruleName = $rule;
    $parameters = [];

    if (strpos($rule, ':') !== false) {
      [$ruleName, $parameters] = explode(':', $rule, 2);
      $parameters = explode(',', $parameters);
    }

    if (!in_array($ruleName, $this->availableRules)) {
      return;
    }

    $method = 'validate' . ucfirst($ruleName);

    if (method_exists($this, $method)) {
      $isValid = $this->$method($field, $value, $parameters);

      if (!$isValid) {
        $this->addError($field, $ruleName, $parameters);
      }
    }
  }

  /**
   * Get value from data, supporting dot notation
   */
  protected function getValue(string $field)
  {
    $keys = explode('.', $field);
    $value = $this->data;

    foreach ($keys as $key) {
      if (!is_array($value) || !array_key_exists($key, $value)) {
        return null;
      }
      $value = $value[$key];
    }

    return $value;
  }

  /**
   * Add validation error
   */
  protected function addError(string $field, string $rule, array $parameters = []): void
  {
    $message = $this->getErrorMessage($field, $rule, $parameters);

    if (!isset($this->errors[$field])) {
      $this->errors[$field] = [];
    }

    $this->errors[$field][] = $message;
  }

  /**
   * Get error message for a rule
   */
  protected function getErrorMessage(string $field, string $rule, array $parameters = []): string
  {
    $fieldName = $this->getFieldName($field);

    // Check for custom message
    $customKey = $field . '.' . $rule;
    if (isset($this->messages[$customKey])) {
      return $this->replaceParameters($this->messages[$customKey], $fieldName, $parameters);
    }

    // Default messages
    $messages = [
      'required' => 'The :field field is required.',
      'email' => 'The :field must be a valid email address.',
      'min' => 'The :field must be at least :min characters.',
      'max' => 'The :field must not exceed :max characters.',
      'between' => 'The :field must be between :min and :max.',
      'confirmed' => 'The :field confirmation does not match.',
      'numeric' => 'The :field must be a number.',
      'integer' => 'The :field must be an integer.',
      'string' => 'The :field must be a string.',
      'array' => 'The :field must be an array.',
      'boolean' => 'The :field must be true or false.',
      'url' => 'The :field must be a valid URL.',
      'date' => 'The :field must be a valid date.',
      'alpha' => 'The :field may only contain letters.',
      'alpha_num' => 'The :field may only contain letters and numbers.',
      'unique' => 'The :field has already been taken.',
      'exists' => 'The selected :field is invalid.',
      'in' => 'The selected :field is invalid.',
      'not_in' => 'The selected :field is invalid.',
      'regex' => 'The :field format is invalid.',
      'same' => 'The :field and :other must match.',
      'different' => 'The :field and :other must be different.',
      'required_if' => 'The :field field is required when :other is :value.',
      'required_unless' => 'The :field field is required unless :other is :value.',
    ];

    $message = $messages[$rule] ?? 'The :field field is invalid.';
    return $this->replaceParameters($message, $fieldName, $parameters);
  }

  /**
   * Get human-readable field name
   */
  protected function getFieldName(string $field): string
  {
    // Check for custom field name in messages
    if (isset($this->messages[$field . '.label'])) {
      return $this->messages[$field . '.label'];
    }

    // Convert snake_case to Title Case
    return str_replace('_', ' ', ucfirst($field));
  }

  /**
   * Replace parameters in message
   */
  /**
   * Fill the placeholders in a message template.
   *
   * Rule parameters are positional, so placeholders are filled in the order
   * they appear in the message. The previous implementation keyed off the
   * parameter index instead, which meant a single-parameter rule like `max:50`
   * never filled its `:max` placeholder and the raw token reached the user.
   *
   * @param string       $message    Message template
   * @param string       $fieldName  Human readable field name
   * @param list<string> $parameters Rule parameters, in declaration order
   */
  protected function replaceParameters(string $message, string $fieldName, array $parameters): string
  {
    $message = str_replace(':field', $fieldName, $message);

    if (preg_match_all('/:(?:min|max|value|other|size|format|date)\b/', $message, $matches) < 1) {
      return $message;
    }

    foreach ($matches[0] as $index => $placeholder) {
      $value = (string) ($parameters[$index] ?? '');
      $replacement = $placeholder === ':other' ? $this->getFieldName($value) : $value;
      $position = strpos($message, $placeholder);

      if ($position !== false) {
        $message = substr_replace($message, $replacement, $position, strlen($placeholder));
      }
    }

    return $message;
  }

  // Validation Rules

  protected function validateRequired(string $field, $value, array $params): bool
  {
    if (is_null($value)) {
      return false;
    }

    if (is_string($value) && trim($value) === '') {
      return false;
    }

    if (is_array($value) && empty($value)) {
      return false;
    }

    return true;
  }

  protected function validateEmail(string $field, $value, array $params): bool
  {
    if (empty($value)) {
      return true; // Skip if empty (use required to enforce)
    }

    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
  }

  protected function validateMin(string $field, $value, array $params): bool
  {
    $min = (int) ($params[0] ?? 0);

    if (is_string($value)) {
      return strlen($value) >= $min;
    }

    if (is_numeric($value)) {
      return $value >= $min;
    }

    if (is_array($value)) {
      return count($value) >= $min;
    }

    return false;
  }

  protected function validateMax(string $field, $value, array $params): bool
  {
    $max = (int) ($params[0] ?? PHP_INT_MAX);

    if (is_string($value)) {
      return strlen($value) <= $max;
    }

    if (is_numeric($value)) {
      return $value <= $max;
    }

    if (is_array($value)) {
      return count($value) <= $max;
    }

    return false;
  }

  protected function validateBetween(string $field, $value, array $params): bool
  {
    $min = (int) ($params[0] ?? 0);
    $max = (int) ($params[1] ?? PHP_INT_MAX);

    if (is_string($value)) {
      $len = strlen($value);
      return $len >= $min && $len <= $max;
    }

    if (is_numeric($value)) {
      return $value >= $min && $value <= $max;
    }

    if (is_array($value)) {
      $count = count($value);
      return $count >= $min && $count <= $max;
    }

    return false;
  }

  protected function validateConfirmed(string $field, $value, array $params): bool
  {
    $confirmationField = $field . '_confirmation';
    $confirmation = $this->getValue($confirmationField);

    return $value === $confirmation;
  }

  protected function validateNumeric(string $field, $value, array $params): bool
  {
    return is_numeric($value);
  }

  protected function validateInteger(string $field, $value, array $params): bool
  {
    return filter_var($value, FILTER_VALIDATE_INT) !== false;
  }

  protected function validateString(string $field, $value, array $params): bool
  {
    return is_string($value);
  }

  protected function validateArray(string $field, $value, array $params): bool
  {
    return is_array($value);
  }

  protected function validateBoolean(string $field, $value, array $params): bool
  {
    return is_bool($value) || in_array($value, ['true', 'false', '1', '0', 1, 0], true);
  }

  protected function validateUrl(string $field, $value, array $params): bool
  {
    if (empty($value)) {
      return true;
    }

    return filter_var($value, FILTER_VALIDATE_URL) !== false;
  }

  protected function validateDate(string $field, $value, array $params): bool
  {
    if (empty($value)) {
      return true;
    }

    $timestamp = strtotime($value);
    return $timestamp !== false;
  }

  protected function validateAlpha(string $field, $value, array $params): bool
  {
    return ctype_alpha($value);
  }

  protected function validateAlphaNum(string $field, $value, array $params): bool
  {
    return ctype_alnum($value);
  }

  protected function validateIn(string $field, $value, array $params): bool
  {
    return in_array($value, $params);
  }

  protected function validateNotIn(string $field, $value, array $params): bool
  {
    return !in_array($value, $params);
  }

  protected function validateRegex(string $field, $value, array $params): bool
  {
    if (empty($params[0])) {
      return true;
    }

    return preg_match($params[0], $value) === 1;
  }

  protected function validateSame(string $field, $value, array $params): bool
  {
    $other = $params[0] ?? null;
    if (!$other) {
      return true;
    }

    $otherValue = $this->getValue($other);
    return $value === $otherValue;
  }

  protected function validateDifferent(string $field, $value, array $params): bool
  {
    $other = $params[0] ?? null;
    if (!$other) {
      return true;
    }

    $otherValue = $this->getValue($other);
    return $value !== $otherValue;
  }

  protected function validateRequiredIf(string $field, $value, array $params): bool
  {
    $other = $params[0] ?? null;
    $requiredValue = $params[1] ?? null;

    if (!$other) {
      return true;
    }

    $otherValue = $this->getValue($other);

    if ($otherValue == $requiredValue) {
      return $this->validateRequired($field, $value, []);
    }

    return true;
  }

  protected function validateRequiredUnless(string $field, $value, array $params): bool
  {
    $other = $params[0] ?? null;
    $unlessValue = $params[1] ?? null;

    if (!$other) {
      return true;
    }

    $otherValue = $this->getValue($other);

    if ($otherValue != $unlessValue) {
      return $this->validateRequired($field, $value, []);
    }

    return true;
  }

  // Getters

  public function getErrors(): array
  {
    return $this->errors;
  }

  public function hasErrors(): bool
  {
    return !empty($this->errors);
  }

  public function getFirstError(string $field): ?string
  {
    return $this->errors[$field][0] ?? null;
  }

  public function getValidated(): array
  {
    $validated = [];

    foreach ($this->rules as $field => $rules) {
      $validated[$field] = $this->getValue($field);
    }

    return $validated;
  }

  public function setMessages(array $messages): self
  {
    $this->messages = $messages;
    return $this;
  }

  public function setData(array $data): self
  {
    $this->data = $data;
    return $this;
  }

  public function setRules(array $rules): self
  {
    $this->rules = $rules;
    return $this;
  }
}
