<?php

return [
    'plans' => [
        'free' => [
            'name' => 'Free',
            'price_eur' => 0,
            'stripe_price_id' => null,
            'limits' => [
                'tournaments' => 1,
                'teams' => 8,
            ],
            'features' => [],
        ],
        'starter' => [
            'name' => 'Starter',
            'price_eur' => 19,
            'stripe_price_id' => env('STRIPE_PRICE_STARTER'),
            'limits' => [
                'tournaments' => 3,
                'teams' => 20,
            ],
            'features' => [],
        ],
        'pro' => [
            'name' => 'Pro',
            'price_eur' => 49,
            'stripe_price_id' => env('STRIPE_PRICE_PRO'),
            'limits' => [
                'tournaments' => null,
                'teams' => null,
            ],
            'features' => [
                'multiple_locations',
                'advanced_score_screens',
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price_eur' => 149,
            'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE'),
            'limits' => [
                'tournaments' => null,
                'teams' => null,
            ],
            'features' => [
                'white_label',
                'multiple_locations',
                'advanced_score_screens',
                'api_access',
            ],
        ],
    ],

    'stripe' => [
        'checkout' => [
            'payment_method_types' => ['card', 'ideal', 'bancontact', 'sepa_debit'],
        ],
    ],
];
