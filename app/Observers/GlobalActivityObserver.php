<?php

namespace App\Observers;

use App\Models\LogActivity;
use Illuminate\Support\Facades\Auth;

class GlobalActivityObserver
{
    private array $ignoreFields = [
        'password',
        'remember_token',
        'api_token'
    ];

    private array $moduleAliases = [
        'TermBilling' => 'Tagihan Termin',
    ];

    public function created($model)
    {
        if ($this->shouldLog($model)) {
            $this->logActivity(
                $model,
                'create',
                $this->filterFields($model->getAttributes())
            );
        }
    }

    public function updated($model)
    {
        if ($this->shouldLog($model)) {
            $changes = $this->formatChanges($model);
            if (!empty($changes)) {
                $this->logActivity($model, 'update', $changes);
            }
        }
    }

    public function deleted($model)
    {
        if ($this->shouldLog($model)) {
            $this->logActivity(
                $model,
                'delete',
                $this->filterFields($model->getOriginal())
            );
        }
    }

    private function logActivity($model, $action, $changes)
    {
        LogActivity::create([
            'user_id'    => Auth::id(),
            'module'     => $this->getModuleAlias(class_basename($model)),
            'action'     => $action,
            'changes'    => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    private function shouldLog($model)
    {
        return !($model instanceof LogActivity);
    }

    private function formatChanges($model)
    {
        $changes = [];
        $dirty   = $model->getChanges();
        unset($dirty['updated_at']);

        foreach ($dirty as $field => $newValue) {
            if (in_array($field, $this->ignoreFields)) {
                continue;
            }

            $oldValue = $model->getOriginal($field);
            $changes[$field] = [
                'old' => $oldValue,
                'new' => $newValue
            ];
        }

        return $changes;
    }

    private function filterFields($attributes)
    {
        return collect($attributes)
            ->except($this->ignoreFields)
            ->toArray();
    }

    private function getModuleAlias($modelName)
    {
        return $this->moduleAliases[$modelName] ?? $modelName;
    }
}

