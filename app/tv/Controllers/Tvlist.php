<?php

namespace tv\Controllers;

use Ninja\DatabaseTable;
use tv\Controllers;
use tv\Entity\Entity;

//include_once 'config.php';
//include_once FUNCTIONS;

class Tvlist extends Controller
{
    public function __construct(protected DatabaseTable $table, protected DatabaseTable $foreigntable, $limit = 8)
    {
    }
    public function onpersist($e, $id = '', $fail = '')
    {
        return $e;
    }

    public function editSubmit()
    {
        $id = nullify($_POST['pk']);
        $data = $_POST['data'];
        $assign = $_POST['assign'];
        $e = null;
    
        if ($assign && ! empty($data['series_id'])) {
            return $this->edit($id ?? 0, 'conflict');
        }
    
        $data['id'] = $_POST['pk'];
        $data = array_map(nullify(...), $data);
        $persist = $this->persist();
        $e = $persist($data, is_null($id));
        if ($e) {
            $action = $this->checkAssign($id, $assign);
            if ($action) {
                $persist = $this->persist('recording_id', ['series_id']);
                $persist(['recording_id' => $e->id, 'series_id' => $assign], $action === 'insert', 'foreigntable');
            }
        }
    }
    protected function filterBy(?string $prop = '')
    {
        $files = $this->table->findAll();
        if ($prop){
            return safeFilter($files, fn($o) => $o->{$prop});
        }
    }
    public function add()
{
    return $this->edit();
}

public function unassign($id){
    $this->foreigntable->delete('recording_id', $id);
}

public function edit(?int $id = 0, $key = '')
{
    $file = $this->fetch('table', 'id', $id);
    $files = $this->filterBy('series_id');
    $options = array_column($files, 'title', 'series_id');
    $episode = $file?->getEpisode();
    return [
     'template' => 'edit.html.php',
     'title' => 'TV List',
     'variables' => [
         'action' => '/tvlist/edit/',
         'exit' => '/tvlist/display',
         'options' => $options,
         'key' => $key,
         'target' => $episode?->series_id ?? 0,
         'identity' => 'series_id',
         'file' => $file
         ]
     ];
}

public function delete(?int $id)
{
    $file = $this->fetch('table', 'id', $id);
    return [
     'template' => 'delete.html.php',
     'title' => 'TV List',
     'variables' => [
         'action' => '/tvlist/confirm/',
         'perform' => 'delete',
         'exit' => '/tvlist/display',
         'file' => $file
         ]
     ];
}

public function toggle(int $id)
{
    $set = doSetCookie(true);
    $unset = doSetCookie(false);
    $tog = isset($_COOKIE['tog']) ? $_COOKIE['tog'] : 0;
    $tog = intval($tog);

    if ($tog && $tog === $id) {
        $unset('tog');
    } else {
        $set('tog', intval($id));
    }
    reLocate('/tvlist/display');
}

    public function display()
    {
         $files = $this->table->findAll();
         $tog = isset($_COOKIE['tog']) ? $_COOKIE['tog'] : 0;
         $episodes = $this->foreigntable->findAll();
         $episodeIds = array_map(fn($o) => $o->recording_id, $episodes);
         $tmp = [];


        foreach ($files as $file) {
            if (!in_array($file->id, $episodeIds)) {
                $tmp[] = $file;
            }
        }
        $episodes = safeFilter(array_udiff($files, $tmp, fn($a, $b) => $a->id - $b->id));
        $episodes = array_map(fn($o) => $o->getEpisode(true), $episodes);

        if ($tog) {
            $episodes = safeFilter($episodes, fn($o) => $o->series_id == $tog);
        }
        $files = $tmp;
         return [
             'template' => 'tvlist.html.php',
             'title' => 'TV List',
             'variables' => [
                 'files' => $files,
                 'group' => $episodes,
                 'tog' => $tog ? intval($tog) : 0,
                 'heading' => empty($files) ? 'Add a page by clicking the + button' : '',
                 'action' => '/pages/delete/',
                 'perform' => 'delete',
                 'exit' => BADMINTON
                 ]
             ];
    }
    public function series(?int $id = 0, $key = '')
    {
        $files = $this->foreigntable->find('id', $id);
        return [
            'template' => 'series.html.php',
            'title' => 'TV List',
            'variables' => [
                'files' => $files,
                'heading' => empty($files) ? 'Add a page by clicking the + button' : '',
                'action' => '/pages/delete/',
                'perform' => 'delete',
                'exit' => BADMINTON
                ]
            ];
    }

    public function confirm()
    {
    }

    public function confirmSubmit()
    {
        $this->table->delete('id', $_POST['pk']);
    }

    protected function checkAssign($recid, $series)
    {
        if (!$recid) {
            return 'insert';
        }
        $file = $this->fetch('foreigntable', 'recording_id', $recid);
        if($file){
            if($file->series_id == $series){
                return '';
            }
            if($series){
                return 'assign';
            }
            else {
                return $this->unassign($recid);
            }
        }
        else {
            return 'insert';
        }
    }
}
