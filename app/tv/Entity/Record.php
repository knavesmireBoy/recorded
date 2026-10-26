<?php

namespace tv\Entity;



 class Record extends Entity {

    public $id;
    public $title;
    public $page;
    public $recdate;

    public function __construct(protected \Ninja\DatabaseTable $table)
    {
    }
    
    public function onpersist(?Entity $e, $id = '', $key = ''){

    }
 }