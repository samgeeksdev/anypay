
# Laravel Anypay  

## Install

Via Composer

``` bash
$ composer require samgeeksdev/anypay
```

## Publish Vendor Files

- **publish configuration files:**
``` bash
php artisan vendor:publish --tag=Anypay-config
```

 - **publish views for customization:**
``` bash
php artisan vendor:publish --tag=Anypay-views
```

## Configure

If you are using `Laravel 5.5` or higher then you don't need to add the provider and alias. (Skip to b)

a. In your `config/app.php` file add these two lines.

```php
// In your providers array.
'providers' => [
    ...
    samgeeksdev\Anypay\Provider\AnypayServiceProvider::class,
],

// In your aliases array.
'aliases' => [
    ...
    'Anypay' => samgeeksdev\Anypay\Facade\Anypay::class,
],
```

In the config file you can set the `default driver` to use for all your Anypays. But you can also change the driver at runtime.

Choose what gateway you would like to use in your application. Then make that as default driver so that you don't have to specify that everywhere. But, you can also use multiple gateways in a project.

```php
// Eg. if you want to use zarinpal.
'default' => 'zarinpal',
```

Then fill the credentials for that gateway in the drivers array.

```php
'drivers' => [
    'zarinpal' => [
        // Fill in the credentials here.
        'apiPurchaseUrl' => 'https://www.zarinpal.com/pg/rest/WebGate/AnypayRequest.json',
        'apiAnypayUrl' => 'https://www.zarinpal.com/pg/StartPay/',
        'apiVerificationUrl' => 'https://www.zarinpal.com/pg/rest/WebGate/AnypayVerification.json',
        'merchantId' => '',
        'callbackUrl' => 'http://yoursite.com/path/to',
        'description' => 'Anypay in '.config('app.name'),
    ],
    ...
]
```

## How to use

your `Invoice` holds your Anypay details, so initially we'll talk about `Invoice` class. 

#### Working with invoices

before doing any thing you need to use `Invoice` class to create an invoice.


In your code, use it like the below:

```php
// At the top of the file.
use samgeeksdev\Anypay\Invoice;
...

// Create new invoice.
$invoice = new Invoice;

// Set invoice amount.
$invoice->amount(1000);

// Add invoice details: There are 4 syntax available for this.
// 1
$invoice->detail(['detailName' => 'your detail goes here']);
// 2 
$invoice->detail('detailName','your detail goes here');
// 3
$invoice->detail(['name1' => 'detail1','name2' => 'detail2']);
// 4
$invoice->detail('detailName1','your detail1 goes here')
        ->detail('detailName2','your detail2 goes here');

```
available methods:

- `uuid`: set the invoice unique id
- `getUuid`: retrieve the invoice current unique id
- `detail`: attach some custom details into invoice
- `getDetails`: retrieve all custom details 
- `amount`: set the invoice amount
- `getAmount`: retrieve invoice amount
- `transactionId`: set invoice Anypay transaction id
- `getTransactionId`: retrieve Anypay transaction id
- `via`: set a driver we use to pay the invoice
- `getDriver`: retrieve the driver

#### Purchase invoice
In order to pay the invoice, we need the Anypay transactionId.
We purchase the invoice to retrieve transaction id:

```php
// At the top of the file.
use samgeeksdev\Anypay\Invoice;
use samgeeksdev\Anypay\Facade\Anypay;
...

// Create new invoice.
$invoice = (new Invoice)->amount(1000);

// Purchase the given invoice.
Anypay::purchase($invoice,function($driver, $transactionId) {
	// We can store $transactionId in database.
});

// Purchase method accepts a callback function.
Anypay::purchase($invoice, function($driver, $transactionId) {
    // We can store $transactionId in database.
});

// You can specify callbackUrl
Anypay::callbackUrl('http://yoursite.com/verify')->purchase(
    $invoice, 
    function($driver, $transactionId) {
    	// We can store $transactionId in database.
	}
);
```

#### Pay invoice

After purchasing the invoice, we can redirect the user to the bank Anypay page:

```php
// At the top of the file.
use samgeeksdev\Anypay\Invoice;
use samgeeksdev\Anypay\Facade\Anypay;
...

// Create new invoice.
$invoice = (new Invoice)->amount(1000);
// Purchase and pay the given invoice.
// You should use return statement to redirect user to the bank page.
return Anypay::purchase($invoice, function($driver, $transactionId) {
    // Store transactionId in database as we need it to verify Anypay in the future.
})->pay()->render();

// Do all things together in a single line.
return Anypay::purchase(
    (new Invoice)->amount(1000), 
    function($driver, $transactionId) {
    	// Store transactionId in database.
        // We need the transactionId to verify Anypay in the future.
	}
)->pay()->render();

// Retrieve json format of Redirection (in this case you can handle redirection to bank gateway)
return Anypay::purchase(
    (new Invoice)->amount(1000), 
    function($driver, $transactionId) {
    	// Store transactionId in database.
        // We need the transactionId to verify Anypay in the future.
	}
)->pay()->toJson();
```

#### Verify Anypay

When user has completed the Anypay, the bank redirects them to your website, then you need to **verify your Anypay** in order to ensure the `invoice` has been **paid**.

```php
// At the top of the file.
use samgeeksdev\Anypay\Facade\Anypay;
use samgeeksdev\Anypay\Exceptions\InvalidAnypayException;
...

// You need to verify the Anypay to ensure the invoice has been paid successfully.
// We use transaction id to verify Anypays
// It is a good practice to add invoice amount as well.
try {
	$receipt = Anypay::amount(1000)->transactionId($transaction_id)->verify();

    // You can show Anypay referenceId to the user.
    echo $receipt->getReferenceId();

    ...
} catch (InvalidAnypayException $exception) {
    /**
    	when Anypay is not verified, it will throw an exception.
    	We can catch the exception to handle invalid Anypays.
    	getMessage method, returns a suitable message that can be used in user interface.
    **/
    echo $exception->getMessage();
}
```

#### Useful methods

- ###### `callbackUrl`: can be used to change callbackUrl on the runtime.

  ```php
  // At the top of the file.
  use samgeeksdev\Anypay\Invoice;
  use samgeeksdev\Anypay\Facade\Anypay;
  ...
  
  // Create new invoice.
  $invoice = (new Invoice)->amount(1000);
  
  // Purchase the given invoice.
  Anypay::callbackUrl($url)->purchase(
      $invoice, 
      function($driver, $transactionId) {
      // We can store $transactionId in database.
  	}
  );
  ```

- ###### `amount`: you can set the invoice amount directly

  ```php
  // At the top of the file.
  use samgeeksdev\Anypay\Invoice;
  use samgeeksdev\Anypay\Facade\Anypay;
  ...
  
  // Purchase (we set invoice to null).
  Anypay::callbackUrl($url)->amount(1000)->purchase(
      null, 
      function($driver, $transactionId) {
      // We can store $transactionId in database.
  	}
  );
  ```

- ###### `via`: change driver on the fly

  ```php
  // At the top of the file.
  use samgeeksdev\Anypay\Invoice;
  use samgeeksdev\Anypay\Facade\Anypay;
  ...
  
  // Create new invoice.
  $invoice = (new Invoice)->amount(1000);
  
  // Purchase the given invoice.
  Anypay::via('driverName')->purchase(
      $invoice, 
      function($driver, $transactionId) {
      // We can store $transactionId in database.
  	}
  );
  ```
  
- ###### `config`: set driver configs on the fly

  ```php
  // At the top of the file.
  use samgeeksdev\Anypay\Invoice;
  use samgeeksdev\Anypay\Facade\Anypay;
  ...
  
  // Create new invoice.
  $invoice = (new Invoice)->amount(1000);
  
  // Purchase the given invoice with custom driver configs.
  Anypay::config('mechandId', 'your mechand id')->purchase(
      $invoice,
      function($driver, $transactionId) {
      // We can store $transactionId in database.
  	}
  );

  // Also we can change multiple configs at the same time.
  Anypay::config(['key1' => 'value1', 'key2' => 'value2'])->purchase(
      $invoice,
      function($driver, $transactionId) {
   	}
  );
  ```

#### Create custom drivers:

First you have to add the name of your driver, in the drivers array and also you can specify any config parameters you want.

```php
'drivers' => [
    'zarinpal' => [...],
    'my_driver' => [
        ... // Your Config Params here.
    ]
]
```

Now you have to create a Driver Map Class that will be used to pay invoices.
In your driver, You just have to extend `samgeeksdev\Anypay\Abstracts\Driver`.

Eg. You created a class: `App\Packages\AnypayDriver\MyDriver`.

```php
namespace App\Packages\AnypayDriver;

use samgeeksdev\Anypay\Abstracts\Driver;
use samgeeksdev\Anypay\Exceptions\InvalidAnypayException;
use samgeeksdev\Anypay\{Contracts\ReceiptInterface, Invoice, Receipt};

class MyDriver extends Driver
{
    protected $invoice; // Invoice.

    protected $settings; // Driver settings.

    public function __construct(Invoice $invoice, $settings)
    {
        $this->invoice($invoice); // Set the invoice.
        $this->settings = (object) $settings; // Set settings.
    }

    // Purchase the invoice, save its transactionId and finaly return it.
    public function purchase() {
        // Request for a Anypay transaction id.
        ...
            
        $this->invoice->transactionId($transId);
        
        return $transId;
    }
    
    // Redirect into bank using transactionId, to complete the Anypay.
    public function pay() {
        // It is better to set bankApiUrl in config/Anypay.php and retrieve it here:
        $bankUrl = $this->settings->bankApiUrl; // bankApiUrl is the config name.

        // Prepare Anypay url.
        $payUrl = $bankUrl.$this->invoice->getTransactionId();

        // Redirect to the bank.
        return redirect()->to($payUrl);
    }
    
    // Verify the Anypay (we must verify to ensure that user has paid the invoice).
    public function verify(): ReceiptInterface {
        $verifyAnypay = $this->settings->verifyApiUrl;
        
        $verifyUrl = $verifyAnypay.$this->invoice->getTransactionId();
        
        ...
        
        /**
			Then we send a request to $verifyUrl and if Anypay is not valid we throw an InvalidAnypayException with a suitable message.
        **/
        throw new InvalidAnypayException('a suitable message');
        
        /**
        	We create a receipt for this Anypay if everything goes normally.
        **/
        return new Receipt('driverName', 'Anypay_receipt_number');
    }
}
```


- ###### `amount`: you can set the invoice amount directly

  ```php
  // At the top of the file.
  use samgeeksdev\Anypay\Invoice;
  use samgeeksdev\Anypay\Facade\Anypay;
  ...
  
  // Purchase (we set invoice to null).
  Anypay::callbackUrl($url)->amount(1000)->purchase(
      null, 
      function($driver, $transactionId) {
      // We can store $transactionId in database.
  	}
  );
  ```

- ###### `payWith`: create driver on the fly

  ```php
 Anypay::payWith($gateway, $amount,  $credentials);

  ...
  
 
 
- [asanpardakht](https://asanpardakht.ir/) :heavy_check_mark:
- [aqayepardakht](https://aqayepardakht.ir/) :heavy_check_mark:
- [atipay](https://www.atipay.net/) :heavy_check_mark:
- [azkiVam (Installment Anypay)](https://www.azkivam.com/) :heavy_check_mark:
- [behpardakht (mellat)](http://www.behpardakht.com/) :heavy_check_mark:
- [bitpay](https://bitpay.ir/) :heavy_check_mark:
- [digipay](https://www.mydigipay.com/) :heavy_check_mark:
- [etebarino (Installment Anypay)](https://etebarino.com/) :heavy_check_mark:
- [fanavacard](https://www.fanava.com/) :heavy_check_mark:
- [idpay](https://idpay.ir/) :heavy_check_mark:
- [irankish](http://irankish.com/) :heavy_check_mark:
- [local](#local-driver) :heavy_check_mark:
- [jibit](https://jibit.ir/) :heavy_check_mark:
- [nextpay](https://nextpay.ir/) :heavy_check_mark:
- [omidpay](https://omidAnypay.ir/) :heavy_check_mark:
- [parsian](https://www.pec.ir/) :heavy_check_mark:
- [pasargad](https://bpi.ir/) :heavy_check_mark:
- [payir](https://pay.ir/) :heavy_check_mark:
- [payfa](https://payfa.com/) :heavy_check_mark:
- [paypal](http://www.paypal.com/) (will be added soon in next version)
- [payping](https://www.payping.ir/) :heavy_check_mark:
- [paystar](http://paystar.ir/) :heavy_check_mark:
- [poolam](https://poolam.ir/) :heavy_check_mark:
- [rayanpay](https://rayanpay.com/) :heavy_check_mark:
- [sadad (melli)](https://sadadpsp.ir/) :heavy_check_mark:
- [saman](https://www.sep.ir) :heavy_check_mark:
- [sep (saman electronic Anypay) Keshavarzi & Saderat](https://www.sep.ir) :heavy_check_mark:
- [sepehr (saderat)](https://www.sepehrpay.com/) :heavy_check_mark:
- [sepordeh](https://sepordeh.com/) :heavy_check_mark:
- [sizpay](https://www.sizpay.ir/) :heavy_check_mark:
- [toman](https://tomanpay.net/) :heavy_check_mark:
- [vandar](https://vandar.io/) :heavy_check_mark:
- [walleta (Installment Anypay)](https://walleta.ir/) :heavy_check_mark:
- [yekpay](https://yekpay.com/) :heavy_check_mark:
- [zarinpal](https://www.zarinpal.com/) :heavy_check_mark:
- [zibal](https://www.zibal.ir/) :heavy_check_mark:
 

  ```
  

