<?php

namespace App\Core;

class Migrations
{
    public static function up()
    {
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        // 1. Users Table (Admin Credentials)
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');

        // 2. Routers (Sessions) Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS routers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_name TEXT NOT NULL UNIQUE,
            ip_address TEXT,
            username TEXT,
            password TEXT,
            hotspot_name TEXT,
            dns_name TEXT,
            currency TEXT DEFAULT 'RP',
            reload_interval INTEGER DEFAULT 60,
            interface TEXT,
            description TEXT,
            quick_access INTEGER DEFAULT 0,
            port INTEGER DEFAULT 8728,
            ssl INTEGER DEFAULT 0
        )");

        // 3. Quick Access (Dashboard Shortcuts)
        $pdo->exec("CREATE TABLE IF NOT EXISTS quick_access (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            label TEXT NOT NULL,
            url TEXT NOT NULL,
            icon TEXT,
            category TEXT DEFAULT 'general',
            active INTEGER DEFAULT 1
        )");

        // 4. Settings (Key-Value Store)
        $pdo->exec('CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        )');

        // 5. Logos (Branding)
        $pdo->exec('CREATE TABLE IF NOT EXISTS logos (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            path TEXT NOT NULL,
            type TEXT,
            size INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');

        // 6. Quick Prints (Voucher Printing Profiles)
        $pdo->exec("CREATE TABLE IF NOT EXISTS quick_prints (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            router_id INTEGER,
            session_name TEXT NOT NULL,
            name TEXT NOT NULL,
            server TEXT NOT NULL,
            profile TEXT NOT NULL,
            prefix TEXT DEFAULT '',
            char_length INTEGER DEFAULT 4,
            price INTEGER DEFAULT 0,
            selling_price INTEGER DEFAULT 0,
            time_limit TEXT DEFAULT '',
            data_limit TEXT DEFAULT '',
            comment TEXT DEFAULT '',
            color TEXT DEFAULT 'bg-blue-500',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // 7. Voucher Templates
        $pdo->exec('CREATE TABLE IF NOT EXISTS voucher_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            router_id INTEGER,
            session_name TEXT NOT NULL,
            name TEXT NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');

        // 8. API CORS Rules
        $pdo->exec("CREATE TABLE IF NOT EXISTS api_cors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            origin TEXT NOT NULL,
            methods TEXT DEFAULT '[\"GET\",\"POST\"]',
            headers TEXT DEFAULT '[\"*\"]',
            max_age INTEGER DEFAULT 3600,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // 9. Router Probe Logs (health history, 7-day retention)
        $pdo->exec('CREATE TABLE IF NOT EXISTS router_probe_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            router_id INTEGER NOT NULL,
            checked_at DATETIME NOT NULL,
            status TEXT NOT NULL,
            cpu_load REAL,
            uptime TEXT,
            response_ms INTEGER
        )');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_probe_logs_router ON router_probe_logs(router_id, checked_at)');

        // 10. Router Events (status transitions & alerts)
        $pdo->exec('CREATE TABLE IF NOT EXISTS router_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            router_id INTEGER NOT NULL,
            event_type TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_router_events_router ON router_events(router_id, created_at)');

        // 11. Additive migration: ensure new columns exist on upgrades
        // (CREATE TABLE IF NOT EXISTS won't add columns to existing tables)
        self::ensureColumn($pdo, 'routers', 'port', 'INTEGER DEFAULT 8728');
        self::ensureColumn($pdo, 'routers', 'ssl', 'INTEGER DEFAULT 0');

        // 12. Per-session scoping untuk voucher templates & logos
        self::ensureColumn($pdo, 'voucher_templates', 'session_id', 'INTEGER NULL REFERENCES routers(id)');
        self::ensureColumn($pdo, 'logos', 'session_id', 'INTEGER NULL REFERENCES routers(id)');

        // Session Dashboard: active-users snapshots (sampler 5 menit)
        $pdo->exec("CREATE TABLE IF NOT EXISTS session_activity_snapshots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_name TEXT NOT NULL,
            active_users INTEGER NOT NULL DEFAULT 0,
            recorded_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_sas_session_time
            ON session_activity_snapshots(session_name, recorded_at)');

        return true;
    }

    /**
     * Add a column to a table only if it doesn't already exist (SQLite).
     *
     * @param \PDO   $pdo
     * @param string $table
     * @param string $column
     * @param string $definition
     */
    private static function ensureColumn(\PDO $pdo, $table, $column, $definition)
    {
        $existing = $pdo->query("PRAGMA table_info({$table})")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($existing as $row) {
            if (($row['name'] ?? '') === $column) {
                return; // already present
            }
        }

        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}
