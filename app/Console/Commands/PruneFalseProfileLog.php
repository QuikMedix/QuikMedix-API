<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Until November 2026 the mobile profile logged changes it did not make: "change name from Casey to "
 * for fields a request did not send, and "change pharmacy_id from 4 to 9" for a pharmacy it never
 * saves. This finds the rows that provably never happened and, when asked, removes them after
 * saving a copy.
 */
class PruneFalseProfileLog extends Command
{
    use ConfirmableTrait;

    protected $signature = 'quikmedix:prune-profile-log
        {--delete : Remove the rows after saving a copy; without it nothing changes}
        {--force : Do not ask for confirmation in production}';

    protected $description = 'Find profile log rows that record a change that never happened, and optionally remove them';

    /** Log type => users column, for the fields the mobile profile compared without having received them. */
    private const FIELDS = [
        'change name' => 'name',
        'change last_name' => 'last_name',
        'change email' => 'email',
        'change phone' => 'phone',
        'change zip' => 'zip',
        'change apartment' => 'apartment',
    ];

    /** The mobile profile also logged a submitted pharmacy as a change, although it does not save one. */
    private const PHARMACY = 'change pharmacy_id';

    public function handle(): int
    {
        foreach (['id', 'type', 'comment', 'user_id', 'action_user_id'] as $column) {
            if (! Schema::hasColumn('action_log', $column)) {
                $this->error("action_log has no $column column; nothing was checked.");

                return self::FAILURE;
            }
        }

        $false = $this->falseRows();
        if ($false === []) {
            $this->info('No profile log rows record a change that did not happen.');

            return self::SUCCESS;
        }

        $this->info(count($false).' profile log rows on '.count(array_unique(array_column($false, 'user_id'))).' accounts record a change although the field kept its value (values are not printed):');
        foreach (array_count_values(array_column($false, 'type')) as $type => $count) {
            $this->line("  $type: $count");
        }
        if (! $this->option('delete')) {
            $this->comment('Nothing was changed. Add --delete to remove these rows after saving a copy.');

            return self::SUCCESS;
        }
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $ids = array_column($false, 'id');
        $copy = 'action-log-pruned-'.now()->format('Ymd-His').'.json';
        $rows = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            array_push($rows, ...DB::table('action_log')->whereIn('id', $chunk)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
        }
        if (! Storage::disk('local')->put($copy, json_encode($rows, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
            $this->error('The copy could not be saved, so nothing was removed.');

            return self::FAILURE;
        }

        $removed = DB::transaction(function () use ($ids): int {
            $removed = 0;
            foreach (array_chunk($ids, 500) as $chunk) {
                $removed += DB::table('action_log')->whereIn('id', $chunk)->delete();
            }

            return $removed;
        });
        $this->info("Removed $removed rows. A copy of them is in ".Storage::disk('local')->path($copy));

        return self::SUCCESS;
    }

    /**
     * Rows a user wrote about their own profile, saying a field went from X to blank (or the
     * pharmacy from X to another), where the field demonstrably stayed X: the next change of that
     * field still starts from X, or, when there is no later change, the account still holds X.
     * A change that really happened is kept.
     *
     * @return list<array{id: int, type: string, user_id: int}>
     */
    private function falseRows(): array
    {
        $columns = self::FIELDS + [self::PHARMACY => 'pharmacy_id'];
        $userIds = DB::table('action_log')->whereColumn('user_id', 'action_user_id')
            ->where(function ($query): void {
                $query->where(fn ($blank) => $blank->whereIn('type', array_keys(self::FIELDS))->where('comment', 'like', 'from % to '))
                    ->orWhere('type', self::PHARMACY);
            })->distinct()->orderBy('user_id')->pluck('user_id');

        $false = [];
        foreach ($userIds->chunk(200) as $chunk) {
            $users = DB::table('users')->whereIn('id', $chunk)->get(['id', ...array_values($columns)])->keyBy('id');
            $history = DB::table('action_log')->whereIn('user_id', $chunk)->whereIn('type', array_keys($columns))
                ->orderBy('id')->get(['id', 'type', 'comment', 'user_id', 'action_user_id'])->groupBy(fn ($row) => $row->user_id.'|'.$row->type);

            foreach ($history as $rows) {
                $rows = $rows->values();
                foreach ($rows as $position => $row) {
                    $claimedOld = $row->user_id == $row->action_user_id ? $this->claimedOldValue($row) : null;
                    if ($claimedOld === null) {
                        continue;
                    }
                    $next = $rows->get($position + 1);
                    $user = $users->get($row->user_id);
                    $stayed = $next !== null
                        ? str_starts_with((string) $next->comment, 'from '.$claimedOld.' to ')
                        : $user !== null && (string) $user->{$columns[$row->type]} === $claimedOld;
                    if ($stayed) {
                        $false[] = ['id' => (int) $row->id, 'type' => $row->type, 'user_id' => (int) $row->user_id];
                    }
                }
            }
        }

        return $false;
    }

    /**
     * The value a row says the field had before, when the row has the shape the mobile profile wrote
     * for a change it did not make; null for any other row.
     */
    private function claimedOldValue(object $row): ?string
    {
        $comment = (string) $row->comment;
        if ($row->type === self::PHARMACY) {
            return preg_match('/^from (\d*) to (\d+)$/', $comment, $match) === 1 && $match[1] !== $match[2] ? $match[1] : null;
        }

        return str_starts_with($comment, 'from ') && str_ends_with($comment, ' to ') && strlen($comment) > 9 ? substr($comment, 5, -4) : null;
    }
}
