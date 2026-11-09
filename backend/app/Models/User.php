<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'status' => UserStatus::class,
        ];
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'student_id');
    }

    public function activeBooking(): HasOne
    {
        return $this->hasOne(Booking::class, 'student_id')
            ->where('status', BookingStatus::Approved->value)
            ->latestOfMany();
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'student_id');
    }

    public function assignedComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'assigned_to');
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class, 'student_id');
    }

    public function roomSwapRequests(): HasMany
    {
        return $this->hasMany(RoomSwapRequest::class, 'student_id');
    }

    public function studentRequests(): HasMany
    {
        return $this->hasMany(StudentRequest::class, 'student_id');
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function isStudent(): bool
    {
        return $this->role === Role::Student;
    }

    public function isEmployee(): bool
    {
        return $this->role === Role::Employee;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function hasRole(Role|string ...$roles): bool
    {
        $values = array_map(fn ($role) => $role instanceof Role ? $role->value : $role, $roles);

        return in_array($this->role->value, $values, true);
    }
}
