<?php

namespace App\Http\Controllers\Admin\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PhoneBlacklist;
use App\Rules\BangladeshiMobile;
use App\Support\BdPhone;
use Illuminate\Http\Request;

class PhoneBlacklistController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:30']);
        $digits = preg_replace('/\D+/', '', (string) ($filters['q'] ?? ''));

        $entries = PhoneBlacklist::with('admin:id,name')
            ->when($digits !== '', fn ($q) => $q->where('phone', 'like', '%' . (BdPhone::normalize($digits) ?? $digits) . '%'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $orderCounts = Order::whereIn('phone_normalized', $entries->pluck('phone'))
            ->selectRaw('phone_normalized, COUNT(*) as total')
            ->groupBy('phone_normalized')
            ->pluck('total', 'phone_normalized');

        return view('admin.fraud.blacklist', compact('entries', 'filters', 'orderCounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20', new BangladeshiMobile()],
            'reason' => 'required|string|max:255',
        ]);

        $phone = BdPhone::normalize($data['phone']);
        $entry = PhoneBlacklist::updateOrCreate(['phone' => $phone], ['reason' => $data['reason'], 'admin_id' => auth('admin')->id()]);

        activity('fraud')->performedOn($entry)->causedBy(auth('admin')->user())
            ->withProperties(['phone' => BdPhone::mask($phone), 'reason' => $data['reason']])
            ->log('Added a number to the phone blacklist');

        return back()->with('success', 'Number ' . BdPhone::mask($phone) . ' blacklisted.');
    }

    public function destroy(PhoneBlacklist $blacklist)
    {
        $blacklist->delete();

        activity('fraud')->causedBy(auth('admin')->user())
            ->withProperties(['phone' => BdPhone::mask($blacklist->phone)])
            ->log('Removed a number from the phone blacklist');

        return back()->with('success', 'Number ' . BdPhone::mask($blacklist->phone) . ' removed from the blacklist.');
    }
}
