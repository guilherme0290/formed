<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ModelActivityObserver
{
    private array $hiddenAttributes = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function created(Model $model): void
    {
        if (!$this->shouldLog($model)) {
            return;
        }

        activity($this->logName($model))
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->event('created')
            ->withProperties([
                'attributes' => $this->sanitizeAttributes($model->getAttributes()),
                'meta' => $this->meta(),
            ])
            ->log('created');
    }

    public function updated(Model $model): void
    {
        if (!$this->shouldLog($model)) {
            return;
        }

        $changes = $this->sanitizeAttributes($model->getChanges());
        if (empty($changes)) {
            return;
        }

        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = $model->getOriginal($key);
        }

        activity($this->logName($model))
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->event('updated')
            ->withProperties([
                'old' => $this->sanitizeAttributes($old),
                'attributes' => $changes,
                'meta' => $this->meta(),
            ])
            ->log('updated');
    }

    public function deleted(Model $model): void
    {
        if (!$this->shouldLog($model)) {
            return;
        }

        activity($this->logName($model))
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->event('deleted')
            ->withProperties([
                'old' => $this->sanitizeAttributes($model->getOriginal()),
                'meta' => $this->meta(),
            ])
            ->log('deleted');
    }

    public function restored(Model $model): void
    {
        if (!$this->shouldLog($model)) {
            return;
        }

        activity($this->logName($model))
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->event('restored')
            ->withProperties([
                'attributes' => $this->sanitizeAttributes($model->getAttributes()),
                'meta' => $this->meta(),
            ])
            ->log('restored');
    }

    private function shouldLog(Model $model): bool
    {
        if ($model instanceof Activity) {
            return false;
        }

        if ($model->getTable() === config('activitylog.table_name')) {
            return false;
        }

        return true;
    }

    private function sanitizeAttributes(array $attributes): array
    {
        foreach ($this->hiddenAttributes as $key) {
            Arr::forget($attributes, $key);
        }

        return $attributes;
    }

    private function logName(Model $model): string
    {
        return Str::snake(class_basename($model));
    }

    private function meta(): array
    {
        if (!request()) {
            return [];
        }

        return [
            'ip' => request()->ip(),
            'method' => request()->method(),
            'url' => request()->fullUrl(),
            'user_agent' => (string) request()->userAgent(),
        ];
    }
}
