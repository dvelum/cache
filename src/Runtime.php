<?php

/**
 * DVelum project https://github.com/dvelum/cache , https://github.com/dvelum/dvelum
 *
 * MIT License
 *
 * Copyright (C) 2020-2021  Kirill Yegorov
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

namespace Dvelum\Cache;

/**
 * Class Runtime
 * Адаптер кэширования данных в рантайме
 * @package App\Cache
 */
class Runtime implements CacheInterface
{
    /**
     * @var array<string,mixed>
     */
    protected $data;
    /**
     * @var array<string,mixed>
     */
    protected $stat = [
        'save' => 0,
        'remove' => 0,
        'load' => 0
    ];

    private const DATA = 0;
    private const TIME = 1;

    /**
     * @inheritDoc
     */
    public function save(string $key, $data, $lifetime = false): bool
    {
        $this->stat['save']++;

        if ($lifetime) {
            $lifetime = time() + $lifetime;
        } else {
            $lifetime = time() + 3600 * 24 * 365;
        }
        $this->data[$key] = [
            self::DATA => $data,
            self::TIME => $lifetime
        ];
        return true;
    }

    /**
     * @inheritDoc
     */
    public function load(string $key)
    {
        $this->stat['load']++;
        if (!isset($this->data[$key])) {
            return null;
        }

        if ($this->data[$key][self::TIME] > time()) {
            return $this->data[$key][self::DATA];
        }

        unset($this->data[$key]);
    }

    /**
     * @inheritDoc
     */
    public function clean(): bool
    {
        $this->data = [];
        return true;
    }

    /**
     * @inheritDoc
     */
    public function remove(string $key): bool
    {
        $this->stat['remove']++;
        unset($this->data[$key]);
        return true;
    }

    /**
     * @inheritDoc
     * @return array<string,mixed>
     */
    public function getOperationsStat(): array
    {
        return $this->stat;
    }

    /**
     * @inheritDocs
     */
    public function resetOperationsStat(): void
    {
        $this->stat = [
            'load' => 0,
            'save' => 0,
            'remove' => 0
        ];
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        $this->clean();
    }

    /**
     * @param string $id
     * @param mixed $data
     * @param int|false $specificLifetime
     * @return bool
     */
    public function add(string $id, $data, $specificLifetime = false): bool
    {
        $this->stat['add']++;

        if (isset($this->data[$id]) && $this->data[$id][self::TIME] < time()) {
            return false;
        }

        if ($specificLifetime) {
            $lifetime = time() + $specificLifetime;
        } else {
            $lifetime = time() + 3600 * 24 * 365;
        }
        $this->data[$id] = [
            self::DATA => $data,
            self::TIME => $lifetime
        ];
        return true;
    }
}
