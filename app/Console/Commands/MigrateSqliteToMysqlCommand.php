<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateSqliteToMysqlCommand extends Command
{
    protected $signature = 'samuel:migrate-sqlite-to-mysql
                            {--sqlite= : Ruta al database.sqlite de origen}
                            {--mysql=mysql : Conexión Laravel destino}
                            {--dry-run : Solo comparar conteos, no copiar}
                            {--skip-sessions : No copiar la tabla sessions}
                            {--backup : Copiar sqlite a storage/backups antes de importar}';

    protected $description = 'Copia datos de SQLite a MySQL/MariaDB preservando IDs (migración one-shot).';

    /** @var list<string> */
    private const TABLE_ORDER = [
        'family_members',
        'drive_accounts',
        'albums',
        'memory_posts',
        'memory_media',
        'memory_reactions',
        'samuel_profiles',
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    private const SQLITE_IMPORT = 'sqlite_import';

    public function handle(): int
    {
        $sqlitePath = $this->resolveSqlitePath();
        if ($sqlitePath === null) {
            return self::FAILURE;
        }

        $mysql = (string) $this->option('mysql');
        if (! config("database.connections.{$mysql}")) {
            $this->error("Conexión destino desconocida: {$mysql}");

            return self::FAILURE;
        }

        $this->registerSqliteConnection($sqlitePath);

        if ($this->option('backup') && ! $this->option('dry-run')) {
            $this->backupSqlite($sqlitePath);
        }

        $source = DB::connection(self::SQLITE_IMPORT);
        $target = DB::connection($mysql);

        if (! $this->option('dry-run')) {
            if (! Schema::connection($mysql)->hasTable('migrations')) {
                $this->error('En MySQL no existe la tabla migrations. Ejecuta antes: php artisan migrate --force');

                return self::FAILURE;
            }
        }

        $tables = $this->tablesToCopy();
        $this->info('Tablas a procesar: '.implode(', ', $tables));

        $summary = [];
        foreach ($tables as $table) {
            if (! Schema::connection(self::SQLITE_IMPORT)->hasTable($table)) {
                $this->line("  [omitida] {$table} (no está en SQLite)");
                continue;
            }

            $srcCount = (int) $source->table($table)->count();
            $destCount = $this->countOnMysql($mysql, $target, $table);

            $summary[] = [$table, $srcCount, $destCount];

            if ($this->option('dry-run')) {
                continue;
            }

            if (! Schema::connection($mysql)->hasTable($table)) {
                $this->warn("  [omitida] {$table} (no existe en MySQL — ¿falta migrate?)");

                continue;
            }

            if ($srcCount === 0) {
                $this->line("  [vacía] {$table}");

                continue;
            }

            $this->copyTable($source, $target, $mysql, $table);
            $destCount = (int) $target->table($table)->count();
            $summary[count($summary) - 1][2] = $destCount;
        }

        $this->newLine();
        $this->table(['Tabla', 'SQLite', 'MySQL'], $summary);

        if ($this->option('dry-run')) {
            $this->comment('Dry-run: no se copió nada.');

            return self::SUCCESS;
        }

        $this->info('Importación terminada.');

        return self::SUCCESS;
    }

    private function countOnMysql(string $mysql, $target, string $table): int|string
    {
        try {
            if (! Schema::connection($mysql)->hasTable($table)) {
                return 0;
            }

            return (int) $target->table($table)->count();
        } catch (\Throwable) {
            return 'n/a';
        }
    }

    private function resolveSqlitePath(): ?string
    {
        $path = (string) ($this->option('sqlite') ?: database_path('database.sqlite'));
        $path = realpath($path) ?: $path;

        if (! is_file($path)) {
            $this->error("No se encontró SQLite: {$path}");

            return null;
        }

        return $path;
    }

    private function registerSqliteConnection(string $path): void
    {
        config([
            'database.connections.'.self::SQLITE_IMPORT => [
                'driver' => 'sqlite',
                'database' => $path,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge(self::SQLITE_IMPORT);
    }

    private function backupSqlite(string $path): void
    {
        $dir = storage_path('backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $dest = $dir.'/sqlite-'.now()->format('Ymd-His').'.sqlite';
        if (! copy($path, $dest)) {
            $this->warn('No se pudo crear backup en '.$dest);

            return;
        }

        $this->info('Backup SQLite: '.$dest);
    }

    /** @return list<string> */
    private function tablesToCopy(): array
    {
        $tables = self::TABLE_ORDER;
        if ($this->option('skip-sessions')) {
            $tables = array_values(array_filter($tables, fn ($t) => $t !== 'sessions'));
        }

        return $tables;
    }

    private function copyTable($source, $target, string $mysqlConnection, string $table): void
    {
        $this->line("  Copiando {$table}…");

        $target->statement('SET FOREIGN_KEY_CHECKS=0');
        $target->table($table)->delete();

        $rows = $source->table($table)->get();
        foreach ($rows->chunk(200) as $chunk) {
            $payload = $chunk->map(fn ($row) => (array) $row)->all();
            if ($payload !== []) {
                $target->table($table)->insert($payload);
            }
        }

        if (Schema::connection($mysqlConnection)->hasColumn($table, 'id')) {
            $maxId = $target->table($table)->max('id');
            if ($maxId !== null) {
                $target->statement('ALTER TABLE `'.$table.'` AUTO_INCREMENT = '.((int) $maxId + 1));
            }
        }

        $target->statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
