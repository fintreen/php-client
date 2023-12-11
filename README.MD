# PHP client for fintreen.com.
Fintreen.com php client for payment gateway with crypto



Tested on php 8.0, 8.1, 8.2

Installation:

Example:
```php

<?php

include (__DIR__ . DIRECTORY_SEPARATOR . 'FintreenClient.php');

$token = 'yourtokenhere';
$email = 'youremailhere';
$isTestMode = true;


$fintreenClient = new FintreenClient($token, $email, $isTestMode, true);
$currencies = $fintreenClient->getCurrenciesList();

var_dump($currencies);

$orderStatusList = $fintreenClient->getOrderStatusList();

var_dump($orderStatusList);

// Calculate 25 eur in USDT and BTC
$currencyCodesToCalculate = ['USDT-TRC20', 'USDT-ERC20', 'BTC'];
$calculation = $fintreenClient->calculate(25, $currencyCodesToCalculate);

var_dump($calculation);

// Create order for 26 eur in usdt-trc20
$transactionCreated = $fintreenClient->createTransaction(26, 'USDT-TRC20', FintreenClient::DEFAULT_FIAT_CODE);

var_dump($transactionCreated);

$checkedTransaction = $fintreenClient->checkTransaction((int)$transactionCreated['data']['id']);

var_dump($checkedTransaction);

// Filter params for transactions list
$filters = [];
$filters['statusId'] = 1; // New
$filters['isTest'] = (int)$isTestMode; // should be same or will return 404
$filters['perPage'] = 5; // items per page
$filters['page'] = 1;
$filters['codesFilter'] = implode(',', $currencyCodesToCalculate);// comma seprated codes to filter transaction with
// Note that you can use it without filters

$transactionsList = $fintreenClient->getTransactionsList($filters);

// You can alternatively use $fintreenClient->sendRequest

$notFilteredTransactions = $fintreenClient->sendRequest('transactions', 'GET');
var_dump(@json_decode($notFilteredTransactions, true));

```
