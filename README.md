# Zkteko Laravel Package

A Laravel package for ZKTeco biometric devices, ported from a Python implementation.

## Installation

You can install the package via composer:

```bash
composer require anascloud/zkteko
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Anascloud\Zkteko\ZktekoServiceProvider" --tag="zkteko-config"
```

Set your device details in your `.env` file:

```env
ZKTEKO_IP=192.168.10.79
ZKTEKO_PORT=4370
ZKTEKO_PASSWORD=2837
```

## Usage

### Direct Usage

```php
use Anascloud\Zkteko\ZKTeco;

$zk = new ZKTeco('192.168.10.79', 4370, 2837);

if ($zk->connect()) {
    $attendance = $zk->getAttendance();
    
    foreach ($attendance as $log) {
        // Process logs
    }
    
    $zk->disconnect();
}
```

### Facade Usage

```php
use Anascloud\Zkteko\Facades\Zkteko;

if (Zkteko::connect()) {
    $attendance = Zkteko::getAttendance();
    Zkteko::disconnect();
}
```

## Credits

- Author: [anascloud](https://github.com/anascloud)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
