<?php

namespace tv\Controllers;

use Ninja\DatabaseTable;
use tv\Controllers;
use tv\Entity\Entity;

//include_once 'config.php';
//include_once FUNCTIONS;

class Tvlist extends Controller
{
    public function __construct(protected DatabaseTable $table, protected DatabaseTable $foreigntable, $limit = 8){

    }
    public function onpersist(?Entity $e, $id = '', $fail = ''){

    }

    public function display(){
         $files = $this->table->findAll();
         $tog = isset($_COOKIE['tog']) ? $_COOKIE['tog'] : 0;
        $group = $this->foreigntable->find('series_id', $tog);
        
       
        $episodes = $this->foreigntable->findAll();
        $eps = array_map(fn($o) => $o->recording_id, $episodes);
        $tmp = [];
      
        foreach ($files as $file) {
            if(!in_array($file->id, $eps)){
                $tmp[] = $file;
            }
        }
        $eps = [];
        if(!empty($group)){
            $groupIds = array_map(fn($o) => $o->recording_id, $group);
           
            foreach ($files as $file) {
                if(in_array($file->id, $groupIds)){
                    $eps[] = $file;
                }
            }
        }
        $files = $tmp;
         return [
             'template' => 'tvlist.html.php',
             'title' => 'TV List',
             'variables' => [
                 'files' => $files,
                 'group' => $eps,
                 'tog' => $tog ? intval($tog) : 0,
                 'heading' => empty($files) ? 'Add a page by clicking the + button' : '',
                 'action' => '/pages/delete/',
                 'perform' => 'delete',
                 'exit' => BADMINTON
                 ]
             ];
    }
    public function series(?int $id = 0, $key = ''){

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


   public function confirm(){
   
   }

   public function confirmSubmit(){
    $this->table->delete('id', $_POST['pk']);
   }


   public function delete(?int $id){

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

   public function toggle(int $id){

    $set = doSetCookie(true);
    $unset = doSetCookie(false);

    $tog = isset($_COOKIE['tog']) ? $_COOKIE['tog'] : 0;
    $tog = intval($tog);

    if($tog && $tog === $id){
        $unset('tog');
    }
    else {
        $set('tog', intval($id));
    }
    reLocate('/tvlist/display');
}
}