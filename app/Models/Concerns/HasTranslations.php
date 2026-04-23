<?php

namespace App\Models\Concerns;

trait HasTranslations
{
    /**
     * Get the translated string from a JSON column.
     * Fallback to 'id' if current locale is not found.
     * Fallback to the raw value if it's not an array.
     */
    public function getTranslated(string $field): ?string
    {
        $value = $this->{$field};

        if (!is_array($value)) {
            return $value;
        }

        $locale = app()->getLocale();

        return $value[$locale] ?? $value['id'] ?? array_values($value)[0] ?? null;
    }
}
