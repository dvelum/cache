<?php

/**
 *    DVelum project https://github.com/dvelum/dvelum
 *    Copyright (C) 2011-2017  Kirill Yegorov
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Dvelum\Cache;

/**
 * Cache Backend Memcached
 * Simple Memcached adapter
 * @author Kirill Yegorov 2011-2017
 */
class Memcached extends AbstractAdapter implements CacheInterface
{
    public const DEFAULT_HOST = '127.0.0.1';
    public const DEFAULT_PORT = 11211;
    public const DEFAULT_PERSISTENT_KEY = false;
    public const DEFAULT_WEIGHT = 1;
    public const DEFAULT_TIMEOUT = 1;

    public const DEFAULT_KEY_PREFIX = '';
    public const DEFAULT_COMPRESSION = false;
    public const DEFAULT_SERIALIZER = \Memcached::SERIALIZER_PHP;
    public const DEFAULT_LIFETIME = 0;
    public const DEFAULT_NORMALIZE_KEYS = true;

    /**
     * @var \Memcached
     */
    protected $memcached = null;

    /**
     * @param array<string,mixed> $settings
     *
     *        'servers' => array(
     *            array(
     *                'host' => self::DEFAULT_HOST,
     *                'port' => self::DEFAULT_PORT,
     *                'weight'  => self::DEFAULT_WEIGHT,
     *            )
     *         ),
     *        'compression' => self::DEFAULT_COMPRESSION,
     *        'normalizeKeys'=>sef::DEFAULT_NORMALIZE_KEYS,
     *        'defaultLifeTime=> self::DEFAULT_LIFETIME
     *        'keyPrefix'=>self:DEFAULT_KEY_PREFIX
     *      'persistent_key' => self::DEFAULT_PERSISTENT_KEY
     * @return array<string,mixed>
     */
    protected function initConfiguration(array $settings): array
    {
        if (!isset($settings['compression'])) {
            $settings['compression'] = self::DEFAULT_COMPRESSION;
        }

        if (!isset($settings['serializer'])) {
            $settings['serializer'] = self::DEFAULT_SERIALIZER;
        }

        if (!isset($settings['normalizeKeys'])) {
            $settings['normalizeKeys'] = self::DEFAULT_NORMALIZE_KEYS;
        }

        if (!isset($settings['keyPrefix'])) {
            $settings['keyPrefix'] = self::DEFAULT_KEY_PREFIX;
        }

        if (!isset($settings['persistent_key'])) {
            $settings['persistent_key'] = self::DEFAULT_PERSISTENT_KEY;
        }

        return $settings;
    }

    /**
     * @return void
     */
    protected function connect()
    {
        $settings = $this->getSettings();

        if ($settings['persistent_key']) {
            $this->memcached = new \Memcached($settings['persistent_key']);
        } else {
            $this->memcached = new \Memcached();
        }

        $this->memcached->setOptions([
                                         \Memcached::OPT_COMPRESSION => $settings['compression'],
                                         \Memcached::OPT_SERIALIZER => $settings['serializer'],
                                         \Memcached::OPT_PREFIX_KEY => $settings['keyPrefix'],
                                         \Memcached::OPT_LIBKETAMA_COMPATIBLE => true
                                     ]);

        if (!count($this->memcached->getServerList())) {
            foreach ($settings['servers'] as $server) {
                if (!isset($server['port'])) {
                    $server['port'] = self::DEFAULT_PORT;
                }

                if (!isset($server['weight'])) {
                    $server['weight'] = self::DEFAULT_WEIGHT;
                }
                $this->memcached->addServer($server['host'], $server['port'], $server['weight']);
            }
        }
    }

    /**
     * Save some string data into a cache record
     * @param  string $id Cache id
     * @param  mixed $data Data to cache
     * @param  int | false $specificLifetime If != false, set a specific lifetime
     *  for this cache record (null => infinite lifetime)
     * @return bool True if no problem
     */
    public function save(string $id, $data, $specificLifetime = false): bool
    {
        if (!isset($this->memcached)) {
            $this->connect();
        }

        if ($specificLifetime === false) {
            $specificLifetime = $this->settings['defaultLifeTime'];
        }

        $id = $this->prepareKey($id); // cache id may need normalization
        try {
            $result = $this->memcached->set($id, $data, $specificLifetime);
            $this->stat['save']++;
            return $result;
        } catch (\Error $e) {
            return false;
        }
    }

    /**
     * @param string $id
     * @param mixed $data
     * @param int|false $specificLifetime If != false, set a specific lifetime
     *  for this cache record (null => infinite lifetime)
     * @return bool
     */
    public function add(string $id, $data, $specificLifetime = false): bool
    {
        if (!isset($this->memcached)) {
            $this->connect();
        }

        if ($specificLifetime === false) {
            $specificLifetime = $this->settings['defaultLifeTime'];
        }
        $id = $this->prepareKey($id); // cache id may need normalization
        try {
            $result = $this->memcached->add($id, $data, $specificLifetime);
            $this->stat['add']++;
            return $result;
        } catch (\Error $e) {
            return false;
        }
    }

    /**
     * Remove a cache record : bool
     * @param string $id Cache id
     * @return bool True if no problem
     */
    public function remove(string $id): bool
    {
        if (!isset($this->memcached)) {
            $this->connect();
        }

        $id = $this->prepareKey($id); // cache id may need normalization
        $this->stat['remove']++;
        return $this->memcached->delete($id);
    }

    /**
     * Clean some cache records
     * @return bool True if no problem
     */
    public function clean(): bool
    {
        if (!isset($this->memcached)) {
            $this->connect();
        }

        return $this->memcached->flush();
    }

    /**
     * Load data from cache
     * @param  string $id Cache id
     * @return mixed|null Cached null on not found
     */
    public function load(string $id)
    {
        if (!isset($this->memcached)) {
            $this->connect();
        }

        $id = $this->prepareKey($id); // cache id may need normalization

        $data = $this->memcached->get($id);
        $this->stat['load']++;
        if ($data === false && $this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            return null;
        }
        return $data;
    }

    /**
     * Get Memcache object link
     * @return \Memcached
     */
    public function getHandler(): \Memcached
    {
        if (!isset($this->memcached)) {
            $this->connect();
        }
        return $this->memcached;
    }

    /**
     * Reset connection
     */
    public function close(): void
    {
        if (!isset($this->memcached)) {
            return;
        }

        $this->memcached->quit();
        unset($this->memcached);
    }
}
