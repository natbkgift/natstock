<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;

class CommaSeparatedEmails implements Rule, DataAwareRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    public function setData($data): static
    {
        $this->data = (array) $data;

        return $this;
    }

    public function passes($attribute, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $emails = array_filter(array_map('trim', explode(',', (string) $value)), static fn ($email) => $email !== '');

        if (empty($emails)) {
            return false;
        }

        foreach ($emails as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return 'รูปแบบอีเมลไม่ถูกต้อง กรุณาระบุอีเมลคั่นด้วยเครื่องหมายจุลภาค (,).';
    }
}
