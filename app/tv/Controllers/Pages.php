<?php

namespace tv\Controllers;

use Ninja\DatabaseTable;
use Ninja\Component;
use tv\tvWebsite;
use tv\Entity\Entity;
use Ninja\Strategy\Checker;
use Ninja\Strategy\Negator;
use Ninja\Strategy\Empti;
use Ninja\Strategy\PhoneNumber;
use Ninja\Strategy\isName;
use Ninja\Strategy\isEmail;
use Ninja\Strategy\isMatch;
use Ninja\Strategy\isSmallMsg;
use Ninja\Strategy\isLargeMsg;
use Ninja\Composite\Composite;
use Michelf\MarkdownExtra;
use tv\Entity\Article;
use tv\Entity\Slot;


class Pages extends Composite
{
    protected $pp = '';
    protected $table;
    protected $foreignTable = null;
    protected $locations = [];
    protected $website = null;
    protected $public = false;
    protected $attributes;

    public function __construct(DatabaseTable $t, DatabaseTable $at, tvWebsite $www, string $pp)
    {
        $this->pp = $pp;
        $this->table = $t;
        $this->foreignTable = $at;
        $this->website = $www;
        //$this->public = $public;
        $this->init(strtolower($pp));
    }
     
    public function onpersist(?Entity $e, $id = '', $key = ''){
        if($key && !preg_match('/\s/', $key)){
            $id = $id ? $id : 0;
            return $this->edit($id, $key);
        }
        return $e;
    }

    protected function getNameOfClass()
    {
        return static::class;
    }

    public function addItem(Component $comp)
    {
        if (!\in_array($comp, $this->items)) {
            array_push($this->items, $comp);
        }
    }

    public function getClassName(string $prefix = '')
    {
        return $prefix . strtolower(substr(strrchr($this->getNameOfClass(), '\\'), 1));
    }

    public function setLocations(array $loc)
    {
        $this->locations = $loc;
    }

    protected function hasTitle(string $content)
    {
        $hardcoded = partial('preg_match', '/^\w+\.html\.php$/');
        return $hardcoded($content) || (!empty($content) && pregMatch('/^#/', $content));
    }

    protected function redirect()
    {
        $page = $this->locations[$this->pp] ?? '';
        if ($page) {
            return partial('reLocate', $page);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'post') {
            return $this->locations["_$this->pp"] ?? '';
        }
    }

    protected function repop(\tv\Entity\Page $pp, string $rmv = '', string $add = '')
    {
        $currentList = array_map(fn($o) => $o->title, $pp->findAll());
        $currentList = array_map('trim', $currentList);
        //!!remove BEFORE add in case both are same
        [$cb, $pdo] = $this->resetSequence();
        $pp->setName('pp');
        //$pp below is now a Slot Entity not a Page Entity
        $pp = $pp->thenSetCallback($cb, $pdo);//cut out page entity direct to slot;
        $currentList = safeFilter($currentList, fn($str) => $str !== $rmv);
        if ($add) {
            $currentList[] = $add;
        }
        return $pp->repop($currentList);
    }

    protected function checkTitle(int $id, array $payload)
    {
        $title = $payload['title'];
        if ($id) {
            $record = $this->fetch('table', 'title', $payload['title']);
            return $id == $record->id;
        }
    }

    protected function onCheckUnique(array $payload, bool $checked = false, ?int $id = 0)
    {
        $dotrim = doWhen(negate('is_null'), 'trim');
        $e = $this->table->getEntity();
        //checks SLOT table (pp)

        $e = $id ? null : $e->checkUnique(array_map($dotrim, $payload));
        if (!$e) {
            if ($checked || $this->checkTitle($id, $payload)) {
                return;
            }
            $pending = $this->table->findAll('id', 0, 0, \PDO::FETCH_ASSOC);
            $isPending = partial('preg_match', '/^pending/');
            //exclude entries that are pending as the title is already saved to the database and would otherwise not pass this test
            $pending = safeFilter($pending, fn($o) => !$isPending($o['name']));
            $titles = array_map(fn($o) => $o['title'], $pending);
            $t = in_array($payload['title'], $titles);
        }
        //guard against inserting a page with existing name: FCOL
        if ($e || $t) {
            $arg = $e ? 'name' : 'title';
            reLocate(PAGES_ADD . "/baddpage_$arg");
        } else {
            return true;
        }
    }

    protected function init(string $pp)
    {
        $set = doSetCookie(true);
        $unset = doSetCookie(false);
        $map = partial('array_map', fn($o) => $o->title);
        $lower = partial('array_map', 'strtolower');
        $f = composer($lower, $map);
        //note curryleft not partial; provides all functions with the $pages array
        $partials = [curry2L('safeNull'), curry2('array_search')];
        $admin = $pp === 'pages';
        $this->website->queryCurrentPage($pp);
        $activepage = $this->website->getCurrentPage();
        $this->pp = $activepage ? $activepage : $this->pp;

        if ($admin) {
            if (isset($_COOKIE['pagebuild'])) {
                if (is_numeric($_COOKIE['pagebuild'])) {
                    reLocate(ARTICLES_LIST . -1);
                } else {
                    $unset('pagebuild');
                    reLocate(PAGES_LIST);
                }
            }
        }

        if (!$admin && !$activepage) {
            //pages exist but have no articles assigned
            $pages = $this->website->getActivePages(true);
            [$f1, $f2] = array_map(curry2('simpleInvoke')($pages), $partials);
            $finder = composer($f1, $f2);
            $publicpage = $finder($pp);
            if ($publicpage) {
                $e = $this->fetch('table', 'name', $publicpage);
                $set('pagebuild', $e->id);
                retour();
            } else {
                $set('pagebuild');
                retour();
            }
        }
    }

    protected function getAccess(int $i)
    {
        //2 'Content Editors' //4 'Photo Editors'
        $lib = [1 => 'Registered Users', 2 => 'Content Editors', 4 => 'Photo Editors', 8 => 'Chief Editors'];
        return isset($lib[$i]) ? $lib[$i] : 'Account Administrators';
    }

    protected function resetSequence(){
        $spf = partial('sprintf', "SELECT pg_get_serial_sequence('%s', 'id')");
        $pdo = $this->website->getPDO();
        $cb = composer(partial([$pdo, 'query']), $spf);
        return [$cb, $pdo];
    }

    protected function order(int $id, int $pos, bool $shuffle = false)
    {

        $this->groundZero($_POST);
        $label = $_POST['details']['name'];
            $destinationID = \intval($pos - 1);
            $pp = $this->fetch('table', 'id', $id);
            [$cb, $pdo] = $this->resetSequence();
            //shuffle and swap delegates to current slot
        if ($pp) {
            $pp->setName('pp');
            //$pp below is now a Slot Entity not a Page Entity
            $pp = $pp->thenSetCallback($cb, $pdo);//cut out page entity direct to slot;
            if ($shuffle) {
                $res = $pp->shuffle($destinationID, $label);
            } else {
                $res = $pp->swap($destinationID, $label);
            }
        }
        if ($res) {
            unset($_SESSION['nav']);
            $this->website->setNavBar();
            retour('home');
        }
        reLocate(BADMINTON, '../..');
    }

    //optionally exclude articles without a heading; although they would not survive validation?? apart from hardcoded
    protected function fetchAllArticles(Article $entity, string $table, string $k, $flag = false)
    {
        $res = $this->website->select($entity->getName());
        if (!$res) {
            reLocate('/pages/retour/table');
        }
        $all = $entity->findAll('id');
        $items = [];
        $current = null;
        foreach ($all as $item) {
            if ($item) {
                $current = $this->fetch($table, $k, $item->{$k});
                if ($current) {
                    if ($flag && !$this->hasTitle($current->content)) {
                        continue;
                    }
                    $items[] = $current;
                }
            }
        }
        return $items;
    }

    protected function doUpdateArticles(Article $entity)
    {
        $cb = fn($o) => $o->title;
        $displayOrder = array_map($cb, $this->fetchAllArticles($entity, 'foreignTable', 'title', true));
        $current = $this->foreignTable->find('page', $this->pp);
        $currentArticles = array_map($cb, $current);
        [$diff] = getDiff($currentArticles, $displayOrder);
        if (!empty($diff)) { //NEW article to be appended
            $entity->thenTrigger($diff);
        }
    }

    protected function fetchArticles()
    {
        $entity = $this->foreignTable->getEntity();
        $entity->setName($this->pp);//sets slotTable
        $this->doUpdateArticles($entity);
        return $this->fetchAllArticles($entity, 'foreignTable', 'title', true);
    }

    protected function buildArticles()
    {
        $articles = $this->fetchArticles();
        $comp = null;
        $id = null;

        $articles = array_map(function ($article) {
            $article->mdcontent = MarkdownExtra::defaultTransform($article->content);
            return $article;
        }, $articles);

        foreach ($articles as $leaf) {
            $leaf->retrieveAssets($leaf->id);
            //$leaf->assets =
            $res = $leaf->attr_id && findMatch('|(\w*)\.section|', $leaf->attr_id) ? $leaf->attr_id : null;
            if ($res) {
                if ($id && $res !== $id) {//ensure $leafs belong to the correct section
                    if ($comp) {
                        $this->addItem($comp);
                    }
                    $comp = new Composite();
                    $comp->addItem($leaf);
                } else {
                    $id = $res;
                    //https://benjamincrozat.com/php-double-question-mark-null-coalescing-operator
                    $comp = $comp ? $comp : new Composite();
                    $comp->addItem($leaf);
                }
            } else {
                if (!empty($comp)) {
                    $this->addItem($comp);
                    $comp = null;
                }
                $this->addItem($leaf);
            }
        }
        /*
        if there were no further articles after a .section article,
        $this->addItem($comp) would not get called in the loop
        */
        if ($comp) {
            $this->addItem($comp);
        }
    }

    protected function getTemplate($pp = null, $key = '')
    {
        $max = \count($_SESSION['nav'] ?? []);
        if (!$max) {
            $max = count($this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC));
        }
        return [
        'template' => 'editpage.html.php',
        'title' => "Edit Page",
        'variables' => [
            'action' => PAGES_EDIT,
            'exit' => BADMINTON,
            'ppid' => $pp->id ?? 0,
            'pptitle' => $pp->title ?? '',
            'ppname' => $pp->name ?? '',
            'ppmax' => $pp ? $max : 0,
            'ppcontent' => $pp->content ?? '',
            'ppdescription' => $pp->description ?? '',
            'required' => '',
            'key' => $key,
            'submit' => 'submit'
        ]
        ];
    }

    protected function processForm()
    {
        $this->groundZero($_POST);
        $host = 'north.wolds@btinternet.com';
        $to = 'andrewsykes@btinternet.com';
        $expected = [
        'name',
        'email',
        'msg',
        'phone',
        'addr1',
        'addr2',
        'addr3',
        'addr4',
        'postcode',
        'country',
        'comments'
        ];
        $text = "Please use this area for comments or questions";
        $post_text = 'Please enter your message';
        $klas = 'empty';
        $mailsent = false;
        $missing = [];
        $mailnotsent = '';
        $fieldset = 'tv contact form';
        $suspect = false;
        $item = 'item';
        $empty = new Checker("required fields are indicated", new Negator(new Empti()));
        $subtext = substr($text, 0, 13);
        $subpost_text = substr($post_text, 0, 13);
        $isNum = new Checker('please supply a phone number', new PhoneNumber());
        $isEmail = new Checker('please supply a valid email address', new isEmail());
        $namer = new isName();
        $isName = new Checker('please supply name in the expected format: "FirstName [MiddleName] LastName"', $namer);
        $isSmallMsg = new Checker('Message is very small, please elaborate', new isSmallMsg());
        $r = 'Reduce word count or please email instead';
        $isLargeMsg = new Checker("Word count of your message is too great. $r", new isLargeMsg());
        $comment = new Checker($post_text, new Negator(new isMatch("/^$subtext/")));
        $postcomment = new Checker($post_text, new Negator(new isMatch("/^$subpost_text/")));
        $required = [
        'name' => preconditions($empty, $isName),
        'email' => preconditions($empty, $isEmail),
        'comments' => preconditions($empty, $comment, $postcomment, $isSmallMsg, $isLargeMsg)
        ];

        if (!empty($_POST['details'])) {
            $message = '';
            $data = array_map('spam_scrubber', $_POST['details']);
            $suspect = !empty(array_filter($data, 'single_space'));
            //honeypot
            if (!$suspect && $_POST['url']) {
                $suspect = true;
            }
            if (!$suspect) {
                foreach ($data as $k => $v) {
                    if (isset($required[$k])) {
                        $res = $required[$k]('identity', $v);
                        //$res will be a string if valid, or an array of issues
                        if (\is_array($res)) {
                            $missing[$k] = $res;
                            $k = null;
                        }
                    }
                    if (\in_array($k, $expected)) {
                        //sets vars used below, $email, $comments
                        ${$k} = trim($v);
                        $message .= buildMessage($k, $v, $k === 'comments');
                    }
                } //each
            }
            if (empty($missing)) {
                $message = wordwrap($message, 70);
                $headers = "From: $host";
                $headers .= "\r\nContent-Type: text/plain; charset=utf-8";
                $headers .= "\r\nReply-To: $email";
                //$mailsent = mail($host, 'Website Enquiry', $message, $headers);
                $mailsent = true;
                $klas = 'success';
                if ($mailsent) {
                    unset($missing);
                } else {
                    $mailnotsent = '<h1>Sorry, There was a problem sending your message. Please try again later.</h1>';
                } //not sent
            } else {
                $item = \count($missing) > 1 ? 'items' : 'item';
                $fieldset = "Please complete the missing $item indicated";
                $klas = 'warning ';
                //sort...
                $keys = array_keys($missing);
                $fieldset = array_values($missing)[0][0];
                $klas .= $keys[0];
                //https://stackoverflow.com/questions/24403817/html5-required-attribute-one-of-two-fields;
            }
        } //posted
        //NOTE variables created on-the-fly for $email(line 327) and $comments
        $this->buildArticles();
        return [
        'template' => 'page.html.php',
        'title' => ucfirst('enquiries'),
        'variables' => [
            'klas' => $klas,
            'status' => $klas,
            'mailsent' => $mailsent,
            'mailnotsent' => $mailnotsent,
            'missing' => $missing ?? [],
            'fieldset' => $fieldset,
            'myemail' => $email ?? '',
            'name' => $data['name'] ?? '',
            'phone' => $data['phone'] ?? '',
            'mycomment' => $data['comments'] ?? '',
            'country' => $data['country'] ?? '',
            'postcode' => $data['postcode'] ?? '',
            'addr1' => $data['addr1'] ?? '',
            'addr2' => $data['addr2'] ?? '',
            'addr3' => $data['addr3'] ?? '',
            'addr4' => $data['addr4'] ?? '',
            'email' => $email ?? 'andrewsykes@btinternet.com',
            'comments' => $comments ?? '',
            'articles' => $this->items
        ]
        ];
    }

    public function display()
    {
        $action = $this->redirect();
        if (is_callable($action)) {
            return $action();
        } elseif (is_callable([$this, $action])) {
            return $this->{$action}();
        }

        //dump($_SESSION);
        if (!in_array($this->pp, WEBPAGES)) {
            retour();
        }

        $this->buildArticles();
        return [
        'template' => 'page.html.php',
        'title' => ucfirst($this->pp),
        'variables' => [
            'articles' => $this->items,
            'klas' => '',
            'status' => ''
        ]
        ];
    }

    public function getMeta(string $col, string $val, string $prop = '')
    {
        $res = $this->fetch('table', $col, $val);
        return $prop ? $res->{$prop} : $res;
    }

    protected function isPending()
    {
        $files = $this->table->findAll();
        $names = array_map(fn($o) => $o->name, $files);
        $pending = preg_grep('/^pending/', $names);
        if ($pending && isset($_SESSION['filestore'])) {
            $ret = $this->table->find('name', 'pending%', null, 0, 0, \PDO::FETCH_CLASS, ' LIKE :value');
            return $ret[0] ?? null;
        }
    }

    //allow more than one pending page limit to WEBPAGES length
    protected function setPending()
    {
        $i = 0;
        $pending = 'pending';
        $all = array_map(fn($o) => $o->name, $this->table->findAll('name'));
        $pended = safeFilter($all, partial('preg_match', '/^pending/'));
        if (empty($pended)) {
            return 'pending1';
        }
        while (isset(WEBPAGES[$i])) {
            if ($i && !isset($pended[$i])) {
                $i = $i + 1;
                $pending .= $i;
                break;
            }
            $i++;
        }
        return $pending;
    }

    protected function onPrepareInsert(int $id, string $name)
    {
        $set = doSetCookie(true);
        $record = $this->fetch('table', 'id', $id);
        if (isset($_SESSION['filestore']) && preg_match('/^pending/i', $record->name)) {
            $set('pending', $name);
            reLocate(PAGES_APPROVE . $id);
        }
    }

    protected function onValidatePageName(?int $id, string $name)
    {
        if (isset($_SESSION['filestore'])) {
            [$public] = $this->website->validatePageName($name);
            if (!$public) {
                reLocate(PAGES_EDIT . "$id/pagename");
            }
        }
    }

    protected function onCheckPending(?int $id, string $name)
    {
        if (isset($id) && preg_match('/^pending/', $name)) {
            reLocate("/pages/delete/$id/impending");
        }
    }
    //should not need but directly adding an entry to pages in the db will not update pp table
    //so in case things have got outta whack
    protected function doPop()
    {
        $pp = $this->table->getEntity();    
        $pp->setName('pp');
        $files = $this->table->findAll();
        $pages = $pp->findAll();

        if (count($files) > count($pages)) {
            $files = array_map(fn($o) => $o->name, $files);
            $pages = array_map(fn($o) => $o->title, $pages);
            $res = [...array_values($pages), ...array_values($files)];
            $ispending = partial('preg_match', '/^pending/');
            $res = safeFilter($res, negate($ispending));
            [$cb, $pdo] = $this->resetSequence();
            $pp->setName('pp');
            //$pp below is now a Slot Entity not a Page Entity
            $pp = $pp->thenSetCallback($cb, $pdo);//cut out page entity direct to slot;
            $pp->repop(array_unique($res));
        }
            
    }

    public function add(string $key = '')
    {
        return $this->getTemplate(null, $key);
    }

    public function edit(int $id = 0, string $key = '')
    {
        //$this->doPop(); //required if repop fails to run (or when in development)
        $pp = $this->fetch('table', 'id', $id);
        if ($pp) {
            return $this->getTemplate($pp, $key);
        } else {
            return $this->add($key);
        }
    }

    public function delete(int $id = 0, string $key = '')
    {
        $file = $this->fetch('table', 'id', $id);
        if ($file) {
            return [
            'template' => 'delete.html.php',
            'title' => ucfirst($this->pp),
            'variables' => [
            'action' => "/pages/retire/",
            'file' => $file,
            'perform' => 'delete',
            'key' => $key
            ]
            ];
        }
        reLocate('/pages/display/');
    }

    public function createSubmit()
    {
        $this->groundZero($_POST);
        $unset = doSetCookie(false);
        $id = $_POST['pk'] ?? '';

        //dump([$id, $_COOKIE,$_POST]);
        if (isset($_POST['cancel'])) {
            $this->table->delete('id', $id);
            $unset('pending');
            reLocate(PAGES_LIST);
        }
        $res = $this->fetch('TABLE', 'id', $id);
        
        if ($res) {
            $pending = $res['name'];
            $name = $_COOKIE['pending'] ?? '';
            $res['name'] = $name;
            if ($name) {
                $e = $this->persist($res);
                $this->website->create($name);
                $unset('pending');
            //$pending and $str would be the same if approval was after the fact;
            //otherwise $pending would BEGIN with 'pending' ie 'pending1' if instigated by super
            $e->setName('pp');
            $this->repop($e, $pending, $name);
        }
        }
        reLocate(PAGES_LIST);
    }

    public function approve(int $id = 0)
    {
        if (isset($_COOKIE['pending']) && $id) {
            $pp = $_COOKIE['pending'];
            //dump($pp);
            //doSetCookie(false)('pending');
            return [
            'template' => 'delete.html.php', //to provide submit AND cancel buttons
            'title' => 'Approve New Page',
            'variables' => [
                'action' => "/pages/create/",
                'perform' => 'create',
                'file' => toObject(['id' => $id]), //to conform with template
                'submit' => 'submit',
                'exit' => PAGES_LIST,
                'key' => "onpending_$pp"
            ]
            ];
        } else {
            reLocate('/pages/display');
        }
    }

    public function retireSubmit()
    {
        /*
        ALTER TABLE articles ADD CONSTRAINT `pager_fk` FOREIGN KEY (`page`)
        REFERENCES `pages` (`name`) ON DELETE SET NULL ON UPDATE CASCADE;
        */
        $this->groundZero($_POST);
        if (isset($_POST['cancel'])) {
            reLocate(PAGES_LIST);
        }
        $id = $_POST['pk'] ?? 0;
        $pp = $this->fetch('table', 'id', $id);
        if ($pp) {
            $this->table->delete('id', $id);
            $this->website->drop(strtolower($pp->name));
            unset($_SESSION['nav']);
            if (isset($pages[1])) {
                reLocate(PAGES_LIST);
            }
        }
        reLocate(BADMINTON);
    }

    public function editSubmit()
    {
        /*
        the general idea here is that a new page can be created
        BUT it MAY be depending approval by a senior member
        //https://www.commandprompt.com/education/how-to-fix-the-column-can-only-be-updated-to-default-error-in-postgres/
        */

        $this->groundZero($_POST);

        $id = $_POST['pk'] ?? null;
        $checked = false;
        if ($id && !empty($_POST['position'])) {
            return $this->order($id, $_POST['position'], isset($_POST['shuffle']));
        }

        $dotrim = doWhen(negate('is_null'), 'trim');
        $payload = array_map($dotrim, $_POST['details']);
        $name = $payload['name'];
        $current = ['title' => ''];
        $this->onValidatePageName($id, $name);//allow any name UNTIL approval by super
        if ($id) {
            $current = $this->fetch('TABLE', 'id', $_POST['pk']);
            $payload['name'] = preg_match('/^pending/', $name) ? $current['name'] : $name;
            $payload['id'] = $_POST['pk']; // ||  $current['id']
            //!avoid changing name as there MAY be no slot db, delete and insert instead
        } else {
            $checked = $this->onCheckUnique($payload);
            $payload['name'] = $this->setPending();
        }
        $e = $this->persist($payload);
        $this->onCheckPending($current['id'] ?? null, $payload['name']);
        $this->onCheckUnique($payload, $checked, $current['id'] ?? null);
        $this->onPrepareInsert($e->id, $name);

        if ($current['title'] && $payload['title'] !== $current['title']) {
            unset($_SESSION['nav']);
            retour($name);
        }
        reLocate(PAGES_LIST);
    }

    public function list($key = '')
    {
        //will deal with one file at a time as array could have a series of pages waiting approval ['pending1', 'pending2', ...]
        $file = $this->isPending();
        if ($file) {
            return $this->edit($file->id, 'pending');
        }

        $files = $this->table->findAll();
        return [
            'template' => 'pagelist.html.php',
            'title' => 'Page List',
            'variables' => [
                'files' => $files,
                'heading' => empty($files) ? 'Add a page by clicking the + button' : '',
                'action' => '/pages/delete/',
                'perform' => 'delete',
                'key' => $key,
                'exit' => BADMINTON
                ]
            ];
    }

    public function process()
    {
        if (isset($_POST)) {
            return $this->processForm();
        }
    }

    public function retour(string $key = '')
    {
        $para = 'Sorry, the page you are looking for could not be found.';
        if ($key === 'table') {
            $route = $_COOKIE['pagebuild'] ?? '';
            $route = $route === is_numeric($route) ? PAGES_EDIT . $route : PAGES_LIST;
            $para = "You have requested a legitimate route but the required page has yet to be created. Click <strong><a href='$route'>here</a></strong> to do so!";
        }
        if ($key === 'controller') {
            $para = "The wrong controller is being deployed";
        }

        if ($key === 'persist') {
            $para = "Disaster Averted!";
        }
        return [
            'template' => 'lost.html.php',
            'variables' => [
                'para' => 'Sorry, the page you are looking for could not be found.',
                'TITLE' => 'PAGE NOT FOUND',
                'para' => $para,
                'KLAS' => 'nowhere',
                'scripts' => [],
                'loggedIn' => null,
                'nav' => []
                ]
            ];
    }
}
