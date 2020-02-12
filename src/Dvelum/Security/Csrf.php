<?php
/**
 * DVelum project https://github.com/dvelum/dvelum-core , https://github.com/dvelum/dvelum
 *
 * MIT License
 *
 * Copyright (C) 2011-2020  Kirill Yegorov
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */
declare(strict_types=1);

namespace Dvelum\Security;

use Dvelum\Request;
use Dvelum\Store\AdapterInterface;
use Dvelum\Store\Factory;
use Dvelum\Store\Session;
use Dvelum\Utils;
use \Exception;

/**
 * Security_Csrf class handles creation and validation
 * of tokens aimed at anti-CSRF protection.
 * @author Kirill Egorov
 * @package Security
 * @uses Utils, AdapterInterface , Session , Request
 */
class Csrf
{
    /**
     * A constant value, the name of the header parameter carrying the token
     * @var string
     */
    const HEADER_VAR = 'HTTP_X_CSRF_TOKEN';

    /**
     * A constant value, the name of the token parameter being passed by POST request
     * @var string
     */
    const POST_VAR = 'xscrftoken';

    /**
     * Token lifetime (1 hour 3600s)
     * @var integer
     */
    static protected $lifetime = 3600;
    /**
     * Limit of tokens count to perform cleanup
     * @var integer
     */
    static protected $cleanupLimit = 300;

    /**
     * Token storage
     * @var AdapterInterface|false
     */
    static protected $storage = false;

    /**
     * Set token storage implementing the Store_interface
     * @param AdapterInterface $store
     */
    static public function setStorage(AdapterInterface $store)
    {
        static::$storage = $store;
    }

    /**
     * Set config options (storage , lifetime , cleanupLimit)
     * @param array $options
     * @throws Exception
     */
    static public function setOptions(array $options)
    {
        if (isset($options['storage'])) {
            if ($options['storage'] instanceof AdapterInterface) {
                static::$storage = $options['storage'];
            } else {
                throw new Exception('invalid storage');
            }
        }

        if (isset($options['lifetime'])) {
            static::$lifetime = intval($options['lifetime']);
        }

        if (isset($options['cleanupLimit'])) {
            static::$cleanupLimit = intval($options['cleanupLimit']);
        }

    }

    public function __construct()
    {
        if (!self::$storage) {
            self::$storage = Factory::get(Factory::SESSION, 'security_csrf');
        }
    }

    /**
     * Create and store token
     * @return string
     */
    public function createToken()
    {
        /*
         * Cleanup storage
         */
        if (self::$storage->getCount() > self::$cleanupLimit) {
            $this->cleanup();
        }

        $token = md5(Utils::getRandomString(16) . uniqid('', true));
        self::$storage->set($token, time());
        return $token;
    }

    /**
     * Check if token is valid
     * @param string $token
     * @return boolean
     */
    public function isValidToken($token)
    {
        if (!self::$storage->keyExists($token)) {
            return false;
        }

        if (time() < intval(self::$storage->get($token)) + self::$lifetime) {
            return true;
        } else {
            self::$storage->remove($token);
            return false;
        }
    }

    /**
     * Remove tokens with expired lifetime
     */
    public function cleanup() : void
    {
        $tokens = self::$storage->getData();
        $time = time();

        foreach ($tokens as $k => $v) {
            if (intval($v) + self::$lifetime < $time) {
                self::$storage->remove($k);
            }
        }
    }

    /**
     * Invalidate (remove) token
     * @param string $token
     */
    public function removeToken($token)
    {
        self::$storage->remove($token);
    }

    /**
     * Check POST request for a token
     * @param string $tokenVar - Variable name in the request
     * @return boolean
     */
    public function checkPost($tokenVar = self::POST_VAR)
    {
        $var = Request::factory()->post($tokenVar, 'string', false);
        if ($var !== false && $this->isValidToken($var)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check HEADER for a token
     * @param string $tokenVar - Variable name in the header
     * @return boolean
     */
    public function checkHeader($tokenVar = self::HEADER_VAR)
    {
        $var = Request::factory()->server($tokenVar, 'string', false);
        if ($var !== false && $this->isValidToken($var)) {
            return true;
        } else {
            return false;
        }
    }
}