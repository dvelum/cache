<?php
/**
 *  DVelum project https://github.com/dvelum/dvelum
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
 */
declare(strict_types=1);

namespace Dvelum\Orm\Record\Config;

use Dvelum\Config\ConfigInterface;
use Dvelum\Config\Storage\StorageInterface;
use Dvelum\Lang;

class Translator
{
	protected $commonPath = '';
	protected $localesDir = '';

	protected $translation = false;
	/**
	 * @var \Lang
	 */
	protected $lang = false;
	/**
	 * @param string $commonPath - path to translation Array config
     * @param string $localesDir - locales directory (relative)
	*/
	public function __construct(string $commonPath, string $localesDir)
	{
		$this->commonPath = $commonPath;
		$this->localesDir = $localesDir;
	}

    /**
     * Get object fields translation
     * @param string $objectName
     * @return array|null
     */
	public function getTranslation(string $objectName) : ?array
	{
		if(!$this->translation){
			$this->translation = Lang::storage()->get($this->commonPath, true, true)->__toArray();
		}

		if(!isset($this->translation[$objectName])){
            $localFile = $this->localesDir . strtolower($objectName) . '.php';

		    if(Lang::storage()->exists($localFile)) {
                $this->translation[$objectName] = Lang::storage()->get($localFile, true, true)->__toArray();
            }
        }
		return $this->translation;
	}

    /**
     * Get translations storage
     * @return StorageInterface
     */
	public function getStorage() : StorageInterface
    {
        return \Lang::storage();
    }

	/**
	 * Get common config path
	 * @return string
	 */
	public function getcommonConfigPath() : string
	{
		return $this->commonPath;
	}
	
	/**
	 * Translate Object config
	 * @param string $objectName
	 * @param array & $objectConfig
	 */
	public function translate($objectName , & $objectConfig)
	{
		$translation = $this->getTranslation($objectName);

		if($translation)
		{
			if(isset($translation[$objectName]['title']) && strlen($translation[$objectName]['title']))
				$objectConfig['title'] = $translation[$objectName]['title'];
			else
				$objectConfig['title'] = $objectName;
			 
			if(isset($translation[$objectName]['fields']) && is_array($translation[$objectName]['fields']))
				$fieldTranslates = $translation[$objectName]['fields'];
		}
		else
		{
			if(isset($dataLink[$objectName]['title']) && strlen($objectConfig[$objectName]['title']))
				$objectConfig['title'] = $objectConfig[$objectName]['title'];
			else
				$objectConfig['title'] = $objectName;
		}
		 
		foreach ($objectConfig['fields'] as $k => &$v)
		{
			if(isset($v['lazyLang']) && $v['lazyLang'])
			{
				if(!$this->lang)
					$this->lang = Lang::lang();

				if(isset($v['title']))
					$v['title'] = $this->lang->get($v['title']);
				else 
					$v['title'] = '';
			}	
			elseif(isset($fieldTranslates[$k]) && strlen($fieldTranslates[$k]))
			{
				$v['title'] = $fieldTranslates[$k];
			}
			elseif(!isset($v['title']) || !strlen($v['title']))
			{
				$v['title'] = $k;
			}
		}unset($v);
	}
}