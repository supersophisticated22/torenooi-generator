<?php

namespace App\Domain\Billing\Enums;

enum BillingFeature: string
{
    case CreateTournament = 'create_tournament';
    case CreateTeam = 'create_team';
    case WhiteLabel = 'white_label';
    case AdvancedScoreScreens = 'advanced_score_screens';
    case MultipleLocations = 'multiple_locations';
    case ApiAccess = 'api_access';
}
