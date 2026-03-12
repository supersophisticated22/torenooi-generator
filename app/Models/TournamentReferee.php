<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TournamentReferee extends Pivot
{
    protected $table = 'referee_tournament';

    public $incrementing = true;

    protected $fillable = [
        'organization_id',
        'tournament_id',
        'referee_id',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Referee::class);
    }
}
