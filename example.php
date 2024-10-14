<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$logger = (new \Logs\LoggerFactory('testLog'))->getLogger();
$logger->info('Запись в info общая информация', ['env' => 'PROD', 'login' => 'test']);
$logger->debug('Запись в debug подробная информация', ['env' => 'PROD', 'data' => ['login' => 'test', 'password' => 'test', 'contact' => ['email' => 'test', 'phone' => 'test']]]);
$logger->critical('Запись в critical нужно реагировать', ['env' => 'PROD', 'data' => ['login' => 'test', 'password' => 'test', 'contact' => ['email' => 'test', 'phone' => 'test']]]);

echo '<pre>';
print_r($logger);
echo '</pre>';
