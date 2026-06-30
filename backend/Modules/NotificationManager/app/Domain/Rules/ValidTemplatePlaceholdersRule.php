<?php

namespace Modules\NotificationManager\Domain\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidTemplatePlaceholdersRule implements Rule
{
    protected $allowedPlaceholders = ['member_name', 'expiry_date', 'plan_name', 'remaining_sessions', 'paid_amount'];

    public function passes($attribute, $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $lang => $content) {
                if (!$this->checkContent($content)) return false;
            }
        } else {
            return $this->checkContent($value);
        }
        return true;
    }

    protected function checkContent($content)
    {
        preg_match_all('/\{(.*?)\}/', $content, $matches);
        foreach ($matches[1] as $placeholder) {
            if (!in_array($placeholder, $this->allowedPlaceholders)) {
                return false;
            }
        }
        return true;
    }

    public function message(): string
    {
        return __('The template contains invalid placeholders. Allowed: ') . implode(', ', $this->allowedPlaceholders);
    }
}
