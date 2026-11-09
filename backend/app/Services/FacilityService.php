<?php

namespace App\Services;

use App\Enums\FacilityStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\AppNotification;
use App\Models\Facility;
use App\Models\User;

class FacilityService
{
    /**
     * Update a facility status and broadcast emergency closures to every resident.
     */
    public function setStatus(Facility $facility, FacilityStatus $status): Facility
    {
        $facility->update(['status' => $status]);

        if ($status === FacilityStatus::Emergency) {
            $students = User::query()
                ->where('role', Role::Student->value)
                ->where('status', UserStatus::Active->value)
                ->get();

            foreach ($students as $student) {
                AppNotification::send(
                    $student,
                    'facility',
                    "Facility alert: {$facility->name}",
                    'This facility is temporarily closed due to an emergency.',
                    ['facility_id' => $facility->id],
                );
            }
        }

        return $facility;
    }
}
