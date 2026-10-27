<?php

namespace tv\Controllers;

use Ninja\DatabaseTable;
use tv\Controllers;
use tv\Entity\Entity;

//include_once 'config.php';
//include_once FUNCTIONS;

class Series extends Controller
{
    public function __construct(private DatabaseTable $table){

    }
    public function onpersist(?Entity $e, $id = '', $fail = ''){

    }

    public function display(int $id = 0){

         $files = $this->fetch('TABLE','id', $id);
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
}