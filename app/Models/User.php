<?php

namespace App\Models;

use App\Domain\Auth\Enums\OnboardingStatus;
use App\Domain\Auth\Enums\OrganizationRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_organization_id',
        'is_platform_admin',
        'disabled_at',
        'onboarding_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'bool',
            'disabled_at' => 'datetime',
            'onboarding_status' => OnboardingStatus::class,
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function currentOrganizationRelation(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'current_organization_id');
    }

    public function currentOrganization(): ?Organization
    {
        $organization = $this->currentOrganizationRelation()
            ->whereNull('disabled_at')
            ->first();

        if ($organization !== null && $this->belongsToOrganizationId($organization->id)) {
            return $organization;
        }

        return $this->organizations()
            ->whereNull('organizations.disabled_at')
            ->first();
    }

    public function belongsToOrganizationId(int $organizationId): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        return $this->organizations()
            ->whereKey($organizationId)
            ->whereNull('organizations.disabled_at')
            ->exists();
    }

    public function isPlatformAdmin(): bool
    {
        return (bool) $this->is_platform_admin;
    }

    public function organizationRole(int $organizationId): ?OrganizationRole
    {
        if ($this->isPlatformAdmin()) {
            return OrganizationRole::OrganizationAdmin;
        }

        $role = $this->organizations()
            ->whereKey($organizationId)
            ->whereNull('organizations.disabled_at')
            ->value('organization_user.role');

        if (! is_string($role) || $role === '') {
            return null;
        }

        return OrganizationRole::tryFrom($role);
    }

    public function hasOrganizationRole(int $organizationId, OrganizationRole ...$roles): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        $currentRole = $this->organizationRole($organizationId);

        if ($currentRole === null) {
            return false;
        }

        return in_array($currentRole, $roles, true);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }
}
