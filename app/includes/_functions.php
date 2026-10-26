<?php

function fullHouse($all, $real)
{
    /*
    $all = [[1,2,3,4,5,7], [8,9,10,11,12,13,14]];
    $sean = ['dn','fr','tb', 'yt', 'daf', 'gf'];
    $rog = ['l', 'mw', 's', 'm', 'f', 'o'];
    $real = [$sean, $rog];
    */
    $output = [];
    $i = 0;
    $j = 0;

    while (isset($all[$i])) {
        $control = $all[$i];
        $live = $real[$i];
        $tmp = [];
        while (isset($control[$j])) {
            if (!isset($live[$j])) {
                $tmp = null;
                break;
            }
            $tmp[] = $live[$j];
            $j++;
        }
        if ($tmp) {
            $output[] = $tmp;
        }
        $j = 0;
        $i++;
    }
}

function changePathDepth($dir, $subdir)
{
    $reg = '|(^\/\w+\/\w+\/)([^\/]+)$|';
    if (pregMatch($reg, $dir)) {
        return preg_replace($reg, "$1$subdir/$2", $dir);
    } else {
        return preg_replace('|(^\/\w+\/\w+\/)(\w+\/)([^\/]+)$|', "$1$3", $dir);
    }
}
function changePathDepth2($dir, $subdir, $flag = false)
{
    $reg = '|(^\/\w+\/\w+\/)([^\/]+)$|';
    if (pregMatch($reg, $dir)) {
        return preg_replace($reg, "$1$subdir/$2", $dir);
    } else {
        if ($flag) {
            return preg_replace('|(^\/\w+\/\w+\/)(\w+\/)([^\/]+)$|', "$1$subdir/$3", $dir);
        }
        return preg_replace('|(^\/\w+\/\w+\/)(\w+\/)([^\/]+)$|', "$1$3", $dir);
    }
}
function changePathDepth3($dir, $subdir)
{
    $reg = '|(^\/\w+\/\w+\/)([^\/]+)$|';
    $flag = strtolower($subdir) === $subdir ? false : true;
    if (pregMatch($reg, $dir)) {
        return preg_replace($reg, "$1$subdir/$2", $dir);
    } else {
        if ($flag) {
            $subdir = strtolower($subdir);
            return preg_replace('|(^\/\w+\/\w+\/)(\w+\/)([^\/]+)$|', "$1$subdir/$3", $dir);
        }
        return preg_replace('|(^\/\w+\/\w+\/)(\w+\/)([^\/]+)$|', "$1$3", $dir);
    }
}

function invokeDefer(callable $func)
{
    return function (...$args) use ($func) {
        return $func(...$args);
    };
}

function invokeArray(callable $cb, array $args)
{
    return $cb(...$args);
}

function invoke(callable $func, ...$args)
{
    return $func(...$args);
}

function lazyRest($func, $b)
{
    return function ($a, $c) use ($func, $b) {
        if (is_callable($func)) {
            call_user_func($func, $a, $b, $c);
        }
    };
}

function lazyMiddle($func, $a, $c)
{
    return function ($b) use ($func, $a, $c) {
        if (is_callable($func)) {
            call_user_func($func, $a, $b, $c);
        }
    };
}

function curryDeferByRef($fun)
{
    return function (&$arg1) use ($fun) {
        return function () use ($fun, &$arg1) {
            return $fun($arg1);
        };
    };
}

function curry2LDefer($fun)
{
    return function ($arg1) use ($fun) {
        return function ($arg2) use ($fun, $arg1) {
            return function () use ($fun, $arg1, $arg2) {
                return $fun($arg1, $arg2);
            };
        };
    };
}

function curry2ByRef($fun)
{
    return function (&$arg1) use ($fun) {
        return function ($arg2) use ($fun, &$arg1) {
            return $fun($arg1, $arg2);
        };
    };
}


function mittel($func, $b)
{
    return function ($a, $c) use ($func, $b) {
        return $func($a, $b, $c);
    };
}

function lazyMittel($func, $a, $c)
{
    return function ($b) use ($func, $a, $c) {
        return $func($a, $b, $c);
    };
}

function eagerMittel($func, $b)
{
    return function ($a) use ($func, $b) {
        return function ($c) use ($func, $b, $a) {
            return $func($a, $b, $c);
        };
    };
}
function callUserFuncArray($args)
{
    $func = array_shift($args);
    return function ($newargs) use ($func, $args) {
        return call_user_func_array($func, array_merge($args, $newargs));
    };
}

function partialDefer($func, ...$args)
{
    return function (...$newargs) use ($func, $args) {
        return function () use ($func, $args, $newargs) {
            return $func(...$args, ...$newargs);
        };
    };
}

function partialDefer2($func, ...$args)
{
    return function () use ($func, $args) {
        return function ($arg) use ($func, $args) {
            return $func(...$args);
        };
    };
}

function partialRef($f, &$a, ...$args)
{
    return function (...$newargs) use ($f, &$a, $args) {
        call_user_func_array($f, [&$a, ...$args, ...$newargs]);
        return $a;
    };
}
function doEcho($arg)
{
    echo $arg;
}
function split($data, $not)
{
    $ret = [];
    $sale = [];
    $alp = [];
    $pair = [];
    foreach ($data as $d) {
        foreach ($d as $k => $v) {
            if ($k === strtolower($k)) {
                $sale[$k] = $v;
            } else {
                $alp[$k] = $v;
            }
        }
        $pair[] = $sale;
        $pair[] = $alp;
        //$tmp = $pair;
        $ret[] = $pair;
    }
    return $ret;
}
function inMyArray($needle, $haystack)
{
    $cb = function ($n) {
        return function ($agg, $cur) use ($n) {
            return $agg ? $agg : pregMatch("/^$n$/i", $cur);
        };
    };
    return array_reduce($haystack, $cb($needle));
}
function preconditionsMod()
{
    $checkers = func_get_args();
    return function ($strategy, $value) use ($checkers) {
        $errors = array_reduce(array_map(
            function ($checker) use ($strategy, $value) {
                return $checker->validate($value) ? array() : array(
                    $checker->message
                );
            },
            $checkers
        ), 'array_merge', array());
        if (!empty($errors)) {
            return $errors;
        }
        //return $strategy->algorithm($value);
        return $strategy($value);
    };
}

//https://stackoverflow.com/questions/25105796/php-add-value-to-a-existing-query-string
function setQueryString($url, $key, $val)
{
    $pUrl = parse_url($url);
    if (isset($pUrl['query'])) {
        parse_str($pUrl['query'], $pUrl['query']);
    } else {
        $pUrl['query'] = [];
    }
    $pUrl['query'][$key] = $val;

    $scheme = isset($pUrl['scheme']) ? $pUrl['scheme'] . '://' : '';
    $host = isset($pUrl['host']) ? $pUrl['host'] : '';
    $path = isset($pUrl['path']) ? $pUrl['path'] : '';
    $path = count($pUrl['query']) > 0 ? $path . '?' : $path;
    return $scheme . $host . $path . http_build_query($pUrl['query']);
}
function path($f, $d = '/templates/')
{
    $path = strrchr(__DIR__, '/');
    include __DIR__ . "../../../websites$path$d$f";
}

function loadTemplate($templateFileName, $variables)
{
    extract($variables);
    ob_start();
    include TEMPLATE . $templateFileName;
    return ob_get_clean();
}

function checkUri($uri)
{
    if ($uri != strtolower($uri)) {
        http_response_code(301);
        header('Location: ' . strtolower($uri));
    }
}
//https://philio.me/recursively-remove-a-directory-in-php-using-spl/
function recursiveRmDir($dir)
{
    $kid = \RecursiveIteratorIterator::CHILD_FIRST;
    $f = \FilesystemIterator::SKIP_DOTS;
    $iterator = new RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, $f), $kid);
    foreach ($iterator as $filename => $fileInfo) {
        if ($fileInfo->isDir()) {
            rmdir($filename);
        } else {
            unlink($filename);
        }
    }
}
function swapExtension($path, $ext, $sep = '')
{
    if ($sep) {
        $path = explode('/', $path);
        $path = array_key_last($path);
    }
    return preg_replace('|\.\w+$|', $ext, $path);
}
//makes some assumptions, a warning is in an array, a string is ok
///but echo may be delegated so just return
function flushMsg($missing, $data, $klas = 'warning')
{
    return function ($k, $flag = false, $ret = false) use ($missing, $data, $klas) {
        $output = isset($missing[$k]) && is_array($missing[$k]) ? $missing[$k][0] : null;
        if (isset($output)) {
            //we can be adding to value field of input, or class of label
            //assumes no other class present
            if (!$ret) { //default is echo
                echo $flag ? $output : " class=$klas";
            } else {
                return $flag ? $output : " class=$klas";
            }
        } else {
            htmlout(trim(strip_tags($data[$k]))); //outputs
        }
    };
}

function flushMsgCb($missing, $data, $klas = 'warning')
{
    return function ($k, $flag = false) use ($missing, $data, $klas) {
        $output = isset($missing[$k]) && is_array($missing[$k]) ? $missing[$k][0] : null;
        if (isset($output)) {
            return $flag ? $output : " class=$klas";
        } else {
            htmlout(trim(strip_tags($data[$k]))); //outputs
        }
    };
}
function getAccess($i)
{
    $lib = [null, 'Content Editors', 'Photo Editors'];
    return isset($lib[$i]) ? $lib[$i] : 'Account Administrators';
}
function getPeers($p, $b = 64)
{
    $test = ($p % $b) === $p;
    if ($test) {
        $b /= 2;
        return getPeers($p, $b);
    } else {
        return $b;
    }
}
function doBest($pred, $actions)
{
    return function (...$args) use ($pred, $actions) {
        return array_reduce($actions, function ($a, $b) use ($pred, $args) {
            return $a && $pred(...$args) ? $a : $b;
        });
    };
}

function doBestInvoke($pred, $actions)
{
    return function (...$args) use ($pred, $actions) {
        return array_reduce($actions, function ($a, $b) use ($pred, $args) {
            return $a && $pred(...$args) ? $a(...$args) : $b(...$args);
        });
    };
}
function alternate($predicate)
{
    return function ($arg) use (&$predicate) {
        $i = $predicate($arg);
        if (!$i) {
            $predicate = negate($predicate);
        }
        return $i;
    };
}

/*https://medium.com/@assertchris/function-composition-c8094ae9be63
$reduce = function ($result, $item) {
    return !is_array($result) ? call_user_func_array($item, [$result]) : call_user_func_array($item, $result);
};

$compose = function () use ($reduce) {
    $callbacks = func_get_args();
    return function () use ($callbacks, $reduce) {
        return array_reduce($callbacks, $reduce, func_get_args());
    };
};
*/
function compose($reducer)
{
    return function () use ($reducer) {
        $callbacks = func_get_args();
        return function () use ($callbacks, $reducer) {
            return array_reduce($callbacks, $reducer, func_get_args());
        };
    };
}

//assumes a seed value (or two)
function modCompose(...$fns)
{
    return function (...$args) use ($fns) {
        return array_reduce($fns, function ($val, $fn) {
            return $fn($val);
        }, ...$args);
    };
}

/*
in case we need it
function composeroo(...$fns) {
    return array_reduce($fns, function ($f, $g) {
        return function(...$vals) use($f, $g) {
            return $f($g(...$vals));
        };
    }, 'identity');
}
    */

    function unsetCookie($str)
{
    unset($_COOKIE[$str]);
    setcookie($str, '', -1, '/');
}

function doSetCookie1($flag)
{
    return function ($k, $v = '', $time = -1) use ($flag) {

        //need if undefined here
        if (!is_string($v)) {
            if (!is_int($v)) {
                $v = $k;
            }
        }

        if (!is_int($v)) {
            if (!is_string($v)) {
                $v = $k;
            }
        }

        if (!isset($_COOKIE[$k]) && $flag) {
            setcookie($k, $v, $time, '/');
            $_COOKIE[$k] = $v;
        } elseif (isset($_COOKIE[$k]) && !$flag) {
            unset($_COOKIE[$k]);
            setcookie($k, '', -1, '/');
        }
    };
}
function doCookie($k, $v, $sep = ';')
{
    if (isset($_COOKIE[$k])) {
        $store = explode($sep, $_COOKIE[$k]);
        $arr = explode($sep, $v);
        $arr = array_merge($store, $arr);
        $arr = array_unique($arr);
        $v = implode($sep, $arr);
        unset($_COOKIE[$k]);
        setcookie($k, '', -1, '/');
    }
    setcookie($k, $v, time() + 60 * 60 * 24 * 30, '/');
    return $v;
}
function doInclude($content, $template = '_image_article.html.php')
{
    if (pregMatch('/\w+\.html\.php$/', $content)) {
        return $content;
    }
    return $template;
}
function formatError($e)
{
    $ret = implode(preg_split('/SQLSTATE\[[^:]+:/', $e));
    return implode(preg_split('/:\s\d+/', $ret));
}

function doPreparedQuery($st, $values = [], $msg = '')
{
    try {
        $bool = $st->execute($values);
        // dump(is_bool($bool));
        return $bool && is_bool($bool) ? $st : null;
    } catch (PDOException $e) {
        $error = $msg . ' ' . $e->getMessage();
        $error = formatError($error);
        return $error;
    }
}

function foobar($haystack, $needle, $str)
{
    $haystack = explode(' ', strtolower($haystack));
    $needle = explode(' ', strtolower($needle));
    $word = array_intersect($haystack, $needle);

    if (isset($word[1])) {
        $word = implode(' ', $word);
    } else {
        $word = current($word);
    }
    $orig = $word;
    //$pre = findMatch("/\[?\w+(?=\s$word)/i", $str, 0);
    $aft = [];
    $pre = [];
    $i = 0;

    while ($word = preg_match("/(?<=$word\s)\w+(?:\]\[(\d+)\])?/i", $str, $m)) {
        if (!empty($m)) {
            $j = stripos($m[0], '[');
            if ($j) {
                list($word) = (explode(']', $m[0]));
                $aft[] = $word;
                $i = $m[1];
                break;
            } else {
                $word = $m[0];
                $aft[] = $word;
            }
        } else {
            break;
        }
    }
    $word = $orig;
    //$pre = findMatch("/\w+(?=\s\[?$word)/i", $str, 0);

    while ($word = preg_match("/\[?\w+(?=\s$word)/i", $str, $m)) {
        if (!empty($m)) {
            $j = stripos($m[0], '[');
            if (is_numeric($j)) {
                list($_, $word) = explode('[', $m[0]);
                $pre[] = $word;
                break;
            } else {
                $word = $m[0];
                $pre[] = $word;
            }
        } else {
            break;
        }
    }
    return implode(' ', array_merge([$pre], [$orig], $aft));
    /*
    $string = "This is a string, keyword next, some more text.
    keyword next-word. keyword another_word. Okay, keyword do-rae-mi-fa_so_la.";
    // Set the regex.
    $regex = '/(?<=\bkeyword\s)(?:[\w-]+)/is';
    // Run the regex with preg_match_all.
    preg_match_all($regex, $string, $matches);
    // Dump the resulst for testing.
    echo '<pre>';
    print_r($matches);
    echo '</pre>';
    */
}
function cc($unpaid, $balance, $dur, $rate)
{
    while ($dur) {
        $min = ($unpaid * $rate) + ($balance / 100);
        $unpaid -= $min;
        $balance -= $min;
        $dur--;
    }
    return [$unpaid, $balance];
}

function tsb($unpaid, $balance, $dur, $rate)
{
    while ($dur) {
        $min = $balance * .02;
        $unpaid -= $min;
        $balance -= $min;
        $dur--;
    }
    return [$unpaid, $balance];
}
////
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
public function getActivePages1(bool $flag = false)
{
    $pages = $this->pagesTbl->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
    $isPending = partial('preg_match', '/^pending/');
    $pending = safeFilter($pages, fn($o) => !$isPending($o['name']));
    $pages = array_map(fn($o) => $o['name'], $pages);
    $articles = $this->articleTbl->filterNull('page', false, 'id', 0, 0, \PDO::FETCH_ASSOC);
    $map = partial('array_map', fn($o) => $o['page']);
    $articles = composer('array_unique', $map)($articles);
    [$a, $b] = getDiff($pages, $articles);
    $ret = $flag ? $a : array_diff($b, $a);
    $ret = safeFilter($ret, 'identity');

    $output = [];
    foreach ($ret as $tablename) {
        if ($this->select($tablename)) {
            $output[] = $tablename;
        }
    }
    return $output;
}
public function list(mixed $folio = 1, string $key = '')
{
    $unset = doSetCookie(false);
    $setcookie = doSetCookie(true);
    $unset('js');
    $unset('resident');
    $unset('article');
    $list = "list_$folio";
    //$unset('list');
    $by = $_COOKIE['date'] ?? 'title';
    $pp = $_COOKIE['page'] ?? '';
    $offset = 0;
    $ky = 0; //NOT $key
    $prev = null;
    $next = null;
    $filterNull = partial($this->table->filterNull(...), 'page');
    $noactive = empty($filterNull(false));
    $archived = $filterNull(true);

    $this->localStorage(-1);

    $alt = isset($_COOKIE['altmode']);
    $neg = is_numeric($folio) && \intval($folio) < 0;

    if ($key === 'exit') {
        $unset('altmode');
        reLocate(ARTICLES_LIST . 1);
    }

    if (!$alt && (($noactive && !empty($archived)) || $neg)) {
        $setcookie('altmode');
        $this->localStorage(-1);
        reLocate(ARTICLES_LIST . -1);
    }

    if (!$neg && isset($_POST['pp']) && $_POST['pp'] === 'date') {
        $by = 'date';
        $setcookie('date', 'date', time() + 10);
    }

    $folio = $alt && $folio < 0 ? 1 : $folio;
    $records = $_COOKIE['records'] ?? $this->inc;
    $this->setInc($records);

    if (isset($_POST['records'])) {
        $this->setInc(\intval($_POST['records']));
        $setcookie('records', $this->inc);
    }
    if ($folio === 'clear') {
        $this->unsetPage(true);
        $folio = 1;
        $pp = null;
    }
    $range = $this->validateFolio();

    if (!empty($_POST['pp']) || !empty($pp)) {
        $pubdate = $_POST['pp'] !== 'date';
        //let's not bother paginating a page view
        $max = $this->maxArticleByPage('page', 'id', \PDO::FETCH_NUM);
        $this->setInc($max);
        $pp = isset($_POST['pp']) && $pubdate ? $_POST['pp'] : $pp;
        if (!empty($pp)) {
            $unset('date');
            $by = 'title';
            $this->localStorage(-1);
            $files = $this->listByPage($pp);
            //page may no longer have articles
            if (empty($files)) {
                $this->unsetPage(true);
            } else {
                $setcookie('page', $pp);
            }
            if (\count($files) > $this->inc) {
                [$key, $prev, $next, $offset] = $this->prepPaginationBar($files, $folio);
                $files = $this->listByPage($pp, $this->inc, $offset);
            }
        }
    }
    //not yet set by page/date
    if (empty($files)) {
        if ($folio && !is_numeric($folio)) {
            //redirect to origin page
            $folio = $this->findFolio(ucwords(urldecode($folio)));
        }

        if (!$alt && empty($folio)) {
            $folio = 1;
        }

        $getAllFiles = curry2($filterNull)($by);
        //$this->localStorage();
        if (!isset($_COOKIE['list'])) {
            $files = $getAllFiles($alt);
            $files = $this->localStorage(0, $files);
        }
        else {
            $files = $this->localStorage(0);
        }

        if (!(\count($files) > $this->count)) {
            $folio = ($folio > $range || $folio < 1) ? 1 : $folio;
        }

        [$ky, $prev, $next, $offset] = $this->prepPaginationBar($files, \intval($folio));

        $filterBy = curry4($filterNull)($offset)($this->inc)($by);
       
        if (!isset($_COOKIE[$list])) {
            $files = $filterBy($alt);
            $files = $this->localStorage($folio, $files);
        } else {
            $files = $this->localStorage($folio);
        }
    }       
    return $this->prepTemplate($files, $prev, $next, $ky, $archived, $alt);
}

protected function localStorage($folio, $files = [])
{
    $set = doSetCookie(true);
    $unset = doSetCookie(false);
    $list = $folio ? "list_$folio" : 'list';
    $i = 0;

    if($folio < 0){
        foreach($_COOKIE as $k => $v) {
            $i++;
            if(preg_match('/^list/', $k)){
                $unset($k);
            }
        }
        return;
    }

    if (!empty($files) && !isset($_COOKIE[$list])) {
        $files = toObject($files, true);
        $output = [];
        foreach ($files as $file) {
            $output[] = array_column([$file], 'title', 'id');
        }
        $output;
        $str = '';
        foreach ($output as $o) {
            foreach ($o as $k => $v) {
                  $str .= $k;
                  $str .= ":$v;";
            }
        }
        $set($list, $str);
    }

   if(isset($_COOKIE[$list])){
        $ret = [];
        $arr = safeFilter(explode(';', $_COOKIE[$list]), 'identity');
        foreach ($arr as $a) {
            [$k,$v] = safeFilter(explode(':', $a), 'identity');
            $tmp = [];
            $tmp['id'] = $k;
            $tmp['title'] = ucwords($v);
            $ret[] = $tmp;
        }
        return toObject($ret);
    }
}