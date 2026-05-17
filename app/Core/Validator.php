<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Hafif input validator. Controller'lardaki trim/preg_match/strlen
 * tekrarlarını tek noktaya çeker.
 *
 * Kullanım:
 *   $v = new Validator($_POST);
 *   $v->require('name', 'Ad zorunludur.');
 *   $v->tcKimlik('tc_kimlik');
 *   $v->phone('phone');
 *   $v->email('email', false);
 *   $v->inSet('role', ['uye','yk_adayi','denetleme_adayi','disiplin_adayi']);
 *   if ($v->hasErrors()) flash('error', $v->firstError());
 *   $data = $v->cleaned();
 */
final class Validator
{
    private array $data;
    private array $errors  = [];
    private array $cleaned = [];

    public function __construct(array $input)
    {
        $this->data = $input;
    }

    public function require(string $field, ?string $message = null): self
    {
        $v = $this->getTrimmed($field);
        if ($v === '') {
            $this->errors[$field] = $message ?? "{$field} zorunludur.";
        } else {
            $this->cleaned[$field] = $v;
        }
        return $this;
    }

    public function optional(string $field): self
    {
        $v = $this->getTrimmed($field);
        $this->cleaned[$field] = $v === '' ? null : $v;
        return $this;
    }

    public function tcKimlik(string $field, bool $required = false): self
    {
        $v = $this->getTrimmed($field);
        if ($v === '') {
            if ($required) {
                $this->errors[$field] = 'TC Kimlik numarası zorunludur.';
            } else {
                $this->cleaned[$field] = null;
            }
            return $this;
        }
        if (!ctype_digit($v) || strlen($v) !== 11) {
            $this->errors[$field] = 'TC Kimlik numarası 11 haneli rakam olmalıdır.';
            return $this;
        }
        // İlk hane 0 olamaz, mod-11 checksum (basit Mernis algoritması)
        if ($v[0] === '0') {
            $this->errors[$field] = 'TC Kimlik numarası 0 ile başlayamaz.';
            return $this;
        }
        $digits = array_map('intval', str_split($v));
        $sumOdd  = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
        $sumEven = $digits[1] + $digits[3] + $digits[5] + $digits[7];
        $check10 = (($sumOdd * 7) - $sumEven) % 10;
        if ($check10 < 0) $check10 += 10;
        if ($check10 !== $digits[9]) {
            $this->errors[$field] = 'TC Kimlik numarası geçersiz (10. hane uyumsuz).';
            return $this;
        }
        $sum11 = array_sum(array_slice($digits, 0, 10)) % 10;
        if ($sum11 !== $digits[10]) {
            $this->errors[$field] = 'TC Kimlik numarası geçersiz (11. hane uyumsuz).';
            return $this;
        }
        $this->cleaned[$field] = $v;
        return $this;
    }

    public function phone(string $field, bool $required = false): self
    {
        $v = $this->getTrimmed($field);
        if ($v === '') {
            if ($required) $this->errors[$field] = 'Telefon zorunludur.';
            else $this->cleaned[$field] = null;
            return $this;
        }
        if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $v)) {
            $this->errors[$field] = 'Geçersiz telefon numarası formatı.';
            return $this;
        }
        $this->cleaned[$field] = $v;
        return $this;
    }

    public function email(string $field, bool $required = false): self
    {
        $v = $this->getTrimmed($field);
        if ($v === '') {
            if ($required) $this->errors[$field] = 'E-posta zorunludur.';
            else $this->cleaned[$field] = null;
            return $this;
        }
        if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Geçersiz e-posta adresi.';
            return $this;
        }
        $this->cleaned[$field] = strtolower($v);
        return $this;
    }

    public function inSet(string $field, array $allowed, ?string $default = null): self
    {
        $v = $this->getTrimmed($field);
        if (in_array($v, $allowed, true)) {
            $this->cleaned[$field] = $v;
        } elseif ($default !== null) {
            $this->cleaned[$field] = $default;
        } else {
            $this->errors[$field] = 'Geçersiz değer.';
        }
        return $this;
    }

    public function minLength(string $field, int $min): self
    {
        $v = (string) ($this->cleaned[$field] ?? $this->getTrimmed($field));
        if (mb_strlen($v) < $min) {
            $this->errors[$field] = "En az {$min} karakter olmalı.";
        }
        return $this;
    }

    public function password(string $field): self
    {
        $v = (string) ($this->data[$field] ?? '');
        $err = PasswordPolicy::validate($v);
        if ($err !== null) {
            $this->errors[$field] = $err;
        } else {
            $this->cleaned[$field] = $v;
        }
        return $this;
    }

    public function intRange(string $field, int $min, int $max, ?int $default = null): self
    {
        $raw = $this->data[$field] ?? null;
        if ($raw === null || $raw === '') {
            if ($default !== null) {
                $this->cleaned[$field] = $default;
                return $this;
            }
            $this->errors[$field] = "{$field} sayı olmalı.";
            return $this;
        }
        if (!is_numeric($raw)) {
            $this->errors[$field] = "{$field} sayı olmalı.";
            return $this;
        }
        $i = (int) $raw;
        if ($i < $min || $i > $max) {
            $this->errors[$field] = "{$field} {$min}-{$max} arasında olmalı.";
            return $this;
        }
        $this->cleaned[$field] = $i;
        return $this;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $msg) return $msg;
        return null;
    }

    public function cleaned(): array
    {
        return $this->cleaned;
    }

    private function getTrimmed(string $field): string
    {
        $v = $this->data[$field] ?? '';
        return is_string($v) ? trim($v) : '';
    }
}
