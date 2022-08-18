<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
ini_set("display_errors","On");
error_reporting(E_ALL);

use Hhz\Sloth\Client;

// 生成任务id
try {
	$params = [
		'id' => 1,
		'body' => json_encode([
			'controller' => '',
			'queue_name' => '',
		], JSON_THROW_ON_ERROR),
		'client' => Client::CLIENT_YAPI,
		'version' => Client::VERSION
	];

	$task_id = Client::genTaskId($params);
	echo "task_id: $task_id".PHP_EOL;
} catch (JsonException $e) {
	error_log("#error#: ". $e->getMessage(), 3, 'debug.log');
}

// 写队列
$res = Client::insertQueue('queue/delayqueue/test', [
	'topic' => 'designer',
	'delay' => 10,
	'body' => [
		'uid' => "19625752",
	]
], Client::QUEUE_NAME, Client::CLIENT_YAPI, Client::VERSION);
var_dump($res);
