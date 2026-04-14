<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasPrefixedId
{
    /**
     * Boot function from Laravel.
     */
    protected static function bootHasPrefixedId()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $prefix = $model->getIdPrefix();
                // Menghasilkan id seperti: mtc_9b1deb4d...
                $model->{$model->getKeyName()} = $prefix . str_replace('-', '', Str::uuid()->toString());
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        return 'string';
    }

    /**
     * Get the prefix for the model ID.
     * Default to empty string if not defined in the model.
     *
     * @return string
     */
    protected function getIdPrefix(): string
    {
        return property_exists($this, 'idPrefix') ? $this->idPrefix : '';
    }
}
