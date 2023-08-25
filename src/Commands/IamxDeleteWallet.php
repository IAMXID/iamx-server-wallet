<?php

namespace IAMXID\IamxServerWallet\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class IamxDeleteWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iamx:delete-wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all data of an existing IAMX server wallet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Delete private key
        $privateKeyFile = storage_path('/iamx_wallet/keys/private_key.pem');
        File::delete($privateKeyFile);

        // Delete public key
        $publicKeyFile = storage_path('/iamx_wallet/keys/public_key.pem');
        File::delete($publicKeyFile);

        // Delete identity file
        $identityFile = storage_path('/iamx_wallet/identity/identity.json');
        File::delete($identityFile);
    }
}
