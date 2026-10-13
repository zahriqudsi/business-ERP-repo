<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Records create / update / delete of the model in the activity log.
 */
trait LogsActivity
{
    /**
     * Fields never written to the log: noise, secrets or very large values.
     */
    private static array $unloggedFields = ['updated_at', 'created_at', 'last_login_at', 'password', 'customer_signature', 'customer_id', 'supplier_id', 'stock_qty'];

    /**
     * Short type key used in the log (see ActivityLog::SUBJECTS).
     */
    abstract public function activityType(): string;

    /**
     * How the record is named in the log, e.g. "Invoice #1417".
     */
    abstract public function activityLabel(): string;

    protected static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => ActivityLog::record('created', $model->activityLabel(), $model->activityType(), $model->getKey()));

        static::updated(function (Model $model) {
            $changes = [];
            foreach (array_keys($model->getChanges()) as $field) {
                if (in_array($field, self::$unloggedFields, true)) {
                    // Note that the password changed without logging it
                    if ($field === 'password') {
                        $changes['password'] = ['•••', 'changed'];
                    }

                    continue;
                }
                $old = $model->getOriginal($field);
                $new = $model->getAttribute($field);
                $changes[$field] = [self::loggable($old), self::loggable($new)];
            }

            if ($changes !== []) {
                ActivityLog::record('updated', $model->activityLabel(), $model->activityType(), $model->getKey(), $changes);
            }
        });

        static::deleted(fn (Model $model) => ActivityLog::record('deleted', $model->activityLabel(), $model->activityType(), $model->getKey()));
    }

    private static function loggable(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
            is_string($value) && mb_strlen($value) > 120 => mb_substr($value, 0, 117).'…',
            default => $value,
        };
    }
}
