<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriberController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $subscribers = $this->query($filters)->latest()->paginate(30)->withQueryString();
        $counts = [
            'total'    => Subscriber::count(),
            'active'   => Subscriber::where('status', true)->count(),
            'last30'   => Subscriber::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return view('admin.subscribers.index', compact('subscribers', 'filters', 'counts'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->query($this->filters($request))->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Email', 'Status', 'Subscribed At']);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $subscriber) {
                    fputcsv($out, [$subscriber->email, $subscriber->status ? 'active' : 'unsubscribed', $subscriber->created_at?->format('Y-m-d H:i')]);
                }
            });

            fclose($out);
        }, 'subscribers_' . now()->format('Y_m_d_H_i') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function toggle(Subscriber $subscriber)
    {
        $subscriber->update(['status' => !$subscriber->status]);

        return back()->with('success', $subscriber->email . ($subscriber->status ? ' re-subscribed.' : ' unsubscribed.'));
    }

    public function destroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return back()->with('success', 'Subscriber removed.');
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'search' => 'nullable|string|max:150',
            'status' => 'nullable|in:active,unsubscribed',
        ]);
    }

    private function query(array $filters): Builder
    {
        return Subscriber::query()
            ->when($filters['search'] ?? null, fn (Builder $q, $s) => $q->where('email', 'like', "%{$s}%"))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $q) => $q->where('status', true))
            ->when(($filters['status'] ?? null) === 'unsubscribed', fn (Builder $q) => $q->where('status', false));
    }
}
