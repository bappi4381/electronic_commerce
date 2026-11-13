<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public const LOGS = [
        'products' => 'Products',
        'orders'   => 'Orders',
        'stock'    => 'Stock',
        'settings' => 'Settings',
        'roles'    => 'Roles & Access',
        'marketing'=> 'Marketing',
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'log'       => 'nullable|in:' . implode(',', array_keys(self::LOGS)),
            'admin_id'  => 'nullable|integer',
            'event'     => 'nullable|string|max:50',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
        ]);

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($filters['log'] ?? null, fn ($q, $log) => $q->where('log_name', $log))
            ->when($filters['event'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->when($filters['admin_id'] ?? null, fn ($q, $id) => $q->where('causer_type', Admin::class)->where('causer_id', $id))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        return view('admin.activity.index', [
            'activities' => $activities,
            'filters'    => $filters,
            'logs'       => self::LOGS,
            'admins'     => Admin::orderBy('name')->get(['id', 'name']),
            'events'     => Activity::query()->whereNotNull('event')->distinct()->orderBy('event')->pluck('event'),
        ]);
    }
}
