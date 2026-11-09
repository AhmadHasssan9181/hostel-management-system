<?php

namespace App\Http\Controllers\Web;

use App\Enums\BookingStatus;
use App\Enums\FacilityStatus;
use App\Enums\FeeStatus;
use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Facility;
use App\Models\Fee;
use App\Models\InventoryItem;
use App\Models\Room;
use App\Models\RoomSwapRequest;
use App\Models\StudentRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        return $request->user()->isStudent()
            ? $this->student($request)
            : $this->staff();
    }

    private function student(Request $request): View
    {
        $user = $request->user()->load('studentProfile', 'activeBooking.room.hostel');

        $outstanding = (float) Fee::query()
            ->where('student_id', $user->id)
            ->outstanding()
            ->sum(DB::raw('amount + fine'));

        return view('dashboard.student', [
            'user' => $user,
            'booking' => $user->activeBooking,
            'outstanding' => $outstanding,
            'nextDue' => Fee::query()
                ->where('student_id', $user->id)
                ->outstanding()
                ->orderBy('due_date')
                ->first(),
            'openComplaints' => Complaint::query()->where('student_id', $user->id)->open()->count(),
            'recentComplaints' => Complaint::query()
                ->where('student_id', $user->id)
                ->latest()
                ->limit(4)
                ->get(),
            'recentFees' => Fee::query()
                ->where('student_id', $user->id)
                ->latest()
                ->limit(4)
                ->get(),
            'notifications' => AppNotification::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(4)
                ->get(),
            'unread' => AppNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count(),
            'facilities' => Facility::query()->visible()->orderBy('name')->get(),
            'availableRooms' => Room::query()->withAvailability()->count(),
            'hasPendingSwap' => RoomSwapRequest::query()
                ->where('student_id', $user->id)
                ->where('status', RequestStatus::Pending->value)
                ->exists(),
        ]);
    }

    private function staff(): View
    {
        $capacity = (int) Room::sum('capacity');
        $occupied = (int) Room::sum('occupied_beds');

        $monthly = collect(range(5, 0))->map(function (int $offset) {
            $month = now()->startOfMonth()->subMonths($offset);

            return [
                'label' => $month->format('M'),
                'total' => (float) Fee::query()
                    ->where('status', FeeStatus::Paid->value)
                    ->whereYear('paid_at', $month->year)
                    ->whereMonth('paid_at', $month->month)
                    ->sum(DB::raw('amount + fine')),
            ];
        });

        $categories = Complaint::query()
            ->open()
            ->select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return view('dashboard.staff', [
            'studentsTotal' => User::query()->where('role', Role::Student->value)->count(),
            'checkedIn' => User::query()
                ->where('role', Role::Student->value)
                ->whereHas('studentProfile', fn ($q) => $q->where('check_in_status', 'checked_in'))
                ->count(),
            'roomsTotal' => Room::count(),
            'bedsTotal' => $capacity,
            'bedsOccupied' => $occupied,
            'occupancyRate' => $capacity > 0 ? round($occupied / $capacity * 100) : 0,
            'roomsAvailable' => Room::query()
                ->where('status', RoomStatus::Available->value)
                ->whereColumn('occupied_beds', '<', 'capacity')
                ->count(),
            'pendingBookings' => Booking::query()->where('status', BookingStatus::Pending->value)->count(),
            'openComplaints' => Complaint::query()->open()->count(),
            'urgentComplaints' => Complaint::query()->open()->whereIn('priority', ['high', 'urgent'])->count(),
            'unassignedComplaints' => Complaint::query()->open()->whereNull('assigned_to')->count(),
            'collected' => (float) Fee::query()
                ->where('status', FeeStatus::Paid->value)
                ->whereYear('paid_at', now()->year)
                ->whereMonth('paid_at', now()->month)
                ->sum(DB::raw('amount + fine')),
            'outstanding' => (float) Fee::query()->outstanding()->sum(DB::raw('amount + fine')),
            'pendingVerification' => Fee::query()->where('status', FeeStatus::PendingVerification->value)->count(),
            'pendingSwaps' => RoomSwapRequest::query()->where('status', RequestStatus::Pending->value)->count(),
            'pendingRequests' => StudentRequest::query()->where('status', RequestStatus::Pending->value)->count(),
            'lowStock' => InventoryItem::query()->whereColumn('quantity', '<=', 'threshold')->count(),
            'monthly' => $monthly,
            'monthlyMax' => max(1, (float) $monthly->max('total')),
            'categories' => $categories,
            'categoryMax' => max(1, (int) $categories->max('total')),
            'recentComplaints' => Complaint::query()
                ->with('student:id,name', 'room:id,room_number')
                ->open()
                ->latest()
                ->limit(5)
                ->get(),
            'pendingBookingList' => Booking::query()
                ->with('student:id,name', 'room.hostel')
                ->pending()
                ->latest()
                ->limit(4)
                ->get(),
            'lowStockItems' => InventoryItem::query()
                ->with('hostel:id,name')
                ->whereColumn('quantity', '<=', 'threshold')
                ->orderBy('quantity')
                ->limit(4)
                ->get(),
            'facilityIssues' => Facility::query()
                ->whereIn('status', [
                    FacilityStatus::Maintenance->value,
                    FacilityStatus::Emergency->value,
                    FacilityStatus::Closed->value,
                ])
                ->get(),
            'recentStudents' => User::query()
                ->where('role', Role::Student->value)
                ->with('studentProfile')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
