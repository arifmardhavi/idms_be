<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    protected static function boot()
    {
        parent::boot();

        $ignoreFields = ['created_at', 'updated_at','id','status'];

        // Create dengan snapshot after
        static::created(function ($model) use ($ignoreFields) {
            $afterData = [];
            foreach ($model->getAttributes() as $field => $value) {
                if (in_array($field, $ignoreFields)) {
                    continue;
                }
                $afterData[$field] = [
                    'before' => null,
                    'after'  => $value
                ];
            }

            self::logActivity($model, 'create', $afterData);
        });

        // Update dengan before-after
        static::updating(function ($model) use ($ignoreFields) {
            $original = $model->getOriginal();
            $changes = $model->getDirty();

            $beforeAfter = [];
            foreach ($changes as $field => $newValue) {
                if (in_array($field, $ignoreFields)) {
                    continue;
                }
                $beforeAfter[$field] = [
                    'before' => $original[$field] ?? null,
                    'after'  => $newValue
                ];
            }

            if (!empty($beforeAfter)) {
                self::logActivity($model, 'update', $beforeAfter);
            }
        });

        // Delete
        static::deleted(function ($model) {
            // Field yang mau diabaikan supaya gak ke-log
            $ignoreSensitive = ['password', 'remember_token', 'secret_key', 'id', 'status', 'created_at', 'updated_at'];

            $original = $model->getOriginal();
            $beforeData = [];

            foreach ($original as $field => $value) {
                if (in_array($field, $ignoreSensitive)) {
                    continue; // skip field sensitif
                }
                $beforeData[$field] = [
                    'before' => $value,
                    'after'  => null
                ];
            }

            self::logActivity($model, 'delete', $beforeData);
        });

    }

    protected static function logActivity($model, $action, $changes = null)
    {
        // Hindari infinite loop
        if ($model instanceof LogActivity) {
            return;
        }

        // // Skip untuk user tertentu misalnya ID 6
        // if (auth()->check() && auth()->id() === 6) {
        //     return;
        // }

        try {
            LogActivity::create([
                'user_id'    => auth()->check() ? auth()->id() : null,
                'module'     => class_basename($model),
                'action'     => $action,
                'changes'    => $changes,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Throwable $th) {
            logger()->error('Failed to log activity: ' . $th->getMessage());
        }
    }
}
