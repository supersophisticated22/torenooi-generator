<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Exceptions\BillingException;
use App\Domain\Billing\Services\HandleStripeWebhook;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, HandleStripeWebhook $handleStripeWebhook): JsonResponse
    {
        $signature = (string) $request->header('Stripe-Signature', '');

        try {
            $processed = $handleStripeWebhook->handle($request->getContent(), $signature);
        } catch (BillingException) {
            return response()->json(['message' => 'Invalid webhook.'], 400);
        }

        return response()->json([
            'processed' => $processed,
        ]);
    }
}
