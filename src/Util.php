<?php

namespace Hhz\Sloth;

use JsonException;

class Util
{
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
}
