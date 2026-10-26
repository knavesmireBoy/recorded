<?php

namespace tv\Entity;

class Page extends Entity
{
    public $id;
    public $name;
    public $title;
    public $content;
    public $description;
    public $image;
    public $url;
    protected $callback = [];

    //checkUnique checks SLOT table (pp) ie $this protected $table NOT pages table where name would be required
    protected $unique = ['title'];

    //!!$table is 'slot' mostly pp not pages 
    public function __construct(protected \Ninja\DatabaseTable $table)
    {
    }

    public function onpersist(?Entity $e, $id = '', $key = ''){

    }
    public function complete(){
        return $this;
    }
        
    public function findAll(...$args)
    {
        return $this->table->findAll(...$args);
    }

    public function find(?int $id)
    {
        $ret = $this->table->find('id', $id, null, 0, 0, \PDO::FETCH_ASSOC);
        return $ret ? $ret[0] : $ret;
    }

    protected function fetch(string $table, string $prop, mixed $val, ...$rest)
    {
        $ret = [];
        if ($val) { //safeguard against missing values
            $ret = $this->{$table}->find($prop, $val, ...$rest);
        }
        return empty($ret) ? null : $ret[0];
    }

    public function checkUnique(array $data, $col = 'name')
    {
        $ret = null;
        $op = DBSYSTEM === 'postgres' ? ' ILIKE :value' : ' LIKE :value';
        $payload = [];
        foreach ($data as $k => $v) {
            if($k === $col){
                $payload[$k] = $v;
            }
        }
        foreach ($payload as $k => $v) {
            if (in_array($k, $this->unique)) {
                $ret =  $this->fetch('table', $k, "$v", null, 0, 0, 8, $op);
                if ($ret) {
                    break;
                }
            }
        }
        return $ret;
    }

    public function setCallback(callable $cb, \PDO $pdo){
        $this->callback = [$cb, $pdo];
    }

    public function getCallback(){
        return $this->callback;
    }

    public function setName(string $name)
    {
        return $this->table->setName($name);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSlotEntity()
    {
        return $this->table->getEntity();
    }

    public function thenRepop(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->repop(...$args);
    }

    public function thenSwap(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->swap(...$args);
    }

    public function thenShuffle(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->shuffle(...$args);
    }

    public function thenSetCallback(...$args){
        $slot = $this->getSlotEntity();
        $slot->setCallback(...$args);
        return $slot;
    }

    public function doSetCallback(...$args){
        $this->setName('pages');
        $this->setCallback(...$args);
    }

}
