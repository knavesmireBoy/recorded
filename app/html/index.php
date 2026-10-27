<?php

ini_set("display_errors", true);
//ini_set( "display_errors", false);
ini_set('memory_limit', '1024M'); // or you could use 1G
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '125M');
ini_set('pcre.jit', false);
date_default_timezone_set("Europe/London");

require_once 'config.php';
require_once FUNCTIONS;

spl_autoload_register('autoloader');
session_start();
require_once CONNECT;
$layout = 'pagelayout.html.php';

$route = fixUri();
$uri = empty($route) || empty($route[0]) ? ['tvlist', 'display'] : $route;

$posts = ['edit', 'confirm'];
$ispublic = inArray($uri[0], WEBPAGES) ? $uri[0] : '';
$website = new \tv\tvWebsite($ispublic);

$entryPoint = new \Ninja\EntryPoint($website, $ispublic, $posts);
$layoutVariables = $entryPoint->run(implode('/',$uri), $_SERVER['REQUEST_METHOD'], 'public');

echo $entryPoint->loadTemplate($layout, $layoutVariables); 
