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
    private $seriesTable;
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


    public function setHome(string $str): string
    {
        $this->home = $str;
        return $str;
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
        $this->seriesTable = new DatabaseTable($this, 'series', 'id', '\tv\Entity\Series', [&$this->seriesTable, &$this->recordTable]);
        $this->recordTable = new DatabaseTable($this, 'recordings', 'id', '\tv\Entity\Record', [$this->seriesTable, &$this->recordTable]);
        $this->userTable = new DatabaseTable($this, 'users', 'id', '\PoloAfrica\Entity\User', [&$this->userTable]);
        if ($this->userTable) {
            $this->authentication = new Authentication($this->userTable, 'email', 'password');
        }

       //doSetCookie(false)('tog');
    }

    private function validate($key, $array)
    {
        $k = ($key === 'logger') ? 'login' : $key;
        return \in_array($k, $array) ? $k : null;
    }

    //normalise strings by removing forward slashes
    private function baseAccess($uri)
    {
        return [];
    }

    private function factory(string $id, array $args)
    {
        $controllers = [
            'user' => ['table' => ['id','name', 'email', 'permissions', 'password']],
            'login' => [],
            'tvlist' => ['table' => 'id', 'title', 'recdate', 'series_id'],

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
        $id = ($id === $name) ? $id : $name;
        return $this->factory($id, [...$mandatory, ...$optional, ...$user]);
    }

    public function setNavBar()
    {
    }

    public function getController(string $name = '', array $args = [], array $user_args = [], bool $super = false): ?object
    {
        $defaultArgs = [
            'login' => [$this->authentication],
            'logger' => [$this->authentication],
            'user' => [$this->userTable],
            'tvlist' => [$this->recordTable, $this->seriesTable]
        ];

        $ensureArray = fnull([], 'is_array');
        $name = $this->mapSubClassName($name, $super);
        if (isset($defaultArgs[$name])) {
            $args = $ensureArray($args);
            $user_args = $ensureArray($user_args);//[id,permissions]
            return $this->factoryBridge($name, $defaultArgs[$name], $args, $user_args);
        }
        $locations = [];
        return null;
    }

    public function getScripts(string $key = ''): array
    {
        return [];
    }

    public function getControllerArgs(string $k, $super = false): array
    {
       return [];
    }

    public function getLayoutVariables(string $key): array
    {
        return [];
    }
    //needs to be public method because use of partial 1st line of checkLogin which uses call_user_func_array
    public function reroute(string $uri, int $acceslevel, string $flag = '')
    {
    }

    public function checkLogin(string $uri): array
    {
        return [];
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
