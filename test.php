<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
ini_set("display_errors", "On");
error_reporting(E_ALL);

use Hhz\Sloth\HttpClient;

// HTTP客户端（multipart/form-data）
$oClient = new HttpClient();
for ($i = 150; $i < 350; $i++) {
	$resp = $oClient->AddTask('queue/delayqueue/test', [
		'topic' => 'designer',
		'delay' => $i,
		'body' => [
			'uid' => "19625752",
		]
	], HttpClient::QUEUE_NAME, HttpClient::CLIENT_YAPI, HttpClient::VERSION);

	var_dump($resp);
}
