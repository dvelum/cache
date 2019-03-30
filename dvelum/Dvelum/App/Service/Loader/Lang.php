<?php
/**
 *  DVelum project http://code.google.com/p/dvelum/ , https://github.com/k-samuel/dvelum , http://dvelum.net
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

namespace Dvelum\App\Service\Loader;

use Dvelum\Config;
use Dvelum;

class Lang extends AbstractAdapter
{
    public function loadService()
    {
        $language = $this->config->get('appConfig')->get('language');

        $langService = new Dvelum\Lang();
        $langService->addLoader(
            $language,
            $language . '.php',
            Config\Factory::File_Array
        );
        $langService->setDefaultDictionary($language);
        $langStorage = $langService->getStorage();
        $langStorage->setConfig(Config\Factory::storage()->get('lang_storage.php')->__toArray());

        return $langService;
    }
}