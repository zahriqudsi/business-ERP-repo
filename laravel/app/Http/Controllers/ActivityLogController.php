<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'action' => ['nullable', Rule::in(array_keys(ActivityLog::ACTIONS))],
            'type' => ['nullable', Rule::in(array_keys(ActivityLog::SUBJECTS))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $entries = ActivityLog::with('user:id,username,name')
            ->when($filters['user_id'] ?? null, fn (Builder $q, $id) => $q->where('user_id', $id))
            ->when($filters['action'] ?? null, fn (Builder $q, $action) => $q->where('action', $action))
            ->when($filters['type'] ?? null, fn (Builder $q, $type) => $q->where('subject_type', $type))
            ->when($filters['from'] ?? null, fn (Builder $q, $from) => $q->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to) => $q->where('created_at', '<', now()->parse($to)->addDay()))
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->where('description', 'like', "%{$search}%"))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('activity.index', [
            'entries' => $entries,
            'filters' => $filters,
            'users' => User::orderBy('username')->get(['id', 'username', 'name']),
        ]);
    }
}
