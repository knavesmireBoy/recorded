<?php

namespace tv\Entity;



 class Episodes extends Entity {

    public $recording_id;
    public $series_id;

    public function __construct(protected \Ninja\DatabaseTable $table, protected \Ninja\DatabaseTable $foreigntable)
    {
    }
    
    public function onpersist(?Entity $e, $id = '', $key = ''){

    }
 }