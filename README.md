# iamx-server-wallet

IAMX server wallet is a Laravel package to create and manage your IAMX wallet in your laravel application.

- [IAMX-server-wallet](#iamx-server-wallet)
    - [Installation](#Installation)
    - [Configuration](#Configuration)
    - [Usage](#Usage)
    - [Bugs, Suggestions, Contributions and Support](#bugs-and-suggestions)
    - [Copyright and License](#copyright-and-license)

## Installation

Install the current version of the `iamxid/iamx-server-wallet` package via composer:

```sh
    composer require iamxid/iamx-server-wallet:dev-main
```

## Configuration

No configuration needed

## Usage

You do receive a UUID and a PIN after completing your KYC process at https://kyc.iamx.id.

To create your server wallet using your KYC data you need to run the following command:

```php
php artisan iamx:create-wallet <UUID> <PIN>
```

This will create your public and private key file and your encrypted identity data in two subfolders of the application
storage folder:

```
├──storage
├────iamx_wallet
├──────identity   # identity.json
├──────keys       # private_key.pem and public_key.pem
```

Just call the command iamx:delete-wallet if you want to delete your server wallet.

```php
php artisan iamx:delete-wallet
```

## Examples

Import the ServerWallet Facade in any controller you like to use the server wallet.

Fetch a defined scope of your identity:

```php
<?php

namespace App\Http\Controllers;

use IAMXID\IamxServerWallet\Facades\ServerWallet;

class TestController extends Controller
{
    public function test()
    {
        ServerWallet::setScope(['did' => '', 'person' => [], 'address' => []]);

        $identityArray = ServerWallet::getScopedIdentity();

        dd($identityArray);
    }
}
```

Encrypt and decrypt data using your iamx server wallet:

```php
<?php

namespace App\Http\Controllers;

use IAMXID\IamxServerWallet\Facades\ServerWallet;

class TestController extends Controller
{
    public function test()
    {
        $encrypted = ServerWallet::encrypt('This is a test message');
        echo $encrypted."<br><br>";

        $decrypted = ServerWallet::decrypt($encrypted);
        echo $decrypted;
    }
}
```

Sign and verify data using your iamx server wallet:

```php
<?php

namespace App\Http\Controllers;

use IAMXID\IamxServerWallet\Facades\ServerWallet;

class TestController extends Controller
{
    public function test()
    {
        $signature = ServerWallet::sign('This is a test message');
        echo $signature."<br>";
        
        $verify = ServerWallet::verify('This is a test message', $signature);
        
        if ($verify) {
            echo "verified";
        } else {
            echo "not verified";
        }
    }
}
```

## Bugs and Suggestions

## Copyright and License

[MIT](https://choosealicense.com/licenses/mit/)
