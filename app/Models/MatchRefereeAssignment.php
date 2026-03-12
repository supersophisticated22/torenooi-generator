<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MatchRefereeAssignment extends Pivot
{
    protected $table = 'match_referee_assignments';

    public $incrementing = true;

    protected $fillable = [
        'organization_id',
        'match_id',
        'referee_id',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Referee::class);
    }
}
