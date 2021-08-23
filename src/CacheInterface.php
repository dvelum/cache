<?php

/**
 *  DVelum project https://github.com/dvelum/dvelum , https://github.com/k-samuel/dvelum , http://dvelum.net
 *  Copyright (C) 2011-2017  Kirill Yegorov
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Dvelum\Cache;

/**
 * Simple interface for cache adapters
 * @package Dvelum\Cache
 */
interface CacheInterface
{
    /**
     * Add / replace cache variable
     * @param string $key
     * @param mixed $data
     * @param integer|false $lifetime - optional
     * @return bool
     */
    public function save(string $key, $data, $lifetime = false): bool;

    /**
     * Load cached variable
     * @param string $key
     * @return mixed|null
     */
    public function load(string $key);

    /**
     * Clear cache
     * @return bool
     */
    public function clean(): bool;

    /**
     * Remove cached variable
     * @param string $key
     * @return bool
     */
    public function remove(string $key): bool;

    /**
     * Get statistics for cache operation
     * @return array<string,mixed>
     */
    public function getOperationsStat(): array;

    /**
     * Reset operations statistics
     */
    public function resetOperationsStat(): void;

    /**
     * Reset connection
     * @return void
     */
    public function close(): void;

    /**
     * @param string $id
     * @param mixed $data
     * @param int|false $specificLifetime
     * @return bool
     */
    public function add(string $id, $data, $specificLifetime = false): bool;
}
