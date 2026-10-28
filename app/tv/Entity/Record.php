<?php

namespace tv\Entity;



 class Record extends Entity {

    public $id;
    public $title;
    public $recdate;
    public $series_id;

    public function __construct(protected \Ninja\DatabaseTable $table, protected \Ninja\DatabaseTable $foreigntable)
    {
    }
    
    public function onpersist(?Entity $e, $id = '', $key = ''){

    }

    public function delete($id){
      return $this->table->delete('id', $id);
    }

    public function getEpisode($flag = false){

      $episode = $this->fetch('foreigntable','recording_id', $this->id);
      if($episode && $flag){
        $this->series_id = $episode->series_id;
        return $this;
      }
      return $episode;
     
    }
 }