<?php

namespace App\Livewire\Players;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

#[Title('Players')]
class Index extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $import_file = null;

    public ?int $import_team_id = null;

    /**
     * @var array<int, string>
     */
    public array $import_headers = [];

    /**
     * @var array<int, array<int, mixed>>
     */
    public array $import_preview_rows = [];

    /**
     * @var array<string, int|string|null>
     */
    public array $import_mapping = [
        'first_name' => null,
        'last_name' => null,
        'number' => null,
        'email' => null,
        'jersey_number' => null,
    ];

    /**
     * @var array<int, string>
     */
    public array $import_errors = [];

    /**
     * @var array{imported:int,updated:int,assigned:int,skipped:int,errors:int}
     */
    public array $import_counts = [
        'imported' => 0,
        'updated' => 0,
        'assigned' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    public ?string $import_status = null;

    public function deletePlayer(int $playerId): void
    {
        $player = Player::query()->withCount(['tournamentEntries', 'matchEvents', 'teams'])->findOrFail($playerId);

        Gate::authorize('manage-tenant-record', $player);

        if ($player->tournament_entries_count > 0 || $player->match_events_count > 0 || $player->teams_count > 0) {
            $this->addError('delete', 'This player is in use and cannot be deleted.');

            return;
        }

        $player->delete();
        session()->flash('status', 'Player deleted successfully.');
    }

    public function prepareImport(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        $validated = $this->validate([
            'import_file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xlsx'],
            'import_team_id' => ['required', Rule::exists('teams', 'id')->where('organization_id', $organization->id)],
        ]);

        $team = Team::query()->findOrFail($validated['import_team_id']);
        Gate::authorize('manage-tenant-record', $team);

        $rows = $this->readSpreadsheetRows($validated['import_file']);

        if ($rows === [] || ! isset($rows[0])) {
            $this->addError('import_file', 'Uploaded file is empty.');

            return;
        }

        $headers = $this->extractHeaders($rows[0]);

        if ($headers === []) {
            $this->addError('import_file', 'The first row must contain header columns.');

            return;
        }

        $this->resetImportResults();
        $this->import_headers = $headers;
        $this->import_preview_rows = array_slice($rows, 1, 5);
        $this->import_status = 'File loaded. Review mappings and import.';
        $this->applySuggestedMapping();
    }

    public function applySuggestedMapping(): void
    {
        if ($this->import_headers === []) {
            return;
        }

        $suggestions = [
            'first_name' => ['first_name', 'firstname', 'first', 'given_name', 'givenname'],
            'last_name' => ['last_name', 'lastname', 'last', 'family_name', 'familyname', 'surname'],
            'number' => ['number', 'player_number', 'playernumber', 'shirt_number', 'shirtnumber', 'nr', 'no'],
            'email' => ['email', 'e_mail', 'mail'],
            'jersey_number' => ['jersey_number', 'jerseynumber', 'jersey', 'shirt', 'shirt_no', 'shirt_nr'],
        ];

        $headerIndexes = [];

        foreach ($this->import_headers as $index => $header) {
            $headerIndexes[$this->normalizeHeader((string) $header)] = $index;
        }

        foreach ($suggestions as $field => $aliases) {
            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $headerIndexes)) {
                    $this->import_mapping[$field] = $headerIndexes[$alias];
                    break;
                }
            }
        }
    }

    public function importPlayers(): void
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            abort(403);
        }

        Gate::authorize('create-tenant-record', Player::class);

        $validated = $this->validate([
            'import_file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xlsx'],
            'import_team_id' => ['required', Rule::exists('teams', 'id')->where('organization_id', $organization->id)],
            'import_mapping.first_name' => ['required', 'integer'],
            'import_mapping.last_name' => ['required', 'integer'],
            'import_mapping.number' => ['required', 'integer'],
            'import_mapping.email' => ['nullable', 'integer'],
            'import_mapping.jersey_number' => ['nullable', 'integer'],
        ]);

        $team = Team::query()->findOrFail((int) $validated['import_team_id']);
        Gate::authorize('manage-tenant-record', $team);

        $rows = $this->readSpreadsheetRows($validated['import_file']);

        if ($rows === [] || ! isset($rows[0])) {
            $this->addError('import_file', 'Uploaded file is empty.');

            return;
        }

        $counts = [
            'imported' => 0,
            'updated' => 0,
            'assigned' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];
        $errors = [];

        foreach (array_slice($rows, 1) as $offset => $row) {
            $lineNumber = $offset + 2;

            if ($this->rowIsEmpty($row)) {
                $counts['skipped']++;

                continue;
            }

            $firstName = trim($this->cellValue($row, $validated['import_mapping']['first_name']));
            $lastName = trim($this->cellValue($row, $validated['import_mapping']['last_name']));
            $numberRaw = trim($this->cellValue($row, $validated['import_mapping']['number']));
            $emailRaw = trim($this->cellValue($row, $validated['import_mapping']['email']));
            $jerseyRaw = trim($this->cellValue($row, $validated['import_mapping']['jersey_number']));

            if ($firstName === '' || $lastName === '') {
                $errors[] = 'Line '.$lineNumber.': first name and last name are required.';
                $counts['errors']++;
                $counts['skipped']++;

                continue;
            }

            if (! ctype_digit($numberRaw) || (int) $numberRaw < 1) {
                $errors[] = 'Line '.$lineNumber.': number must be an integer greater than 0.';
                $counts['errors']++;
                $counts['skipped']++;

                continue;
            }

            $number = (int) $numberRaw;

            if ($emailRaw !== '' && filter_var($emailRaw, FILTER_VALIDATE_EMAIL) === false) {
                $errors[] = 'Line '.$lineNumber.': email must be a valid email address.';
                $counts['errors']++;
                $counts['skipped']++;

                continue;
            }

            $jerseyNumber = null;

            if ($validated['import_mapping']['jersey_number'] !== null && $jerseyRaw !== '') {
                if (! ctype_digit($jerseyRaw)) {
                    $errors[] = 'Line '.$lineNumber.': jersey number must be a positive integer.';
                    $counts['errors']++;
                    $counts['skipped']++;

                    continue;
                }

                $jerseyNumber = (int) $jerseyRaw;

                if ($jerseyNumber < 0 || $jerseyNumber > 999) {
                    $errors[] = 'Line '.$lineNumber.': jersey number must be between 0 and 999.';
                    $counts['errors']++;
                    $counts['skipped']++;

                    continue;
                }
            }

            try {
                $rowCounts = [
                    'imported' => 0,
                    'updated' => 0,
                    'assigned' => 0,
                ];

                DB::transaction(function () use (
                    $organization,
                    $team,
                    $firstName,
                    $lastName,
                    $number,
                    $emailRaw,
                    $validated,
                    $jerseyNumber,
                    &$rowCounts
                ): void {
                    $player = Player::query()
                        ->where('organization_id', $organization->id)
                        ->where('number', $number)
                        ->first();

                    if ($player === null) {
                        $player = Player::query()->create([
                            'organization_id' => $organization->id,
                            'team_id' => null,
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'number' => $number,
                            'email' => $emailRaw !== '' ? $emailRaw : null,
                        ]);
                        $rowCounts['imported']++;
                    } else {
                        Gate::authorize('manage-tenant-record', $player);

                        $updates = [
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                        ];

                        if ($validated['import_mapping']['email'] !== null) {
                            $updates['email'] = $emailRaw !== '' ? $emailRaw : null;
                        }

                        $player->update($updates);
                        $rowCounts['updated']++;
                    }

                    $existingPivot = $team->players()
                        ->whereKey($player->id)
                        ->first();

                    if ($existingPivot === null) {
                        if ($jerseyNumber !== null
                            && $team->players()->wherePivot('jersey_number', $jerseyNumber)->exists()) {
                            throw new \RuntimeException('Jersey number '.$jerseyNumber.' is already used by this team.');
                        }

                        $team->players()->attach($player->id, [
                            'organization_id' => $organization->id,
                            'jersey_number' => $jerseyNumber,
                        ]);

                        $rowCounts['assigned']++;

                        return;
                    }

                    if ($validated['import_mapping']['jersey_number'] !== null) {
                        if ($jerseyNumber !== null
                            && $team->players()
                                ->whereKeyNot($player->id)
                                ->wherePivot('jersey_number', $jerseyNumber)
                                ->exists()) {
                            throw new \RuntimeException('Jersey number '.$jerseyNumber.' is already used by this team.');
                        }

                        $team->players()->updateExistingPivot($player->id, [
                            'jersey_number' => $jerseyNumber,
                        ]);
                    }
                });

                $counts['imported'] += $rowCounts['imported'];
                $counts['updated'] += $rowCounts['updated'];
                $counts['assigned'] += $rowCounts['assigned'];
            } catch (\RuntimeException $exception) {
                $errors[] = 'Line '.$lineNumber.': '.$exception->getMessage();
                $counts['errors']++;
                $counts['skipped']++;
            }
        }

        $this->import_counts = $counts;
        $this->import_errors = $errors;
        $this->import_status = 'Import completed.';
        session()->flash('status', 'Player import completed.');
    }

    public function updatedImportFile(): void
    {
        $this->import_headers = [];
        $this->import_preview_rows = [];
        $this->import_mapping = [
            'first_name' => null,
            'last_name' => null,
            'number' => null,
            'email' => null,
            'jersey_number' => null,
        ];
        $this->resetImportResults();
    }

    #[Computed]
    public function importTeams()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Team::query()
            ->forOrganization($organization)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function players()
    {
        $organization = Auth::user()?->currentOrganization();

        if ($organization === null) {
            return collect();
        }

        return Player::query()
            ->forOrganization($organization)
            ->orderBy('number')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.players.index');
    }

    private function resetImportResults(): void
    {
        $this->import_errors = [];
        $this->import_status = null;
        $this->import_counts = [
            'imported' => 0,
            'updated' => 0,
            'assigned' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readSpreadsheetRows(TemporaryUploadedFile $file): array
    {
        $sheets = Excel::toArray([], $file->getRealPath());

        $rows = $sheets[0] ?? [];

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter(
            $rows,
            static fn ($row): bool => is_array($row),
        ));
    }

    /**
     * @param  array<int, mixed>  $headerRow
     * @return array<int, string>
     */
    private function extractHeaders(array $headerRow): array
    {
        $headers = [];

        foreach ($headerRow as $index => $value) {
            $label = trim((string) $value);

            if ($label === '') {
                continue;
            }

            $headers[(int) $index] = $label;
        }

        return $headers;
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)
            ->lower()
            ->replace('-', '_')
            ->replace(' ', '_')
            ->replace('.', '_')
            ->replace('/', '_')
            ->replace('__', '_')
            ->trim('_')
            ->toString();
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function cellValue(array $row, mixed $index): string
    {
        if ($index === null || $index === '') {
            return '';
        }

        $resolvedIndex = is_int($index) ? $index : (int) $index;

        return (string) ($row[$resolvedIndex] ?? '');
    }
}
