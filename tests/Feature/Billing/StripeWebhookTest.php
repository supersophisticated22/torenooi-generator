<?php

declare(strict_types=1);

use App\Domain\Billing\Actions\SyncStripeSubscriptionAction;
use App\Domain\Billing\Exceptions\BillingException;
use App\Domain\Billing\Services\HandleStripeWebhook;
use App\Domain\Billing\Services\StripeApi;
use App\Models\BillingEvent;
use App\Models\Organization;
use Stripe\Event;

it('returns bad request when webhook signature verification fails', function (): void {
    $handler = Mockery::mock(HandleStripeWebhook::class);
    $handler->shouldReceive('handle')->once()->andThrow(new BillingException('Invalid webhook'));

    app()->instance(HandleStripeWebhook::class, $handler);

    $this->postJson(route('stripe.webhook'), [], [
        'Stripe-Signature' => 'invalid',
    ])->assertStatus(400);
});

it('syncs subscription data once and ignores duplicate webhook event ids', function (): void {
    $organization = Organization::factory()->create([
        'stripe_customer_id' => 'cus_duplicate_123',
    ]);

    $stripeApi = Mockery::mock(StripeApi::class);

    $event = fakeStripeEvent(
        id: 'evt_duplicate_123',
        type: 'checkout.session.completed',
        object: (object) [
            'customer' => 'cus_duplicate_123',
            'subscription' => 'sub_duplicate_123',
        ],
    );

    $stripeApi->shouldReceive('verifyWebhook')->twice()->andReturn($event);
    app()->instance(StripeApi::class, $stripeApi);

    $syncSubscription = Mockery::mock(SyncStripeSubscriptionAction::class);
    $syncSubscription
        ->shouldReceive('__invoke')
        ->once()
        ->withArgs(fn (Organization $org, string $subscriptionId): bool => $org->is($organization) && $subscriptionId === 'sub_duplicate_123');

    app()->instance(SyncStripeSubscriptionAction::class, $syncSubscription);

    $service = app(HandleStripeWebhook::class);

    $first = $service->handle('payload', 'signature');
    $second = $service->handle('payload', 'signature');

    expect($first)->toBeTrue()
        ->and($second)->toBeFalse()
        ->and(BillingEvent::query()->where('event_id', 'evt_duplicate_123')->count())->toBe(1)
        ->and(BillingEvent::query()->where('event_id', 'evt_duplicate_123')->value('status'))->toBe('processed');
});

function fakeStripeEvent(string $id, string $type, object $object): Event
{
    return Event::constructFrom([
        'id' => $id,
        'type' => $type,
        'created' => now()->timestamp,
        'data' => [
            'object' => (array) $object,
        ],
    ]);
}
