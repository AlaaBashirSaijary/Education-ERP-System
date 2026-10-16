<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Records created / updated / deleted for a model, with the signed-in user and old → new values.
 * Only changes made by a signed-in person are logged (seeders, scheduled jobs and the
 * attendance auto-close run without one and stay out of the trail).
 */
trait Auditable
{
    /** Never written to the trail. */
    private static array $auditHidden = ['password', 'remember_token', 'qr_token', 'created_at', 'updated_at'];

    /** Events to skip for noisy models, e.g. ['created']. */
    protected function auditSkips(): array
    {
        return [];
    }

    /** Short human description shown in the trail. */
    public function auditLabel(): string
    {
        return (string) ($this->name ?? $this->title ?? '#'.$this->getKey());
    }

    public static function bootAuditable(): void
    {
        static::created(fn ($m) => $m->audit('created', [], $m->getAttributes()));
        static::updated(fn ($m) => $m->audit('updated', array_intersect_key($m->getOriginal(), $m->getChanges()), $m->getChanges()));
        static::deleted(fn ($m) => $m->audit('deleted', $m->getAttributes(), []));
    }

    protected function audit(string $event, array $old, array $new): void
    {
        $user = Auth::user();
        if (! $user || in_array($event, $this->auditSkips(), true)) {
            return;
        }

        $hidden = array_merge(self::$auditHidden, $this->getHidden());
        $old = array_diff_key($old, array_flip($hidden));
        $new = array_diff_key($new, array_flip($hidden));
        if ($event === 'updated' && ! $new) {
            return;
        }

        AuditLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'event' => $event,
            'auditable_type' => class_basename($this),
            'auditable_id' => $this->getKey(),
            'label' => mb_substr($this->auditLabel(), 0, 190),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
