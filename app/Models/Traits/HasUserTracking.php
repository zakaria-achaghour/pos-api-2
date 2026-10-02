<?php

namespace App\Models\Traits;

use Infrastructure\Tenancy\Tenant;

trait HasUserTracking
{
    /**
     * Boot the trait.
     */
    protected static function bootHasUserTracking(): void
    {
        // Set created_by when creating a new record
        static::creating(function ($model) {
            $actorId = Tenant::actorId();
            if ($actorId !== null && ! $model->isDirty('created_by')) {
                $model->created_by = $actorId;
            }
            if ($actorId !== null && ! $model->isDirty('updated_by')) {
                $model->updated_by = $actorId;
            }
        });

        // Set updated_by when updating a record
        static::updating(function ($model) {
            $actorId = Tenant::actorId();
            if ($actorId !== null && ! $model->isDirty('updated_by')) {
                $model->updated_by = $actorId;
            }
        });
    }

    /**
     * Get the user who created this record.
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
