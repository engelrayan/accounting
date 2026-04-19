<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Record an activity.
     *
     * @param int    $companyId
     * @param string $action      e.g. 'created', 'posted', 'deleted'
     * @param string $entityType  e.g. 'journal_entry', 'transaction'
     * @param int    $entityId
     * @param string $entityLabel Human-readable ID / name, e.g. "JE-2024-00001"
     * @param string $description Optional free-text description
     */
    public function log(
        int    $companyId,
        string $action,
        string $entityType,
        int    $entityId,
        string $entityLabel = '',
        string $description = ''
    ): void {
        ActivityLog::create([
            'company_id'   => $companyId,
            'user_id'      => Auth::id(),
            'action'       => $action,
            'entity_type'  => $entityType,
            'entity_id'    => $entityId,
            'entity_label' => $entityLabel,
            'description'  => $description,
        ]);
    }
}
