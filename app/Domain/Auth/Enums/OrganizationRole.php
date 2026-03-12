<?php

namespace App\Domain\Auth\Enums;

enum OrganizationRole: string
{
    case OrganizationAdmin = 'organization_admin';
    case EventManager = 'event_manager';
    case Referee = 'referee';
    case Scorekeeper = 'scorekeeper';
    case Viewer = 'viewer';
}
