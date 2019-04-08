<?php
use Dvelum\Config;
use Dvelum\Orm;
use Dvelum\Orm\Model;
use Dvelum\Lang;
use Dvelum\View;
use Dvelum\Template;

class Install_Controller
{
    /**
     * Document root
     * @var string
     */
    protected $docRoot;
    /**
     * Template
     * @var \Dvelum\View $template
     */
    protected $template;

    /**
     * @var Lang $localization
     */
    protected $localization;

    /**
     * @var Config\ConfigInterface $applicationConfig
     */
    protected $appConfig;

    protected $lang;
    protected $phpExt;

    protected $session;
    protected $wwwRoot;
    protected $wwwPath;
    protected $_action;

    /**
     * @var Dvelum\Autoload $autoloader
     */
    protected $autoloader;

    public function __construct()
    {
        $this->session = \Dvelum\Store\Factory::get(\Dvelum\Store\Factory::SESSION , 'install');
        $this->appConfig = Config::storage()->get('main.php' , false , false);
        $this->wwwPath = DVELUM_WWW_PATH;
        $this->docRoot = DVELUM_ROOT;
        $this->wwwRoot = '/';

        $uri = $_SERVER['REQUEST_URI'];
        $parts = explode('/', $uri);
        for($i=1;$i<sizeof($parts);$i++)
        {
            if($parts[$i]==='install'){
                break;
            }
            $this->wwwRoot.=$parts[$i].'/';
        }
        /*
         * Set localization storage options
         */
        //Lang::storage()->setConfig(Config::storage()->get('lang_storage.php')->__toArray());
        /*
         * Set template storage options
         */
        View::storage()->setConfig(Config::storage()->get('template_storage.php')->__toArray());

    }

    public function setAutoloader(Dvelum\Autoload $autoloader)
    {
        $this->autoloader = $autoloader;
    }

    public function run()
    {
        $action = Request::post('action', 'string', false);
        $lang = $this->session->get('lang');

        if(!empty($lang))
            $this->lang = $lang;
        else
            $this->lang = 'en';


        $lang = $this->lang;
        $this->appConfig->set('language', $lang);
        /*
         * Register Services
         */
        \Dvelum\Service::register(
            Config::storage()->get('services.php'),
            Config\Factory::create([
                'appConfig' => $this->appConfig,
                'dbManager' => false,
                'cache' => false
            ])
        );

        $this->localization = Lang::storage()->get($this->lang  . '/install.php');

        if($action !== false && method_exists($this, $action . 'Action'))
            $this->_action = strtolower($action) . 'Action';
        else
            $this->_action = 'indexAction';

        $this->{$this->_action}();
    }

    public function setlangAction()
    {
        $lang = Request::post('lang', 'string', false);

        if(empty($lang))
            Response::jsonError();

        $this->session->set('lang', $lang);
        Response::jsonSuccess();
    }

    public function indexAction()
    {
        $this->template = View::factory();
        $this->template->url = './index.php';
        $this->template->lang = $this->lang;
        $this->template->dictionary = $this->localization;
        $this->template->wwwRoot = $this->wwwRoot;

        if($this->lang == 'ru')
            $this->template->license = file_get_contents('./data/gpl-3.0_ru.txt');
        else
            $this->template->license = file_get_contents('./data/gpl-3.0_en.txt');

        echo $this->template->render('install/install.php');
    }

    protected function _checkWritable($path, $required, $msg){
        $data = array();
        $data['title'] = $this->localization->get('WRITE_PERMISSIONS') . ' ' . $path;
       // @chmod($this->docRoot . $path, 0775);
        if(is_writable($path)){
            $data['success'] = true;
        }else{
            $data['success'] = !$required;
            $data['error'] = $msg;
        }
        return $data;
    }

    protected function _checkExtension($extension, $required, $msg = false)
    {
        if(!is_array($extension)){
            $extension = [$extension];
        }

        $data['title'] = $this->localization->get('LIBRARY_CHECK') . ' ' . implode(' / ',$extension);
        $exists = false;

        foreach($extension as $item)
        {
            if(in_array($item, $this->phpExt, true)){
                $exists = true;
            }
        }

        if ($exists) {
            $data['success'] = true;
        } else {
            $data['success'] = !$required;
            $data['error'] = $msg ? $msg : $this->localization->get('LIBRARY_NOT_EXISTS');
        }
        return $data;
    }

    public function firstcheckAction() {
        $data = array();
        $this->phpExt = get_loaded_extensions();

        /**
         * Check for php version
         */
        $data['items'][0]['title'] = $this->localization->get('PHP_V') . ' >= 7.2.0';
        if (version_compare(PHP_VERSION, '7.2.0', '<')) {
            $data['items'][0]['success'] = false;
            $data['items'][0]['error'] = $this->localization->get('UR_PHP_V') . ' ' . PHP_VERSION;
        } else
            $data['items'][0]['success'] = true;

        $extensions = [
            [
                'name'=>'mysqli',
                'accessType'=>'required',
                'msg'=>false
            ],
            [
                'name'=>['memcache','memcached'],
                'accessType'=>'allowed',
                'msg'=>$this->localization->get('PERFORMANCE_WARNING')
            ],
            [
                'name'=>'gd',
                'accessType'=>'required',
                'msg'=>false
            ],
            [
                'name'=>'mbstring',
                'accessType'=>'required',
                'msg'=>false
            ],
            [
               'name' => 'openssl',
               'accessType'=>'allowed',
               'msg'=>$this->localization->get('WARNING')
            ],
            [
                'name'=>'json',
                'accessType'=>'required',
                'msg'=>false
            ]
        ];

        $writablePaths = array(
            array(
                'path'=>'application/configs',
                'accessType'=>'required'
            ),
            array(
                'path'=>'application/locales/local',
                'accessType'=>'required'
            ),
            array(
                'path'=>'temp',
                'accessType'=>'required'
            ),
            array(
                'path'=>'data/logs',
                'accessType'=>'required'
            ),
            array(
                'path'=>'data/key',
                'accessType'=>'required'
            ),
            array(
                'path'=>$this->wwwPath . 'js/lang',
                'accessType'=>'required'
            ),
            array(
                'path'=>$this->wwwPath . 'js/cache',
                'accessType'=>'required'
            ),
            array(
                'path'=>$this->wwwPath . 'css/cache',
                'accessType'=>'required'
            ),
            array(
                'path'=>$this->wwwPath . 'media',
                'accessType'=>'required'
            ),

        );

        foreach ($extensions as $v){
            switch ($v['accessType']){
                case 'required':
                    $data['items'][] = $this->_checkExtension($v['name'], true, $v['msg']);
                    break;
                case 'allowed':
                    $data['items'][] = $this->_checkExtension($v['name'], false, $v['msg']);
                    break;
            }
        }

        foreach ($writablePaths as $v){
            switch ($v['accessType']){
                case 'required':
                    $data['items'][] = $this->_checkWritable($v['path'], true, $this->localization->get('RECORDING_IS_PROHIBITED'));
                    break;
                case 'allowed':
                    $data['items'][] = $this->_checkWritable($v['path'], false, $this->localization->get('RECORDING_IS_PROHIBITED_OK'));
                    break;
            }
        }

        $data['info'] = $this->localization->get('WARNING_ABOUT_RIGHTS');

        Response::jsonSuccess($data);
    }
    public function dbcheckAction()
    {
        $host = Request::post('host', 'str', '');
        $port = Request::post('port', 'int', 0);
        $prefix = Request::post('prefix', 'str', '');

        $installDocs = Request::post('install_docs' , 'boolean' , false);
        $this->session->set('install_docs' , $installDocs);

        $params = array(
            'host'           => $host,
            'username'       => Request::post('username', 'str', false),
            'password'       => Request::post('password', 'str', false),
            'dbname'         => Request::post('dbname', 'str', false),
            'driver'  => 'Mysqli',
            'adapter'  => 'Mysqli',
            'transactionIsolationLevel' => 'default'
        );

        if ($port != 0)
            $params['port'] = $port;

        $flag = false;
        if ($params['host'] && $params['username'] && $params['dbname'])
            try {
                $db = new \Zend\Db\Adapter\Adapter($params);
                @$db->getDriver()->getConnection()->getCurrentSchema();
                $data['success'] = true;
                $data['msg'] = $this->localization->get('SUCCESS_DB_CHECK');
                $flag = true;
            } catch (Exception $e) {
                $data['success'] = false;
                $data['msg'] = $this->localization->get('FAILURE_DB_CHECK') . ' ' . $e->getMessage();
            }
        else {
            $data['success'] = false;
            $data['msg'] = $this->localization->get('REQUIRED_DB_SETTINGS');
        }

        if ($flag){
            try {

                $configs = array(
                     './application/configs/prod/db/default.php',
                     './application/configs/prod/db/error.php',
                     './application/configs/prod/db/sharding_index.php',

                    './application/configs/dev/db/default.php',
                    './application/configs/dev/db/error.php',
                    './application/configs/dev/db/sharding_index.php',

                    './application/configs/test/db/default.php',
                    './application/configs/test/db/error.php',
                    './application/configs/test/db/sharding_index.php',
                );

                $params['prefix'] = $prefix;
                $params['charset'] = 'UTF8';
                $storage = Config::storage();
                foreach($configs as $item) {
                    $cfg = \Dvelum\Config\Factory::create($params, $item);

                    $dirName = dirname($item);
                    if(!file_exists($dirName)){
                        if(!mkdir($dirName,0777,true)){
                            throw new Exception('Cant write '.$dirName);
                        }
                    }
                    if (!$storage->save($cfg))
                        throw new Exception();
                }

            } catch (Throwable $e) {
                $data['success'] = false;
                $data['msg'] = $this->localization->get('CONNECTION_SAVE_FAIL');
            }
        }
        Response::jsonSuccess($data);
    }
    /**
     * Create Database tables
     */
    public function createtablesAction()
    {
        $mainConfig = Config::storage()->get('main.php', false ,true);

        $appClass = $mainConfig->get('application');
        if(!class_exists($appClass))
            throw new Exception('Application class '.$appClass.' does not exist! Check config "application" option!');

        $app = new $appClass($mainConfig);
        $app->setAutoloader($this->autoloader);
        $app->init();

        $dbObjectManager = new Orm\Record\Manager();
        $objects = $dbObjectManager->getRegisteredObjects();

        foreach ($objects as $name)
        {
            $dbObjectBuilder = Orm\Record\Builder::factory($name);
            if(!$dbObjectBuilder->build(false))
                $buildErrors[] = $name; // . ': '.$dbObjectBuilder->getErrors()."<br>".PHP_EOL;
        }

        foreach ($objects as $name)
        {
            $dbObjectBuilder = Orm\Record\Builder::factory($name);
            if(!$dbObjectBuilder->buildForeignKeys())
                $buildErrors[] = $name; // . ': '.$dbObjectBuilder->getErrors()."<br>".PHP_EOL;
        }

        if(!empty($buildErrors))
            Response::jsonError($this->localization->get('BUILD_ERR') . ' ' . implode(', ', $buildErrors));
        else
            Response::jsonSuccess('', array('msg'=>$this->localization->get('DB_DONE')));
    }

    public function setuserpassAction()
    {
        $pass = Request::post('pass', 'str', '');
        $passConfirm = Request::post('pass_confirm', 'str', '');
        $lang = Request::post('lang', 'string', 'en');
        $timezone = Request::post('timezone', 'string', '');
        $adminpath = strtolower(Request::post('adminpath', 'string', ''));
        $user = Request::post('user',  'str', '');

        $errors = array();

        if(!strlen($user))
            $errors[] = $this->localization->get('INVALID_USERNAME');

        if(empty($pass) || empty($passConfirm) || $pass != $passConfirm)
            $errors[] = $this->localization->get('PASS_MISMATCH');

        $timezones = timezone_identifiers_list();
        if(empty($timezone) || !in_array($timezone, $timezones, true))
            $errors[] = $this->localization->get('TIMEZOME_REQUIRED');

        if(!Validator_Alphanum::validate($adminpath)  || is_dir('./dvelum/app/Backend/'.ucfirst($adminpath)))
            $errors[] = $this->localization->get('INVALID_ADMINPATH');

        if(!empty($errors))
            Response::jsonError(implode(', ', $errors));

        $mainConfig = array(
            'development' => 1,
            'timezone' => $timezone,
            'adminPath'=> $adminpath,
            'language' =>$lang,
            'wwwroot' => $this->wwwRoot
        );

        $mainCfgStorage = Config::storage();
        $mainCfg = $mainCfgStorage->get('main.php' , false , false);
        $ormCfg = $mainCfgStorage->get('orm.php');
        $writePath = $mainCfgStorage->getWrite();

        if(!is_dir(dirname($writePath)) && !@mkdir($writePath , 0755, true)){
            Response::jsonError($this->localization->get('CANT_WRITE_FS').' '.dirname($writePath));
        }

        if(!\Dvelum\Utils::exportArray($writePath . 'main.php' , $mainConfig))
            Response::jsonError($this->localization->get('CANT_WRITE_FS').' '.$writePath);


        if(extension_loaded('openssl')){
            $service = new \Dvelum\Security\CryptService($mainCfgStorage->get('crypt.php'));
            if($service->canCrypt()){
                try{
                    $key = $service->createPrivateKey();
                }catch (\Exception $e){
                    $key = md5(uniqid(md5(time())));
                }
            }
        }else{
            $key = md5(uniqid(md5(time())));
        }

        $cryptConfig = $mainCfgStorage->get('crypt.php');

        if(!file_put_contents($cryptConfig->get('key'), $key)){
            Response::jsonError($this->localization->get('CANT_WRITE_FS') . ' ' . $cryptConfig->get('key'));
        }

        $mainConfig = Config::storage()->get('main.php', false ,true);

        /*
         * Starting the application
         */
        $appClass = $mainConfig->get('application');
        if(!class_exists($appClass))
            throw new Exception('Core class '.$appClass.' does not exist! Check config "application" option!');

        $app = new $appClass($mainConfig);
        $app->setAutoloader($this->autoloader);
        $app->init();

        $this->_compileLangs($mainConfig);

        if(!$this->_prepareRecords($pass, $user))
            Response::jsonError($this->localization->get('CANT_WRITE_TO_DB'));

        Response::jsonSuccess(array('link'=>$adminpath));
    }

    protected function _compileLangs($mainConfig){
        $langManager = new \Dvelum\App\Backend\Localization\Manager($mainConfig);
        try{
            $langManager->compileLangFiles();
        }catch (Exception $e){
            Response::jsonError($e->getMessage());
        }
    }

    protected function _prepareRecords($adminPass, $adminName)
    {
        $objectToClean = [
            'User',
            'Group',
            'Permissions',
            'Page',
            'Blocks',
            'Menu',
            'Menu_Item'
        ];

        try
        {
            foreach ($objectToClean as $object){
                $model = Model::factory($object);
                $model->getDbConnection()->delete($model->table());
            }

            // Add user. User cannot be created using ORM it causes HistoryLog error
            $userModel = Model::factory('User');
            $db = $userModel->getDbConnection();
            try{
                $db->beginTransaction();
                $db->insert($userModel->table(),[
                    'name' =>'Admin',
                    'login' => $adminName,
                    'pass' => password_hash($adminPass , PASSWORD_DEFAULT),
                    'enabled' => true,
                    'admin' => true,
                    'registration_date' => date('Y-m-d H:i:s'),
                    'confirmation_code' => md5(date('Y-m-d H:i:s')),
                    'group_id' => null,
                    'confirmed' => true,
                    'avatar' => '',
                    'registration_ip' => $_SERVER['REMOTE_ADDR'],
                    'last_ip' => $_SERVER['REMOTE_ADDR'],
                    'confirmation_date' =>date('Y-m-d H:i:s')
                ]);
                $userId = $db->lastInsertId($userModel->table());
                $db->commit();
            }catch (Exception $e){
                return false;
            }

            // Add group
            $group = Orm\Record::factory('Group');
            $group->setValues(array(
                'title'=>$this->localization->get('ADMINISTRATORS') ,
                'system'=>true
            ));
            $group->save(true);
            $groupId = $group->getId();

            // Set user group
            try{
                $db->update($userModel->table(),['group_id'=>$groupId],'id = '.$userId);
            }catch (Exception $e){
                return false;
            }

            // Add permissions
            $permissionsModel = Model::factory('Permissions');
            $modulesManager = new Modules_Manager();
            $modules = $modulesManager->getList();

            foreach ($modules as $name=>$config)
                if(!$permissionsModel->setGroupPermissions($groupId , $name , true, true , true , true))
                    return false;

            $u = Dvelum\App\Session\User::getInstance();
            $u->setId($userId);
            $u->setAuthorized();

            // Add index Page
            $page = Orm\Record::factory('Page');
            $page->setValues(array(
                'code'=>'index',
                'is_fixed'=>1,
                'html_title'=>'Index',
                'menu_title'=>'Index',
                'page_title'=>'Index',
                'meta_keywords'=>'',
                'meta_description'=>'',
                'parent_id'=>null,
                'text' =>'[Index page content]',
                'func_code'=>'',
                'order_no' => 1,
                'show_blocks'=>true,
                'published'=>true,
                'published_version'=>0,
                'date_created'=>date('Y-m-d H:i:s'),
                'date_updated'=>date('Y-m-d H:i:s'),
                'blocks'=>'',
                'theme'=>'default',
                'date_published'=>date('Y-m-d H:i:s'),
                'in_site_map'=>false,
                'default_blocks'=>true
            ));

            if(!$page->saveVersion() || !$page->publish())
                return false;

            // add menu
            $topMenu = Orm\Record::factory('Menu');
            $topMenu->setValues([
                'code' =>'headerMenu',
                'title' => 'Header Menu'
            ]);

            if(!$topMenu->save())
                return false;

            $topMenuItem = Orm\Record::factory('Menu_Item');
            $topMenuItem->setValues([
                'page_id'=>$page->getId(),
                'title'=>'Index',
                'menu_id'=>$topMenu->getId(),
                'order'=>0,
                'parent_id'=>null,
                'tree_id'=>1,
                'link_type'=>'page',
                'published'=>1
            ]);

            if(!$topMenuItem->save())
                return false;


            $bottomMenu = Orm\Record::factory('Menu');
            $bottomMenu->setValues([
                'code' =>'footerMenu',
                'title' => 'Footer Menu'
            ]);
            if(!$bottomMenu->save())
                return false;

            $bottomMenuItem = Orm\Record::factory('Menu_Item');
            $bottomMenuItem->setValues([
                'page_id'=>$page->getId(),
                'title'=>'Index',
                'menu_id'=>$bottomMenu->getId(),
                'order'=>0,
                'parent_id'=>null,
                'tree_id'=>1,
                'link_type'=>'page',
                'published'=>1
            ]);
            if(!$bottomMenuItem->save())
                return false;


            $leftMenu = Orm\Record::factory('Menu');
            $leftMenu->setValues([
                'code' =>'menu',
                'title' => 'Menu'
            ]);
            if(!$leftMenu->save())
                return false;

            $leftMenuItem = Orm\Record::factory('Menu_Item');
            $leftMenuItem->setValues([
                'page_id'=>$page->getId(),
                'title'=>'Index',
                'menu_id'=>$leftMenu->getId(),
                'order'=>0,
                'parent_id'=>null,
                'tree_id'=>1,
                'link_type'=>'page',
                'published'=>1
            ]);
            if(!$leftMenuItem->save())
                return false;


            // add blocks
            $topMenuBlock = Orm\Record::factory('Blocks');
            $topMenuBlock->setValues([
                'title' => 'Header Menu',
                'published' =>1,
                'show_title'=>false,
                'published_version'=>0,
                'is_system'=>1,
                'sys_name'=>'Block_Menu_Top',
                'is_menu' =>1,
                'menu_id'=>$topMenu->getId(),
                'last_version'=>0
            ]);
            if(!$topMenuBlock->saveVersion() || !$topMenuBlock->publish())
                return false;


            $bottomMenuBlock = Orm\Record::factory('Blocks');
            $bottomMenuBlock->setValues([
                'title' => 'Footer Menu',
                'published' =>1,
                'show_title'=>false,
                'published_version'=>0,
                'is_system'=>1,
                'sys_name'=>'Block_Menu_Footer',
                'is_menu' =>1,
                'menu_id'=>$bottomMenu->getId(),
                'last_version'=>0
            ]);
            if(!$bottomMenuBlock->saveVersion() || !$bottomMenuBlock->publish())
                return false;

            $menuBlock = Orm\Record::factory('Blocks');
            $menuBlock->setValues([
                'title' => 'Menu',
                'published' =>1,
                'show_title'=>true,
                'published_version'=>0,
                'is_system'=>1,
                'sys_name'=>'Block_Menu',
                'is_menu' =>1,
                'menu_id'=>$leftMenu->getId(),
                'last_version'=>0
            ]);
            if(!$menuBlock->saveVersion() || !$menuBlock->publish())
                return false;

            $testBlock = Orm\Record::factory('Blocks');
            $testBlock->setValues([
                'title' => 'Test Block',
                'published' =>1,
                'show_title'=>true,
                'published_version'=>0,
                'text'=>'
                    <ul>
                        <li>Articles</li>
                        <li>Pages</li>
                        <li>Blocks</li>
                        <li>Users</li>
                    </ul>
                ',
                'is_system'=>false,
                'is_menu' =>false,
                'last_version'=>0
            ]);
            if(!$testBlock->saveVersion() || !$testBlock->publish())
                return false;

            $blockMapping = Model::factory('Blockmapping');
            $blockMapping->clearMap(0);
            $blockMap = [
                'top-blocks' => [
                    ['id'=>$topMenuBlock->getId()]
                ],
                'bottom-blocks' => [
                    ['id'=>$bottomMenuBlock->getId()]
                ],
                'left-blocks' => [
                    ['id'=>$menuBlock->getId()]
                ],
                'right-blocks' => [
                    ['id'=>$testBlock->getId()]
                ]
            ];

            foreach ($blockMap as $place=>$items)
                $blockMapping->addBlocks(0 , $place , \Dvelum\Utils::fetchCol('id', $items));

            return true;

        } catch (Exception $e){
            Response::jsonError($e->getMessage());
            return false;
        }
    }
}
