<?php

declare(strict_types=1);

use Hhz\Sloth\Client;

class ClientTest
{
	public static function genTaskId(): void
	{
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
		error_log("task_id: ". $task_id, 3, 'debug.log');
	}

	public static function insertQueue(): void
	{
		$res = Client::insertQueue('queue/delayqueue/test', [
			'topic' => 'designer',
			'delay' => 10,
			'body' => [
				'uid' => "19625752",
			]
		], Client::QUEUE_NAME, Client::CLIENT_YAPI, Client::VERSION);
		var_dump($res);die();
	}
}

$func = $argv[1] ?? 'genTaskId';
ClientTest::$func();
