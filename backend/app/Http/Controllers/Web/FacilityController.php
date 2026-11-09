<?php

namespace App\Http\Controllers\Web;

use App\Enums\FacilityStatus;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Services\FacilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FacilityController extends Controller
{
    public function __construct(private readonly FacilityService $facilities) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Facility::query();

        if ($user->isStudent()) {
            $query->visible();
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('facilities.index', [
            'facilities' => $query->orderBy('name')->get(),
            'isStaff' => $user->isStaff(),
            'stats' => $user->isStaff()
                ? [
                    'total' => Facility::count(),
                    'operational' => Facility::where('status', FacilityStatus::Operational->value)->count(),
                    'attention' => Facility::whereIn('status', [
                        FacilityStatus::Maintenance->value,
                        FacilityStatus::Emergency->value,
                        FacilityStatus::Closed->value,
                    ])->count(),
                    'hidden' => Facility::where('is_visible', false)->count(),
                ]
                : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:facilities,name'],
            'type' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:190'],
            'opens_at' => ['nullable', 'date_format:H:i'],
            'closes_at' => ['nullable', 'date_format:H:i'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::enum(FacilityStatus::class)],
            'is_visible' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $image = $request->file('image');
        unset($data['image']);

        if ($image) {
            $data['image_path'] = $image->store('facilities', 'public');
        }

        $data['is_visible'] = $request->boolean('is_visible', true);
        Facility::create($data);

        return back()->with('success', 'Facility created.');
    }

    public function update(Request $request, Facility $facility): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120', Rule::unique('facilities', 'name')->ignore($facility->id)],
            'type' => ['sometimes', 'string', 'max:30'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'location' => ['sometimes', 'nullable', 'string', 'max:190'],
            'opens_at' => ['sometimes', 'nullable', 'date_format:H:i'],
            'closes_at' => ['sometimes', 'nullable', 'date_format:H:i'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::enum(FacilityStatus::class)],
            'is_visible' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $image = $request->file('image');
        unset($data['image']);

        if ($image) {
            if ($facility->image_path && ! str_starts_with($facility->image_path, 'images/')) {
                Storage::disk('public')->delete($facility->image_path);
            }

            $data['image_path'] = $image->store('facilities', 'public');
        }

        if ($request->has('is_visible')) {
            $data['is_visible'] = $request->boolean('is_visible');
        }

        $status = isset($data['status']) ? FacilityStatus::from($data['status']) : null;
        unset($data['status']);

        $facility->update($data);

        if ($status !== null) {
            $this->facilities->setStatus($facility, $status);
        }

        return back()->with('success', 'Facility updated.');
    }

    public function setStatus(Request $request, Facility $facility): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(FacilityStatus::class)]]);

        $this->facilities->setStatus($facility, FacilityStatus::from($data['status']));

        return back()->with('success', "{$facility->name} is now {$data['status']}.");
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        $facility->delete();

        return back()->with('success', 'Facility deleted.');
    }
}
