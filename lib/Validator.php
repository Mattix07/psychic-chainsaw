<?php
/**
 * Validator
 *
 * Centralizza tutta la logica di validazione.
 */

require_once __DIR__ . '/../config/messages.php';
require_once __DIR__ . '/../config/app_config.php';

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Valida che un campo sia richiesto
     */
    public function required(string $field, string $message = null): self
    {
        if (empty($this->data[$field])) {
            $this->errors[$field][] = $message ?? "Il campo $field è obbligatorio";
        }
        return $this;
    }

    /**
     * Valida che un campo sia un'email valida
     */
    public function email(string $field, string $message = null): self
    {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?? ERR_INVALID_EMAIL;
        }
        return $this;
    }

    /**
     * Valida lunghezza minima
     */
    public function min(string $field, int $min, string $message = null): self
    {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field][] = $message ?? "Il campo $field deve essere di almeno $min caratteri";
        }
        return $this;
    }

    /**
     * Valida lunghezza massima
     */
    public function max(string $field, int $max, string $message = null): self
    {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field][] = $message ?? "Il campo $field non può superare $max caratteri";
        }
        return $this;
    }

    /**
     * Valida che due campi siano uguali (es. password e conferma)
     */
    public function matches(string $field, string $otherField, string $message = null): self
    {
        if (($this->data[$field] ?? '') !== ($this->data[$otherField] ?? '')) {
            $this->errors[$field][] = $message ?? ERR_PASSWORD_MISMATCH;
        }
        return $this;
    }

    /**
     * Valida che un campo sia numerico
     */
    public function numeric(string $field, string $message = null): self
    {
        if (!empty($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = $message ?? "Il campo $field deve essere numerico";
        }
        return $this;
    }

    /**
     * Valida che un campo sia in un array di valori
     */
    public function in(string $field, array $values, string $message = null): self
    {
        if (!empty($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field][] = $message ?? "Il campo $field contiene un valore non valido";
        }
        return $this;
    }

    /**
     * Valida una data
     */
    public function date(string $field, string $format = 'Y-m-d', string $message = null): self
    {
        if (!empty($this->data[$field])) {
            $d = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field][] = $message ?? ERR_INVALID_DATE;
            }
        }
        return $this;
    }

    /**
     * Valida che una data sia futura
     */
    public function future(string $field, string $message = null): self
    {
        if (!empty($this->data[$field])) {
            $date = strtotime($this->data[$field]);
            if ($date && $date <= time()) {
                $this->errors[$field][] = $message ?? "Il campo $field deve essere una data futura";
            }
        }
        return $this;
    }

    /**
     * Valida un URL
     */
    public function url(string $field, string $message = null): self
    {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = $message ?? "Il campo $field deve essere un URL valido";
        }
        return $this;
    }

    /**
     * Validazione personalizzata con callback
     */
    public function custom(string $field, callable $callback, string $message = null): self
    {
        if (!$callback($this->data[$field] ?? null, $this->data)) {
            $this->errors[$field][] = $message ?? "Il campo $field non è valido";
        }
        return $this;
    }

    /**
     * Verifica se la validazione è passata
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Verifica se la validazione è fallita
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Ottiene gli errori
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Ottiene il primo errore
     */
    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }

    /**
     * Ottiene tutti gli errori come stringa
     */
    public function errorsAsString(): string
    {
        $messages = [];
        foreach ($this->errors as $fieldErrors) {
            $messages = array_merge($messages, $fieldErrors);
        }
        return implode('; ', $messages);
    }
}

/**
 * Helper per creare un Validator
 */
if (!function_exists('validate')) {
    function validate(array $data): Validator
    {
        return new Validator($data);
    }
}

/**
 * Esempi di utilizzo:
 *
 * // Validazione login
 * $validator = validate($_POST)
 *     ->required('email')
 *     ->email('email')
 *     ->required('password')
 *     ->min('password', PASSWORD_MIN_LENGTH);
 *
 * if ($validator->fails()) {
 *     setErrorMessage($validator->firstError());
 *     redirect('login.php');
 * }
 *
 * // Validazione registrazione
 * $validator = validate($_POST)
 *     ->required('nome')
 *     ->required('cognome')
 *     ->required('email')
 *     ->email('email')
 *     ->required('password')
 *     ->min('password', PASSWORD_MIN_LENGTH)
 *     ->required('password_confirm')
 *     ->matches('password', 'password_confirm');
 *
 * // Validazione evento
 * $validator = validate($_POST)
 *     ->required('nome')
 *     ->required('data')
 *     ->date('data')
 *     ->future('data')
 *     ->required('categoria')
 *     ->in('categoria', [
 *         CATEGORIA_CONCERTI,
 *         CATEGORIA_TEATRO,
 *         CATEGORIA_SPORT
 *     ]);
 */
