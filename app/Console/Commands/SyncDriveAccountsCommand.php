<?php

namespace App\Console\Commands;

use Database\Seeders\SamuelSeeder;
use Illuminate\Console\Command;

class SyncDriveAccountsCommand extends Command
{
    protected $signature = 'samuel:sync-drive-accounts';

    protected $description = 'Importa cuentas Google Drive desde SAMUEL_DRIVE_ACCOUNTS_JSON en .env';

    public function handle(): int
    {
        (new SamuelSeeder)->syncDriveAccountsFromConfig();

        $this->info('Cuentas Drive sincronizadas desde .env');

        return self::SUCCESS;
    }
}
