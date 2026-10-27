<?php

namespace tv\Entity;



 class Series extends Entity {

    public $id;
    public $title;
    public $recdate;

    public function __construct(protected \Ninja\DatabaseTable $table, protected \Ninja\DatabaseTable $foreigntable)
    {
    }
    
    public function onpersist(?Entity $e, $id = '', $key = ''){

    }
 }