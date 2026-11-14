<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Until November 2026 the mobile profile logged "change name from Casey to " style rows for
 * fields a request did not send. This finds the rows that provably never happened and, when
 * asked, removes them after saving a copy.
 */
class PruneBlankProfileLog extends Command
{
    use ConfirmableTrait;

    protected $signature = 'quikmedix:prune-profile-log
        {--delete : Remove the rows after saving a copy; without it nothing changes}
        {--force : Do not ask for confirmation in production}';

    protected $description = 'Find profile log rows that claim a field was blanked although it never was, and optionally remove them';

    /** Log type => users column, for the fields the mobile profile compared without having received them. */
    private const FIELDS = [
        'change name' => 'name',
        'change last_name' => 'last_name',
        'change email' => 'email',
        'change phone' => 'phone',
        'change zip' => 'zip',
        'change apartment' => 'apartment',
    ];

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
            $this->info('No profile log rows claim a blanking that did not happen.');

            return self::SUCCESS;
        }

        $this->info(count($false).' profile log rows on '.count(array_unique(array_column($false, 'user_id'))).' accounts claim a field was blanked although it kept its value (values are not printed):');
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
     * Rows a user wrote about their own profile saying "from X to " (blank) where the field
     * demonstrably stayed X: the next change of that field still starts from X, or, when there
     * is no later change, the account still holds X. A field that really was blanked is kept.
     *
     * @return list<array{id: int, type: string, user_id: int}>
     */
    private function falseRows(): array
    {
        $userIds = DB::table('action_log')->whereIn('type', array_keys(self::FIELDS))->whereColumn('user_id', 'action_user_id')
            ->where('comment', 'like', 'from % to ')->distinct()->orderBy('user_id')->pluck('user_id');

        $false = [];
        foreach ($userIds->chunk(200) as $chunk) {
            $users = DB::table('users')->whereIn('id', $chunk)->get(['id', ...array_values(self::FIELDS)])->keyBy('id');
            $history = DB::table('action_log')->whereIn('user_id', $chunk)->whereIn('type', array_keys(self::FIELDS))
                ->orderBy('id')->get(['id', 'type', 'comment', 'user_id', 'action_user_id'])->groupBy(fn ($row) => $row->user_id.'|'.$row->type);

            foreach ($history as $rows) {
                foreach ($rows->values() as $position => $row) {
                    $comment = (string) $row->comment;
                    if ($row->user_id != $row->action_user_id || ! str_starts_with($comment, 'from ') || ! str_ends_with($comment, ' to ') || strlen($comment) <= 9) {
                        continue;
                    }
                    $claimedOld = substr($comment, 5, -4);
                    $next = $rows->values()->get($position + 1);
                    $user = $users->get($row->user_id);
                    $stayed = $next !== null
                        ? str_starts_with((string) $next->comment, 'from '.$claimedOld.' to ')
                        : $user !== null && (string) $user->{self::FIELDS[$row->type]} === $claimedOld;
                    if ($stayed) {
                        $false[] = ['id' => (int) $row->id, 'type' => $row->type, 'user_id' => (int) $row->user_id];
                    }
                }
            }
        }

        return $false;
    }
}
