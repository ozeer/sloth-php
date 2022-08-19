<?php

namespace Hhz\Sloth;

class Constants
{
	public const HTTP_CLIENT_RETRY_COUNTER = 2; // http客户端遇错重试次数
	public const HTTP_CLIENT_RETRY_SLEEP   = 1; // http客户端遇错重试间隔时间，单位秒
	public const GRPC_CLIENT_RETRY_COUNTER = 2; // grpc客户端遇错重试次数
	public const GRPC_CLIENT_RETRY_SLEEP   = 1; // grpc客户端遇错重试间隔时间，单位秒

	public const COMMON_SUCCESS_CODE = 0;  // 通用成功
}
