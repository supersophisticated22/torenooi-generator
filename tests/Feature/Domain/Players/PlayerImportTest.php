<?php

declare(strict_types=1);

use App\Livewire\Players\Index as PlayerIndex;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();

    $this->user->organizations()->attach($this->organization->id);
    $this->user->update(['current_organization_id' => $this->organization->id]);

    $this->sport = Sport::factory()->create([
        'organization_id' => $this->organization->id,
        'slug' => 'player-import-sport',
    ]);

    $this->team = Team::factory()->create([
        'organization_id' => $this->organization->id,
        'sport_id' => $this->sport->id,
        'category_id' => null,
    ]);

    $this->actingAs($this->user);
});

test('players can be imported from csv and assigned to selected team', function (): void {
    $file = makeCsvUpload(<<<'CSV'
first_name,last_name,number,email,jersey_number
Alex,Stone,10,alex@example.com,9
Sam,Lee,11,sam@example.com,11
CSV);

    Livewire::test(PlayerIndex::class)
        ->set('import_team_id', $this->team->id)
        ->set('import_file', $file)
        ->call('prepareImport')
        ->assertHasNoErrors()
        ->call('importPlayers')
        ->assertHasNoErrors();

    $alex = Player::query()
        ->where('organization_id', $this->organization->id)
        ->where('number', 10)
        ->firstOrFail();
    $sam = Player::query()
        ->where('organization_id', $this->organization->id)
        ->where('number', 11)
        ->firstOrFail();

    expect($alex->email)->toBe('alex@example.com')
        ->and($sam->email)->toBe('sam@example.com');

    $alexPivot = $this->team->players()->whereKey($alex->id)->firstOrFail()->pivot;
    $samPivot = $this->team->players()->whereKey($sam->id)->firstOrFail()->pivot;

    expect((int) $alexPivot->jersey_number)->toBe(9)
        ->and((int) $samPivot->jersey_number)->toBe(11);
});

test('duplicate player number updates existing player and assigns to selected team', function (): void {
    $existingPlayer = Player::factory()->create([
        'organization_id' => $this->organization->id,
        'first_name' => 'Old',
        'last_name' => 'Name',
        'number' => 77,
        'email' => null,
    ]);

    $file = makeCsvUpload(<<<'CSV'
first_name,last_name,number,email,jersey_number
New,Name,77,new@example.com,8
CSV);

    Livewire::test(PlayerIndex::class)
        ->set('import_team_id', $this->team->id)
        ->set('import_file', $file)
        ->call('prepareImport')
        ->assertHasNoErrors()
        ->call('importPlayers')
        ->assertHasNoErrors()
        ->assertSet('import_counts.imported', 0)
        ->assertSet('import_counts.updated', 1)
        ->assertSet('import_counts.assigned', 1);

    $existingPlayer->refresh();

    expect($existingPlayer->first_name)->toBe('New')
        ->and($existingPlayer->last_name)->toBe('Name')
        ->and($existingPlayer->email)->toBe('new@example.com');

    $pivot = $this->team->players()->whereKey($existingPlayer->id)->firstOrFail()->pivot;

    expect((int) $pivot->jersey_number)->toBe(8);
});

test('import validates team within current organization scope', function (): void {
    $otherOrganization = Organization::factory()->create();
    $otherSport = Sport::factory()->create([
        'organization_id' => $otherOrganization->id,
        'slug' => 'other-player-import-sport',
    ]);
    $otherTeam = Team::factory()->create([
        'organization_id' => $otherOrganization->id,
        'sport_id' => $otherSport->id,
        'category_id' => null,
    ]);

    $file = makeCsvUpload(<<<'CSV'
first_name,last_name,number
Alex,Stone,10
CSV);

    Livewire::test(PlayerIndex::class)
        ->set('import_team_id', $otherTeam->id)
        ->set('import_file', $file)
        ->call('prepareImport')
        ->assertHasErrors(['import_team_id']);
});

test('invalid rows are skipped while valid rows are imported', function (): void {
    $file = makeCsvUpload(<<<'CSV'
first_name,last_name,number,email
Alex,Stone,10,alex@example.com
,MissingFirstName,11,missing@example.com
Sam,Lee,invalid-number,sam@example.com
CSV);

    Livewire::test(PlayerIndex::class)
        ->set('import_team_id', $this->team->id)
        ->set('import_file', $file)
        ->call('prepareImport')
        ->assertHasNoErrors()
        ->call('importPlayers')
        ->assertHasNoErrors()
        ->assertSet('import_counts.imported', 1)
        ->assertSet('import_counts.skipped', 2)
        ->assertSet('import_counts.errors', 2);

    expect(Player::query()
        ->where('organization_id', $this->organization->id)
        ->where('number', 10)
        ->exists())->toBeTrue()
        ->and(Player::query()
            ->where('organization_id', $this->organization->id)
            ->where('number', 11)
            ->exists())->toBeFalse();
});

test('jersey number conflicts are reported and row is skipped', function (): void {
    $existingPlayer = Player::factory()->create([
        'organization_id' => $this->organization->id,
        'number' => 33,
    ]);
    $this->team->players()->attach($existingPlayer->id, [
        'organization_id' => $this->organization->id,
        'jersey_number' => 7,
    ]);

    $file = makeCsvUpload(<<<'CSV'
first_name,last_name,number,jersey_number
Alex,Stone,88,7
CSV);

    Livewire::test(PlayerIndex::class)
        ->set('import_team_id', $this->team->id)
        ->set('import_file', $file)
        ->call('prepareImport')
        ->assertHasNoErrors()
        ->call('importPlayers')
        ->assertHasNoErrors()
        ->assertSet('import_counts.imported', 0)
        ->assertSet('import_counts.errors', 1)
        ->assertSet('import_counts.skipped', 1);

    expect(Player::query()
        ->where('organization_id', $this->organization->id)
        ->where('number', 88)
        ->exists())->toBeFalse();
});

function makeCsvUpload(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('players.csv', $content);
}
