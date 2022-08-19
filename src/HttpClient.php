<?php

namespace Hhz\Sloth;

use Curl\Curl;
use Exception;
use JsonException;

/**
 * sloth延迟队列项目的php客户端请求封装类
 *
 * @category   Module name
 * @package    PSR
 * @subpackage Documentation\API
 * @author     zhouyang  <zhouyang@haohaozhu.com>
 * @license    GPL https://www.haohaozhu.cn
 * @link       https://www.haohaozhu.cn
 * @date       2022/5/19
 * @time       01:22
 */
class HttpClient
{
	private ?Curl $oCurl = null;

	// 调用来源客户端
	public const CLIENT_YAPI = 'delay_queue_yapi';
	public const CLIENT_ADMIN = 'delay_queue_admin';
	public const CLIENT_DORAEMON = 'delay_queue_doraemon';

	// 接口服务版本号
	public const VERSION = 'v1';

	// 队列名称
	public const QUEUE_NAME = 'hzQDoraemon';
	private string $sHostName;
	/**
	 * @var mixed
	 */
	private $aSdkConfig;

	/**
	 * 写入任务队列id
	 * @param string $controller
	 * @param array $params
	 * @param string $queue_name
	 * @param string $client
	 * @param string $version
	 * @return bool
	 */
	public function insertQueue(string $controller, array $params, string $queue_name, string $client, string $version): ?bool
	{
		try {
			$task_id = self::genTaskId($params);
			$params['id'] = $task_id;
			$params['body']['controller'] = $controller;
			$params['body']['queue_name'] = $queue_name;
			$params['body'] = json_encode($params['body'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

			$url = $this->sHostName. '/v1/add_task';
			$params['client'] = $client;
			$params['version'] = $version;
			$headers = [
				'client' => $client,
				'version' => $version,
			];
			$this->oCurl->setHeaders($headers);
			$aResp = $this->oCurl->post($url, $params);

			// 调试
			Log::info("Add Task", [
				'url' => $url,
				'params' => $params,
				'aResp' => $aResp
			]);

			if (!empty($aResp) && $aResp->code === 0) {
				return true;
			}

			throw new \RuntimeException('Add task fail: '. $aResp->msg);
		} catch (Exception $e) {
			Log::error($queue_name . "#" . $controller, [
				$params,
				$e->getMessage()
			]);
			return false;
		}
	}

	/**
	 * 生成任务唯一id
	 * @param $params
	 * @return int
	 * @throws JsonException
	 */
	public static function genTaskId($params): int
	{
		return crc32(json_encode($params, JSON_THROW_ON_ERROR) . time());
	}

	/**
	 * 删除任务方法
	 * @param $task_id
	 * @return string
	 */
	public static function delTask($task_id): string
	{
		return "";
	}

	/**
	 * @throws Exception
	 */
	public function __construct()
	{
		if (null === $this->oCurl) {
			try {
				$this->oCurl = new Curl();
				// curl的配置
				//$this->oCurl->setHeader('Content-Type', 'application/json');
				$this->oCurl->setHeader('Content-Type', 'multipart/form-data');
				//$this->oCurl->setDefaultJsonDecoder();
				// sdk的配置
				$oConfig = new Config();
				$this->aSdkConfig = $oConfig->parse();
				// 执行初始化
				if (!isset($this->aSdkConfig['server']['http'])) {
					throw new \RuntimeException("配置文件中没有发现服务器");
				}
				if (!isset($this->aSdkConfig['server']['http'][0])) {
					throw new \RuntimeException("配置文件中没有发现服务器");
				}
				$sProtocol = $this->aSdkConfig['server']['http'][0]['protocol'];
				$sIp       = $this->aSdkConfig['server']['http'][0]['ip'];
				$iPort     = $this->aSdkConfig['server']['http'][0]['port'];
				$sHostName = $sProtocol. $sIp. ':'. $iPort;
				$this->sHostName = $sHostName;
				//Log::debug("构造函数", [$sProtocol, $sIp, $iPort, $sHostName]);
			} catch (\Exception $e) {
				// 此处记录日志，等待日志组件与日志统一标准格式
				Log::error("#初始化http client失败#", [
					'err_code' => $e->getCode(),
					'err_msg' => $e->getMessage()
				]);
				throw new \RuntimeException($e->getMessage());
				exit;
			}
		}
	}

	/**
	 * @param : $sMethodName 方法名称
	 * @param : $aArguments，0偏移量必存在，为json字符串；1作为options，array类型，可能会存在
	 * @throws Exception
	 */
	public function __call($sMethodName, $aArguments)
	{
		if (!isset($this->aMethod2ApiMap[$sMethodName])) {
			// 此处记录日志，等待日志组件与日志统一标准格式
			Log::error("#错误的方法名#", [
				'sMethodName' => $sMethodName
			]);
			throw new \RuntimeException("错误的方法名: " .$sMethodName);
		}
		if (!isset($aArguments[0])) {
			// 此处记录日志，等待日志组件与日志统一标准格式
			Log::error("#错误的参数#", $aArguments);
			throw new \RuntimeException("错误的参数: ".json_encode($aArguments));
		}
		$sApiPath = $this->sHostName.$this->aMethod2ApiMap[$sMethodName];
		// 获取发起请求的json参数
		$sJsonParam = $aArguments[0];
		// 获取可能存在的curl options参数
		$aCurlOptions = $aArguments[1] ?? [];
		$aResp = $this->oCurl->post($sApiPath, $sJsonParam);
		$iTotalCounter = Constants::HTTP_CLIENT_RETRY_COUNTER;
		while ($this->oCurl->error && $iTotalCounter--) {
			$iErrCode = $this->oCurl->errorCode;
			$sErrMsg  = $this->oCurl->errorMessage;
			sleep(Constants::HTTP_CLIENT_RETRY_SLEEP);
			$aResp = $this->oCurl->post($sApiPath, $sJsonParam);
			// 此处记录日志，等待日志组件与日志统一标准格式
			Log::error("#curl请求失败#", [
				'err_code' => $iErrCode,
				'err_msg' => $sErrMsg,
				'api' => $sApiPath,
				'params' => $sJsonParam,
				'aResp' => $aResp
			]);
		}

		if ($this->oCurl->error) {
			$iErrCode = $this->oCurl->errorCode;
			$sErrMsg  = $this->oCurl->errorMessage;
			return array(
				'code' => $iErrCode,
				'msg'  => $sErrMsg,
			);
		}
		$oRet = $this->oCurl->response;
		return json_decode(json_encode($oRet), true, 512, JSON_THROW_ON_ERROR);
	}
}
