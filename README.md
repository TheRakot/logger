# Фабрика для Monolog'a

Пример конфигурации Monolog для записи логов в файл с ротацией

## Установка примера

Заходим в папку с проектами в bash / cmd. В ней клонируем репозиторий
```bash
$ git clone git@github.com:TheRakot/logger.git ./
```
Или более подробно
```bash
$ git init
$ git remote add origin git@github.com:TheRakot/logger.git ./
$ git fetch
$ git pull origin master
```

Обновляем зависимости через composer
```bash
$ composer install
```

## Чистая установка Monolog

```bash
$ composer require monolog/monolog
```
Скопировать предложенную фабрику с конфигурацией, или написать свою


## Настройка

### Путь к логам
```src/LoggerFactory.php:29``` указываем путь, по которому будут лежать все логи, по умолчанию это

```php
private const MONOLOG_LOG_DIR = '/home/bitrix/logs';
```

### Уровни логирования
```src/LoggerFactory.php:30``` оставляем интересующие нас уровни логирования, на каждый из них будет создан отдельный файл с датой. <br>
Логи записываются по принципу убывания, все что соответствует уровню и ниже, т.е. в ```error``` в данном примере попадет только ```error``` и ```critical```

```php
private const LEVELS = [
    #Logger::API => 'api',
    Logger::DEBUG => 'debug',
    Logger::INFO => 'info',
    #Logger::NOTICE => 'notice',
    #Logger::WARNING => 'warning',
    Logger::ERROR => 'error',
    Logger::CRITICAL => 'critical',
    #Logger::ALERT => 'alert',
    #Logger::EMERGENCY => 'emergency',
];
```

## Использование с фабрикой
Создаем логгер с определенным именем, для него будет создана соответствующая папка в ```MONOLOG_LOG_DIR``` с правами ```775```

```php
$logger = (new \Logs\LoggerFactory('testLog'))->getLogger();
```

В нужном месте пишем логи и добавляем интересующий контекст
```php
$logger->info('Запись в info общая информация', ['env' => 'PROD', 'login' => 'test']);
$logger->debug('Запись в debug подробная информация', ['env' => 'PROD', 'data' => ['login' => 'test', 'password' => 'test', 'contact' => ['email' => 'test', 'phone' => 'test']]]);
$logger->critical('Запись в critical нужно реагировать', ['env' => 'PROD', 'data' => ['login' => 'test', 'password' => 'test', 'contact' => ['email' => 'test', 'phone' => 'test']]]);
```

В файле ```/home/bitrix/logs/testLog/debug-2024-10-14.log```
```log
[2024-10-14 15:40:49:975946] INFO: Запись в info общая информация {"env":"PROD","login":"test"} []
[2024-10-14 15:40:49:976179] DEBUG: Запись в debug подробная информация {"env":"PROD","data":{"login":"test","password":"test","contact":{"email":"test","phone":"test"}}} []
[2024-10-14 15:40:49:976412] CRITICAL: Запись в critical нужно реагировать {"env":"PROD","data":{"login":"test","password":"test","contact":{"email":"test","phone":"test"}}} []
```

В файле ```/home/bitrix/logs/testLog/info-2024-10-14.log```
```log
[2024-10-14 15:40:49:975946] INFO: Запись в info общая информация {"env":"PROD","login":"test"} []
[2024-10-14 15:40:49:976412] CRITICAL: Запись в critical нужно реагировать {"env":"PROD","data":{"login":"test","password":"test","contact":{"email":"test","phone":"test"}}} []
```

В файле ```/home/bitrix/logs/testLog/critical-2024-10-14.log```
```log
[2024-10-14 15:40:49:976412] CRITICAL: Запись в critical нужно реагировать {"env":"PROD","data":{"login":"test","password":"test","contact":{"email":"test","phone":"test"}}} []
```

## Разбор текущей конфигурации
Настройка логгера происходит в методе ```src/LoggerFactory.php:72```

Папка для записи конкретного лога (будет создана если не существует)
```php
$logPath = self::MONOLOG_LOG_DIR . '/' . str_replace(['\\', ':', ';', ' '], '_', $this->loggerName);
# если папки нет — создаем
if (false === realpath($logPath)) {
    self::createDir($logPath);
}
```

Установка формата записи
```php
$dateFormat = 'Y-m-d H:i:s:u';
$output = "[%datetime%] %level_name%: %message% %context% %extra%\n";
$formatter = new LineFormatter($output, $dateFormat);
```

Настройка для каждого из доступных уровней логирования
```php
foreach (self::LEVELS as $level => $levelName) { }
```

Будем писать в файл (для текущего примера итоговый файл будет другой) ```/home/bitrix/logs/#logName#/#level#.log```
```php
$logFile = sprintf('%s/%s.log', $logPath, $levelName);
```


```RotatingFileHandler``` будет сам заниматься удалением устаревших логов, из-за его применения итоговый файл меняется на ```#level#-YYYY-MM-DD.log```
```php
$streamHandler = new RotatingFileHandler($logFile, 7, $level, true); #храним 7 файлов логов на каждый уровень логирования
$streamHandler->setFormatter($formatter); #используем ранее настроенный формат записи в файл
$this->logger->pushHandler($streamHandler); #обновляем конфигурацию
```

 Логи уровня ```CRITICAL``` дополнительно шлем на почту ответственным сотрудникам
```php
if ($level === Logger::CRITICAL) {
    $mailHandler = new NativeMailerHandler(['example@example.com'], 'CRITICAL event on ' . $_SERVER['HTTP_HOST'], 'test@test.ru', Logger::CRITICAL, true);
    $mailHandler->setFormatter(new HtmlFormatter()); #используем формат html для красивых логов на почту
    $mailHandler->setContentType('text/html'); #установим тип формата html
    $this->logger->pushHandler($mailHandler); #обновляем конфигурацию
}
```

## Несколько конфигураций
Метод ```getLogger()``` позволяет сделать несколько предустановленных конфигураций, при необходимости, просто пишем свои конфигурации в switch case для конкретных имен логгера.

## Документация по Monolog
- [Использование](https://github.com/Seldaek/monolog/blob/main/doc/01-usage.md)
- [Handlers](https://github.com/Seldaek/monolog/blob/main/doc/02-handlers-formatters-processors.md#handlers) — куда писать данные
- [Formatters](https://github.com/Seldaek/monolog/blob/main/doc/02-handlers-formatters-processors.md#formatters) — форматирование записей
- [Processors](https://github.com/Seldaek/monolog/blob/main/doc/02-handlers-formatters-processors.md#processors) — обогощение контекста
