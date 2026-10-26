<?php

namespace tv;

use Ninja\Website;
use Ninja\DatabaseTable;
use Ninja\Authentication;
use tv\Controllers\Pages;

class tvWebsite implements Website
{
    private $recordTable;
    private $userTable;
    private $pagesTable;
    private $slotTable;
    private $pdo;
    private $authentication;
    private $pages;
    private $home = 'home';
    private $currentpage = '';
    private $setallowed = false;

    private function makeTable($dependency, $self, $pk, $tablename, $entity = '\stdClass', ...$args)
    {
        if ($dependency) {
            $table = new DatabaseTable($this, $pk, $tablename, $entity);
            if ($self) {
                $table->setConstructorArgs($table, ...$args);
            } else {
                $table->setConstructorArgs(...$args);
            }
            return $table;
        }
        return null;
    }

    private function prepareRoutes(string $k, string $v)
    {
        $route = pregMatch('/photos/i', $v) ? "/gallery/display" : "/$k/";
        //$route = "/$k/";
       // $val = ($v === strtoupper($v)) ? strtolower($v) : $v;
        $val = ($v === strtoupper($v)) ? $v : $v;
        return [$k, ucwords($val)];
    }

    public function fixNavDisplay($nav)
    {
        foreach ($nav as $k => $v) {
            if (strtoupper($v) === $v) {
                $nav[$k] = ucwords($k);
            }
        }
        return $nav;
    }

    private function mapSubClassName(string $k, bool $super = false)
    {
        $lib = ['asset' => 'resource', 'gallery' => 'hockney'];
        return isset($lib[$k]) && $super ? $lib[$k] : $k;
    }

    public function getDefaultRoute(): string
    {
        return $this->home;
    }

    public function getProp($str = 'setallowed')
    {
        return isset($this->{$str});
    }

    public function getActivePages(bool $flag = false)
    {
        $activepages = $this->selectActivePages();
        $output = [];
        foreach ($activepages as $tablename) {
            if ($this->select($tablename, $flag)) {
                $output[] = $tablename;
            }
        }
        return $output;
    }

    public function setCurrentPage(string $str)
    {
        $this->currentpage = strtolower($str);
    }

    public function getCurrentPage(): mixed
    {
        return $this->currentpage;
    }

    public function queryCurrentPage($name = ''): mixed
    {
        $pps = $this->getActivePages();
        $_name = $name ? $name : $this->ispublic;
        $ret = inArray($_name, $pps) ? $_name : '';
        $partials = [curry2L('safeNull'), curry2('array_search')];
        [$f1, $f2] = array_map(curry2('simpleInvoke')($pps), $partials);
        $finder = composer($f1, $f2);
        $ret = $_name ? $finder($_name) : '';
        $this->setCurrentPage($ret);
        return $ret ? $ret : !$_name;
    }

    public function setHome(string $str): string
    {
        $this->home = $str;
        return $str;
    }
    //
    public function validatePageName($str)
    {
        $private = \in_array(strtolower($str), ['pages', 'pending']) ? true : false;
        $public = \in_array(strtolower($str), WEBPAGES) ? true : false;
        /*
        must include pages to allow for pages/retour/approve and..
        pending to allow for deletion of pending page ie superuser leaves name at pending instead of a legit page name to instigate a deletion*/
        return [$public, $private];
    }

    public function getPDO()
    {
        return $this->pdo;
    }

    public function __construct(private $ispublic)
    {
        include CONNECT;
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        //$this->pdo->exec('SET NAMES "utf8"');
        $this->recordTable = new DatabaseTable($this, 'recordings', 'id', '\tv\Entity\Record', [&$this->recordTable]);
        $this->userTable = new DatabaseTable($this, 'users', 'id', '\PoloAfrica\Entity\User', [&$this->userTable]);
        if ($this->userTable) {
            $this->authentication = new Authentication($this->userTable, 'email', 'password');
            $this->slotTable = $this->makeTable($this->userTable, true, 'pp', 'id', '\PoloAfrica\Entity\Slot');
            $this->pagesTable = $this->makeTable($this->slotTable, false, 'pages', 'id', '\tv\Entity\Page', $this->slotTable);
        }

    }

    private function validate($key, $array)
    {
        $k = ($key === 'logger') ? 'login' : $key;
        return \in_array($k, $array) ? $k : null;
    }

    //normalise strings by removing forward slashes
    private function baseAccess($uri)
    {
        $f = partial('preg_replace', '|\/|', '');
        $eq = fn($a, $b) => $a === $b;
        $arr = array_map($f, [BADMINTON, USER_LIST]);
        //if at least one matches
        return array_filter($arr, partial($eq, $f($uri)));
    }

    private function factory(string $id, array $args)
    {
        $controllers = [
            'article' => ['table' => ['id', 'title', 'summary', 'content', 'attr_id', 'page', 'pubdate']],
            'asset' => ['table' => ['id','alt', 'attr_id', 'path', 'article_id', 'date']],
            'resource' => ['table' => ['id','alt', 'attr_id', 'path', 'article_id', 'date']],
            'user' => ['table' => ['id','name', 'email', 'permissions', 'password']],
            'gallery' => ['table' => ['id', 'alt', 'path', 'date'], 'foreignTable' => ['id', 'orient', 'pc_id']],
            'hockney' => ['table' => ['id', 'alt', 'path', 'date'], 'foreignTable' => ['id', 'orient', 'pc_id']],
            'pages' => ['table' => ['id', 'name', 'title', 'content', 'description'/*, 'image', 'url'*/]],
            'login' => [],
            'tvlist' => ['table' => 'id', 'title', 'page', 'recdate']
        ];

        //https://stackoverflow.com/questions/534159/instantiate-a-class-from-a-variable-in-php#:~:text=Put%20the%20classname%20into%20a,%24classname(%22xyz%22)%3B
        $key = $this->validate($id, array_keys($controllers));
        if ($key) {
            $klas = "tv\\Controllers\\" . ucwords($key);
            $controller = new $klas(...$args);
            if (($controllers[$key]) !== []) {
                $controller->setAllowed($controllers[$key]);
            } else {
                $setallowed = true;
            }
            return $controller;
        }
    }

    private function factoryBridge(string $name, array $mandatory, array $optional, array $user)
    {
        $id = array_pop($user) ?? $name;
        //?? why would $id === $name given $user would be an array of two integers user id and permissions
        //unless so called $user array is not what it sounds; investigate
        $id = ($id === $name) ? $id : $name;
        return $this->factory($id, [...$mandatory, ...$optional, ...$user]);
    }

    public function setNavBar()
    {
       return [];
        if(!isset($this->userTable)){
            retour();
        }
        $navlist = $this->getActivePages();
        $nav = $_SESSION['nav'] ?? null;
        $bar = $nav ? \count($nav) : 0;

        if (empty($_SESSION['nav']) || \count($navlist) !== $bar) {
            $pp = $this->pagesTbl->getEntity();
            $pp->setName('pp');
            $navlist = $this->getActivePages();
            $list = array_map(fn($o) => $o->title, $pp->findAll('id')); //current order
            $navbar = [];
            foreach ($list as $l) {
                if (in_array($l, $navlist)) {
                    $navbar[] = $l;
                }
            }
            $all = $this->pagesTbl->findAll('id', 0, 0, \PDO::FETCH_ASSOC);
            $assoc = orderByList($all, $navbar, 'name', 'title');
            $nav = [];
            foreach ($assoc as $k => $v) {
                [$id, $val] = $this->prepareRoutes($k, $v);
                $nav[$id] = $val;
            }
            if (!empty($nav)) {
                $_SESSION['nav'] = $nav;
            }
        }
    }

    public function getController(string $name = '', array $args = [], array $user_args = [], bool $super = false): ?object
    {
        //$pagesArgs = [$this->pagesTbl, $this->articleTbl, $this, 'pages'];

        $defaultArgs = [
            //'pages' => $pagesArgs,
            'login' => [$this->authentication],
            'logger' => [$this->authentication],
            'user' => [$this->userTable],
            'tvlist' => [$this->recordTable]
        ];

        $ensureArray = fnull([], 'is_array');
        $name = $this->mapSubClassName($name, $super);
        if (isset($defaultArgs[$name])) {
            $args = $ensureArray($args);
            $user_args = $ensureArray($user_args);//[id,permissions]
            return $this->factoryBridge($name, $defaultArgs[$name], $args, $user_args);
        }
        $locations = [];
        [$public] = $this->validatePageName($name);
        if ($public) {
            /*
            pages has a display method and the usual set of admin methods
            if the latter it is returned from factoryBridge
            the constructor would receive user id and user permissions arguments
            which - currently - it is not using and are safely ignored
            but we need the $locations array as the fifth argument (to redirect certain routes)
            rather then confuse the __constructor we elect to have a separate setter
            */
            //array_pop($pagesArgs); //pop name argument
            //array_push($pagesArgs, $name/*, $locations*/);
            $pages = new Pages(...$pagesArgs);
            //$pages->setLocations($locations);
            return $pages;
        }
        return null;
    }

    public function getScripts(string $key = ''): array
    {
        //note mis-spelling scripts results in: SyntaxError: Unexpected token '<'
        /*
        issue with ajax and cancel &cancel=cancel, need to find a way to determine
        when form submission was cancelled Probably click handler until then we prevent ajax
        on forms with a cancel option which means we have to reload JS for some routes
        UPDATE  dispense with cancel button, but, ensure we have a back button.
        */
        $js = ['viewport', 'meta', 'utils'];
        $admin = array_merge($js, ['present', 'admin']);
        $markup = array_merge($js, ['present', 'markup','admin']);
        $gal1 = ['iterator', 'tooltips', 'publisher', 'painter'];
        $gal2 = ['slideshow', 'present', 'gallery'];
        $gallery = [...$js, ...$gal1, ...$gal2];
        $xtra = [

            'pages/list' => $admin,
            'gallery/review' => $admin,
            'gallery/display' => $gallery,

            'article/list' => $markup,
            'article/edit' => $markup,
            'article/assets' => $markup,
            'login/reg' => $admin,
            'logger/login' => $markup,
            //'logger/loginSubmit' => $admin,
            'user/list' => $admin,
            /*below three gallery/x lines would only be
            neccesary if JS was enabled AFTER a selection was made from the gallery
            */
            'gallery/loadpic' => $gallery,
            'gallery/next' => $gallery,
            'gallery/prev' => $gallery

        ];
        $scripts =  [
            'pages/list' => $admin,
            'user/admin' => $markup,
            'gallery/display' => $gallery,
            'home/display' => [...$js, 'homealone', 'present', 'ajax'],
            'enquiries/process' => [...$js, 'homealone', 'present', 'ajax']
        ];
        return $scripts[$key] ?? (strpos($key, 'display') ?  [...$js, 'present', 'ajax'] : []);
    }

    public function getControllerArgs(string $k, $super = false): array
    {
       
       return [];
        $k = $this->mapSubClassName($k, $super);
        $gallery_map = [[14, 0], [14, 14], [14, 28], [12, 42], [12, 54], [12, 66], [14, 78]];
        $mix = [1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1];
        $alt = [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1];
        $gallery_seq = [$mix, $mix, $mix, $alt, $alt, $alt, $mix];
        $accept_asset = 'accept="image/*, video/*,application/pdf"';
        $cb = 'equals';
        $gallery_accept = 'accept="image/*"';
        $loader_args = ['application' => [ASSETS], 'image' => [ARTICLE_IMG, ARTICLE_THUMB], 'video' => [VIDEOS]];
        $gallery_args = ['image' => [GALLERY_IMG, GALLERY_THUMBS, 1.5]];
        $lib = [
            'gallery' => [$gallery_map, $gallery_accept, $gallery_args, $gallery_seq],
            'hockney' => [$gallery_map, $gallery_accept, $gallery_args, $gallery_seq],
            'article' => [10, [5, 12]],
            'asset' => [$cb, $accept_asset, $loader_args],
            'resource' => [$cb, $accept_asset, $loader_args]
        ];
        return $lib[$k] ?? [];
    }

    public function getLayoutVariables(string $key): array
    {
        
        return [];
        $user = $this->authentication->isLoggedIn();
        $page = explode('/', $key);
        $gal = 'gallery';
        $klas = $this->ispublic ? 'public' : '';
        $defs = ['loggedIn' => $user, 'KLAS' => $klas, 'USER' => $user->name ?? '', 'adminpage' => ''];
        $pp = ['adminpage' => true];
        $ttl = ['TITLE' => 'Admin'];
        $ptl = ['TITLE' => 'photos', 'KLAS' => ''];
        $pager = ['pager' => array_values(range(1, 7))];
        $doClass = appendToClass(false);

        $lookup = [
            'logger/login' => [...$ttl, ...$defs],
            'logger/logout' => [...$ttl, ...$defs],
            'logger/reg' => [...$ttl, ...$defs],
            'user/admin' => [...$ttl, ...$defs],
            'user/contact' => [...$ttl, ...$defs],
            'user/register' => [...$ttl, ...$defs],
            'user/list' => [...$ttl, ...$defs],
            'article/list' => [...$ttl, ...$defs, ...$pp],
            'asset/edit' => [...$ttl, ...$defs, ...$pp],
            'pages/list' => [...$ttl, ...$defs, ...$pp],
            'gallery/display' => [...$ptl, ...$defs,  ...$pager, 'KLAS' => 'public'],
            'gallery/nextpage' => [...$ptl, ...$defs, ...$pager],
            'gallery/prevpage' => [...$ptl, ...$defs, ...$pager],
            'gallery/pager' => [...$ptl, ...$defs, ...$pager],
            'gallery/loadpic' => [...$ptl, 'KLAS' => 'public showtime'],
            'gallery/next' => [...$ptl, 'KLAS' => 'public showtime'],
            'gallery/prev' => [...$ptl, 'KLAS' => 'public showtime'],
            //'enquiries/process' => ['TITLE' => 'Enquiries',  ...$defs, 'KLAS' => 'public']
        ];
        $title = $klas ? $page[0] : 'Admin';
        return $lookup[$key] ?? [...$defs, 'TITLE' => $title, 'adminpage' => !$klas];
    }
    //needs to be public method because use of partial 1st line of checkLogin which uses call_user_func_array
    public function reroute(string $uri, int $acceslevel, string $flag = '')
    {
        $route = explode('/', $uri);
        $name = $flag ? $flag : $route[0];
        $action = $route[1];
        //$acceslevel will determine the feedback message supplied to acccessdenied.html.php
        $args = "!$action/$acceslevel";
        //CRUCIAL set $route to lowercase otherwise it falls foul of EntryPoint::checkUri
        $route = strtolower($name . '/message/' . $args);
        reLocate("/$route", '../');
    }

    //merge with $actions from checkLogin if required
    private function restrictGalleryAccess($actions)
    {
        $browser = \tv\Entity\User::BROWSER;
        $gal = [
            'gallery/display' => $browser,
            'gallery/loadpic' => $browser,
            'gallery/next' => $browser,
            'gallery/nextpage' => $browser,
            'gallery/pager' => $browser,
            'gallery/prev' => $browser,
            'gallery/prevpage' => $browser
        ];
        return [...$actions, ...$gal];
    }

    public function checkLogin(string $uri): array
    {
        $tags = ['thunderball' => true, 'doctor' => true, 'goldfinger' => true, 'end' => true];

        return [];

        $browser = \tv\Entity\User::BROWSER;
        $content = \tv\Entity\User::CONTENT_EDITOR;
        $photo = \tv\Entity\User::PHOTO_EDITOR;
        $chief = \tv\Entity\User::CHIEF_EDITOR;
        $account = \tv\Entity\User::ACCOUNT_EDITOR;
        $super = \tv\Entity\User::SUPERADMIN;

        $reroute = partial($this->reroute(...), $uri);
        $user = $this->authentication->isLoggedIn();
        $tmp = ['user/edit' => $account,  'user/list' => $account, 'gallery/manage' => $photo];
        $post_access = ['user/success' => $browser, 'user/haspermission' => $browser];
        //'user/register' => $browser,
        $actions = [
            'user/confirm' => $account,
            'user/permissions' => $account,
            'user/changepassword' => $browser,
            'user/changeemail' => $browser,
            'user/forgot' =>  $browser,
            'article/list' => $content,
            'article/edit' => $content,
            'article/confirm' => $content,
            'article/delete' => $content,
            'article/move' => $content,
            'article/restore' => $content,
            'article/assets' => $content,
            'asset/upload' => $content,
            'asset/delete' => $content,
            'asset/edit' => $content,
            'asset/confirm' => $content,
            'asset/assign' => $content,
            'asset/reload' => $content,
            'asset/manage' => $content,
            'pages/add' => $content,
            'pages/list' => $content,
            'pages/edit' => $content,
            'gallery/review' => $photo,
            'gallery/add' => $photo,
            'gallery/upload' => $photo,
            'gallery/edit' => $photo,
            'gallery/destroy' => $photo,
            'gallery/reload' => $photo,
            'gallery/assign' => $photo,
            'gallery/manage' => $photo,
            'pages/delete' => $chief,
            'pages/confirm' => $chief,

            'pages/approve' => $super,
            'asset/add' => $super,
            'asset/retrieve' => $super,
            'asset/getuntracked' => $super,
            'gallery/retrieve' => $super,
            'gallery/getuntracked' => $super,

            //'gallery/assign' => $super
        ];
        /*browser is simply a registered user who has access to privileged PUBLIC content
        for instance slideshow
        */
        //$actions = $this->restrictGalleryAccess($actions);
        if (!$user) { //not logged in
            //@ baseAccess
            //a non-browser has to be able to register user/admin
            //a "BROWSER" is allowed to change details at the very least user/list
            if ($this->baseAccess($uri) || isset($actions[$uri])) {
                reLocate(REG . 'gebruiker');
            }
        } else {
            if (isset($actions[$uri]) && !$user->hasPermission($actions[$uri])) {
                $reroute($actions[$uri], 'user');
                exit;
            }
        }
        $ret = $user ? [$user->id, \intval($user->permissions)] : ['', ''];
        //don't send empty args
        return array_filter($ret, 'identity');
    }
    //DDL
    public function create(string $name): void
    {
        //COLLATE=utf8mb3_general_ci
        //=utf8mb4_unicode_ci
        if ($name && $this->pdo) {
            try {
                $this->drop($name);//run drop in case table exists; avoids duplicate foreign key warning
                $fk = $name . '_fk';
                $pk = $name . '_pk';
                if (DBSYSTEM === 'postgres') {
                    $sql =  "CREATE TABLE IF NOT EXISTS $name (
                    id SERIAL,
                    title varchar(255),
                    CONSTRAINT $pk PRIMARY KEY (title, id)
                  )";
                    $this->pdo->exec($sql);
                    $ql1 = "ALTER TABLE $name ADD CONSTRAINT $fk FOREIGN KEY (title) REFERENCES articles";
                    $ql2 =  ' (title) ON DELETE CASCADE ON UPDATE CASCADE';
                    //had to add a unique constraint to articles.title (postgres) which mysql did not require
                    $this->pdo->exec("$ql1$ql2");
                } else {
                    $ql1 = "CREATE TABLE IF NOT EXISTS $name (
                `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
                `title` varchar(50) NOT NULL,
                PRIMARY KEY (`id`,`title`),";
                    $ql2 = " CONSTRAINT `$fk` FOREIGN KEY (`title`) REFERENCES `articles` (`title`) ON DELETE CASCADE";
                    $ql3 = ' ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci';
                    $this->pdo->exec("$ql1$ql2$ql3");
                }
                unset($_SESSION['nav']);
            } catch (\PDOException $e) {
                dump($e);
                $output = 'Error creating table: ' . $e->getMessage();
            }
        }
        //reLocate(PAGES_LIST);
    }


    public function select(string $name, mixed $flag = null)
    {
        if (!$this->pdo) {
            retour();
        }

        try {
            $sql = "SELECT EXISTS (
            SELECT 1 FROM information_schema.tables WHERE table_schema = 'polo' AND table_name = '$name')";
            $ret = $this->pdo->query($sql);
            $ret = $ret->fetch(3);
            if (is_null($flag)) {
                return empty($ret) ? '' : $name;
            }
        //page may exist but contain no entries; in which case we need the name available for assigning articles to it
            if (current($ret)) {
                $ret = $this->pdo->query("SELECT id from $name ORDER BY id");
                $ret = $ret->fetchAll(3);
                /*
                at this point $flag will be a boolean
                true will return all pages that have a corresponding table even if currently empty
                false will return only those that have a corresponding table with at least one entry
                */
                return $flag ? $name : !empty($ret);
            }
            return '';
        } catch (\Exception $e) {
            //dump($e);
        }
    }

    public function getPresentPages()
    {
        $pages = $this->pagesTbl->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        $isPending = partial('preg_match', '/^pending/');
        $pending = safeFilter($pages, fn($o) => !$isPending($o['name']));
        $pages = array_map(fn($o) => $o['name'], $pages);
        $output = [];
        foreach ($pages as $pp) {
            if ($this->select($pp)) {
                $output[] = $pp;
            }
        }
        return $output;
    }

    protected function selectActivePages()
    {
        /*
        if (!$this->pdo) {
            retour();
        }

        if ($this->select('articles')) {
            $sql = "SELECT page FROM articles WHERE page NOTNULL GROUP BY page";
            $ret = $this->pdo->query($sql);
            $records = array_merge(...$ret->fetchAll(3));
            return $records;
        }
*/
        return [];
    }

    public function drop(string $name)
    {
        if (!$this->pdo) {
            retour();
        }
        if ($name) {
            try {
                $sql = "DROP TABLE IF EXISTS $name";
                $this->pdo->exec($sql);
                $all = $this->pagesTbl->findAll();
                $i = max(count($all), 1);
                $sql = "ALTER TABLE pages AUTO_INCREMENT = $i";
                if (DBSYSTEM === 'postgres') {
                    $sql = "ALTER SEQUENCE pages_id_seq RESTART WITH $i";
                }
                $this->pdo->exec($sql);
            } catch (\PDOException $e) {
                dump($e);
                $output = 'Error deleting table: ' . $e->getMessage();
                exit();
            }
        }
    }
}
