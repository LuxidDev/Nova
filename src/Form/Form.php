<?php

namespace Luxid\Nova\Form;

class Form
{
  protected string $action;
  protected string $method = 'POST';
  protected array $attributes = [];
  protected array $fields = [];
  protected array $errors = [];
  protected array $data = [];

  public function __construct(string $action = '', string $method = 'POST')
  {
    $this->action = $action;
    $this->method = strtoupper($method);
  }

  public static function open(string $action = '', string $method = 'POST'): self
  {
    return new self($action, $method);
  }

  public static function close(): string
  {
    return '</form>';
  }

  public function attribute(string $key, $value): self
  {
    $this->attributes[$key] = $value;
    return $this;
  }

  public function attributes(array $attributes): self
  {
    $this->attributes = array_merge($this->attributes, $attributes);
    return $this;
  }

  public function multipart(): self
  {
    $this->attributes['enctype'] = 'multipart/form-data';
    return $this;
  }

  public function data(array $data): self
  {
    $this->data = $data;
    return $this;
  }

  public function errors(array $errors): self
  {
    $this->errors = $errors;
    return $this;
  }

  public function field(string $name): Field
  {
    $value = $this->data[$name] ?? null;
    $fieldErrors = $this->errors[$name] ?? [];

    return Field::make($name, $value)->errors($fieldErrors);
  }

  public function text(string $name): Field
  {
    return $this->field($name)->type('text');
  }

  public function email(string $name): Field
  {
    return $this->field($name)->type('email')->attribute('autocomplete', 'email');
  }

  public function password(string $name): Field
  {
    return $this->field($name)->type('password');
  }

  public function number(string $name): Field
  {
    return $this->field($name)->type('number');
  }

  public function textarea(string $name): Field
  {
    return $this->field($name)->type('textarea');
  }

  public function checkbox(string $name, $value = '1'): string
  {
    $checked = ($this->data[$name] ?? '') == $value ? 'checked' : '';
    $hasError = !empty($this->errors[$name]);

    $html = '<div class="nova-field-checkbox">';
    $html .= '<input type="checkbox" name="' . $name . '" value="' . htmlspecialchars($value) . '" ' . $checked . '>';
    $html .= '<label>' . htmlspecialchars(ucfirst($name)) . '</label>';

    if ($hasError) {
      $html .= '<div class="nova-field-error-message">' . htmlspecialchars($this->errors[$name][0]) . '</div>';
    }

    $html .= '</div>';

    return $html;
  }

  public function select(string $name, array $options = []): string
  {
    $selected = $this->data[$name] ?? '';
    $hasError = !empty($this->errors[$name]);

    $html = '<div class="nova-field">';
    $html .= '<label>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $name))) . '</label>';
    $html .= '<select name="' . $name . '">';

    foreach ($options as $value => $label) {
      $selectedAttr = $selected == $value ? 'selected' : '';
      $html .= '<option value="' . htmlspecialchars($value) . '" ' . $selectedAttr . '>' . htmlspecialchars($label) . '</option>';
    }

    $html .= '</select>';

    if ($hasError) {
      $html .= '<div class="nova-field-error-message">' . htmlspecialchars($this->errors[$name][0]) . '</div>';
    }

    $html .= '</div>';

    return $html;
  }

  public function submit(string $label = 'Submit', array $attributes = []): string
  {
    $attrs = '';
    foreach ($attributes as $key => $value) {
      $attrs .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
    }

    return '<button type="submit"' . $attrs . '>' . htmlspecialchars($label) . '</button>';
  }

  public function csrf(): string
  {
    $token = bin2hex(random_bytes(32));
    $_SESSION['_token'] = $token;

    return '<input type="hidden" name="_token" value="' . $token . '">';
  }

  public function render(): string
  {
    $attrs = $this->renderAttributes();
    $html = '<form action="' . htmlspecialchars($this->action) . '" method="' . $this->method . '"' . $attrs . '>';

    if ($this->method !== 'GET') {
      $html .= $this->csrf();
    }

    return $html;
  }

  protected function renderAttributes(): string
  {
    $attrs = [];
    foreach ($this->attributes as $key => $value) {
      $attrs[] = $key . '="' . htmlspecialchars($value) . '"';
    }
    return implode(' ', $attrs);
  }

  public function __toString()
  {
    return $this->render();
  }
}
