<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $request->user()->isStudent()
            ? $this->studentDashboard($request->user())
            : $this->staffDashboard();
    }

    private function studentDashboard(User $student): JsonResponse
    {
        $booking = $student->activeBooking()->with('room.hostel')->first();

        $outstanding = Fee::query()
            ->where('student_id', $student->id)
            ->outstanding()
            ->selectRaw('COALESCE(SUM(amount + fine), 0) as total')
            ->value('total');

        return response()->json([
            'role' => Role::Student->value,
            'active_booking' => $booking,
            'fees' => [
                'outstanding' => (float) $outstanding,
                'next_due' => Fee::query()
                    ->where('student_id', $student->id)
                    ->outstanding()
                    ->orderBy('due_date')
                    ->first(),
            ],
            'complaints' => [
                'open' => Complaint::query()
                    ->where('student_id', $student->id)
                    ->open()
                    ->count(),
            ],
            'pending_room_swap' => RoomSwapRequest::query()
                ->where('student_id', $student->id)
                ->where('status', RequestStatus::Pending->value)
                ->exists(),
            'unread_notifications' => AppNotification::query()
                ->where('user_id', $student->id)
                ->whereNull('read_at')
                ->count(),
            'facilities' => Facility::query()
                ->visible()
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'status', 'opens_at', 'closes_at']),
        ]);
    }

    private function staffDashboard(): JsonResponse
    {
        $capacity = (int) Room::sum('capacity');
        $occupied = (int) Room::sum('occupied_beds');

        $collectedThisMonth = (float) Fee::query()
            ->where('status', FeeStatus::Paid->value)
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum(DB::raw('amount + fine'));

        $outstanding = (float) Fee::query()
            ->outstanding()
            ->sum(DB::raw('amount + fine'));

        return response()->json([
            'role' => 'staff',
            'students' => [
                'total' => User::query()->where('role', Role::Student->value)->count(),
                'checked_in' => User::query()
                    ->where('role', Role::Student->value)
                    ->whereHas('studentProfile', fn ($q) => $q->where('check_in_status', 'checked_in'))
                    ->count(),
            ],
            'occupancy' => [
                'rooms' => Room::count(),
                'beds' => $capacity,
                'occupied' => $occupied,
                'free' => max(0, $capacity - $occupied),
                'rate' => $capacity > 0 ? round($occupied / $capacity * 100, 1) : 0,
                'rooms_available' => Room::query()
                    ->where('status', RoomStatus::Available->value)
                    ->whereColumn('occupied_beds', '<', 'capacity')
                    ->count(),
            ],
            'bookings' => [
                'pending' => Booking::query()->where('status', BookingStatus::Pending->value)->count(),
                'approved' => Booking::query()->where('status', BookingStatus::Approved->value)->count(),
            ],
            'complaints' => [
                'open' => Complaint::query()->open()->count(),
                'urgent' => Complaint::query()->open()
                    ->whereIn('priority', ['high', 'urgent'])
                    ->count(),
                'unassigned' => Complaint::query()->open()->whereNull('assigned_to')->count(),
            ],
            'fees' => [
                'collected_this_month' => $collectedThisMonth,
                'outstanding' => $outstanding,
                'pending_verification' => Fee::query()->where('status', FeeStatus::PendingVerification->value)->count(),
                'overdue' => Fee::query()->where('status', FeeStatus::Overdue->value)->count(),
            ],
            'requests' => [
                'room_swaps_pending' => RoomSwapRequest::query()->where('status', RequestStatus::Pending->value)->count(),
                'student_requests_pending' => StudentRequest::query()->where('status', RequestStatus::Pending->value)->count(),
            ],
            'inventory' => [
                'low_stock' => InventoryItem::query()->whereColumn('quantity', '<=', 'threshold')->count(),
                'broken' => InventoryItem::query()->where('condition', 'broken')->count(),
            ],
            'recent_complaints' => Complaint::query()
                ->with('student:id,name', 'room:id,room_number')
                ->open()
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
