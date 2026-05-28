<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

trait HasAuditLogs
{
    public static function bootHasAuditLogs()
    {
        static::created(function ($model) {
            $model->recordAuditLog('created');
        });

        static::updated(function ($model) {
            $model->recordAuditLog('updated');
        });

        static::deleted(function ($model) {
            $model->recordAuditLog('deleted');
        });
    }

    protected function recordAuditLog(string $event)
    {
        $oldValues = null;
        $newValues = null;

        if ($event === 'updated') {
            $oldValues = array_intersect_key($this->getOriginal(), $this->getDirty());
            $newValues = $this->getDirty();
            
            // Remove sensitive or irrelevant fields
            $exclude = ['password', 'remember_token', 'updated_at', 'created_at', 'email_verified_at'];
            foreach ($exclude as $field) {
                unset($oldValues[$field], $newValues[$field]);
            }

            if (empty($newValues)) return;
        } elseif ($event === 'created') {
            $newValues = $this->getAttributes();
            unset($newValues['updated_at'], $newValues['created_at'], $newValues['password']);
        } elseif ($event === 'deleted') {
            $oldValues = $this->getAttributes();
            unset($oldValues['password'], $oldValues['remember_token']);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
