<?php

namespace IAMXID\IamxServerWallet\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Spatie\Crypto\Rsa\KeyPair;
use Spatie\Crypto\Rsa\PublicKey;

class IamxCreateWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iamx:create-wallet {uuid} {pin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new IAMX server wallet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $uuid = $this->argument('uuid');
            $pin = $this->argument('pin');

            $iamxWalletKeyDir = storage_path('/iamx_wallet/keys');
            $iamxIdentityDir = storage_path('/iamx_wallet/identity');


            if (!is_dir($iamxWalletKeyDir)) {
                mkdir($iamxWalletKeyDir, 0777, true);
            }

            if (!is_dir($iamxIdentityDir)) {
                mkdir($iamxIdentityDir, 0777, true);
            }

            $pathToPrivateKey = $iamxWalletKeyDir.'/private_key.pem';
            $pathToPublicKey = $iamxWalletKeyDir.'/public_key.pem';

            if (!File::exists($pathToPrivateKey)) {
                (new KeyPair(OPENSSL_ALGO_SHA256, 2048, OPENSSL_KEYTYPE_RSA))
                    ->generate($pathToPrivateKey, $pathToPublicKey);
            }

            $response = Http::get('https://kyc.iamx.id/api/did/'.$uuid.'/'.$pin);
            $bodyJSON = (array) json_decode($response->body());

            if (isset($bodyJSON[0])) {
                $identityData = (array) json_decode($bodyJSON[0]);
            } else {
                throw new Exception('Could not find UUID and password combination.');
            }

            $concatedData = $this->concatWalletValues($identityData);

            $hash = openssl_digest($concatedData, 'SHA256');

            $identityData['hash'] = $hash;

            $didResponse = Http::post('https://nftidentityservice.iamx.id/did/create', ['hash' => $hash]);
            $didDocument = json_decode($didResponse->body(), true);

            $identityData['DIDDocument'] = $didDocument;

            $publicKey = PublicKey::fromFile($pathToPublicKey);
            $encryptedIdentityData = $this->encryptIdentityData($identityData, $publicKey);

            // Save the encrypted identity on disk
            File::put($iamxIdentityDir.'/identity.json', json_encode($encryptedIdentityData));
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    private function concatWalletValues($elements)
    {
        $concatedValues = '';
        foreach ($elements as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $concatedValues .= $this->concatWalletValues($value);
            } else {
                $concatedValues .= strtolower(str_replace(' ', '', $value));
            }
        }
        return $concatedValues;
    }

    private function encryptIdentityData($data, PublicKey $publicKey)
    {
        $newData = [];
        foreach ($data as $key => $value) {
            if ($key == 'DIDDocument') {
                $newData[$key] = $value;
            } else {
                if (is_object($value) || is_array($value)) {
                    $newData[$key] = $this->encryptIdentityData($value, $publicKey);
                } else {
                    $newData[$key] = base64_encode($publicKey->encrypt(str_replace(' ', '+', $value)));
                }
            }
        }
        return $newData;
    }

    private function generate_vUID($identity)
    {
        $vUID = url('')
            .$identity['person']->firstname
            .$identity['person']->lastname
            .$identity['person']->birthplace
            .$identity['person']->birthdate
            .$identity['person']->nationality_iso;

        $vUID_hash = openssl_digest($vUID, 'SHA256');

        return $vUID_hash;
    }
}
