<?php

namespace Database\Seeders\Demo;

class DemoCatalog
{
    public const ORGANIZATION_NAME = 'Demo Sports League';

    public const ORGANIZATION_SLUG = 'demo-sports-league';

    public const DEMO_PASSWORD = 'password';

    /**
     * @return array<int, array{name:string,email:string}>
     */
    public static function users(): array
    {
        return [
            [
                'name' => 'Demo Organizer',
                'email' => 'organizer@demo.test',
            ],
            [
                'name' => 'Demo Coach',
                'email' => 'coach@demo.test',
            ],
        ];
    }

    /**
     * @return array<int, array{name:string,slug:string}>
     */
    public static function sports(): array
    {
        return [
            [
                'name' => 'Football',
                'slug' => 'football',
            ],
            [
                'name' => 'Basketball',
                'slug' => 'basketball',
            ],
        ];
    }

    /**
     * @return array<string, array{name:string,slug:string}>
     */
    public static function categories(): array
    {
        return [
            'football' => [
                'name' => 'Men Senior',
                'slug' => 'men-senior-football',
            ],
            'basketball' => [
                'name' => 'Senior League',
                'slug' => 'senior-league-basketball',
            ],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function teamNames(): array
    {
        return [
            'football' => [
                'Amsterdam FC',
                'Rotterdam Rovers',
                'Utrecht United',
                'Eindhoven Eagles',
                'Groningen Greens',
                'Den Haag Dolphins',
            ],
            'basketball' => [
                'Amsterdam Arrows',
                'Rotterdam Rockets',
                'Utrecht Giants',
                'Eindhoven Storm',
            ],
        ];
    }

    /**
     * @return array<string, array{name:string,starts_at:string,ends_at:string}>
     */
    public static function events(): array
    {
        return [
            'football' => [
                'name' => 'Amsterdam Football Weekend 2026',
                'starts_at' => '2026-04-11 09:00:00',
                'ends_at' => '2026-04-12 21:00:00',
            ],
            'basketball' => [
                'name' => 'Amsterdam Basketball Day 2026',
                'starts_at' => '2026-04-19 10:00:00',
                'ends_at' => '2026-04-19 22:00:00',
            ],
        ];
    }

    /**
     * @return array<string, array{name:string,scheduled_start_at:string,match_duration_minutes:int,break_duration_minutes:int,final_break_minutes:int,pool_count:int,entry_count:int}>
     */
    public static function tournaments(): array
    {
        return [
            'football' => [
                'name' => 'Amsterdam Football Cup 2026',
                'scheduled_start_at' => '2026-04-11 09:00:00',
                'match_duration_minutes' => 30,
                'break_duration_minutes' => 10,
                'final_break_minutes' => 20,
                'pool_count' => 1,
                'entry_count' => 6,
            ],
            'basketball' => [
                'name' => 'Amsterdam Basketball Cup 2026',
                'scheduled_start_at' => '2026-04-19 10:00:00',
                'match_duration_minutes' => 24,
                'break_duration_minutes' => 8,
                'final_break_minutes' => 12,
                'pool_count' => 1,
                'entry_count' => 4,
            ],
        ];
    }
}
