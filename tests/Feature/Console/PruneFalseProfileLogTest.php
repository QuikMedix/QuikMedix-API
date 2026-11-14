<?php

namespace Tests\Feature\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The mobile profile used to log "from X to " for fields it had not received and a pharmacy change
 * it never saved. The command removes only rows that provably never happened and keeps every real change.
 */
class PruneFalseProfileLogTest extends TestCase
{
    private const CASEY = 1;

    private const JORDAN = 2;

    private const RILEY = 3;

    private const ADMIN = 9;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['name', 'last_name', 'email', 'phone', 'zip', 'apartment'] as $column) {
                $table->string($column)->nullable();
            }
            $table->integer('pharmacy_id')->nullable();
        });
        Schema::create('action_log', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('type');
            $table->text('comment')->nullable();
            $table->integer('user_id');
            $table->integer('action_user_id');
            $table->timestamp('created')->nullable();
        });
        DB::table('users')->insert([
            ['id' => self::CASEY, 'name' => 'Casey', 'last_name' => 'Rider', 'email' => 'casey@example.test', 'phone' => '555-0101', 'zip' => '10001', 'apartment' => '2 to 3', 'pharmacy_id' => 4],
            ['id' => self::JORDAN, 'name' => 'Jordan', 'last_name' => 'Lee', 'email' => 'jordan@example.test', 'phone' => '555-0202', 'zip' => null, 'apartment' => '7B', 'pharmacy_id' => 6],
            ['id' => self::RILEY, 'name' => 'Riley', 'last_name' => 'Park', 'email' => 'riley@example.test', 'phone' => '555-0303', 'zip' => '10003', 'apartment' => null, 'pharmacy_id' => null],
        ]);
        Storage::fake('local');
    }

    private function log(int $user, string $type, ?string $comment, ?int $author = null): int
    {
        return DB::table('action_log')->insertGetId(['type' => $type, 'comment' => $comment, 'user_id' => $user, 'action_user_id' => $author ?? $user, 'created' => '2026-10-01 10:00:00']);
    }

    /**
     * @return array{false: list<int>, real: list<int>}
     */
    private function history(): array
    {
        return [
            'false' => [
                // One phone-only update from the app: the other fields were logged as blanked but still hold their values.
                $this->log(self::CASEY, 'change name', 'from Casey to '),
                $this->log(self::CASEY, 'change email', 'from casey@example.test to '),
                $this->log(self::CASEY, 'change zip', 'from 10001 to '),
                // A value that itself contains " to ".
                $this->log(self::CASEY, 'change apartment', 'from 2 to 3 to '),
                // Logged as blanked, yet the next real change still starts from the same value; and twice in a row.
                $this->log(self::CASEY, 'change last_name', 'from Courier to '),
                $this->log(self::CASEY, 'change last_name', 'from Courier to '),
                // The app logged a pharmacy change it never saved: twice from 4, and Casey is still at pharmacy 4.
                $this->log(self::CASEY, 'change pharmacy_id', 'from 4 to 9'),
                $this->log(self::CASEY, 'change pharmacy_id', 'from 4 to 12'),
                // Riley has no pharmacy and still has none.
                $this->log(self::RILEY, 'change pharmacy_id', 'from  to 9'),
            ],
            'real' => [
                $this->log(self::CASEY, 'change last_name', 'from Courier to Rider'),
                $this->log(self::CASEY, 'change phone', 'from 555-0100 to 555-0101'),
                // Jordan really blanked the zip: it is blank now.
                $this->log(self::JORDAN, 'change zip', 'from 10002 to '),
                // Jordan really blanked the apartment, then set it again: the next change starts from blank.
                $this->log(self::JORDAN, 'change apartment', 'from 7B to '),
                $this->log(self::JORDAN, 'change apartment', 'from  to 7B'),
                // The same wording written by someone else comes from a web form, which saves what it logs.
                $this->log(self::JORDAN, 'change name', 'from Jordan to ', self::ADMIN),
                // A blanking of a value the account no longer holds cannot be judged.
                $this->log(self::JORDAN, 'change phone', 'from 555-0000 to '),
                // Other kinds of rows and accounts that no longer exist are left alone.
                $this->log(self::CASEY, 'change address', 'from 1 Old St to '),
                $this->log(self::CASEY, 'change password', null),
                // An administrator moved Jordan from pharmacy 3 to 5, then Jordan really moved to 6.
                $this->log(self::JORDAN, 'change pharmacy_id', 'from 3 to 5', self::ADMIN),
                $this->log(self::JORDAN, 'change pharmacy_id', 'from 5 to 6'),
                $this->log(77, 'change name', 'from Ghost to '),
                $this->log(77, 'change pharmacy_id', 'from 1 to 2'),
            ],
        ];
    }

    public function test_dry_run_counts_the_false_rows_without_changing_or_printing_them(): void
    {
        $this->history();

        $this->artisan('quikmedix:prune-profile-log')
            ->expectsOutputToContain('9 profile log rows on 2 accounts')
            ->expectsOutputToContain('change last_name: 2')
            ->expectsOutputToContain('change name: 1')
            ->expectsOutputToContain('change pharmacy_id: 3')
            ->expectsOutputToContain('Nothing was changed.')
            ->doesntExpectOutputToContain('Casey')
            ->doesntExpectOutputToContain('casey@example.test')
            ->assertExitCode(0);

        $this->assertDatabaseCount('action_log', 22);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_delete_removes_exactly_the_false_rows_after_saving_a_copy(): void
    {
        $history = $this->history();

        $this->artisan('quikmedix:prune-profile-log', ['--delete' => true])
            ->expectsOutputToContain('Removed 9 rows. A copy of them is in ')
            ->assertExitCode(0);

        $this->assertSame($history['real'], DB::table('action_log')->orderBy('id')->pluck('id')->all());
        $files = Storage::disk('local')->allFiles();
        $this->assertCount(1, $files);
        $this->assertMatchesRegularExpression('/^action-log-pruned-\d{8}-\d{6}\.json$/', $files[0]);
        $copy = json_decode(Storage::disk('local')->get($files[0]), true);
        $this->assertSame($history['false'], array_column($copy, 'id'));
        $this->assertSame(
            ['id' => $history['false'][0], 'type' => 'change name', 'comment' => 'from Casey to ', 'user_id' => self::CASEY, 'action_user_id' => self::CASEY, 'created' => '2026-10-01 10:00:00'],
            $copy[0],
        );

        // A second run finds nothing more.
        $this->artisan('quikmedix:prune-profile-log', ['--delete' => true])
            ->expectsOutputToContain('No profile log rows record a change that did not happen.')->assertExitCode(0);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_production_asks_before_deleting_unless_forced(): void
    {
        $this->history();
        $this->app['env'] = 'production';

        $this->artisan('quikmedix:prune-profile-log', ['--delete' => true])
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->assertExitCode(1);
        $this->assertDatabaseCount('action_log', 22);

        $this->artisan('quikmedix:prune-profile-log', ['--delete' => true, '--force' => true])->assertExitCode(0);
        $this->assertDatabaseCount('action_log', 13);
    }

    public function test_a_log_table_without_the_expected_columns_is_not_touched(): void
    {
        Schema::drop('action_log');
        Schema::create('action_log', function (Blueprint $table): void {
            $table->string('type');
        });

        $this->artisan('quikmedix:prune-profile-log', ['--delete' => true])
            ->expectsOutputToContain('action_log has no id column; nothing was checked.')->assertExitCode(1);
    }
}
