<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HostelController extends Controller
{
    public function index(): View
    {
        $hostels = Hostel::query()
            ->withCount('rooms')
            ->withSum('rooms as capacity_sum', 'capacity')
            ->withSum('rooms as occupied_sum', 'occupied_beds')
            ->orderBy('name')
            ->get();

        return view('hostels.index', [
            'hostels' => $hostels,
            'stats' => [
                'hostels' => $hostels->count(),
                'rooms' => Room::count(),
                'beds' => (int) Room::sum('capacity'),
                'occupied' => (int) Room::sum('occupied_beds'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:hostels,name'],
            'type' => ['required', Rule::in(['boys', 'girls'])],
            'location' => ['nullable', 'string', 'max:190'],
            'total_rooms' => ['sometimes', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $image = $request->file('image');
        unset($data['image']);

        if ($image) {
            $data['image_path'] = $image->store('hostels', 'public');
        }

        Hostel::create($data);

        return back()->with('success', 'Hostel block created.');
    }

    public function update(Request $request, Hostel $hostel): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120', Rule::unique('hostels', 'name')->ignore($hostel->id)],
            'type' => ['sometimes', Rule::in(['boys', 'girls'])],
            'location' => ['sometimes', 'nullable', 'string', 'max:190'],
            'total_rooms' => ['sometimes', 'integer', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $image = $request->file('image');
        unset($data['image']);

        if ($image) {
            if ($hostel->image_path && ! str_starts_with($hostel->image_path, 'images/')) {
                Storage::disk('public')->delete($hostel->image_path);
            }

            $data['image_path'] = $image->store('hostels', 'public');
        }

        $hostel->update($data);

        return back()->with('success', 'Hostel block updated.');
    }

    public function destroy(Hostel $hostel): RedirectResponse
    {
        if ($hostel->rooms()->where('occupied_beds', '>', 0)->exists()) {
            return back()->with('error', 'This block still has residents and cannot be deleted.');
        }

        $hostel->delete();

        return back()->with('success', 'Hostel block deleted.');
    }
}
