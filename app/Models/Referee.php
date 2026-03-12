<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\RefereeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referee extends Model
{
    /** @use HasFactory<RefereeFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'sport_id',
        'first_name',
        'last_name',
        'email',
        'phone',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function primaryMatches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'referee_id');
    }

    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(TournamentMatch::class, 'match_referee_assignments', 'referee_id', 'match_id')
            ->using(MatchRefereeAssignment::class)
            ->withPivot('organization_id')
            ->withTimestamps();
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'referee_tournament')
            ->using(TournamentReferee::class)
            ->withPivot('organization_id')
            ->withTimestamps();
    }
}
