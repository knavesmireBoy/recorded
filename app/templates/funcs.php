<?php

function html2($str)
{
    return htmlspecialchars(is_null($str) ? '' : $str, ENT_QUOTES, 'UTF-8');
}

function upper($str)
{
    return strtoupper($str);
}

function trim2lower($str)
{
    return str_replace(' ', '', strtolower($str));
}

function markUp($str)
{
    // convert $this->string to HTML
    $text = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');

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
    // [linked text](link URL) use single quotes for optional title
    $reg1 = '/\[([^\]]+)]\(([-a-z0-9._~:\/?#@!$&\'()*+,;=%\s]+)\)/i';
    $reg2 = '/\[([^\]]+)]\(([-a-z0-9.:\/]+)\s?([a-z]*)\)/i';
    $text = preg_replace($reg2, '<a href="$2" title="$3">$1</a>', $text);
    return $text;
}

function prepClassName($string, $sep = ' ')
{
    if (empty($string)) {
        return '';
    }
    $parts = explode($sep, $string);
    return trim2lower(array_pop($parts));
}

function appendClassName($klas, $str)
{
    $parts = explode(' ', $klas);
    $parts[] = $str;
    return implode(' ', $parts);
}

function postLogin($output, $klas = '', $user = '', $username = '')
{
    //$in = "<h2>You are logged in as <strong>$user</strong></h2>";
    //$out = "<h2><span>$title</span></h2>";
    preg_match('/h6>you are logged in as ([\w\s?]+)/', $output, $match);
    if (empty($user) && !empty($match)) {
     //  return [$match[1], preg_replace('/<h6.+h6>/', '', $output), 'gebruiker',''];
    }
    return [$user, $output, $klas, $username];
}


function doScanDir($dir, $path)
{
    // Extracts files and directories that match a pattern
    $directory = normalizePath('identity', $dir);
    $items = scandir($directory);
    $items = array_filter($items, fn ($str) => pregMatch('/^\w/', $str));
    foreach ($items as $item) {
        $found = "/$directory$item/$path";
        if ($found && fileExists($found)) {
            break;
        };
    }
    return $found;
}
function preparePoster($str, $ext)
{
    $subpath = substr($str, 0, -3);
    $path = scanMultiDir([ASSETS, IMAGES], $subpath . $ext, ['thumb']);
    return fileExists($path) ? $path : '';
}

function prepareVideo($str, $pp = 'medley')
{
    $subpath = substr($str, 0, -4);
    $i = 0;
    $grp = [];
    $pp = $pp ? $pp : 'medley';
    $pp = preg_replace('|\/|', '', $pp);
    //'video/webm; codecs="av01.2.19H.12.0.000.09.16.09.1, flac"'
    while (isset(VIDEO_CODECS[$i])) {
        if ($pp) {
            $path = normalizePath('identity', VIDEOS . $pp . "/$subpath" . VIDEO_EXT[$i]);
        }
        if (isset($path) && fileExists($path)) {
            $grp[] = ['vsrc' => "/$path", 'vtype' => VIDEO_CODECS[$i]];
        }
        $i++;
    }
    return $grp;
}

function queryMulti(&$classy)
{
       $classy = '';
}
