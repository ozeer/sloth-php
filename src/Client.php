<?php

namespace Hhz\Sloth;

use Doraemon\pockets\config\IniConfig;
use Doraemon\tools\Curl;
use Doraemon\tools\Log;
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
class Client
{
	// 调用来源客户端
	public const CLIENT_YAPI = 'delay_queue_yapi';
	public const CLIENT_ADMIN = 'delay_queue_admin';
	public const CLIENT_DORAEMON = 'delay_queue_doraemon';

	// 接口服务版本号
	public const VERSION = 'v1';

	// 队列名称
	public const QUEUE_NAME = 'hzQDoraemon';

	/**
	 * 写入任务队列id
	 * @param string $controller
	 * @param array $params
	 * @param string $queue_name
	 * @param string $client
	 * @param string $version
	 * @return false|int
	 */
	public static function insertQueue(string $controller, array $params, string $queue_name, string $client, string $version)
	{
		try {
			$task_id = self::genTaskId($params);
			$params['id'] = $task_id;
			$params['body']['controller'] = $controller;
			$params['body']['queue_name'] = $queue_name;
			$params['body'] = json_encode($params['body'], JSON_UNESCAPED_SLASHES);

			$config = self::getConfig();
			$url = $config['delay_queue_api_add'];
			$headers = [
				'client' => $client,
				'version' => $version,
			];
			$params['client'] = $client;
			$params['version'] = $version;
			$res = Curl::Request('POST', $url, ['form_params' => $params, 'headers' => $headers]);
			Log::notice("延迟队列投递: ", [
				'params' => $params
			]);

			if ($res['code'] == 1) {
				return $task_id;
			} else {
				throw new Exception('延迟队列投递失败');
			}
		} catch (Exception $e) {
			Log::queue_insertError_error($queue_name . "#" . $controller, [
				$params,
				$e
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
	 * 获取配置文件信息
	 * @return string
	 * @throws Exception
	 */
	private static function getConfig(): string
	{
		return IniConfig::getConfigSelect('sloth_queue', 'api');
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
}
