<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditBranding extends Command
{
    protected $signature = 'quikmedix:branding-audit {--database : Check saved content; reads counts only}';

    protected $description = 'Check QuikMedix configuration and saved content for legacy branding without changing records';

    public function handle(): int
    {
        $failed = false;
        $legacy = '/a2brx|a2b[ .-]?rx|a2rx|zozland|657[ .-]?9595/i';
        $settings = [
            'app.name', 'app.url', 'chatify.name', 'mail.from.name', 'mail.from.address',
            'branding.legal_name', 'branding.support_email', 'branding.support_phone',
            'branding.billing_email', 'branding.address', 'branding.telegram_auth_url',
            'branding.download_url', 'branding.account_email_domain',
        ];
        foreach ($settings as $key) {
            if (preg_match($legacy, (string) config($key))) {
                $this->error($key.': contains legacy branding');
                $failed = true;
            }
        }
        $this->info('Brand configuration checked (values are not printed).');
        foreach (['support_email', 'support_phone', 'billing_email', 'address'] as $key) {
            if (!config('branding.'.$key)) {
                $this->warn('Not supplied: branding.'.$key);
            }
        }
        foreach (array_keys(config('branding.documents')) as $key) {
            if (!\App\Support\Branding::documentPath($key)) {
                $this->warn('Form artwork unavailable: '.$key);
            }
        }

        if (!$this->option('database')) {
            $this->comment('Saved content was not checked. Add --database to audit it.');
            return $failed ? 1 : 0;
        }

        try {
            DB::connection()->getPdo();
            $tables = [
                'news' => ['title', 'text', 'link'],
                'news_patient' => ['title', 'text', 'link'],
                'faqs' => ['title', 'text'],
                'banners' => ['image', 'href'],
                'notifications' => ['text', 'link'],
            ];
            foreach ($tables as $table => $columns) {
                if (!Schema::hasTable($table)) {
                    $this->comment($table.': table absent');
                    continue;
                }
                $columns = array_intersect($columns, Schema::getColumnListing($table));
                if (!$columns) {
                    continue;
                }
                $count = DB::table($table)->where(function ($query) use ($columns) {
                    foreach ($columns as $column) {
                        foreach (['a2brx', 'a2b rx', 'a2b-rx', 'a2rx', 'zozland', '657-9595', '6579595'] as $term) {
                            $query->orWhereRaw('LOWER('.$column.') LIKE ?', ['%'.$term.'%']);
                        }
                    }
                })->count();
                $this->line($table.': '.$count.' rows contain legacy text or URLs');
                $failed = $failed || $count > 0;
            }
        } catch (\Throwable $exception) {
            // Connection errors can include credentials or private hostnames.
            $this->error('Saved content could not be checked: database connection/query unavailable.');
            return 2;
        }

        $this->comment('No records were changed. Images without recognizable filenames require visual review.');
        return $failed ? 1 : 0;
    }
}
