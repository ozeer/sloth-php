<?php

namespace Hhz\Sloth;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Class Log
 * @method static position_info($title, array $data = [])
 * @method static error(string $message, array $data)
 * @method static info(string $message, array $data)
 * @method static debug(string $message, array $data)
 */
class Log
{
	//const DEFAULT_LOG_PATH_PREFIX = "/data/logs_bak/";
	public const DEFAULT_LOG_PATH_PREFIX = "./"; // local test

	public const DEFAULT_LOG_DIRECTORY = "sloth";

	public const DEFAULT_PROJECT_DIRECTORY_PREFIX = 'g_';

	public const DEFAULT_LOG_SEPARATOR = '#';

	protected static array $levels = [
		'debug' => Logger::DEBUG,
		'info' => Logger::INFO,
		'notice' => Logger::NOTICE,
		'warning' => Logger::WARNING,
		'error' => Logger::ERROR,
		'critical' => Logger::CRITICAL,
		'alert' => Logger::ALERT,
		'emergency' => Logger::EMERGENCY
	];
	private static array $singleLog = [];
	private static array $singleStreamHandler = [];
	private static array $singleStreamHandlerPath = [];
	private static array $singleFormatter = [];
	private static array $logSetting = [
		'logPath' => "",
		'logSlice' => 'daily',
		'expireDay' => 30,
		'logList' => [
			'general' => [
				"dir" => "general/",
				"logLevel" => Logger::DEBUG
			],
			'position' => [
				"dir" => "position/",
				"logLevel" => Logger::DEBUG
			],
		]
	];

	public static function __callstatic($method, $args)
	{
		$backtrace = debug_backtrace(0, 1);
		self::setLogPath($backtrace);
		$methodList = explode("_", strtolower($method));
		if (count($methodList) > 1) {
			if (count($methodList) === 2) {
				self::addSystemLog($methodList[0], $methodList[1], $args);
			} elseif (count($methodList) > 2) {
				self::addSystemLog($methodList[0], $methodList[2], $args, $methodList[1]);
			}
		} else {
			[$logPath, $line] = self::getBackTrace($backtrace);
			self::addGeneralLog($logPath, $line, $method, $args);
		}
	}

	private static function setLogPath($backtrace = null): void
	{
		$sLogDirPrefix = self::DEFAULT_LOG_PATH_PREFIX;
		// 一般服务器上都有/data/logs_bak文件夹，如果没有表示用户是在本地环境，将日志输出到/tmp目录下
		// 无论是dev环境，还是haohaoce环境亦或者是gray和production环境，都是有/data/logs_bak目录
		if (!is_dir(self::DEFAULT_LOG_PATH_PREFIX)) {
			$sLogDirPrefix = "/tmp/";
		}
		if (isset($_SERVER['APP_NAME'])) {
			self::$logSetting['logPath'] = $sLogDirPrefix . $_SERVER['APP_NAME'] . DIRECTORY_SEPARATOR;
		} else {
			$backtraceDir = ltrim(str_replace(dirname(__DIR__, 2), '', $backtrace[0]['file']), '/');
			$dirList = explode("/", $backtraceDir);
			$logPath = str_replace(self::DEFAULT_PROJECT_DIRECTORY_PREFIX, '', array_shift($dirList));
			self::$logSetting['logPath'] = $sLogDirPrefix . $logPath . DIRECTORY_SEPARATOR;
		}
	}

	private static function addSystemLog($logTag, $logType, $logData, $logPrefix = null)
	{
		$logPath = $logPrefix ? $logTag . "-" . $logPrefix : $logTag;
		if (!isset(self::$singleFormatter[$logPath])) {
			$output = "%datetime%#%level_name%#%message%#%context%\n";
			self::$singleFormatter[$logPath] = new LineFormatter($output);
		}
		self::getSingleLog($logPath);
		$realPath = self::$logSetting['logPath'] . self::$logSetting['logList'][$logTag]['dir'] . $logPath . self::getSlice();
		if (!isset(self::$singleStreamHandlerPath[$logPath]) || self::$singleStreamHandlerPath[$logPath] !== $realPath || !file_exists($realPath)) {
			try {
				self::$singleStreamHandler[$logPath] = new StreamHandler($realPath,
					self::$logSetting['logList'][$logTag]['logLevel'], true, 0666);
			} catch (\Exception $e) {
				self::error("system_log_setting", self::$logSetting);
			}
			self::$singleStreamHandler[$logPath]->setFormatter(self::$singleFormatter[$logPath]);
			//释放文件句柄
			if (!empty(self::$singleLog[$logPath]->getHandlers())) {
				self::$singleLog[$logPath]->popHandler()->close();
			}
			self::$singleLog[$logPath]->pushHandler(self::$singleStreamHandler[$logPath]);
			self::$singleStreamHandlerPath[$logPath] = $realPath;
		}
		self::writeLog($logPath, $logType, $logData);
	}

	private static function getSingleLog($logPath)
	{
		if (!isset(self::$singleLog[$logPath])) {
			self::$singleLog[$logPath] = new Logger($logPath);
		}
		return self::$singleLog[$logPath];
	}

	private static function getSlice(): string
	{
		$logSuffix = '';
		switch (self::$logSetting['logSlice']) {
			case "hourly":
				$logSuffix = "-" . date("YmdH") . ".log";
				break;
			case "daily":
				$logSuffix = "-" . date("Ymd") . ".log";
				break;
		}
		return $logSuffix;
	}

	private static function writeLog($logPath, $logType, $logData): void
	{
		$log = self::getSingleLog($logPath);
		//$message = Ip::getClientIp() . self::DEFAULT_LOG_SEPARATOR . array_shift($logData);
		$message = self::DEFAULT_LOG_SEPARATOR . array_shift($logData);
		if (PHP_SAPI === "fpm-fcgi" && isset($_SERVER['HTTP_USER_AGENT'])) {
			array_push($logData, $_SERVER['REQUEST_URI'], $_SERVER['HTTP_USER_AGENT']);
		}
		//$addFunction = 'add' . ucfirst($logType);
		//$log->$addFunction($message, $logData);
		$log->$logType($message, $logData);
	}

	private static function getBackTrace($backtrace): array
	{
		$callFile = ltrim(str_replace(array(
			dirname(__DIR__, 2),
			'.php',
			'/'
		), array(
			"",
			"",
			"-"
		), $backtrace[0]['file']), "-");

		if (empty($callFile)) {
			$callFile = 'general';
		}
		return [
			$callFile,
			$backtrace[0]['line']
		];
	}

	private static function addGeneralLog($logPath, $line, $logType, $logData): void
	{
		$key = $logPath . "-" . $line;
		if (!isset(self::$singleFormatter[$key])) {
			$output = "%datetime%#line:{$line}#%level_name%#%message%#%context%\n";
			self::$singleFormatter[$logPath . "-" . $line] = new LineFormatter($output);
			if (isset(self::$singleStreamHandler[$logPath])) {
				self::$singleStreamHandler[$logPath]->setFormatter(self::$singleFormatter[$logPath . "-" . $line]);
			}
		}
		self::getSingleLog($logPath);
		$realPath = self::$logSetting['logPath'] . self::$logSetting['logList']['general']['dir'] . $logPath . self::getSlice();
		//var_dump($logType);
		//var_dump(self::$logSetting['logPath']);
		//var_dump(self::$logSetting['logList']['general']['dir']);
		//var_dump($logPath);
		//var_dump(self::getSlice());
		//var_dump($realPath);
		if (!isset(self::$singleStreamHandlerPath[$logPath]) || self::$singleStreamHandlerPath[$logPath] !== $realPath || !file_exists($realPath)) {
			try {
				self::$singleStreamHandler[$logPath] = new StreamHandler($realPath,
					self::$logSetting['logList']['general']['logLevel'], true, 0666);
			} catch (\Exception $e) {
				self::error("general_log_setting", self::$logSetting);
			}
			self::$singleStreamHandler[$logPath]->setFormatter(self::$singleFormatter[$logPath . "-" . $line]);
			// 释放文件句柄
			if (!empty(self::$singleLog[$logPath]->getHandlers())) {
				self::$singleLog[$logPath]->popHandler()->close();
			}
			self::$singleLog[$logPath]->pushHandler(self::$singleStreamHandler[$logPath]);
			self::$singleStreamHandlerPath[$logPath] = $realPath;
		}
		self::writeLog($logPath, $logType, $logData);
	}
}
