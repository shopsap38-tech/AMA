<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validateur de données déclaratif.
 *
 * Règles supportées : required, email, min:n, max:n, numeric, integer,
 * in:a,b,c, date, confirmed.
 */
final class Validator
{
    /** @var array<string, array<int, string>> */
    private array $errors = [];

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $rules   ex. ['title' => 'required|max:255']
     * @param array<string, string> $labels  libellés lisibles pour les messages
     */
    public function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $labels = [],
    ) {
    }

    /** @return array<string, array<int, string>> */
    public function validate(): array
    {
        foreach ($this->rules as $field => $ruleSet) {
            foreach (explode('|', $ruleSet) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $name, $param);
            }
        }
        return $this->errors;
    }

    public function fails(): bool
    {
        return $this->validate() !== [];
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, string $rule, ?string $param): void
    {
        $value = $this->data[$field] ?? null;
        $label = $this->labels[$field] ?? $field;

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && $value === [])) {
                    $this->addError($field, "Le champ « {$label} » est obligatoire.");
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "Le champ « {$label} » doit être une adresse email valide.");
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, "Le champ « {$label} » doit être numérique.");
                }
                break;

            case 'integer':
                if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "Le champ « {$label} » doit être un entier.");
                }
                break;

            case 'min':
                if (!empty($value) && mb_strlen((string) $value) < (int) $param) {
                    $this->addError($field, "Le champ « {$label} » doit contenir au moins {$param} caractères.");
                }
                break;

            case 'max':
                if ($value !== null && mb_strlen((string) $value) > (int) $param) {
                    $this->addError($field, "Le champ « {$label} » ne doit pas dépasser {$param} caractères.");
                }
                break;

            case 'in':
                $options = explode(',', (string) $param);
                if (!empty($value) && !in_array((string) $value, $options, true)) {
                    $this->addError($field, "La valeur du champ « {$label} » est invalide.");
                }
                break;

            case 'date':
                if (!empty($value) && strtotime((string) $value) === false) {
                    $this->addError($field, "Le champ « {$label} » doit être une date valide.");
                }
                break;

            case 'confirmed':
                if (($this->data[$field . '_confirmation'] ?? null) !== $value) {
                    $this->addError($field, "La confirmation du champ « {$label} » ne correspond pas.");
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
