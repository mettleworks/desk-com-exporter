## Desk.com customer and case data exporter

[![Build Status](https://travis-ci.org/mettleworks/desk-com-exporter.svg)](https://travis-ci.org/mettleworks/desk-com-exporter)
[![Latest Stable Version](https://poser.pugx.org/mettleworks/desk-com-exporter/v/stable.svg)](https://packagist.org/packages/mettleworks/desk-com-exporter) 
[![License](https://poser.pugx.org/mettleworks/desk-com-exporter/license.svg)](https://packagist.org/packages/mettleworks/desk-com-exporter)

### Installation

```
composer require mettleworks/desk-com-exporter
```

### Code Example

```php 
require 'vendor/autoload.php';

$deskUrl = 'https://YOUR-ACCOUNT.desk.com';
$email = 'example@example.org';
$password = 'your-password';

$client = new \GuzzleHttp\Client([
    'base_uri' => $deskUrl,
    'auth' => [
        $email,
        $password
    ]
]);

$exporter = new \Mettleworks\DeskComExporter\DeskComExporter($client);

$caseList = [];

$exporter->fetchCases(function($customers) use(&$caseList)
{
    foreach($customers['_embedded']['entries'] as $entry)
    {
        $caseList[$entry['id']] = true;
    }

    var_dump(count($caseList));
});

$customerList = [];

$exporter->fetchCases(function($customers) use(&$customerList)
{
    foreach($customers['_embedded']['entries'] as $entry)
    {
        $customerList[$entry['id']] = true;
    }

    var_dump(count($customerList));
});
```

### Testing

Run the tests with:

```bash
vendor/bin/phpunit
```


### Security

If you discover any security related issues, please email aivis@mettle.io.

### License

The package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
