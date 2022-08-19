<?php

namespace Hhz\Sloth;

use Exception;

class Config
{
	private string $sConfigFile;
	public static ?Config $oInstance = null;

	public function __construct()
	{
		$sRootDir = __DIR__;
		$sConfigFileDirPrefix = $sRootDir. DIRECTORY_SEPARATOR. "Config". DIRECTORY_SEPARATOR;
		$sEnv = Env::getEnv();

		if (Env::ENV_PRODUCT === $sEnv) {
			$sConfigFileName = $sConfigFileDirPrefix."config-prod.json";
		} else if(Env::ENV_GRAY === $sEnv) {
			$sConfigFileName = $sConfigFileDirPrefix."config-gray.json";
		} else if(Env::ENV_TEST === $sEnv) {
			$sConfigFileName = $sConfigFileDirPrefix."config-test.json";
		} else if(Env::ENV_DEV === $sEnv) {
			$sConfigFileName = $sConfigFileDirPrefix."config-dev.json";
		} else {
			$sConfigFileName = $sConfigFileDirPrefix."config-prod.json";
		}

		$this->sConfigFile = $sConfigFileName;
		if (null === self::$oInstance) {
			self::$oInstance = $this;
		}
	}

	public static function getInstance(): ?Config
	{
		return self::$oInstance;
	}

	/**
	 * @throws Exception
	 */
	public function parse()
	{
		if (!is_file($this->sConfigFile)) {
			throw new \RuntimeException("配置文件不存在");
		}
		$sConfigContent = file_get_contents($this->sConfigFile);
		if (false === $sConfigContent) {
			throw new \RuntimeException("读取文件内容失败");
		}
		if ('' === trim($sConfigContent)) {
			throw new \RuntimeException("配置文件内容为空");
		}
		$aConfig = json_decode($sConfigContent, true, 512, JSON_THROW_ON_ERROR);
		if (!$aConfig) {
			throw new \RuntimeException("配置文件内容为空");
		}
		return $aConfig;
	}
}
