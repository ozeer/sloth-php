<?php

namespace Hhz\Sloth;

class Constants
{
	const HTTP_CLIENT_RETRY_COUNTER = 2; // http客户端遇错重试次数
	const HTTP_CLIENT_RETRY_SLEEP   = 1; // http客户端遇错重试间隔时间，单位秒
	const GRPC_CLIENT_RETRY_COUNTER = 2; // grpc客户端遇错重试次数
	const GRPC_CLIENT_RETRY_SLEEP   = 1; // grpc客户端遇错重试间隔时间，单位秒

	const COMMON_SUCCESS_CODE = 0;  // 通用成功
}
