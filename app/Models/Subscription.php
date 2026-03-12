<?php

namespace App\Models;

use App\Domain\Billing\Enums\BillingPlan;
use App\Domain\Billing\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'organization_id',
        'stripe_subscription_id',
        'stripe_price_id',
        'plan_code',
        'status',
        'quantity',
        'current_period_start',
        'current_period_end',
        'cancel_at',
        'canceled_at',
        'trial_ends_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'plan_code' => BillingPlan::class,
            'status' => SubscriptionStatus::class,
            'quantity' => 'int',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at' => 'datetime',
            'canceled_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
