<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * The tables that were created by hand before this project used migrations, as they were in
 * the mysql database "default" on 2026-11-15, leaving out ai_agent_*, billing_settings, driver_connect_accounts, driver_payout_*, financial_audit_logs, payment_gateway_settings, pharmacy_route_*, pharmacy_routes, stripe_webhook_events, when `php artisan quikmedix:schema-baseline` wrote this file.
 *
 * A table that is missing is created, columns that are missing from an existing table are
 * added, and nothing else is touched: on a database that already has these tables this
 * migration changes nothing. Each column is [name, Blueprint method, arguments, flags, default].
 */
return new class extends Migration
{
    private const TABLES = [
        'action_log' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['type', 'string', [50], ''],
                ['user_id', 'integer', [], ''],
                ['action_user_id', 'integer', [], ''],
                ['comment', 'mediumText', [], 'nullable'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'additional_recipients' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['user_id', 'integer', [], ''],
                ['family_type', 'string', [50], ''],
                ['family_name', 'string', [100], ''],
                ['family_phone', 'string', [20], 'nullable'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'admin_area_zones' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['admin_area_id', 'integer', [], ''],
                ['area_id', 'integer', [], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'admin_areas' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created_at', 'timestamp', [], 'useCurrent'],
                ['name', 'string', [50], ''],
                ['office_id', 'integer', [], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'area' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['name', 'string', [50], ''],
                ['state', 'string', [30], ''],
                ['tariff', 'float', [24], '', '1'],
                ['polygon', 'geometry', [], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'area_zip' => [
            'columns' => [
                ['id', 'bigInteger', [], 'autoIncrement'],
                ['area_id', 'integer', [], ''],
                ['zip', 'string', [20], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'banners' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['type_id', 'integer', [], '', 1],
                ['image', 'mediumText', [], ''],
                ['title', 'string', [200], 'nullable'],
                ['body', 'mediumText', [], 'nullable'],
                ['href', 'mediumText', [], 'nullable'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'cache' => [
            'columns' => [
                ['key', 'string', [191], ''],
                ['value', 'mediumText', [], ''],
                ['expiration', 'bigInteger', [], ''],
            ],
            'indexes' => [
                ['index', ['expiration'], 'cache_expiration_index'],
                ['primary', ['key'], null],
            ],
            'foreign' => [],
        ],
        'cache_locks' => [
            'columns' => [
                ['key', 'string', [191], ''],
                ['owner', 'string', [191], ''],
                ['expiration', 'bigInteger', [], ''],
            ],
            'indexes' => [
                ['index', ['expiration'], 'cache_locks_expiration_index'],
                ['primary', ['key'], null],
            ],
            'foreign' => [],
        ],
        'calls' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['call_id', 'string', [60], ''],
                ['to', 'string', [14], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'cash_log' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['return_at', 'timestamp', [], 'nullable'],
                ['order_id', 'bigInteger', [], ''],
                ['driver_id', 'bigInteger', [], ''],
                ['copay', 'float', [24], ''],
                ['return', 'enum', [['0', '1']], '', '0'],
                ['admin_id', 'bigInteger', [], 'nullable'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'ch_favorites' => [
            'columns' => [
                ['id', 'char', [36], ''],
                ['user_id', 'bigInteger', [], ''],
                ['favorite_id', 'bigInteger', [], ''],
                ['created_at', 'timestamp', [], 'nullable'],
                ['updated_at', 'timestamp', [], 'nullable'],
            ],
            'indexes' => [
                ['primary', ['id'], null],
            ],
            'foreign' => [],
        ],
        'ch_messages' => [
            'columns' => [
                ['id', 'char', [36], ''],
                ['from_id', 'bigInteger', [], ''],
                ['to_id', 'bigInteger', [], ''],
                ['body', 'string', [5000], 'nullable'],
                ['attachment', 'string', [191], 'nullable'],
                ['seen', 'tinyInteger', [], '', 0],
                ['created_at', 'timestamp', [], 'nullable'],
                ['updated_at', 'timestamp', [], 'nullable'],
            ],
            'indexes' => [
                ['primary', ['id'], null],
            ],
            'foreign' => [],
        ],
        'chats' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['name', 'string', [50], ''],
                ['user1', 'integer', [], ''],
                ['user2', 'integer', [], ''],
                ['unread_user1', 'integer', [], '', 0],
                ['unread_user2', 'integer', [], '', 0],
                ['last_message_date', 'dateTime', [], 'nullable'],
                ['last_message_body', 'string', [500], 'nullable'],
            ],
            'indexes' => [
                ['index', ['id'], 'id'],
                ['index', ['user1'], 'user1'],
                ['index', ['user2'], 'user2'],
            ],
            'foreign' => [],
        ],
        'deleted_patients' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['user_id', 'integer', [], ''],
                ['medic_id', 'integer', [], ''],
                ['reason', 'string', [200], 'nullable'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'delivery_methods' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['name', 'string', [50], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'delivery_times' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['name', 'string', [50], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'drivers_eta' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['driver_id', 'integer', [], ''],
                ['distance', 'float', [24], ''],
                ['eta', 'bigInteger', [], ''],
                ['last_location', 'string', [50], 'nullable'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'failed_jobs' => [
            'columns' => [
                ['id', 'bigInteger', [], 'unsigned autoIncrement'],
                ['connection', 'text', [], ''],
                ['queue', 'text', [], ''],
                ['payload', 'longText', [], ''],
                ['exception', 'longText', [], ''],
                ['failed_at', 'timestamp', [], 'useCurrent'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'family_members' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['user_id', 'integer', [], ''],
                ['family_type', 'string', [50], ''],
                ['family_name', 'string', [100], ''],
                ['family_phone', 'string', [20], 'nullable'],
                ['family_address', 'string', [200], ''],
                ['location', 'string', [50], 'nullable'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'faqs' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['user_id', 'bigInteger', [], ''],
                ['title', 'string', [200], ''],
                ['text', 'mediumText', [], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'invoice_exclusion' => [
            'columns' => [
                ['id', 'bigInteger', [], 'autoIncrement'],
                ['invoice_id', 'bigInteger', [], ''],
                ['order_id', 'bigInteger', [], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'invoices' => [
            'columns' => [
                ['id', 'bigInteger', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['pharmacy_id', 'integer', [], ''],
                ['date_from', 'date', [], ''],
                ['date_to', 'date', [], ''],
                ['count', 'integer', [], '', 0],
                ['amount', 'float', [24], ''],
                ['copay', 'float', [24], ''],
                ['corrections', 'float', [24], '', '0'],
                ['payed', 'enum', [['0', '1']], 'nullable', '0'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'job_batches' => [
            'columns' => [
                ['id', 'string', [191], ''],
                ['name', 'string', [191], ''],
                ['total_jobs', 'integer', [], ''],
                ['pending_jobs', 'integer', [], ''],
                ['failed_jobs', 'integer', [], ''],
                ['failed_job_ids', 'longText', [], ''],
                ['options', 'mediumText', [], 'nullable'],
                ['cancelled_at', 'integer', [], 'nullable'],
                ['created_at', 'integer', [], ''],
                ['finished_at', 'integer', [], 'nullable'],
            ],
            'indexes' => [
                ['primary', ['id'], null],
            ],
            'foreign' => [],
        ],
        'jobs' => [
            'columns' => [
                ['id', 'bigInteger', [], 'unsigned autoIncrement'],
                ['queue', 'string', [191], ''],
                ['payload', 'longText', [], ''],
                ['attempts', 'smallInteger', [], 'unsigned'],
                ['reserved_at', 'integer', [], 'nullable unsigned'],
                ['available_at', 'integer', [], 'unsigned'],
                ['created_at', 'integer', [], 'unsigned'],
            ],
            'indexes' => [
                ['index', ['queue'], 'jobs_queue_index'],
            ],
            'foreign' => [],
        ],
        'locations' => [
            'columns' => [
                ['id', 'bigInteger', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['user_id', 'bigInteger', [], 'unsigned'],
                ['location', 'string', [50], ''],
            ],
            'indexes' => [
                ['index', ['id'], 'id'],
                ['index', ['user_id'], 'user_id'],
            ],
            'foreign' => [],
        ],
        'medicine' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['order_id', 'integer', [], ''],
                ['medicine_id', 'integer', [], ''],
                ['dosage', 'string', [30], 'nullable'],
                ['count', 'integer', [], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'medicines' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['name', 'string', [100], ''],
                ['pharmacy_id', 'integer', [], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'news' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['user_id', 'bigInteger', [], ''],
                ['type', 'string', [50], ''],
                ['link', 'mediumText', [], 'nullable'],
                ['title', 'string', [200], ''],
                ['text', 'mediumText', [], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'news_patient' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['user_id', 'bigInteger', [], ''],
                ['pharmacy_id', 'integer', [], ''],
                ['type', 'string', [50], ''],
                ['link', 'mediumText', [], 'nullable'],
                ['title', 'string', [200], ''],
                ['text', 'mediumText', [], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'news_read' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['news_id', 'integer', [], ''],
                ['user_id', 'bigInteger', [], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'notes' => [
            'columns' => [
                ['id', 'bigInteger', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['order_id', 'bigInteger', [], ''],
                ['user_id', 'bigInteger', [], ''],
                ['type', 'enum', [['1', '2']], '', '1'],
                ['note', 'mediumText', [], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'notifications' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['user_id', 'integer', [], ''],
                ['type', 'string', [50], ''],
                ['link', 'mediumText', [], 'nullable'],
                ['text', 'mediumText', [], ''],
                ['type_text', 'string', [20], '', ''],
                ['viewed', 'integer', [], '', 0],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'oauth_access_tokens' => [
            'columns' => [
                ['id', 'string', [100], ''],
                ['user_id', 'bigInteger', [], 'nullable unsigned'],
                ['client_id', 'bigInteger', [], 'unsigned'],
                ['name', 'string', [191], 'nullable'],
                ['scopes', 'text', [], 'nullable'],
                ['revoked', 'tinyInteger', [], ''],
                ['created_at', 'timestamp', [], 'nullable'],
                ['updated_at', 'timestamp', [], 'nullable'],
                ['expires_at', 'dateTime', [], 'nullable'],
            ],
            'indexes' => [
                ['index', ['client_id'], 'client_id'],
                ['index', ['id'], 'id'],
                ['index', ['user_id'], 'oauth_access_tokens_user_id_index'],
                ['primary', ['id'], null],
                ['index', ['user_id'], 'user_id'],
            ],
            'foreign' => [],
        ],
        'oauth_auth_codes' => [
            'columns' => [
                ['id', 'string', [100], ''],
                ['user_id', 'bigInteger', [], 'unsigned'],
                ['client_id', 'bigInteger', [], 'unsigned'],
                ['scopes', 'text', [], 'nullable'],
                ['revoked', 'tinyInteger', [], ''],
                ['expires_at', 'dateTime', [], 'nullable'],
            ],
            'indexes' => [
                ['index', ['user_id'], 'oauth_auth_codes_user_id_index'],
                ['primary', ['id'], null],
            ],
            'foreign' => [],
        ],
        'oauth_clients' => [
            'columns' => [
                ['id', 'bigInteger', [], 'unsigned autoIncrement'],
                ['user_id', 'bigInteger', [], 'nullable unsigned'],
                ['name', 'string', [191], ''],
                ['secret', 'string', [100], 'nullable'],
                ['provider', 'string', [191], 'nullable'],
                ['redirect', 'text', [], ''],
                ['personal_access_client', 'tinyInteger', [], '', 0],
                ['password_client', 'tinyInteger', [], '', 0],
                ['revoked', 'tinyInteger', [], ''],
                ['created_at', 'timestamp', [], 'nullable'],
                ['updated_at', 'timestamp', [], 'nullable'],
                ['grant_types', 'text', [], 'nullable'],
            ],
            'indexes' => [
                ['index', ['user_id'], 'oauth_clients_user_id_index'],
            ],
            'foreign' => [],
        ],
        'oauth_personal_access_clients' => [
            'columns' => [
                ['id', 'bigInteger', [], 'unsigned autoIncrement'],
                ['client_id', 'bigInteger', [], 'unsigned'],
                ['created_at', 'timestamp', [], 'nullable'],
                ['updated_at', 'timestamp', [], 'nullable'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'oauth_refresh_tokens' => [
            'columns' => [
                ['id', 'string', [100], ''],
                ['access_token_id', 'string', [100], ''],
                ['revoked', 'tinyInteger', [], ''],
                ['expires_at', 'dateTime', [], 'nullable'],
            ],
            'indexes' => [
                ['primary', ['id'], null],
            ],
            'foreign' => [],
        ],
        'offices' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['email', 'string', [50], ''],
                ['phone', 'string', [20], ''],
                ['name', 'string', [100], ''],
                ['address', 'string', [200], ''],
                ['location', 'string', [50], ''],
                ['logo', 'mediumText', [], 'nullable'],
                ['zone_id', 'integer', [], 'nullable', 1],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'order_exceptions' => [
            'columns' => [
                ['id', 'bigInteger', [], 'unsigned autoIncrement'],
                ['order_id', 'bigInteger', [], 'unsigned'],
                ['driver_id', 'bigInteger', [], 'unsigned'],
                ['requested_status_id', 'integer', [], 'unsigned'],
                ['status', 'string', [16], '', 'pending'],
                ['driver_note', 'string', [1000], 'nullable'],
                ['reviewed_by', 'bigInteger', [], 'nullable unsigned'],
                ['review_note', 'string', [1000], 'nullable'],
                ['reviewed_at', 'timestamp', [], 'nullable'],
                ['created_at', 'timestamp', [], 'nullable'],
                ['updated_at', 'timestamp', [], 'nullable'],
            ],
            'indexes' => [
                ['index', ['order_id', 'status'], 'order_exceptions_order_id_status_index'],
            ],
            'foreign' => [],
        ],
        'packages_transitions' => [
            'columns' => [
                ['id', 'bigInteger', [], 'unsigned autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['order_id', 'bigInteger', [], 'unsigned'],
                ['pharmacy_id', 'integer', [], 'nullable'],
                ['office_id', 'integer', [], 'nullable'],
                ['driver_id', 'integer', [], 'nullable'],
                ['user_id', 'integer', [], 'nullable'],
                ['bag', 'integer', [], '', 1],
                ['target', 'string', [10], 'nullable'],
                ['location', 'string', [50], 'nullable'],
            ],
            'indexes' => [
                ['index', ['driver_id'], 'driver_id'],
                ['index', ['id'], 'id'],
                ['index', ['office_id'], 'office_id'],
                ['index', ['order_id'], 'order_id'],
                ['index', ['pharmacy_id'], 'pharmacy_id'],
                ['index', ['user_id'], 'user_id'],
            ],
            'foreign' => [],
        ],
        'password_reset_tokens' => [
            'columns' => [
                ['email', 'string', [191], ''],
                ['token', 'string', [191], ''],
                ['created_at', 'timestamp', [], 'nullable'],
            ],
            'indexes' => [
                ['primary', ['email'], null],
            ],
            'foreign' => [],
        ],
        'password_resets' => [
            'columns' => [
                ['email', 'string', [191], ''],
                ['token', 'string', [191], ''],
                ['created_at', 'timestamp', [], 'nullable'],
            ],
            'indexes' => [
                ['index', ['email'], 'password_resets_email_index'],
            ],
            'foreign' => [],
        ],
        'payment_accounts' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['user_id', 'integer', [], ''],
                ['type', 'string', [10], '', 'card'],
                ['profile_id', 'string', [50], ''],
                ['payment_id', 'string', [60], 'nullable'],
                ['payment_profile_id', 'string', [50], 'nullable'],
                ['card', 'string', [20], 'nullable'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'payment_pharmacy_accounts' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['pharmacy_id', 'integer', [], ''],
                ['type', 'string', [10], '', 'card'],
                ['profile_id', 'string', [50], 'nullable'],
                ['payment_id', 'string', [60], 'nullable'],
                ['payment_profile_id', 'string', [50], 'nullable'],
                ['card', 'string', [20], 'nullable'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'payments' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['order_id', 'integer', [], ''],
                ['transaction_id', 'string', [50], ''],
                ['type', 'string', [10], '', 'copay'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'payouts_driver' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['driver_id', 'integer', [], ''],
                ['order_id', 'integer', [], ''],
                ['amount', 'float', [24], ''],
                ['withdraw', 'integer', [], '', 0],
            ],
            'indexes' => [
                ['index', ['driver_id'], 'driver_id'],
                ['index', ['id'], 'id'],
                ['index', ['order_id'], 'order_id'],
            ],
            'foreign' => [],
        ],
        'pharmacy_areas' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['pharmacy_id', 'integer', [], ''],
                ['area_id', 'integer', [], ''],
                ['type', 'integer', [], '', 1],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'pharmacy_payments' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['pharmacy_id', 'bigInteger', [], ''],
                ['invoice_id', 'integer', [], 'nullable'],
                ['amount', 'float', [24], ''],
                ['transaction_id', 'string', [50], ''],
                ['type', 'string', [10], '', 'refill'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'pharmacys' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['isactive', 'integer', [], '', 1],
                ['isblocked', 'integer', [], '', 0],
                ['email', 'string', [50], ''],
                ['phone', 'string', [20], ''],
                ['name', 'string', [100], ''],
                ['tariff', 'float', [24], 'nullable'],
                ['tariff_next_day', 'float', [24], 'nullable'],
                ['tariff_same_day', 'float', [24], 'nullable'],
                ['tariff_asap', 'float', [24], 'nullable'],
                ['tariff_after_hours', 'float', [24], 'nullable'],
                ['tariff_fridge', 'float', [24], 'nullable'],
                ['tariff_area2', 'float', [24], 'nullable'],
                ['tariff_area3', 'float', [24], 'nullable'],
                ['tariff_area_more', 'float', [24], 'nullable'],
                ['plan_id', 'integer', [], '', 1],
                ['date_end_trial', 'date', [], 'nullable'],
                ['address', 'string', [200], ''],
                ['location', 'string', [50], ''],
                ['logo', 'mediumText', [], 'nullable'],
                ['image_front', 'mediumText', [], 'nullable'],
                ['site', 'string', [200], 'nullable'],
                ['copay_bill', 'enum', [['0', '1']], '', '1'],
                ['massiveBagsTransfer', 'enum', [['0', '1']], '', '0'],
                ['merchantFunc', 'enum', [['0', '1']], 'nullable', '0'],
                ['merchantKey', 'string', [50], 'nullable'],
                ['merchantSecret', 'string', [50], 'nullable'],
                ['npi', 'string', [12], 'nullable'],
                ['ncpdp', 'string', [12], 'nullable'],
                ['dea', 'string', [12], 'nullable'],
                ['merchant_agreement', 'mediumText', [], 'nullable'],
                ['pioneerrxFunc', 'enum', [['0', '1']], '', '0'],
                ['pioneerrxKey', 'string', [60], 'nullable'],
                ['pioneerrxSecret', 'string', [60], 'nullable'],
                ['bestrxFunc', 'enum', [['0', '1']], '', '0'],
                ['bestrxKey', 'string', [60], 'nullable'],
                ['bestrxSecret', 'string', [60], 'nullable'],
                ['bestrx_pharmacy_id', 'string', [40], 'nullable'],
                ['admin_id', 'bigInteger', [], 'nullable'],
                ['ref_id', 'bigInteger', [], 'nullable'],
                ['balance', 'float', [24], '', '0'],
                ['balance_ban', 'enum', [['0', '1']], '', '0'],
                ['schedule', 'text', [], 'nullable'],
                ['ip', 'string', [60], 'nullable'],
                ['zone_id', 'integer', [], 'nullable', 1],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'plans' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['name', 'string', [50], ''],
                ['order_rate', 'integer', [], ''],
                ['tariff', 'float', [24], ''],
                ['tariff_next_day', 'float', [24], ''],
                ['tariff_same_day', 'float', [24], ''],
                ['tariff_asap', 'float', [24], ''],
                ['tariff_after_hours', 'float', [24], ''],
                ['tariff_fridge', 'float', [24], ''],
                ['tariff_area2', 'float', [24], ''],
                ['tariff_area3', 'float', [24], ''],
                ['tariff_area_more', 'float', [24], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'route_templates' => [
            'columns' => [
                ['id', 'bigInteger', [], 'unsigned autoIncrement'],
                ['name', 'string', [191], ''],
                ['created_at', 'timestamp', [], 'nullable'],
                ['updated_at', 'timestamp', [], 'nullable'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'routes' => [
            'columns' => [
                ['id', 'bigInteger', [], 'autoIncrement'],
                ['created_at', 'timestamp', [], 'useCurrent'],
                ['finished_at', 'timestamp', [], 'nullable'],
                ['driver_id', 'integer', [], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'routes_priority' => [
            'columns' => [
                ['id', 'bigInteger', [], 'autoIncrement'],
                ['driver_id', 'integer', [], ''],
                ['order_id', 'bigInteger', [], ''],
                ['type', 'string', [50], ''],
                ['type_id', 'integer', [], ''],
                ['type_pay', 'integer', [], '', 1],
                ['pay_value', 'float', [24], '', '1'],
                ['priority', 'integer', [], ''],
                ['eta', 'bigInteger', [], 'nullable'],
                ['pharmacy_route_id', 'bigInteger', [], 'nullable unsigned'],
                ['pharmacy_route_item_id', 'bigInteger', [], 'nullable unsigned'],
            ],
            'indexes' => [
                ['index', ['driver_id'], 'driver_id'],
                ['index', ['id'], 'id'],
                ['index', ['order_id'], 'order_id'],
                ['index', ['pharmacy_route_item_id'], 'rp_pharmacy_item_idx'],
                ['index', ['pharmacy_route_id'], 'rp_pharmacy_route_idx'],
                ['index', ['type_id'], 'type_id'],
            ],
            'foreign' => [],
        ],
        'routes_priority_logs' => [
            'columns' => [
                ['id', 'bigInteger', [], ''],
                ['created', 'timestamp', [], 'useCurrent'],
                ['driver_id', 'integer', [], ''],
                ['order_id', 'bigInteger', [], ''],
                ['type', 'string', [50], ''],
                ['type_id', 'integer', [], ''],
                ['type_pay', 'integer', [], '', 1],
                ['pay_value', 'float', [24], '', '1'],
                ['pharmacy_route_id', 'bigInteger', [], 'nullable unsigned'],
                ['pharmacy_route_item_id', 'bigInteger', [], 'nullable unsigned'],
            ],
            'indexes' => [
                ['index', ['driver_id'], 'driver_id'],
                ['index', ['id'], 'id'],
                ['index', ['order_id'], 'order_id'],
                ['primary', ['id'], null],
                ['index', ['pharmacy_route_item_id'], 'rpl_pharmacy_item_idx'],
                ['index', ['pharmacy_route_id'], 'rpl_pharmacy_route_idx'],
                ['index', ['type_id'], 'type_id'],
            ],
            'foreign' => [],
        ],
        'rxs' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['order_id', 'integer', [], ''],
                ['rx_id', 'string', [20], 'nullable'],
                ['rx_date', 'date', [], 'nullable'],
                ['rx_count', 'smallInteger', [], '', 1],
                ['rx_recipient', 'integer', [], 'nullable'],
                ['rx_copay', 'float', [24], '', '0'],
            ],
            'indexes' => [
                ['index', ['id'], 'id'],
                ['index', ['order_id'], 'order_id'],
                ['index', ['rx_id'], 'rx_id'],
            ],
            'foreign' => [],
        ],
        'sessions' => [
            'columns' => [
                ['id', 'string', [191], ''],
                ['user_id', 'bigInteger', [], 'nullable unsigned'],
                ['ip_address', 'string', [45], 'nullable'],
                ['user_agent', 'text', [], 'nullable'],
                ['payload', 'longText', [], ''],
                ['last_activity', 'integer', [], ''],
            ],
            'indexes' => [
                ['primary', ['id'], null],
                ['index', ['last_activity'], 'sessions_last_activity_index'],
                ['index', ['user_id'], 'sessions_user_id_index'],
            ],
            'foreign' => [],
        ],
        'states' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['name', 'string', [50], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'statuses' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['name', 'string', [50], ''],
                ['color', 'string', [10], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'statuses_copay' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['name', 'string', [50], ''],
                ['color', 'string', [10], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'tokens_driver' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['user_id', 'integer', [], ''],
                ['token', 'string', [50], ''],
            ],
            'indexes' => [
                ['index', ['id'], 'id'],
                ['index', ['user_id'], 'user_id'],
            ],
            'foreign' => [],
        ],
        'try_call' => [
            'columns' => [
                ['id', 'bigInteger', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['order_id', 'bigInteger', [], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'user_codes' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['user_id', 'bigInteger', [], ''],
                ['code', 'string', [10], ''],
                ['created_at', 'timestamp', [], 'useCurrent'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'users' => [
            'columns' => [
                ['id', 'bigInteger', [], 'unsigned autoIncrement'],
                ['name', 'string', [191], ''],
                ['last_name', 'string', [50], ''],
                ['email', 'string', [191], ''],
                ['phone', 'string', [20], ''],
                ['home_phone', 'string', [20], 'nullable'],
                ['birth_date', 'date', [], 'nullable'],
                ['email_verified_at', 'timestamp', [], 'nullable'],
                ['password', 'string', [191], ''],
                ['isactive', 'integer', [], '', 1],
                ['isblocked', 'integer', [], '', 0],
                ['image', 'string', [500], '', '/images/users/default-user-image.png'],
                ['primary_address', 'tinyInteger', [], '', 1],
                ['address', 'string', [100], 'nullable'],
                ['location', 'string', [50], 'nullable'],
                ['apartment', 'string', [50], 'nullable'],
                ['city', 'string', [50], 'nullable'],
                ['state', 'string', [50], 'nullable'],
                ['zip', 'string', [30], 'nullable'],
                ['address2', 'string', [100], 'nullable'],
                ['location2', 'string', [50], 'nullable'],
                ['apartment2', 'string', [50], 'nullable'],
                ['zip2', 'string', [30], 'nullable'],
                ['address3', 'string', [100], 'nullable'],
                ['location3', 'string', [50], 'nullable'],
                ['apartment3', 'string', [50], 'nullable'],
                ['zip3', 'string', [30], 'nullable'],
                ['role', 'string', [20], '', 'user'],
                ['pharmacy_id', 'integer', [], 'nullable'],
                ['office_id', 'integer', [], 'nullable', 1],
                ['driving_license', 'string', [30], 'nullable'],
                ['driving_license_img', 'mediumText', [], 'nullable'],
                ['identification_cards', 'string', [30], 'nullable'],
                ['transport', 'enum', [['1', '2']], 'nullable', '1'],
                ['car_info', 'string', [150], 'nullable'],
                ['car_img', 'mediumText', [], 'nullable'],
                ['payment_card', 'string', [30], 'nullable'],
                ['os', 'integer', [], 'nullable'],
                ['device_token', 'string', [500], 'nullable'],
                ['work_now', 'integer', [], '', 0],
                ['route_status', 'string', [15], 'nullable'],
                ['call_ready', 'enum', [['0', '1']], 'nullable', '1'],
                ['remember_token', 'string', [100], 'nullable'],
                ['ip', 'string', [60], 'nullable'],
                ['last_login', 'timestamp', [], 'nullable'],
                ['quickbook_id', 'bigInteger', [], 'nullable'],
                ['auth_chat_id', 'string', [50], 'nullable'],
                ['created_at', 'timestamp', [], 'nullable useCurrent'],
                ['updated_at', 'timestamp', [], 'nullable'],
                ['active_status', 'timestamp', [], 'nullable'],
                ['avatar', 'string', [191], ''],
                ['dark_mode', 'tinyInteger', [], '', 0],
                ['messenger_color', 'string', [191], 'nullable'],
                ['zone_id', 'integer', [], 'nullable'],
            ],
            'indexes' => [
                ['index', ['id'], 'id'],
                ['index', ['office_id'], 'office_id'],
                ['index', ['pharmacy_id'], 'pharmacy_id'],
            ],
            'foreign' => [],
        ],
        'white_list_ip' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['ip', 'string', [50], ''],
                ['created_at', 'timestamp', [], 'useCurrent'],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'wishes' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['category_id', 'integer', [], ''],
                ['text', 'string', [200], ''],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'wishes_category' => [
            'columns' => [
                ['id', 'integer', [], 'autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['name', 'string', [100], ''],
                ['status', 'integer', [], '', 0],
            ],
            'indexes' => [],
            'foreign' => [],
        ],
        'messages' => [
            'columns' => [
                ['id', 'bigInteger', [], 'unsigned autoIncrement'],
                ['user_id', 'bigInteger', [], 'unsigned'],
                ['message', 'text', [], ''],
                ['created_at', 'timestamp', [], 'nullable'],
                ['updated_at', 'timestamp', [], 'nullable'],
            ],
            'indexes' => [
                ['index', ['user_id'], 'messages_user_id_foreign'],
            ],
            'foreign' => [
                [['user_id'], 'users', ['id'], 'no action', 'no action', 'messages_user_id_foreign'],
            ],
        ],
        'orders' => [
            'columns' => [
                ['id', 'bigInteger', [], 'unsigned autoIncrement'],
                ['created', 'timestamp', [], 'useCurrent'],
                ['finish', 'timestamp', [], 'nullable'],
                ['pharmacy_id', 'integer', [], ''],
                ['user_id', 'integer', [], ''],
                ['type_driver', 'integer', [], '', 1],
                ['driver_id', 'integer', [], 'nullable'],
                ['statuse_id', 'integer', [], '', 1],
                ['rx', 'string', [500], '', '0'],
                ['rating', 'integer', [], 'nullable'],
                ['special_instructions', 'mediumText', [], 'nullable'],
                ['dispatcher_notes', 'mediumText', [], 'nullable'],
                ['copay', 'float', [24], '', '0.00'],
                ['statuse_copay', 'integer', [], 'nullable'],
                ['tariff', 'float', [24], 'nullable'],
                ['delivery_method_id', 'integer', [], '', 1],
                ['delivery_time_id', 'integer', [], '', 1],
                ['delivery_date', 'date', [], 'nullable'],
                ['delivery_time_range', 'string', [20], 'nullable'],
                ['signature', 'integer', [], '', 1],
                ['fridge', 'integer', [], '', 0],
                ['count_bags', 'integer', [], '', 1],
                ['drop_off_photo', 'string', [300], 'nullable'],
                ['signature_photo', 'string', [300], 'nullable'],
                ['signature_type', 'string', [30], 'nullable'],
                ['family_id', 'integer', [], 'nullable'],
                ['not_delivered', 'integer', [], '', 0],
                ['ready', 'enum', [['0', '1', '2']], '', '0'],
                ['extra_charge_driver', 'float', [24], 'nullable', '0'],
                ['eta', 'bigInteger', [], 'nullable'],
                ['actual', 'enum', [['0', '1', '2']], '', '0'],
                ['invoice_payed', 'enum', [['0', '1']], 'nullable', '0'],
                ['merchantOrder', 'enum', [['0', '1']], 'nullable', '0'],
                ['facility', 'tinyInteger', [], 'nullable'],
                ['medic_id', 'integer', [], 'nullable'],
                ['delivery_address', 'string', [100], 'nullable'],
                ['delivery_location', 'string', [50], 'nullable'],
                ['bestrx_order_id', 'string', [20], 'nullable'],
            ],
            'indexes' => [
                ['index', ['delivery_method_id'], 'delivery_method_id'],
                ['index', ['delivery_time_id'], 'delivery_time_id'],
                ['index', ['driver_id'], 'driver_id'],
                ['index', ['family_id'], 'family_id'],
                ['index', ['id'], 'id'],
                ['index', ['pharmacy_id'], 'pharmacy_id'],
                ['index', ['statuse_id'], 'statuse_id'],
                ['index', ['user_id'], 'user_id'],
            ],
            'foreign' => [
                [['statuse_id'], 'statuses', ['id'], 'no action', 'no action', 'orders_ibfk_1'],
                [['delivery_method_id'], 'delivery_methods', ['id'], 'no action', 'no action', 'orders_ibfk_2'],
                [['delivery_time_id'], 'delivery_times', ['id'], 'no action', 'no action', 'orders_ibfk_3'],
                [['family_id'], 'family_members', ['id'], 'no action', 'no action', 'orders_ibfk_4'],
            ],
        ],
        'route_template_items' => [
            'columns' => [
                ['id', 'bigInteger', [], 'unsigned autoIncrement'],
                ['route_template_id', 'bigInteger', [], 'unsigned'],
                ['type', 'enum', [['pharmacy', 'patient', 'office']], ''],
                ['type_id', 'bigInteger', [], 'unsigned'],
                ['priority', 'integer', [], '', 0],
                ['created_at', 'timestamp', [], 'nullable'],
                ['updated_at', 'timestamp', [], 'nullable'],
            ],
            'indexes' => [
                ['index', ['route_template_id'], 'route_template_items_route_template_id_foreign'],
            ],
            'foreign' => [
                [['route_template_id'], 'route_templates', ['id'], 'cascade', 'no action', 'route_template_items_route_template_id_foreign'],
            ],
        ],
    ];

    public function up(): void
    {
        $this->apply(Schema::connection($this->getConnection()));
    }

    public function down(): void
    {
        // These tables hold the application's data and were there before this migration. Nothing is dropped.
    }

    public function apply(Builder $schema): void
    {
        $sqlite = $schema->getConnection()->getDriverName() === 'sqlite';

        foreach (self::TABLES as $name => $definition) {
            if (! $schema->hasTable($name)) {
                $schema->create($name, function (Blueprint $table) use ($name, $definition, $sqlite): void {
                    foreach ($definition['columns'] as $column) {
                        $this->column($table, $column);
                    }
                    foreach ($definition['indexes'] as [$kind, $columns, $indexName]) {
                        if ($sqlite && in_array($kind, ['fullText', 'spatialIndex'], true)) {
                            continue;
                        }
                        // MySQL index names belong to their table; SQLite needs them unique in the database.
                        if ($sqlite && $indexName !== null && ! str_starts_with($indexName, $name.'_')) {
                            $indexName = $name.'_'.$indexName;
                        }
                        $table->{$kind}($columns, $indexName);
                    }
                    foreach ($definition['foreign'] as [$columns, $on, $references, $onDelete, $onUpdate, $keyName]) {
                        $table->foreign($columns, $keyName)->references($references)->on($on)->onDelete($onDelete)->onUpdate($onUpdate);
                    }
                });

                continue;
            }

            $missing = array_values(array_filter($definition['columns'], fn (array $column): bool => ! $schema->hasColumn($name, $column[0])));
            if ($missing !== []) {
                $schema->table($name, function (Blueprint $table) use ($missing, $sqlite): void {
                    foreach ($missing as $column) {
                        $this->column($table, $column, added: true, sqlite: $sqlite);
                    }
                });
            }
        }
    }

    /**
     * @param  array<int, mixed>  $column
     * @param  bool  $added  the column joins a table that already exists, so it cannot become its key
     */
    private function column(Blueprint $table, array $column, bool $added = false, bool $sqlite = false): void
    {
        [$name, $method, $arguments, $flags] = $column;
        $flags = explode(' ', $flags);
        $hasDefault = array_key_exists(4, $column) || in_array('useCurrent', $flags, true);

        $definition = $table->{$method}($name, ...$arguments);
        if (in_array('unsigned', $flags, true)) {
            $definition->unsigned();
        }
        if (in_array('autoIncrement', $flags, true) && ! $added) {
            $definition->autoIncrement();
        }
        // SQLite cannot add a required column without a default to an existing table.
        if (in_array('nullable', $flags, true) || ($added && $sqlite && ! $hasDefault)) {
            $definition->nullable();
        }
        if (in_array('useCurrent', $flags, true)) {
            $definition->useCurrent();
        } elseif (array_key_exists(4, $column)) {
            $definition->default($column[4]);
        }
    }
};
