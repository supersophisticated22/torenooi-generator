<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function sports(): HasMany
    {
        return $this->hasMany(Sport::class);
    }

    public function sportRules(): HasMany
    {
        return $this->hasMany(SportRule::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class);
    }

    public function referees(): HasMany
    {
        return $this->hasMany(Referee::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function tournamentEntries(): HasMany
    {
        return $this->hasMany(TournamentEntry::class);
    }

    public function pools(): HasMany
    {
        return $this->hasMany(Pool::class);
    }

    public function poolEntries(): HasMany
    {
        return $this->hasMany(PoolEntry::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'organization_id');
    }

    public function matchResults(): HasMany
    {
        return $this->hasMany(MatchResult::class);
    }

    public function matchEvents(): HasMany
    {
        return $this->hasMany(MatchEvent::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }
}
