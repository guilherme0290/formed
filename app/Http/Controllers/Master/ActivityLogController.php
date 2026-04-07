<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $activityModelClass = config('activitylog.activity_model', Activity::class);
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $activityModelClass::query()
            ->with('causer')
            ->orderByDesc('id');

        if ($request->filled('id')) {
            $query->whereKey((int) $request->integer('id'));
        }

        if ($request->filled('user_id')) {
            $query->where('causer_type', User::class)
                ->where('causer_id', (int) $request->integer('user_id'));
        }

        if ($request->filled('event')) {
            $query->where('event', (string) $request->string('event'));
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', (string) $request->string('subject_type'));
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', (string) $request->string('log_name'));
        }

        if ($request->filled('date_start')) {
            $start = $this->parseDateTime((string) $request->string('date_start'));
            if ($start) {
                $query->where('created_at', '>=', $start);
            }
        }

        if ($request->filled('date_end')) {
            $end = $this->parseDateTime((string) $request->string('date_end'));
            if ($end) {
                $query->where('created_at', '<=', $end);
            }
        }

        $activities = $query->paginate(30)->withQueryString();

        $users = User::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $subjectTypes = $activityModelClass::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        $logNames = $activityModelClass::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        return view('master.activity-log.index', [
            'activities' => $activities,
            'users' => $users,
            'subjectTypes' => $subjectTypes,
            'logNames' => $logNames,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $activityModelClass = config('activitylog.activity_model', Activity::class);
        /** @var \Spatie\Activitylog\Models\Activity $activity */
        $activity = $activityModelClass::query()
            ->with('causer')
            ->findOrFail($id);

        $properties = $this->normalizeProperties($activity->properties);
        $old = $properties['old'] ?? [];
        $attributes = $properties['attributes'] ?? [];

        return response()->json([
            'id' => $activity->id,
            'event' => $activity->event,
            'description' => $activity->description,
            'log_name' => $activity->log_name,
            'subject_type' => $activity->subject_type,
            'subject_label' => $this->subjectLabel($activity->subject_type),
            'subject_id' => $activity->subject_id,
            'causer_name' => optional($activity->causer)->name ?? 'Sistema',
            'created_at' => optional($activity->created_at)?->format('d/m/Y H:i:s'),
            'old' => $old,
            'attributes' => $attributes,
            'properties' => $properties,
        ]);
    }

    private function parseDateTime(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function subjectLabel(?string $subjectType): string
    {
        if (!$subjectType) {
            return '-';
        }

        return (string) Str::of($subjectType)->afterLast('\\');
    }

    private function normalizeProperties(mixed $properties): array
    {
        if ($properties instanceof Arrayable) {
            return $properties->toArray();
        }

        if (is_string($properties)) {
            $decoded = json_decode($properties, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($properties) ? $properties : [];
    }
}
