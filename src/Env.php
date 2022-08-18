<?php

namespace Hhz\Sloth;

class Env
{
	const PRODUCTION_HOSTNAME_PREFIX = 'hzv_';
	const BE_NEW_ADMIN_PRODUCTION_HOSTNAME = "hzv_admin_new";
	const COMMON_HOSTNAME_PREFIX = 'common_';
	const API_GRAY_HOSTNAME_PREFIX = 'hzv_webgray';

	const ENV_PRODUCT = 'production';
	const ENV_GRAY    = 'gray';
	const ENV_TEST    = 'hhc-tech';
	const ENV_DEV     = 'hhz-dev';
	const ENV_LIST    = [
		self::ENV_PRODUCT,
		self::ENV_GRAY,
		self::ENV_TEST,
		self::ENV_DEV,
	];

	/**
	 * 获取当前运行环境，在按照正常逻辑获取失败的情况下，走兜底逻辑返回线上正式环境
	 * @return array|string
	 */
	public static function getEnv()
	{
		$sEnv = getenv('CLUSTER_NAME');
		if (false === $sEnv) {
			$sEnv = getenv('ECS_ENV_NAME');
		}

		if (false === $sEnv || !in_array($sEnv, self::ENV_LIST)) {
			// 一期兜底策略，没查到环境的情况下先返回production并记录异常日志
			// 上线后观察一段时间，如果没有异常日志的话，直接抛出异常
			//Log::debug('运行环境获取异常', [$sEnv, debug_backtrace(5), getenv()]);
			return self::ENV_PRODUCT;
		}
		return $sEnv;
	}

	/**
	 * 获取traceid
	 * @return mixed|string
	 */
	public static function getTraceId()
	{
		return $_SERVER['HTTP_TRACEID'] ?? md5(time());
	}

	/**
	 * 判断是否为线上new-admin机器
	 * @return bool
	 */
	public static function isNewAdminProduction(): bool
	{
		if (self::BE_NEW_ADMIN_PRODUCTION_HOSTNAME == gethostname()) {
			return true;
		} else {
			return false;
		}
	}

	public static function isProduction(): bool
	{
		if (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] == "production" && (strpos(gethostname(), self::PRODUCTION_HOSTNAME_PREFIX) === 0 || strpos(gethostname(), self::COMMON_HOSTNAME_PREFIX) === 0)
		) {
			return true;
		} else {
			return false;
		}
	}

	public static function isGray(): bool
	{
		if (strpos(gethostname(), self::API_GRAY_HOSTNAME_PREFIX) === 0) {
			return true;
		}
		return false;
	}

	public static function isDockerQa(): bool
	{
		if (getenv('env_domain_suffix')) {
			return true;
		}
		return false;
	}

	public static function isProductionWithoutGray(): bool
	{
		$grayCheck = true;
		if (PHP_SAPI != 'cli') {
			$grayCheck = isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] == "production";
		}
		if ($grayCheck && strpos(gethostname(), self::PRODUCTION_HOSTNAME_PREFIX) === 0 && strpos(gethostname(), self::API_GRAY_HOSTNAME_PREFIX) !== 0
		) {
			return true;
		} else {
			return false;
		}
	}
}
