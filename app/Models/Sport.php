<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\SportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sport extends Model
{
    /** @use HasFactory<SportFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sportRule(): HasOne
    {
        return $this->hasOne(SportRule::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
}
