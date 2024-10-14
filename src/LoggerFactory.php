<?php
declare(strict_types=1);

namespace Logs;

use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Exception;
use RuntimeException;

/**
 * Class LoggerFactory.
 *
 * debug — Подробная информация для отладки
 * info — Интересные события
 * notice — Существенные события, но не ошибки
 * warning — Исключительные случаи, но не ошибки
 * error — Ошибки исполнения, не требующие сиюминутного вмешательства
 * critical — Критические состояния (компонент системы недоступен, неожиданное исключение)
 * alert — Действие требует безотлагательного вмешательства
 * emergency — Система не работает
 */
class LoggerFactory
{
	private const MONOLOG_LOG_DIR = '/home/bitrix/logs';
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
	private LoggerInterface $logger;
	private string $loggerName;

	/**
	 * @param string $loggerName
	 */
	public function __construct(string $loggerName)
	{
		$this->loggerName = $loggerName;
		$this->logger = new Logger($loggerName);
	}

	/**
	 * @return LoggerInterface
	 * @throws Exception
	 */
	public function getLogger(): LoggerInterface
	{
		#Если нужны кастомные настройки под канал доставки, например все - в файл, выгрузка - на почту, заказ - в телеграмм
		switch ($this->loggerName) {
			case 'testLog':
				$this->configLogByName();
				break;
			default:
				#Универсальный логгер с любым именем
				$this->configLogByName();
				break;
		}
		return $this->logger;
	}

	private function configLogByName(): void
	{
		$logPath = self::MONOLOG_LOG_DIR . '/' . str_replace(['\\', ':', ';', ' '], '_', $this->loggerName);
		# если папки нет — создаем
		if (false === realpath($logPath)) {
			self::createDir($logPath);
		}

		# Выбранный путь существует и является папкой
		if (file_exists($logPath)) {
			$dateFormat = 'Y-m-d H:i:s:u';
			$output = "[%datetime%] %level_name%: %message% %context% %extra%\n";
			$formatter = new LineFormatter($output, $dateFormat);

			# регистрируем pushHandler согласно уровней логирования проекта
			foreach (self::LEVELS as $level => $levelName) {
				$logFile = sprintf('%s/%s.log', $logPath, $levelName);

				#храним логи 1 неделю
				$streamHandler = new RotatingFileHandler($logFile, 7, $level, true);
				$streamHandler->setFormatter($formatter);
				$this->logger->pushHandler($streamHandler);

				#логи уровня CRITICAL дополнительно шлем на почту ответственным
				if ($level === Logger::CRITICAL) {
					$mailHandler = new NativeMailerHandler(
						['example@example.com'], 'CRITICAL event on ' . $_SERVER['HTTP_HOST'], 'test@test.ru', Logger::CRITICAL, true
					);
					$mailHandler->setFormatter(new HtmlFormatter());
					$mailHandler->setContentType('text/html');
					$this->logger->pushHandler($mailHandler);
				}
			}
		}
		else {
			throw new RuntimeException(sprintf('\'Cannot find / create logs directory "%s"\'', $logPath));
		}
	}

	/**
	 * Создает папку $dir
	 * @param string $dir
	 *
	 * @return void
	 */
	private static function createDir(string $dir): void
	{
		if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
			throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
		}
	}
}
