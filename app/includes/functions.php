<?php

function autoloader($className)
{
    $fileName = str_replace('\\', '/', $className) . '.php';
    $file = __DIR__ . '/../' . $fileName;
    include $file;
}

function sortAlpha($arr){
    $alpha =  ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x','y', 'z'];
    $i = 0;
    $order = [];
    $output = '';
    while(isset($alpha[$i])){
        $char = $alpha[$i];
        foreach ($arr as $k => $v) {
          if(preg_match("/^$char/", $k)){
            $output .= "'" . $k . "'";
            $output .= ' => ';
            $output .= '"' . $v . '"' .',';
          }
        }
        $i++;
    }
    return $output;
}

function getResult(mixed $arg)
{
    if (is_callable($arg)) {
        return $arg();
    }
    return $arg;
}

function safeNull(array $o, $p)
{
    return $o[$p] ?? null;
}

function fnull(mixed $default, $cb = 'identity')
{
    return function ($arg) use ($default, $cb) {
        return $arg && $cb($arg) ? $arg : $default;
    };
}

function nullify(mixed $arg)
{
    return !$arg ? null : $arg;
}

//i'd be a rich man if i got a pound for everytime i got a null warning re the second argument
//this kills it
function pregMatch(string $reg, mixed $arg)
{
    $f = doWhen(negate('is_null'), partial('preg_match', $reg));
    return $f($arg);
}

//recur once used as private method in Website may prove handy in future??
function listo($data, $names, $ret, $i, $j)
{
    if (isset($data[$i]) && isset($names[$j])) {
        $tgt = $names[$j];
        //iterate until you find KEY to title
        if ($data[$i]['name'] === $tgt) {
            $ret[] = $data[$i]['title'];
            $j += 1; //advance
            $i = 0; //reset
            return listo($data, $names, $ret, $i, $j);
        } else {
            //increment $pagedata
            $ret = listo($data, $names, $ret, $i += 1, $j);
            return $ret;
        }
    } else {
        return $ret;
    }
}

function relocateArgs(...$args)
{
    $l = count($args);
    $i = 0;
    $str = '';
    $tmp = null;
    while ($l--) {
        if ($args[$i]) {
            $tmp = $args[$i];
            $str .= "/$tmp";
        } else {
            $str .= '/';
        }
        $i++;
    }
    return $str;
}

function assetMoveFactory(string $path)
{
    [$_, $from] = getMimeType($path, [], ['thumbs']);
    if (pregMatch('/\.pdf$/', $path)) {
        return function ($page = '') use ($from) {
            $path = basename($from);
            $destination = ASSETS;
            $reg = "|(\/?\w+\/\w+\/)(\w+/)?$path|i";
            if ($page) {
                $mkdir = composer(curry2('mkdir')(0777), curry2('substr')(1));
                $doMkDir = doBestDefer('isDir', [fn($o) => null, $mkdir]);
                $doMkDir("$destination$page")();
            }
            $path = $page ? "/$path" : $path;
            $to = preg_replace($reg, "$1$page$path", $from);
            if($from !== $to){
                @rename($from, $to);
            }
        };
    }
    //super user only: we can suppress output of image to public page by putting a ! in the meta_data field
    //file becomes available for assigning a background image (which could be styled inline by a punter I guess)
    //decided AGAINST moving these files to the dev folder so only pdf files get moved; scoped by page or at root of assets if archived
    return function ($flag = false) use ($from) {
        $path = basename($from);
        $dev = substr(DEV . $path, 1);
        $img = substr(ARTICLE_IMG . $path, 1);
        if (!$flag && pregMatch('|\/dev\/|', $from)) {
            //@rename($from, $img);
        } elseif ($flag && is_bool($flag) && fileExists($img)) {
            //@rename($from, $dev);
        }
    };
}

function doWhen(callable $predicate, callable $action)
{
    return function (...$args) use ($predicate, $action) {
        if ($predicate(...$args)) {
            return $action(...$args);
        }
    };
}

function truthy($a, $b = null)
{
    return is_null($b) ? $a : $a && $b;
}

function inArray(mixed $needle, array $haystack)
{
    $cb = function ($n) {
        return function ($agg, $cur) use ($n) {
            return $agg ? $agg : pregMatch("/^$n$/i", $cur);
        };
    };
    return array_reduce($haystack, $cb($needle));
}


function keyValue($arr, $k, $v, $flag = null)
{
    $ret = [];
    $arr = array_map(curry2('toObject')(true), $arr);
    foreach ($arr as $item) {
        if (isset($item[$k]) && isset($item[$v])) {
            $ret[$item[$k]] = $item[$v];
        }
    }
    return $ret;
}

function getExtension($path)
{
    $path = basename($path);
    return strtolower(substr(strrchr($path, "."), 1));
}

function checkLeadingSlash($str)
{
    return str_starts_with($str, '/') ? substr($str, 1) : $str;
}

function isDir($file)
{
    if (is_dir("../$file")) {
        //return realpath("../$file");//do no work??
        return "../$file";
    }
    if ($file) {
        $file = checkLeadingSlash($file);
        return is_dir($file) ? $file : '';
    }
    //path may have been removed??
    return '';
}

function safeScanDir(string $directory)
{
    $scandir = doWhen('identity', 'scandir');
    $filter = doWhen('identity', partial('preg_grep', "/^[^\.]/"));
    $getValues = doWhen('identity', 'array_values');
    $ifNull = fnull([]);
    $getDirFiles = composer($ifNull, $getValues, $filter, $scandir, 'isDir');
    return $getDirFiles(strtolower($directory));
}

function scanDirectory(string $dir)
{
    $dir = checkLeadingSlash($dir);
    return scandir($dir);
}

function myScanDir(string $path, array $blacklist = ['thumb'])
{
    $path = isDir($path);
    if ($path) {
        $directories = scanDirectory($path);
        $directories = array_diff($directories, $blacklist);
        //filter directories that match words ONLY not '..' etc..
        $f = partial('preg_match', '|^\w+|');
        $dirs = safeFilter($directories, $f);
        return $dirs;
    }
    return [];
}

//DO NOT FORGET a file may not exist
//ensure uberpath has subdirectories
//files/home/home.jpg we need to start from files/ not files/home/
//issue with same file existing in two folders discovered when uncropped gallery image found in articles
function scanMultiDir(array $ubers, string $thefile, array $blacklist = [])
{
    $map = function ($o) use ($blacklist) {
        $dir = myScanDir($o, $blacklist);
        $r = null;
        $isFile = fn($agg, $cur) => $agg && pregMatch('/\w+\.\w+$/', $cur);
        $allfiles = array_reduce($dir, $isFile, true);
        //safe if all files, but not if a mix of files/subdirectories
        if ($allfiles) {
            $r = preg_replace('|\/\w+\/$|', '/', $o);
        }
        return $r && pregMatch('/\w+/', $r) ? $r : $o;
    };

    $uberpaths = array_map($map, $ubers);
    $uberpaths = safeFilter($uberpaths, 'identity');
    $scan = curry2('myScanDir')([...$blacklist, 'css', 'js', 'fonts', 'thumbs']);
    //filter out subfolders
    $scandirs = array_map($scan, $uberpaths);
    $scans = [];
    foreach ($scandirs as $dir) {
        $L = count($dir);
        $ret = [];
        for ($i = 0; $i < $L; $i++) {
            $str = isset($dir[$i]) ? $dir[$i] : '';
            if ($str && !pregMatch("|\w\.\w+$|", $str)) {
                $ret[] = $str;
            }
        }
        $scans[] = $ret;
    }
    $scandirs = empty($scans) ? $scandirs : $scans;


    //can't use function statement here, must assign to var (redclare)
    $callback = function ($uberpaths, $scandirs, $file) {

        $drive = function ($i = 0, $j = 0) use ($uberpaths, $scandirs, $file, &$drive) {
            $uberpath = $uberpaths[$i] ?? '';
            $found = false;
            if ($uberpath) {
                $current = $scandirs[$i];
            }
            $subpath = isset($current[$j]) ? $current[$j] : '';
            $subpath = $subpath && isDir("$uberpath$subpath") ? $subpath : '';
            //if($j > 1) dump("$uberpath$subpath");
            [$path] = findfile("$uberpath$subpath", $file, 2);
            if ($path) {
                $found = true;
                return "$uberpath$subpath/$path";
            }
            if (!$found && isset($current[$j + 1])) {
                return $drive($i, $j + 1);
            }

            if (!$found && isset($uberpaths[$i + 1])) {
                return $drive($i + 1, 0);
            }
            //!!return an empty string
            return '';
        };
        return $drive;
    };

    return $callback($uberpaths, $scandirs, $thefile)();
}

function always(mixed $x)
{
    return function () use ($x) {
        return $x;
    };
}

function arrayFindKey(array $array, callable $callback)
{
    foreach ($array as $key => $value) {
        if ($callback($value, $key)) {
            return $key;
        }
    }
    return null;
}

function getter(array $o, mixed $p)
{
    return $o[$p];
}


function isSubString(string $haystack, mixed $needle, int $offset = 0)
{
    return composer('is_numeric', 'stripos')($haystack, $needle, $offset);
}

function invokeOnly(callable $func)
{
    return $func();
}

function simpleInvoke(callable $f, mixed $a)
{
    return $f($a);
}

function deco($fn)
{
    return function (...$args) use ($fn) {
        return $fn(...$args);
    };
}

function curry2($fun)
{
    return function ($arg2) use ($fun) {
        return function ($arg1) use ($fun, $arg2) {
            return $fun($arg1, $arg2);
        };
    };
}

function curryDefer($fun)
{
    return function ($arg1) use ($fun) {
        return function () use ($fun, $arg1) {
            return $fun($arg1);
        };
    };
}

function curry22($fun)
{
    return function ($arg2) use ($fun) {
        return function ($arg1) use ($fun, $arg2) {
            return function () use ($fun, $arg1, $arg2) {
                return $fun($arg1, $arg2);
            };
        };
    };
}

function curry22Ref($fun)
{
    return function ($arg2) use ($fun) {
        return function (&$arg1) use ($fun, $arg2) {
            return function () use ($fun, &$arg1, $arg2) {
                return $fun($arg1, $arg2);
            };
        };
    };
}

function curry3($fun)
{
    return function ($arg3) use ($fun) {
        return function ($arg2) use ($fun, $arg3) {
            return function ($arg1) use ($fun, $arg2, $arg3) {
                return $fun($arg1, $arg2, $arg3);
            };
        };
    };
}

function curry33($fun)
{
    return function ($arg3) use ($fun) {
        return function ($arg2) use ($fun, $arg3) {
            return function ($arg1) use ($fun, $arg2, $arg3) {
                return function () use ($fun, $arg1, $arg2, $arg3) {
                    return $fun($arg1, $arg2, $arg3);
                };
            };
        };
    };
}

function curry4($fun)
{
    return function ($arg4) use ($fun) {
        return function ($arg3) use ($fun, $arg4) {
            return function ($arg2) use ($fun, $arg3, $arg4) {
                return function ($arg1) use ($fun, $arg2, $arg3, $arg4) {
                    return $fun($arg1, $arg2, $arg3, $arg4);
                };
            };
        };
    };
}

function curry2L($fun)
{
    return function ($arg1) use ($fun) {
        return function ($arg2) use ($fun, $arg1) {
            return $fun($arg1, $arg2);
        };
    };
}

function curry2ByRefDefer($fun)
{
    return function (&$arg1) use ($fun) {
        return function ($arg2) use ($fun, &$arg1) {
            return function () use ($fun, &$arg1, $arg2) {
                return $fun($arg1, $arg2);
            };
        };
    };
}

//https://eddmann.com/posts/using-partial-application-in-php/
//simple version that expects arguments are fulfilled on the NEXT call
//a clever version would count the expected number of arguments and return partials until all args have been received
function partial($func, ...$args)
{
    return function (...$newargs) use ($func, $args) {
        return $func(...$args, ...$newargs);
    };
}

function partial2($func, ...$args)
{
    return function (...$newargs) use ($func, $args) {
        return function(...$deferargs) use($func, $args, $newargs){
            return $func(...$args, ...$newargs, ...$deferargs);
        };
    };
}

function dump($arg = "")
{
    if (!is_array($arg) && is_callable($arg)) {
        if ($arg()) {
            var_dump($arg);
            exit;
        }
    } else {
        var_dump($arg);
        exit;
    }
}

function vdump($a)
{
    var_dump($a);
    return $a;
}

function greaterThan($a, $b)
{
    return $a > $b;
}

function greaterEqual($a, $b)
{
    return $a >= $b;
}

function lesserEqual($a, $b)
{
    return $a <= $b;
}

function lesserThan($a, $b)
{
    return $a < $b;
}

function divideBy($divisor)
{
    return function ($dividend) use ($divisor) {
        return $dividend / $divisor;
    };
}

function multiplyBy($multiplier)
{
    return function ($multiplicand) use ($multiplier) {
        return $multiplicand * $multiplier;
    };
}

function divide($dividend, $divisor)
{
    return $dividend / $divisor;
}

function add($a, $b)
{
    return $a + $b;
}
function minus($a, $b)
{
    return $a - $b;
}
function equals($a, $b)
{
    return $a === $b;
}

function doMatch($k, $v)
{
    return function ($o) use ($k, $v) {
        if (is_array($o)) {
            return isset($o[$k]) && $o[$k] === $v;
        }
        return isset($o->{$k}) && $o->{$k} === $v;
    };
}

function toObject($o, $arg = false)
{
    return json_decode(json_encode($o), $arg);
}

function doEncode($str)
{
    return urlencode(strtolower($str));
}

function beautify($txt)
{
    return ucwords(strtolower(str_replace('_', ' ', $txt)));
}

function isUpperCase($str, $flag = false)
{
    return  $str && ucwords($str) === $str ? ($flag ? strtolower($str) : true) : false;
}

function isUpperCaseKey($str)
{
    return strtoupper($str) === $str;
}

function exclaim($msg, $char = '!')
{
    $msg = urldecode($msg);
    if (substr($msg, 0, 1) === $char) {
        $msg = substr($msg, 1);
    } else {
        $msg = '';
    }
    return is_numeric($msg) ? '' : $msg;
}


function html($str)
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function htmlout($str)
{
    echo html($str);
}

function toHtml($str)
{
    // convert $this->string to HTML
    $text = htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // strong (bold)
    $text = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
    // emphasis (italic)
    $text = preg_replace('/_([^_]+)_/', '<em>$1</em>', $text);
    $text = preg_replace('/\*([^\*]+)\*/', '<em>$1</em>', $text);
    // Convert Windows (\r\n) to Unix (\n)
    $text = str_replace("\r\n", "\n", $text);
    // Convert Macintosh (\r) to Unix (\n)
    $text = str_replace("\r", "\n", $text);
    /*
        // Paragraphs
        $text = '<p>' . str_replace("\n\n", '</p><p>', $text) . '</p>';
        // Line breaks
        $text = str_replace("\n", '<br>', $text);
*/
    // [linked text](link URL)
    $text = preg_replace('/\[([^\]]+)]\(([-a-z0-9._~:\/?#@!$&\'()*+,;=%]+)\)/i', '<a href="$2">$1</a>', $text);
    return $text;
}

//https://stackoverflow.com/questions/14334740/missing-parentheses-with-regex; adapted from js
//usage: replace ALL but special characters then replace ONLY those that FOLLOW one another
//if orphans are left return true ie: !$str
function checkBrackets($str, $pair = null)
{
    $s = '';
    $reg = null;
    $rpl = null;

    if ($pair) {
        $reg = "/[^$pair]/";
        //must escape end square bracket in both scenarios when in an exclusion block [^abc[\]]
        $reg = substr($pair, 1) === ']' ? "/[^[\]]/" : $reg;
        //brace does not need escaping but no harm is done
        $rpl = preg_quote($pair);
    }
    $reg = $reg ? $reg : '/[^{}[\]()]/';
    $rpl = $rpl ? $rpl : '{}|\[\]|\(\)';
    $str = preg_replace($reg, '', $str);
    while ($s != $str) {
        $s = $str;
        $str = preg_replace("/$rpl/", '', $str);
    }
    return !$str;
}

function single_space($value)
{
    return pregMatch('/^\s$/', $value);
}
function identity($arg)
{
    return $arg;
}

// Function to find out the maximum repeating
// character in given string
function maxRepeating($str, $limit, $exceptions)
{
    $n = strlen($str);
    $maxCnt = 0;
    //at least three letter word
    $res = $str[2] ?? null;
    $cnt = 1;
    $limit = is_numeric($res) ? 7 : $limit;
    if ($res) {
        for ($i = 1; $i < $n; $i++) {
            if ($cnt > $limit) {
                $res = true;
                break;
            }
            if (in_array($str[$i], $exceptions)) {
                continue;
            }
            if ($str[$i] && ($str[$i] === $str[$i - 1])) {
                $cnt++;
            } else {
                $cnt = 1;
            }

            if ($cnt > $maxCnt) {
                $maxCnt = $cnt;
                $res = $str[$i - 1];
            }
        }
        return $res;
    }
}

function spam_scrubber($value)
{
    //usage: $scrubbed = array_map('spam_scrubber', $data($_POST));
    // List of very bad values:
    $very_bad = array(
        'to:',
        'cc:',
        'bcc:',
        'content-type:',
        'mime-version:',
        'multipart-mixed:',
        'content-transfer-encoding:'
    );
    if (is_array($value)) {
        foreach ($value as $v) {
            return spam_scrubber($v);
        }
    }
    // If any of the very bad strings are in
    // the submitted value, return an empty string:
    foreach ($very_bad as $v) {
        if (pregMatch("/^$v$/", $value)) {
            return '';
        }
        if (isSubString($value, $v)) {
            // return '';
        }
    }
    /*Replace any newline characters with spaces:
    $value = str_replace(array(
        "\r",
        "\n",
        "%0a",
        "%0d",
        "\t",
        "%08",
        "%09"
    ), ' ', $value);
    */
    //$value = str_replace(array('Fucking', 'fucking', 'fuck', 'wank', 'cunt'), '***', $value);
    // Return the value:
    return trim($value);
}

function spam_scrubber2($value)
{

    //usage: $scrubbed = array_map('spam_scrubber', $data($_POST));
    // List of very bad values:
    $very_bad = array(
        'to:',
        'cc:',
        'bcc:',
        'content-type:',
        'mime-version:',
        'multipart-mixed:',
        'content-transfer-encoding:'
    );

    $postbad = [];

    if (is_array($value)) {
        /*
        foreach ($value as $v) {
         return spam_scrubber($v);
        }
        */
        $value = implode(' ', $value);
    }
    // If any of the very bad strings are in
    // the submitted value, return an empty string:

    foreach ($very_bad as $v) {
        $postbad[] = stripos($value, $v) ? '' : $v;
    }
    $postbad = safeFilter($postbad, fn($o) => !$o);
    if ($postbad === []) {
        $spacelike = [
            "\r",
            "\n",
            "%0a",
            "%0d",
            "\t",
            "%08",
            "%09"
        ];
        // Replace any newline characters with spaces:
        $value = str_replace($spacelike, ' ', $value);
        return is_array($value) ? array_map('trim', $value) : trim($value);
    }
    return $postbad[0];
    //$value = str_replace(array('Fucking', 'fucking', 'fuck', 'wank', 'cunt'), '***', $value);
}

function buildMessage($k, $v, $flag)
{
    $ret = $flag ? "" : "\r\n\r\n"; //!MUST BE DOUBLE QUOTES!
    $str = ucfirst($k) . ': ' . $v;
    return $str . $ret;
}


function getDiff(array $a, array $b)
{
    $big = $a;
    if (count($a) >= count($b)) {
        return [array_values(array_filter(array_diff($a, $b), fn($o) => $o)), $big];
    }
    $big = $b;
    return [array_values(array_filter(array_diff($b, $a), fn($o) => $o)), $big];
}


function arrayFindValues(array $arr, callable $cb)
{
    $res = array_filter($arr, $cb);
    if (empty($res)) {
        return null;
    }
    return current(array_values($res));
}

function getProp(array $o, mixed $p)
{
    return isset($o[$p]) ? $o[$p] : null;
}

function mypluck(array $haystack, mixed $n = 0)
{
    if (!$n) {
        $n = reset($haystack);
        return [$n];
    }
    if (is_bool($n)) {
        $n = array_key_last($haystack);
        return [$n];
    }
    return $haystack[$n] ?? null;
}

function preconditions(...$checkers)
{
    //$checkers = $args;
    return function ($strategy, $value) use ($checkers) {

        $res = array_map(
            function ($checker) use ($strategy, $value) {
                return $checker->validate($value) ? [] : [
                    $checker->message
                ];
            },
            $checkers
        );
        $errors = array_reduce($res, 'array_merge', []);

        if (!empty($errors)) {
            return $errors;
        }
        //$strategy most likely identity
        return $strategy($value);
    };
}

function startSession()
{
    if (!isset($_SESSION)) {
        session_start();
    }
}

function fileExists(string $file, $root = null)
{
    if (!is_string($file)) {
        return false;
    }
    $file = normalize($file);
    $test = $root && is_bool($root);

    $rooty = ROOT ? $_SERVER['DOCUMENT_ROOT'] . ROOT . $file : $_SERVER['DOCUMENT_ROOT'] . $file;

    if ($test && $file) {
        $f = is_bool($root) ? is_file($rooty) : null;
        if ($f) {
            return $file;
        }
    }
    $f = is_file($file) ? $file : null;

    if ($f) {
        return $file;
    } else {
        $rel = is_file("../$file") ? "../$file" : null;
        //filepaths for displaying and RELATIVE filepaths for passing is_file can be different
        //if we want the file for displaying set $root to false
        return !$root && is_bool($root) ? $file : $rel;
    }
}

function MarkDownClearLinkText($str, $aux, $selection, $type)
{
    if ($aux && $selection && $type) {
        //[mylinktext][1] becomes mylinktext IF there was some copy provided
        //it is otherwise left AND the index is available for swapping
        $reg = ($type === 'ref') ? "/\[([^\]]+)\]\[$aux\]/i" : "/\[($aux)\]\([^)]+\)\s*(?:{[^}]+}|)?/i";
        $str = preg_replace($reg, '$1', $str);
    }
    return $str;
}

function MarkDownGetLinkInformation($str, $pathtofile = '')
{
    $type = 'ref';
    if (is_int($pathtofile)) { //int would refer to a regex capturing group
        $reg = "|(\[[^\]]+\][:(])\s?([^){\s]+)|";
        $i = $pathtofile;
    } else { //assume reflink
        $reg = "~\[([^\]]+)\]:\s*\/?$pathtofile~i";
        $i = 1;
    }
    $ret = findMatch($reg, $str, $i);
    if (!$ret && $pathtofile) {
        $reg = "~\[([^\]]+)\]\(.*\/?$pathtofile~i";
        $ret = findMatch($reg, $str, 1);
        $type = $ret ? 'inline' : null;
    }
    return [$ret, $type];
}

function MarkDownGetActiveLinkCopy($str, $selection, $pathtofile)
{
    $type = null;
    $label = '';
    $regx = '';
    $linkref = isSubString($str, $pathtofile);
    $doMatch = curry3('findMatch')(-11)($str);
    //find ALL existing links
    $refreg = "/(?<!#)\[([^\]]+)\]\[[^\]]+\]/i";
    $reg = "/(?<!#)\[([^\]]+)\]\([^)]+\)/i";
    $refcopy = $doMatch($refreg)[1] ?? [];
    $inlinecopy = $doMatch($reg)[1] ?? [];
    if ($linkref) {
        //exclude current link text from linked copy set to allow update
        [$label, $type] = MarkDownGetLinkInformation($str, $pathtofile);
        if ($type === 'ref') {
            if ($label && $selection) {
                $regx = "/(?<!#)\[([^\]]+)\]\[(?!$label)/i";
                $refcopy = $doMatch($regx)[1] ?? [];
            }
        } elseif ($type === 'inline') {
            {
                $regx = "/(?<!#)\[(?!$label)\]\([^\)]+\)/i";
                $inlinecopy = $doMatch($regx)[1] ?? [];
            }
        }
    }
    $regx = $regx ? $regx : ($type === 'inline' ? $reg : $refreg);
    $linkedcopy = array_merge($refcopy, $inlinecopy);
    return [$label, $linkedcopy];
}

function MarkDownRemoveLinkHref($str, $pathtofile, $type = 'ref', $attr_id = false)
{
    if ($type === 'inline') {
        $file = preg_quote($pathtofile);
        //article|author.pdf; //article~author.pdf
        $reg = '~\[([^\]]+)\]\(\/?' . $file . '(\s"([^"]+)"|)\)(?:\s*{[^}]+}|)~i';
        if (is_numeric(strpos($file, '~')) && !is_numeric(strpos($file, '|'))) {
            $reg = '|\[([^\]]+)\]\(\/?' . $file . '(\s"([^"]+)"|)\)(?:\s*{[^}]+}|)|i';
        }
        $linktext = findMatch($reg, $str, 1);
        $title = findMatch($reg, $str, 3);
        if ($attr_id && $linktext) {
            $str = $linktext ? preg_replace($reg, $linktext, $str) : $str;
            return [$str, $title];
        }
        return [$str, $title];
    } else {
        //$reg = "~\[[^\]]+\]:\s*\/?$pathtofile\s?(.+)\n?~i";
        $file = preg_quote($pathtofile);
        $reg = "~\[[^\]]+\]:\s*\/?$file(?:\s?\"([^\"]+)\"|)(?:\s*{[^}]+}|)~i";

        if (is_numeric(strpos($file, '~')) && !is_numeric(strpos($file, '|'))) {
            $reg = "|\[[^\]]+\]:\s*\/?$file(?:\s?\"([^\"]+)\"|)(?:\s*{[^}]+}|)|i";
        }
        $linktext = findMatch($reg, $str);
        $title = findMatch($reg, $str, 1);
        $str = $linktext ? str_replace($linktext, '', $str) : $str;
        return [$str, $title];
    }
}

function MarkDownBreakLink($str, $pathtofile, $selection)
{
    //GIVEN a LIST item we could delete the item on removal of link, but let us leave that to the discretion of the editor
    [$aux, $type] = MarkDownGetLinkInformation($str, $pathtofile); //[n]
    [$str, $title] = MarkDownRemoveLinkHref($str, $pathtofile, $type, $selection);
    $str = MarkDownClearLinkText($str, $aux, $selection, $type);
    $str = preg_replace('/  {/', ' {', $str);
    return [$str, $title];
}

function normalizePath($f, $path, $rev = false)
{
    $path = preg_replace('|\/\/|', '/', $path); //replaces // with /
    if (ROOT) {
        // return $f($_SERVER['DOCUMENT_ROOT'] . ROOT .  $path);
    }
    $i = $path ? strpos($path, '/') : null;
    if ($path) {
        //$path = checkLeadingSlash($path);
        if (is_numeric($i) && $i === 0) {
            $path = substr($path, 1);
        } elseif (!$i && $rev) {
            $path = "/$path";
        }
    }
    return $f($path);
}

function normalize($path)
{
    // $path = ;//replaces // with /
    while (pregMatch('|\/\/|', $path)) {
        $path = preg_replace('|\/\/|', '/', $path);
    }

    return normalizePath('identity', $path);
}

//https://stackoverflow.com/questions/7497733/how-can-i-use-php-to-check-if-a-directory-is-empty
function dir_is_empty($dir)
{
    $handle = @opendir($dir);
    $handle = $handle ? $handle : null; //null not false
    if ($handle) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                @closedir($handle);
                return false;
            }
        }
        @closedir($handle);
        return true;
    }
    return false;
}

//used by scanMultiDir in functions.php (this file) and checkLinkPaths in Article.php controller
function findfile($dir, $path = '', $i = 2)
{
    if (!strpos($path, '/')) {
        $dirpath = normalizePath('identity', "$dir/$path");
    } else {
        $dirpath = normalizePath('identity', $path);
    }
    //!$path assumes $dir is fullpath to file
    if (!$path) {
        $path = @basename($dir);
        $_dir = @dirname($dir);
        $dirpath = normalizePath('identity', "$_dir/$path");
    }
    $ret = @is_file($dirpath);
    if (!$ret) {
        $ret = @is_file("../$dir" . $path);
    }
    if (!$ret && $i && pregMatch('/\.jpe?g$/i', $path)) {
        $ext = stristr($path, '.');
        $pth = strstr($path, '.', true);
        if ((strlen($ext) === 4) && $i) {
            return findfile($dir, $pth . '.jpeg', $i -= 1);
        } elseif ($i) {
            return findfile($dir, $pth . '.jpg', $i -= 1);
        }
    } elseif ($ret) {
        //!!must return $path only
        return [$path, $i];
    }
    return ['', ''];
}

function getMimeType($path, $ubers = [], $blacklist = [])
{
    if (empty($ubers)) {
        $ubers = [GALLERY, IMAGES, ASSETS, VIDEOS, RESOURCES, FILESTORE_DIR];
    }
    $scan = partial('scanMultiDir', $ubers);
    $exists = composer('fileExists', $scan);
    $filename = $exists(basename($path), $blacklist);

    if ($filename && is_file($filename)) { // deal with '../'
        $finfo = @finfo_open(FILEINFO_MIME_TYPE); // return mime type aka mimetype extension
        $mimetype = @finfo_file($finfo, $filename);
        @finfo_close($finfo);
        return [$mimetype, $filename];
    }
    return ['', ''];
}

//https://deadlytechnology.com/blog/web-development-tips/php-ternary-syntax
function prepReg($str, $x = '', $y = '')
{
    return (
        ($x && $y) ? "/^$str$/" : (($x) ? "/^$str/" : (($y) ? "/$str$/" : "/$str/")));
}

function notEmpty($arg = '')
{
    $a = is_array($arg);
    if ($a) {
        return $arg !== [];
    }
    $a = is_string($arg);
    if ($a) {
        return $arg !== '';
    }
    $a = is_numeric($arg);
    if ($a) {
        return $arg !== 0;
    }
    return $arg;
}

function findMatch($reg, $str, $i = 0)
{
    //$reg = preg_quote($reg, '_');
    $def = $i < 0;
    if (!empty($reg) || !empty($str)) {
        if ($def) {
            preg_match_all($reg, $str, $matches);
            $i = abs($i);
            $i = $i > 10 ? true : $i;
        } else {
            preg_match($reg, $str, $matches);
        }
        $ret = $def ? [] : '';
        return empty($matches) ? $ret : (is_bool($i) ? $matches : ((isset($matches[$i])) ? $matches[$i] : $ret));
    }
    return null;
}

function findMatcher($reg, $str, $i = 0)
{
    if (!empty($reg) || !empty($str)) {
        if ($i < 0) {
            preg_match_all($reg, $str, $matches);
            $i = $i < -1 ? true : 0;
        } else {
            preg_match($reg, $str, $matches);
        }
        if (empty($matches)) {
            return '';
        }
        if (is_bool($i)) {
            return $matches;
        }
        return $matches[$i] ?? null;
    }
    return null;
}

function prepRegHost($path)
{
    $finder = curry2('findMatch')(trim($path));
    $host = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]";
    $host = preg_quote($host, '/');
    if ($finder("/$host/")) {
        $path = parse_url($path);
        $path = array_key_last($path);
    }
    return trim($path);
}

function isSpaceDelimited($h, $n)
{
    $i = stripos($h, $n);
    $first = substr($h, $i - 1, 1);
    $last = substr($h, $i + strlen($n), 1);
    return pregMatch("/\s/", $first) && pregMatch("/\s/", $last);
}

function validate_extension($needle, $haystack)
{
    $n = strtolower(strrchr($needle, '.'));
    $h = array_map('strtolower', $haystack);
    return in_array($n, $h);
}

function exemptMember($n, $h, $flag = false)
{
    return in_array($n, $h) ? $flag : !$flag;
}

function xCheck($i, $newblock, $chars)
{
    $ret = true;
    $j = 0;
    while (isset($newblock[$i])) {
        $ret = $ret && $newblock[$i] == $chars[$j];
    }
    return $ret;
}

//https://www.php.net/manual/en/function.getimagesize.php
// Retrieve JPEG width and height without downloading/reading entire image.
function getjpegsize($img_loc)
{
    $img_loc = normalizePath('file_exists', trim($img_loc));
    if (fileExists($img_loc)) {
        $handle = fopen($img_loc, "rb");
        $nb = null;
        if (!feof($handle)) {
            $nb = fread($handle, 32); //$nb should read new_block, shortened to appease formatter
            $i = 0;
            if ($nb[$i] == "\xFF" && $nb[$i + 1] == "\xD8" && $nb[$i + 2] == "\xFF" && $nb[$i + 3] == "\xE0") {
                $i += 4;
                $x = ["\x4A", "\x46", "\x49", "\x46", "\x00"];
                if (xCheck($i, $nb, $x)) {
                    // Read block size and skip ahead to begin cycling through blocks in search of SOF marker
                    $block_size = unpack("H*", $nb[$i] . $nb[$i + 1]);
                    $block_size = hexdec($block_size[1]);
                    while (!feof($handle)) {
                        $i += $block_size;
                        $nb .= fread($handle, $block_size);
                        if ($nb[$i] == "\xFF") {
                            // New block detected, check for SOF marker
                            $x1 = ["\xC0", "\xC1", "\xC2", "\xC3", "\xC5", "\xC6", "\xC7", "\xC8", "\xC9"];
                            $x2 = ["\xCA", "\xCB", "\xCD", "\xCE", "\xCF"];
                            $sof_marker = [...$x1, ...$x2];
                            if (in_array($nb[$i + 1], $sof_marker)) {
                                /*SOF marker detected. Width and height information
                                is contained in bytes 4-7 after this byte.*/
                                $a = $nb[$i + 2] . $nb[$i + 3] . $nb[$i + 4];
                                $b =  $nb[$i + 5] . $nb[$i + 6] . $nb[$i + 7] . $nb[$i + 8];
                                $size_data = $a . $b;
                                $unpacked = unpack("H*", $size_data);
                                $unpacked = $unpacked[1];
                                $height = hexdec($unpacked[6] . $unpacked[7] . $unpacked[8] . $unpacked[9]);
                                $width = hexdec($unpacked[10] . $unpacked[11] . $unpacked[12] . $unpacked[13]);
                                return array($width, $height);
                            } else {
                                // Skip block marker and read block size
                                $i += 2;
                                $block_size = unpack("H*", $nb[$i] . $nb[$i + 1]);
                                $block_size = hexdec($block_size[1]);
                            }
                        } else {
                            return false;
                        }
                    }
                }
            }
        }
    } //fof
    return false;
}
// Retrieve PNG width and height without downloading/reading entire image.
function getpngsize($img_loc)
{
    $handle = fopen($img_loc, "rb") or die("Invalid file stream.");
    if (!feof($handle)) {
        $nb = fread($handle, 24);
        if (
            $nb[0] == "\x89" &&
            $nb[1] == "\x50" &&
            $nb[2] == "\x4E" &&
            $nb[3] == "\x47" &&
            $nb[4] == "\x0D" &&
            $nb[5] == "\x0A" &&
            $nb[6] == "\x1A" &&
            $nb[7] == "\x0A"
        ) {
            if ($nb[12] . $nb[13] . $nb[14] . $nb[15] === "\x49\x48\x44\x52") {
                $width  = unpack('H*', $nb[16] . $nb[17] . $nb[18] . $nb[19]);
                $width  = hexdec($width[1]);
                $height = unpack('H*', $nb[20] . $nb[21] . $nb[22] . $nb[23]);
                $height  = hexdec($height[1]);
                return array($width, $height);
            }
        }
    }
    return false;
}

function splitOn($str, $seps = [' ', '/'])
{
    $L = count($seps);
    $res = null;
    $ret = [];
    while ($L--) {
        $res = explode($seps[$L], $str);
        if (isset($res[1])) {
            $ret = array_map(function ($o) {
                return !is_numeric($o) ? intval($o) : $o;
            }, $res);
            break;
        }
    }
    return empty($ret) ? [$str] : $ret;
}


function trimToLower($str)
{
    return strtolower(trim($str));
}

function reLocate($path, $prefix = '')
{
    header('Location: ' . $path);
    exit();
}

function retour($arg = '')
{
    header("Location: /$arg");
    exit;
}

function fixUri()
{
    $uri = $_SERVER['REQUEST_URI'];
    $uri = strtok(ltrim($uri, '/'), '?');
    $route = explode('/', $uri);
    array_unique($route);
    return $route;
}

function doImmediate($pred, $actions, ...$args)
{
    return array_reduce($actions, function ($a, $b) use ($pred, $actions, $args) {
        return $a && $pred(...$args) ? $a(...$args) : $b(...$args);
    });
}

function doBestDefer($pred, $actions)
{
    return function (...$args) use ($pred, $actions) {
        return array_reduce($actions, function ($a, $b) use ($pred, $args) {
            return $a && $pred(...$args) ? partial($a, ...$args) : partial($b, ...$args);
        });
    };
}

function every($predicates){
    return array_reduce($predicates, fn($agg, $curr) => $agg && getResult($curr), true);
}

function appendToClass($flag = false)
{
    return function (string $klas, string ...$args) use ($flag) {
        if (!$flag) {
            return implode(' ', safeFilter([$klas, ...$args], 'identity'));
        } else {
            $m = $args[0];
            $ret = preg_replace('/^([^.]+)*(\.\w+)*(\s\w+)*/', "$1$2$3 $m", $klas);
            return preg_replace('/^\s(.+)*?/', ".$1", $ret);
        }
    };
}

function appendClass(string $klas, string ...$args)
{
    return implode(' ', safeFilter([$klas, ...$args], 'identity'));
}

function classify($str, $flag = null)
{
    $res = explode('.', $str);
    $ret = 'id=%s class=%s';
    $false = !$flag && is_bool($flag);
    //taking of the piss '.' yields ["",""];
    if (empty($res[0]) && (empty($res[1]) || $flag)) {
        return '';
    }

    if (empty($res[0]) || $false) { //class NO id
        $k = sprintf('class=%s', $res[1] ?? '');
        return pregMatch('/=\w+/', $k) ? $k : '';
    }

    if (empty($res[1]) || $flag) {
        $id = sprintf('id=%s', $res[0] ?? '');
        return pregMatch('/=\w+/', $id) ? $id : '';
    }
    return sprintf($ret, $res[0], $res[1]);
}

function whilst($array, $cb, $i = 0)
{
    $i = $i ? count($array) : 0;
    while (isset($array[$i])) {
        if ($cb($array[$i])) {
            break;
        }

        $i++;
    }
    return $i;
}

function queryPath($pathtofile, $str)
{
    $ext = strrchr($pathtofile, '.');
    $ext = $ext ? strtolower(substr($ext, 1)) : null;
    $segments = explode('/', $pathtofile);
    if ($ext) {
        $file = array_pop($segments);
    } else {
        $subpath = array_pop($segments);
    }
    $res = null;
    switch ($str) {
        case 'ext':
            $res = $ext;
            break;
        case 'file':
            $res = $file;
            break;
        case 'parent':
            $res = $file ? array_key_last($segments) : $subpath;
            break;
        default:
            $res = implode('/', $segments) . '/';
    }
    return $res;
}

function getPortion($h, $sep = '/', $bool = false)
{
    if ($h) {
        return substr(strrchr($h, $sep, $bool), 1);
    }
    return $h;
}

function safeFilter($array, $cb = 'identity', $unique = false)
{
    $ret = array_values(array_filter($array, $cb));
    return $unique ? array_unique($ret) : $ret;
}

function safeList($array, $i)
{
    $n = ($i - 1);
    $i = 0;
    $ret = [];
    while ($i <= $n) {
        if (!isset($array[$i])) {
            $ret[] = null;
        } else {
            $ret[] = $array[$i];
        }
        $i++;
    }
    return $ret;
}

function arrayDiff($arr, ...$args)
{
    return array_values(array_diff($arr, ...$args));
}

function isNull($o, $p)
{
    $arg = isset($o[$p]) ? $o[$p] : null;
    return is_null($arg);
}

function validateMove($arg, $strategy = null)
{
    return $strategy ? $strategy($arg) : true;
}

function doAssign(&$arr, $i)
{
    while (!$next = array_shift($arr)) {
        $i++;
    }
    return [$next, $i];
}

//https://stackoverflow.com/questions/8311074/how-to-call-the-current-anonymous-function-in-php
//https://stackoverflow.com/questions/2480179/anonymous-recursive-php-functions

function driver($strategy)
{
    $drive = function ($end, &$van, &$places, $i) use ($strategy, &$drive) {
        if (!count($van)) {
            return;
        }

        $nextItem = $places[$i];
        $place = null;
        if ($end) { //end is false until we reach the NULL item
            //inward skip NULL items in van
            //eg only connery bonds pass for reassigning['frwl', 'yolt', NULL, 'daf', NULL]
            list($nextItem, $j) = doAssign($van, 1);
            $places[$i] = $nextItem;
            return $drive($end, $van, $places, $i - $j);
            //the location of the item to be moved is set to NULL and put on the van
            //no further processing of $places is required
            //$place = isset($places[$i + 1]) ? $places[$i + 1] : null;
        } elseif (isNull($places, $i + 1)) {
            //arrival
            $j = 0;
            $pass = validateMove($nextItem, $strategy);
            if (!$pass) {
                list($nextItem, $j) = doAssign($van, 1);
            }
            $places[$i + 1] = $nextItem;
            return $drive(true, $van, $places, $i - $j);
        } else {
            //outward, populate $van set failed items to null
            $nextItem = validateMove($nextItem, $strategy) ? $nextItem : null;
            array_unshift($van, $nextItem);
            return $drive($end, $van, $places, $i + 1);
        }
    };
    return $drive;
}
//simple version that requires no strategy
function drive($end, &$van, &$places, $i)
{
    //$i === 'destination';
    //$places [a,b,c,d...]
    //$van [a||b||c||d...]; firstLabel
    if (!isset($van[0])) {
        return;
    }
    $nextLabel = $places[$i];
    if ($end) {
        $places[$i] = array_shift($van);
        return drive($end, $van, $places, $i - 1);
    } elseif (empty($places[$i + 1])) {
        $places[$i + 1] = $nextLabel;
        $end = true;
        return drive($end, $van, $places, $i);
    } else {
        array_unshift($van, $nextLabel);
        $places[$i] = null;
        return drive($end, $van, $places, $i + 1);
    }
}
//!! $flag indicate whether $args should be supplied as an array to call_user_func_array
function negate($predicate, $flag = false)
{
    return function (...$args) use ($predicate, $flag) {
        return $flag ? !$predicate($args) : !$predicate(...$args);
    };
}
function myReducer($agg, $f)
{
    $agg = !is_array($agg) ? array($agg) : $agg;
    return call_user_func_array($f, $agg);
}

//works like JS version but requires initial identity function??
function composer(...$fns)
{
    return array_reduce($fns, fn($f, $g) => fn(...$vals) => $f($g(...$vals)), 'identity');
}

function tester($i = 100)
{
    $add = fn($a, $b) => $a + $b;
    $sub = fn($a, $b) => $a - $b;
    $div = fn($a, $b) => $a / $b;
    $perform = composer(curry2($add)(9), curry2($sub)(1), curry2($div)(2));
    dump($perform($i));
}

//https://stackoverflow.com/questions/24662580/php-setcookie-not-working
function doSetCookie($flag, $time = -1)
{
    return function ($k, $v = '') use ($flag, $time) {
        $v = $v ? $v : $k;
        if ($flag) {
            $v = is_bool($flag) ? $v : $v;
            $v = is_string($v) ? strtolower($v) : $v;

           // dump([$v,$k]);
            setcookie($k, $v, $time, '/');
            $_COOKIE[$k] = $v;
        } elseif (isset($_COOKIE[$k]) && !$flag) {
            if (is_bool($flag)) {
                //this first must include '/'
                setcookie($k, '', -1, '/');
                unset($_COOKIE[$k]);
            }
        }
    };
}

function orderByList($data, $list, $a, $b)
{
    function doAssoc(&$coll, $key)
    {
        return function ($o) use (&$coll, $key) {
            return $coll[$o[$key]] = $o;
        };
    }

    $assoc = [];
    $doassoc = doAssoc($assoc, $a);
    foreach ($data as $d) {
        $doassoc($d);
    }
    $coll = [];

    foreach ($list as $k) {
        if (!isset($assoc[$k])) {
            continue;
        }
        $coll[$k] = $assoc[$k][$b];
    }
    return $coll;
}

function getRatio($w, $h)
{
    $r = $w / $h;
    $ret = $r < 1 ? $h / $w : $r;
    return round($ret, 1);
}

function getRandomString($n)
{
    return bin2hex(random_bytes($n / 2));
}

function pass($cb, $arg)
{
    $res = $cb($arg);
    return $res ? $res : $arg;
}

function markdownTidy2($content)
{
    //$content = preg_replace('/\s\[\s*([^\]]+?)\s*\](\[|\()/', '[$1]$2', $content);
    //[ oh my ] : [oh my]
    // [no label follows] : no label follows but watch out for [\[link]\]]
    //$content = preg_replace('/(\w)([,;])\S/', '$1$2', $content);
    //$content = preg_replace('/([^\s\]])\(([^(])/', '$1 ($2', $content);
    //$content = preg_replace('/\)(\S)/', ') $1', $content);
    //[Ficksburg][3] (25km away) without the space
    //[Ficksburg][3](25km away) our code will think it is dealing with an inline link; need to fix it
    //hello"quot"
    //<nav markdown="1">...
    //  $content = preg_replace('/([^"\s])"([^"\.\s])/', '$1 "$2', $content);
    //prevents this malarkey: Polo[africa][2]
    // $content = preg_replace('/(.)([^*]+)\*+([^*]+)\*+([^*]+)(.)/', ' $1$2$3$4$5 ', $content);
    //[![my icon][1]](#){.right}
    $content = preg_replace('/\s?(\w+)\[([^]]+)\]\[([^]]+)\](\w*)\s?/', ' [$1$2$4][$3] ', $content);
    //$content = preg_replace('/\s?(\S*)\[([^]]+)\]\[([^]]+)\](\S*)\s?/', ' [$1$2$4][$3] ', $content);
    return $content;
}

function markdownTidy($content)
{
    $content = preg_replace('/(?<=!)!+(?=\[)/', '', $content);
    $content = preg_replace('/\[+\[(?=!)+/', '[', $content);
    $content = preg_replace('/\]\]\]+/', ']]', $content);
    $content = preg_replace('/\[\[+/', '[', $content);
    $content = preg_replace('/\](?<=\()[^[]+\]/', ']]', $content);
    $content = preg_replace('/(?<=\s)\[\s*([^\]]+?)\s*\]/', '[$1]', $content);
    $content = preg_replace('/(?<=\s)\[([^\]]+)\](?![[(:\]])/', '$1', $content);
    $content = preg_replace('/([a-zA-Z])(,)([a-zA-Z])/', '$1$2 $3', $content);
    $content = preg_replace('/(\]\[[^\]]+\])\(/', '$1 (', $content);
    $content = preg_replace('/\]\(\s*(\S)/', ']($1', $content);
    $content = preg_replace_callback('/\.\h+([^\n])/', fn($m) => strtoupper($m[0]), $content);
    return markdownTidy2($content);
}

function markdownDoubles($content, $char)
{
    $rpl = substr($char, 1) ? substr($char, 1) : $char;
    $wrapped = pregMatch('/\]\](\[|\()/', $content); // [![my icon][6]][5]
    return $wrapped ? $content : preg_replace("|(?<!\\\\)$char$char|", $rpl, $content);
}
function checkMarkdownFormatting($content)
{
    $brackets = ['()', '{}', '""', '[]'];
    $msg = ['para', 'brace', 'quote', 'bracket'];
    $i = 0;
    $ran = false;
    $key = '';
    $escaped = pregMatch('|(?<=\\\\)\[|', $content);
    if (!$escaped) {
        $ran = true;
        while (isset($brackets[$i])) {
            if (!checkBrackets($content, $brackets[$i])) {
                break;
            }
            $i++;
        }
    }
    $key = $ran && isset($msg[$i]) ? $msg[$i] : '';
    //kludge to allow escaped square brackets [\[Google Map\]][5].
    //'/(?<=\S)\*\w/' '/\S\*+\s\S/','starstart',
    // 'star' =>'/(\S\*+?\S)/'

    if (!$key) {
        $a = ['|<\/?>|', '/\S<[^>]+>\S/',  '/{{[^}]+}}/', '/\[\[/', '/\[[^\]]+\]\([^)]+(?:\s[^"]*\)|\s"[^"]+"[^)])/'];
        $b = ['/\]\([^)]+\)[{^\s\n\r\.,]]/', '/\]?\[\d{3,}\]:?/', '/(?<!#)\[[^\]]+\]\[\w{12,}\](?!:)/'];
        $regs = [...$a, ...$b];
        $msgs = ['emptytag', 'span', 'doublebrace', 'doublebracket', 'attr', 'postattr', 'indexnum', 'indexlimit'];
        $key = '';
        $i = 0;
        $pregs = array_map(curry22('preg_match')($content), $regs);
        foreach ($pregs as $k => $f) {
            if ($f()) {
                $key = $msgs[$k] ?? null;
                break;
            }
        }
    }
    return $key;
}

