<?php

namespace Hhz\Sloth;

use Curl\Curl;
use Exception;
use RuntimeException;

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
	 * 添加延迟任务
	 * @param string $controller
	 * @param array $params
	 * @param string $queue_name
	 * @param string $client
	 * @param string $version
	 * @return bool
	 */
	public function AddTask(string $controller, array $params, string $queue_name, string $client, string $version): ?bool
	{
		try {
			$task_id = Util::genTaskId($params);
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

			// 服务未启动
			if (empty($aResp)) {
				return false;
			}

			// 成功
			if ($aResp->code === 0) {
				return true;
			}

			// 异常或者报错
			throw new RuntimeException('Add task fail: '. $aResp->msg);
		} catch (Exception $e) {
			Log::error($queue_name . "#" . $controller, [
				$params,
				$e->getMessage()
			]);
			return false;
		}
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
					throw new RuntimeException("配置文件中没有发现服务器");
				}
				if (!isset($this->aSdkConfig['server']['http'][0])) {
					throw new RuntimeException("配置文件中没有发现服务器");
				}
				$sProtocol = $this->aSdkConfig['server']['http'][0]['protocol'];
				$sIp       = $this->aSdkConfig['server']['http'][0]['ip'];
				$iPort     = $this->aSdkConfig['server']['http'][0]['port'];
				$sHostName = $sProtocol. $sIp. ':'. $iPort;
				$this->sHostName = $sHostName;
				//Log::debug("构造函数", [$sProtocol, $sIp, $iPort, $sHostName]);
			} catch (Exception $e) {
				// 此处记录日志，等待日志组件与日志统一标准格式
				Log::error("#初始化http client失败#", [
					'err_code' => $e->getCode(),
					'err_msg' => $e->getMessage()
				]);
				throw new RuntimeException($e->getMessage());
			}
		}
	}
}
