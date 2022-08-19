<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
ini_set("display_errors","On");
error_reporting(E_ALL);

use Hhz\Sloth\HttpClient;
use Hhz\Sloth\Log;

// 生成任务id
try {
	$params = [
		'id' => 1,
		'body' => json_encode([
			'controller' => '',
			'queue_name' => '',
		], JSON_THROW_ON_ERROR),
		'client' => HttpClient::CLIENT_YAPI,
		'version' => HttpClient::VERSION
	];

	$task_id = HttpClient::genTaskId($params);
	echo "task_id: $task_id".PHP_EOL;
} catch (Exception $e) {
	Log::error("生成任务id失败", [$e->getMessage()]);
}

// 写队列
$oClient = new HttpClient();
$resp = $oClient->AddTask('queue/delayqueue/test', [
	'topic' => 'designer',
	'delay' => 10,
	'body' => [
		'uid' => "19625752",
	]
], HttpClient::QUEUE_NAME, HttpClient::CLIENT_YAPI, HttpClient::VERSION);

var_dump($resp);
