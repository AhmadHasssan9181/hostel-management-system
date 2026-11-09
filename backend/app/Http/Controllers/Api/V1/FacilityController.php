<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FacilityStatus;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Services\FacilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacilityController extends Controller
{
    public function __construct(private readonly FacilityService $facilities) {}

    public function index(Request $request): JsonResponse
    {
        $query = Facility::query();

        if ($request->user()->isStudent()) {
            $query->visible();
        } elseif ($request->filled('visible')) {
            $query->where('is_visible', $request->boolean('visible'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json(['facilities' => $query->orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:facilities,name'],
            'type' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:190'],
            'opens_at' => ['nullable', 'date_format:H:i'],
            'closes_at' => ['nullable', 'date_format:H:i', 'after:opens_at'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::enum(FacilityStatus::class)],
            'is_visible' => ['sometimes', 'boolean'],
        ]);

        $facility = Facility::create($data);

        return response()->json(['message' => 'Facility created.', 'facility' => $facility], 201);
    }

    public function update(Request $request, Facility $facility): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120', Rule::unique('facilities', 'name')->ignore($facility->id)],
            'type' => ['sometimes', 'string', 'max:30'],
            'description' => ['sometimes', 'nullable', 'string'],
            'location' => ['sometimes', 'nullable', 'string', 'max:190'],
            'opens_at' => ['sometimes', 'nullable', 'date_format:H:i'],
            'closes_at' => ['sometimes', 'nullable', 'date_format:H:i'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::enum(FacilityStatus::class)],
            'is_visible' => ['sometimes', 'boolean'],
        ]);

        $facility->update($data);

        if (($data['status'] ?? null) === FacilityStatus::Emergency->value) {
            $this->facilities->setStatus($facility, FacilityStatus::Emergency);
        }

        return response()->json(['message' => 'Facility updated.', 'facility' => $facility->fresh()]);
    }

    public function setStatus(Request $request, Facility $facility): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(FacilityStatus::class)],
        ]);

        $this->facilities->setStatus($facility, FacilityStatus::from($data['status']));

        return response()->json(['message' => 'Facility status updated.', 'facility' => $facility->fresh()]);
    }

    public function destroy(Request $request, Facility $facility): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Only administrators can delete facilities.');

        $facility->delete();

        return response()->json(['message' => 'Facility deleted.']);
    }
}
