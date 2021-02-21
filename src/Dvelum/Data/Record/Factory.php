<?php
/**
 * DVelum project https://github.com/dvelum/dvelum-core , https://github.com/dvelum/dvelum
 *
 * MIT License
 *
 * Copyright (C) 2011-2021 Kirill Yegorov
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

namespace Dvelum\Data\Record;

use Dvelum\Config\ConfigInterface;
use Dvelum\Config as CoreConfig;
use Dvelum\Data\Record\Export\Database;
use \InvalidArgumentException;
use \Exception;


class Factory
{
    /**
     * @var ConfigInterface $config
     */
    protected $config;

    public function __construct(ConfigInterface $config){
        $this->config = $config;
    }

    /**
     * @param string $recordName
     * @return Record
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function create(string $recordName) : Record
    {
        if(!$this->config->offsetExists($recordName)){
            throw new InvalidArgumentException('Undefined data record '. $recordName);
        }
        $info = $this->config->get($recordName);
        $config = CoreConfig::storage()->get($info['config'])->__toArray();
        return new Record($recordName, new Config($config));
    }

    /**
     * @return Database
     */
    public function getDbExport() : Database
    {
        return new Database();
    }
}