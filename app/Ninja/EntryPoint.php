<?php

namespace Ninja;
use tv\tvWebsite;

class EntryPoint
{
    private $scripts;

    public function loadTemplate($templateFileName, $variables)
    {
        extract($variables);
        ob_start();
        include  TEMPLATE . $templateFileName;
        return ob_get_clean();
    }

    private function checkUri($uri)
    {
        if ($uri !== strtolower($uri)) {
            http_response_code(301);
            reLocate(REG);
        }
    }

    private function setLayoutVars($vars, $k, $v)
    {
        $vars[$k] = $v;
        return $var;
    }
    //posts = ['edit', 'delete'] each member would have the word Submit appended to distinguish get and post requests
    public function __construct(private Website $website, private $ispublic, private array $posts)
    {
    }

    protected function isCallable($controller, ?string $action, string $name, string $publicpage = '')
    {
        if(!$controller){
            return false;
        }
        $pass = false;
        if ($publicpage) {
            $pass = $controller->getClassName() === 'pages';
            if($pass){
                return ['pages', 'display'];
            }
        } else {
            $pass = $controller->getClassName() === $name;
            if($pass){
                return [$name, $action];
            }
            if($name === 'logger'){
                return ['login', $action];
            }
        }
        if(!$pass){
            reLocate('/tvlist/display');
        }
            
        return is_callable([$controller, $action]);
    }

    public function run($uri, $method, $defaultKlas = '', $home = '/tvlist/display')
    {
        try {
            $this->checkUri($uri);
            if ($uri == '') {
                $this->website->setHome($home);
                $uri = $this->website->getDefaultRoute();
            }
            $start = microtime(true);
            $redirect = null;
            $page = null;
            $vars = [];
            $output = null;
            $nav = [];
            $found = false;
            $txt = '';
            $userid = 0;
            $route = explode('/', $uri);
            $name = array_shift($route);
            $action = array_shift($route);
            $layoutVariables['scripts'] = [];
            $layoutVariables['TITLE'] = 'PAGE NOT FOUND';
            $layoutVariables['pageid'] = 'admin';
            $super = isset($_SESSION['filestore']);

            $user = $this->website->checkLogin("$name/$action"); //: array
            $vars = $this->website->getLayoutVariables("$name/$action");

            if ($method === 'POST' && \in_array($action, $this->posts)) {
                $action .= 'Submit';
            }
            $args = $this->website->getControllerArgs($name, $super);
            $controller = $this->website->getController($name, $args, $user, $super);
            [$name, $action] = $this->isCallable($controller, $action, $name, $this->ispublic);
           
            if ($name && $action) {
               
                $page = $controller->$action(...$route);
          
                if ($page && \is_array($page)) {
                    //dump( microtime(true) - $start); //.17
                    $pagevars = $page['variables'] ?? [];
                    //watch $pagevars don't overwrite $vars
                    $layoutVariables = [...$vars, ...$pagevars];
                   // $layoutVariables['scripts'] = $this->website->getScripts("$name/$action");
                    $layoutVariables['TITLE'] = strtolower($layoutVariables['TITLE'] ?? '');
                    $layoutVariables['pageid'] = in_array($layoutVariables['TITLE'], WEBPAGES) ? $layoutVariables['TITLE'] : '';
                } else {
                    retour();
                }
                //if method is post the rest of the page will only be read on relocation
            } else {
                http_response_code(404);
                if ($redirect) {
                    $p1 = '<p>The website has as yet no public pages</p>';
                    $p2 = '<p>The website has as yet no articles assigned to its public pages</p>';
                    $para = $redirect === 'pagebuild' ? $p1 : $p2;
                    //$route may have a key of active to indicate we have a table for at least one page but there are no articles assigned
                    $op = $redirect === 'page' ? 'Add' : 'Edit';
                    $td1 = '<p><a href="/user/admin">Add content</a>';
                    $td2 = "<p><a href='/pages/list/'>$op a page</a>";
                    $todo = isset($_SESSION['nav']) ?  $td1 : $td2;
                    $output = "$para$todo";
                } else {
                    reLocate('/pages/retour/');
                }
            }
        } catch (\PDOException $e) {
            $output = 'Database error: ' . $e->getMessage() . ' in ' .
            $e->getFile() . ':' . $e->getLine();
        }

       
        $output = $this->loadTemplate($page['template'], $layoutVariables);
        $layoutVariables['output'] = $output ?? '';
        return $layoutVariables;
    }
}
