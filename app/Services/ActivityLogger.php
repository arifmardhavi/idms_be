<?php

namespace App\Services;

use App\Models\LogActivity;
use App\Support\ActivityModule;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    protected array $nameCache = [];

    public const ACTIONS = [
        'login',
        'logout',
        'visit',
        'view',
        'download',
        'export',
        'create',
        'update',
        'delete',
        'import',
    ];

    /**
     * Mapping action => kata kerja untuk membangun description ("menambah data", dst).
     */
    protected array $verbMap = [
        'create'   => 'menambah data',
        'update'   => 'mengubah data',
        'delete'   => 'menghapus data',
        'import'   => 'import',
        'export'   => 'export data',
        'visit'    => 'mengunjungi fitur',
        'view'     => 'melihat file',
        'download' => 'mengunduh file',
        'login'    => 'login',
        'logout'   => 'logout',
    ];

    /**
     * @param string $action salah satu dari ActivityLogger::ACTIONS
     * @param string $module nama fitur/modul (mis. 'Coi', 'Auth')
     * @param array  $opts   opsional keys:
     *                       - description : string|null (kalimat lengkap; jika kosong, digenerate)
     *                       - recordId    : int|string|null
     *                       - recordLabel : string|null (mis. 'COI-2025-0012')
     *                       - metadata    : array|null (detail mentah)
     *                       - object      : string|null (object description, mis. nama file / jumlah baris)
     *                       - userId      : int|null (default Auth::id())
     *                       - allowGuest  : bool (default true) apakah log tanpa user diperbolehkan
     */
    public function log(string $action, string $module, array $opts = []): ?LogActivity
    {
        if (!in_array($action, self::ACTIONS, true)) {
            return null;
        }

        $userId = $opts['userId'] ?? Auth::id();
        $module = ActivityModule::label($module);

        $data = [
            'user_id'    => $userId,
            'action'     => $action,
            'module'     => $module,
            'record_id'  => $opts['recordId'] ?? null,
            'record_label' => $opts['recordLabel'] ?? null,
            'description'  => $opts['description'] ?? $this->buildDescription($action, $module, $userId, $opts),
            'metadata'     => $opts['metadata'] ?? null,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
        ];

        return LogActivity::create($data);
    }

    protected function buildDescription(string $action, string $module, $userId, array $opts): ?string
    {
        $name = $this->userName($userId);
        $verb = $this->verbMap[$action] ?? $action;
        $object = $opts['object'] ?? null;
        $label = ActivityModule::label($module);

        if (in_array($action, ['create', 'update', 'delete'], true)) {
            $record = $opts['recordLabel'] ?? ('#' . ($opts['recordId'] ?? ''));
            return trim("{$name} {$verb} {$record} di fitur {$label}");
        }

        if ($action === 'import') {
            return trim("{$name} {$verb} " . ($object ? "{$object} " : '') . "di fitur {$label}");
        }

        if ($action === 'export') {
            return trim("{$name} {$verb} di fitur {$label}");
        }

        if ($action === 'visit') {
            return trim("{$name} {$verb} {$label}");
        }

        if (in_array($action, ['view', 'download'], true)) {
            return trim("{$name} {$verb} " . ($object ? "{$object} " : '') . "di fitur {$label}");
        }

        return trim("{$name} {$verb}");
    }

    protected function userName($userId): string
    {
        if (!$userId) {
            return 'System';
        }

        if (isset($this->nameCache[$userId])) {
            return $this->nameCache[$userId];
        }

        $name = \App\Models\User::find($userId)?->fullname ?? 'User#' . $userId;
        $this->nameCache[$userId] = $name;

        return $name;
    }
}
