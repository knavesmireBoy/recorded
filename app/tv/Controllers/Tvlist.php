<?php

namespace tv\Controllers;

use Ninja\DatabaseTable;
use tv\Entity\Record;

class Tvlist extends Controller
{
    protected $paginator = null;
    protected $searched = [];

    public function __construct(
        protected DatabaseTable $table,
        protected DatabaseTable $foreigntable,
        protected $limit = 8
    ) {
        $this->paginator = new Paginator($this->table, $limit);
    }

    public function unassign($id)
    {
        $this->foreigntable->delete("recording_id", $id);
    }

    protected function checkAssign($recid, $series)
    {
        if (!$recid) {
            return "insert";
        }
        $file = $this->fetch("foreigntable", "recording_id", $recid);
        if ($file) {
            if ($file->series_id == $series) {
                return "";
            }
            if ($series) {
                return "assign";
            } else {
                return $this->unassign($recid);
            }
        } else {
            return "insert";
        }
    }

    public function retour(string $key = "")
    {
        $para = "Sorry, the page you are looking for could not be found.";
        return [
            "template" => "lost.html.php",
            "variables" => [
                "para" =>
                    "Sorry, the page you are looking for could not be found.",
                "TITLE" => "PAGE NOT FOUND",
                "para" => $para,
                "KLAS" => "nowhere",
                "scripts" => [],
                "loggedIn" => null,
                "nav" => [],
            ],
        ];
    }

    protected function filterBy(?string $prop = "")
    {
        $files = $this->table->findAll();
        if ($prop) {
            return safeFilter($files, fn($o) => $o->{$prop});
        }
    }

    public function onpersist($e, $id = "", $fail = "")
    {
        if(is_object($e) && $e instanceof Record){
            return $e;
        }
        else {
            reLocate(TV_DISPLAY);
        }
    }

    public function onCheckImpostor(?int $id, array $data)
    {
        if (!$id) {
            $e = $this->fetch("table", "title", $data["title"]);
            if ($e && $e->series_id == $data["series_id"]) {
                reLocate(TV_EDIT . $e->id . "/impostor");
            }
        }
    }

    public function editSubmit()
    {
        $id = nullify($_POST["pk"]);
        $data = $_POST["data"];

        if(isset($_POST['cancel'])){
            return $this->display();
        }
        $time = strtotime($data['recdate']);
        $data['recdate'] = date("Y/m/d H:i:s", $time);
        
        $assign = isset($_POST["assign"]) ? $_POST["assign"] : null;
        $e = $this->fetch("table", "id", $id);
        $series = $data["series_id"] ?? null;
        $this->onCheckImpostor($id, $data);

        if ($assign && $series) {
            return $this->edit($id ?? 0, "conflict");
        }

        $data["id"] = $_POST["pk"];
        $data = array_map(nullify(...), $data);

        $persist = $this->persist();
        $e = $persist($data, is_null($id));

        if ($e && !$e->series_id && $assign) {
            $action = $this->checkAssign($id, $assign);

            if ($action) {
                $persist = $this->persist("recording_id", ["series_id"]);
                $persist(
                    ["recording_id" => $e->id, "series_id" => $assign],
                    $action === "insert",
                    "FOREIGNTABLE"
                );
            }
        }
        reLocate(TV_DISPLAYER . $_COOKIE['pp']);

    }

    public function add()
    {
        return $this->edit(0);
    }

    public function edit(?int $id = 0, $key = "")
    {
        $file = $this->fetch("table", "id", $id);
        $files = $this->filterBy("series_id");
        $options = array_column($files, "title", "series_id");
        $episode = $file?->getEpisode();

        return [
            "template" => "edit.html.php",
            "title" => "TV List",
            "variables" => [
                "action" => "/tvlist/edit/",
                "exit" => "/tvlist/display",
                "options" => $options,
                "key" => $key,
                "target" => $episode?->series_id ?? 0,
                "identity" => "series_id",
                "max" => count($options) + 1,
                "file" => $file,
                "_assign" => $file?->series_id ? "disabled" : "",
                "_series" => $file?->series_id || $episode ? "disabled" : "",
            ],
        ];
    }
    public function delete(?int $id)
    {
        $file = $this->fetch("table", "id", $id);
        return [
            "template" => "delete.html.php",
            "title" => "TV List",
            "variables" => [
                "action" => "/tvlist/confirm/",
                "perform" => "delete",
                "exit" => "/tvlist/display",
                "file" => $file,
            ],
        ];
    }
    public function toggle(int $id)
    {
        $set = doSetCookie(true);
        $unset = doSetCookie(false);
        $tog = isset($_COOKIE["tog"]) ? $_COOKIE["tog"] : 0;
        $tog = intval($tog);

        if ($tog && $tog === $id) {
            $unset("tog");
        } else {
            $set("tog", intval($id));
        }
        $pp = $_COOKIE["pp"] ?? 1;
        reLocate(TV_DISPLAYER . $pp);
    }

    public function search()
    {
        $term = $_GET["text"];
        $op = DBSYSTEM === "postgres" ? " ILIKE :value" : " LIKE :value";
        $searched = $this->table->find("title", "%$term%", null, 0, 0, 8, $op);
        $set = doSetCookie(true);
        if (!empty($searched)) {
            $this->searched = [$searched, []];
        }
        else {
            $set('search', false);
        }
        return $this->display();
    }

    public function find(){
        $set = doSetCookie(true);
        $unset = doSetCookie(false);
        $set('search', true);
        $unset('tog');
        reLocate(TV_DISPLAY);
    }

    private function findAll()
    {
        $files = $this->table->findAll('recdate_desc');
        $tog = isset($_COOKIE["tog"]) ? $_COOKIE["tog"] : 0;
        $episodes = $this->foreigntable->findAll();

        $episodeIds = array_map(fn($o) => $o->recording_id, $episodes);

        $tmp = [];
        foreach ($files as $file) {
            if (!in_array($file->id, $episodeIds)) {
                $tmp[] = $file;
            }
        }
        $episodes = safeFilter(
            array_udiff($files, $tmp, fn($a, $b) => $a->id - $b->id)
        );
        $episodes = array_map(fn($o) => $o->getEpisode(true), $episodes);
        if ($tog) {
            $episodes = safeFilter($episodes, fn($o) => $o->series_id == $tog);
        }
        return [$tmp, $episodes];
    }

    protected function onPage($n)
    {
        $getCol = curry2(array_column(...))('id');
        $doChunk = curry2(array_chunk(...))($this->limit);
        $getZero = curry2(getProp(...))(0);
        $getPages = composer($doChunk, $getCol, $getZero, $this->findAll(...));
        $pages = $getPages();
        $success = composer(is_numeric(...), partial(array_search(...), $n));
        $i = multiSearch($pages, $success);
        return $i + 1;
    }

    protected function linkToSeries()
    {
        [$searched] = $this->searched;
        $ids = array_map(fn($o) => $o->id, $searched);
        $eps = [];
        $titles = [];
        foreach ($ids as $id) {
            $res = $this->fetch("FOREIGNTABLE", "recording_id", $id);
            if ($res) {
                $eps[] = $res;
            }
        }
        $eps = safeFilter($eps, "identity");
        if (!empty($eps)) {
            foreach ($eps as $ep) {
                $res = $this->fetch("TABLE", "series_id", $ep['series_id']);
                if ($res) {
                    $pp = $this->onPage($ep['recording_id']);
                    $titles[$ep['recording_id']] = $res["title"] . " on page $pp";
                   
                }
            }
        }
        else {
            $n = $searched[0]->id;
            foreach ($searched as $record) {
                $titles[$record->id] = $this->onPage($record->id);
            }
        }
        return $titles;
    }

    public function exit(){
        $unset = doSetCookie(false);
        $unset('search');
        return $this->display();
    }

    public function display($pp = 1)
    {
        $tog = isset($_COOKIE["tog"]) ? $_COOKIE["tog"] : 0;
        $set = doSetCookie(true);
        $unset = doSetCookie(false);
        $set('pp', $pp);
        $titles = [];
        [$files, $episodes] = empty($this->searched)
            ? $this->findAll()
            : $this->searched;

        [$key, $prev, $next, $offset] = $this->paginator->prepPaginationBar(
            $files,
            intval($pp)
        );

        $files = array_slice($files, $offset, $this->limit);
        $files = safeFilter($files,fn($o) => $o->title);

        if (!empty($this->searched)) {
            $titles = $this->linkToSeries();
        }

        $ret = [
            "template" => "tvlist.html.php",
            "title" => "TV List",
            "variables" => [
                "files" => $files,
                "options" => $episodes,
                "prev" => $prev,
                "next" => $next,
                "offset" => $offset,
                "increment" => $this->limit,
                "paginate" => $this->paginator->getPagination(),
                "tog" => $tog ? intval($tog) : 0,
                "heading" => empty($files)
                    ? "Add a page by clicking the + button"
                    : "",
                "action" => "/pages/delete/",
                "perform" => "delete",
                "titles" => $titles,
                "searched" => !empty($this->searched),
                "search" => $_COOKIE['search'] ?? null,
                "klas" => empty($this->searched) ? "dual" : "dual found",
                "exit" => BADMINTON,
            ],
        ];
        if(isset($_COOKIE['search']) && !$_COOKIE['search']){
            $unset('search');
        }
        return $ret;
    }
    public function series(?int $id = 0, $key = "")
    {
        $files = $this->foreigntable->find("id", $id);
        return [
            "template" => "series.html.php",
            "title" => "TV List",
            "variables" => [
                "files" => $files,
                "heading" => empty($files)
                    ? "Add a page by clicking the + button"
                    : "",
                "action" => "/pages/delete/",
                "perform" => "delete",
                "exit" => BADMINTON,
            ],
        ];
    }
    public function confirmSubmit()
    {
        $this->table->delete("id", $_POST["pk"]);
    }

}
