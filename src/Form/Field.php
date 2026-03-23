<?php

namespace Luxid\Nova\Form;

class Field
{
  protected string $name;
  protected string $type = 'text';
  protected $value = null;
  protected array $errors = [];
  protected array $attributes = [];
  protected string $label = '';

  public function __construct(string $name, $value = null)
  {
    $this->name = $name;
    $this->value = $value;
    $this->label = ucfirst(str_replace('_', ' ', $name));
  }

  public static function make(string $name, $value = null): self
  {
    return new self($name, $value);
  }

  public function type(string $type): self
  {
    $this->type = $type;
    return $this;
  }

  public function label(string $label): self
  {
    $this->label = $label;
    return $this;
  }

  public function value($value): self
  {
    $this->value = $value;
    return $this;
  }

  public function errors(array $errors): self
  {
    $this->errors = $errors;
    return $this;
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

  public function required(bool $required = true): self
  {
    if ($required) {
      $this->attributes['required'] = 'required';
    } else {
      unset($this->attributes['required']);
    }
    return $this;
  }

  public function placeholder(string $placeholder): self
  {
    $this->attributes['placeholder'] = $placeholder;
    return $this;
  }

  public function render(): string
  {
    $hasError = !empty($this->errors);
    $errorClass = $hasError ? 'nova-field-error' : '';

    $attributes = $this->renderAttributes();

    $html = '<div class="nova-field ' . $errorClass . '">';
    $html .= '<label for="' . htmlspecialchars($this->name) . '">' . htmlspecialchars($this->label) . '</label>';
    $html .= '<input type="' . $this->type . '" name="' . $this->name . '" value="' . htmlspecialchars($this->value) . '" ' . $attributes . '>';

    if ($hasError) {
      $html .= '<div class="nova-field-error-message">' . htmlspecialchars($this->errors[0]) . '</div>';
    }

    $html .= '</div>';

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
