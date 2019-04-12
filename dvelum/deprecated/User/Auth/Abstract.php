<?php
/**
 * Abstract class of User authentification provider.
 * @author Sergey Leschenko
 */

use Dvelum\Config\ConfigInterface;

abstract class User_Auth_Abstract
{
	protected $userData = false;
	protected $config = false;

	/**
	 * @param ConfigInterface $config - auth provider config
	 */
	public function __construct(ConfigInterface $config)
	{
		$this->config = $config;
	}

	/**
	 * Auth user
	 * @param string $login
	 * @param string $password
	 * @return boolean
	 */
	abstract public function auth($login, $password);

	/**
	 * Get Dvelum user data (object User)
	 * @return array|boolean
	 */
	public function getUserData()
	{
		return $this->userData;
	}
}
