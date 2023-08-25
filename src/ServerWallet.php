<?php

namespace IAMXID\IamxServerWallet;

use Illuminate\Support\Facades\File;
use Spatie\Crypto\Rsa\Exceptions\CouldNotDecryptData;
use Spatie\Crypto\Rsa\KeyPair;
use Spatie\Crypto\Rsa\PrivateKey;
use Spatie\Crypto\Rsa\PublicKey;

class ServerWallet
{
    private PublicKey $publicKey;
    private PrivateKey $privateKey;

    private $identity;

    private $scope;

    public function __construct()
    {
        $iamxWalletKeyDir = storage_path('/iamx_wallet/keys');
        $iamxIdentityDir = storage_path('/iamx_wallet/identity');

        $pathToPrivateKey = $iamxWalletKeyDir.'/private_key.pem';
        $pathToPublicKey = $iamxWalletKeyDir.'/public_key.pem';
        $this->identity = $iamxIdentityDir.'/identity.json';

        $this->scope = [
            'did' => '', 'person' => ['issuer', 'firstname', 'lastname', 'birthdate'], 'address' => [], 'email' => []
        ];

        if (!File::exists($pathToPrivateKey)) {
            (new KeyPair(OPENSSL_ALGO_SHA256, 2048, OPENSSL_KEYTYPE_RSA))
                ->generate($pathToPrivateKey, $pathToPublicKey);
        }

        $this->privateKey = PrivateKey::fromFile($pathToPrivateKey);
        $this->publicKey = PublicKey::fromFile($pathToPublicKey);
    }

    public function setScope(array $scope)
    {
        $this->scope = $scope;
    }

    /**
     * @throws CouldNotDecryptData
     */
    public function getScopedIdentity()
    {
        $identityArray = [];

        $identity = json_decode(file_get_contents($this->identity), true);
        $decryptedIdentity = $this->decryptIdentityData($identity, $this->privateKey);

        $topScopeArray = [];
        foreach ($this->scope as $key => $value) {
            $topScopeArray[] = $key;
        }

        foreach ($decryptedIdentity as $key => $value) {
            if (in_array($key, $topScopeArray)) {
                if (count($this->scope[$key]) == 0) {
                    if (is_array($value)) {
                        foreach ($value as $itemKey => $item) {
                            $identityArray[$key][$itemKey] = $item;
                        }
                    } else {
                        $identityArray[] = $value;
                    }
                } else {
                    foreach ($this->scope[$key] as $subScope) {
                        $identityArray[$key][$subScope] = $value[$subScope];
                    }
                }
            }
        }
        $identityArray['did'] = $decryptedIdentity['DIDDocument']['ipfs'];


        return $identityArray;
    }

    /**
     * @throws CouldNotDecryptData
     */
    private function decryptIdentityData($data, PrivateKey $privateKey)
    {
        $newData = [];
        foreach ($data as $key => $value) {
            if ($key == 'DIDDocument') {
                $newData[$key] = $value;
            } else {
                if (is_object($value) || is_array($value)) {
                    $newData[$key] = $this->decryptIdentityData($value, $privateKey);
                } else {
                    if (!str_contains($key, 'verification')) {
                        $newData[$key] = str_replace('+', ' ', $privateKey->decrypt(base64_decode($value)));
                    }
                }
            }
        }
        return $newData;
    }

    /**
     * @throws CouldNotDecryptData
     */
    public function decrypt($data): ?string
    {
        $data = base64_decode($data);
        if ($this->privateKey->canDecrypt($data)) {
            return str_replace('+', ' ', $this->privateKey->decrypt($data));
        } else {
            return null;
        }
    }

    public function sign($data): string
    {
        $data = str_replace(' ', '+', $data);
        return base64_encode($this->privateKey->sign($data));
    }

    public function verify($data, $signature): bool
    {
        $data = str_replace(' ', '+', $data);
        return $this->publicKey->verify($data, base64_decode($signature));
    }

    public function encrypt($data): string
    {
        $data = str_replace(' ', '+', $data);
        return base64_encode($this->publicKey->encrypt($data));
    }
}