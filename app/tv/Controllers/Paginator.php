<?php

namespace tv\Controllers;

use Ninja\DatabaseTable;
use tv\Controllers;
use tv\Entity\Entity;

//include_once 'config.php';
//include_once FUNCTIONS;

class Paginator extends Controller
{
    protected $count = 1;
    protected $paginate = [];

    public function __construct(protected DatabaseTable $table, protected int $inc = 8){
    }

    protected function getPrev(string $pp)
    {
        $prev = $pp - 1;
        return $prev > 0 ? $prev : null;
    }

    protected function getNext(string $pp)
    {
        $next = $pp + 1;
        return $next <= intval($this->validateFolio()) ? $next : null;
    }

   protected function doPaginate()
    {
        $p = [];
        $tmp = [];
        $inc = $this->inc;
        for ($i = 1, $s = 0; $s < $this->count; $s += $inc) {
            $tmp[] = $i;
            $tmp[] = $s;
            $p[] = $tmp;
            $tmp = [];
            $i++;
        }
        return $p;
    }

  protected function maxArticleByPage(string $groupBy, string $col, int $mode)
    {
        $res = $this->table->count($groupBy, $col, $mode);
        $res = array_map(fn($a) => $a[0], $res);
        return max($res);
    }

    protected function setCount(mixed $files)
    {
        $this->count = \is_array($files) ? \count($files) : 0;
    }

    protected function setInc(int $inc = 10)
    {
        $this->inc = $inc;
    }

    protected function validateFolio()
    {
        return ceil($this->count / $this->inc);
    }

    protected function findFolio(mixed $arg)
    {
        $files = $this->table->filterNull('page', false, 'title');
        $titles = array_map(fn($o) => $o->title, $files);
        if (is_numeric($arg)) {
            $entry = $this->fetch('table', 'id', $arg);
        } elseif ($arg) {
            $entry = $this->fetch('table', 'title', $arg);
        }
        if (isset($entry)) {
            $index = array_search($entry->title, $titles);
            if ($index >= 0) {
                for ($k = 0; $k <= $index; $k += $this->inc) {
                }
                return $k /= $this->inc;
            }
        }
        return 0;
    }

   public function prepPaginationBar(array $files, mixed $folio = 1)
    {
        $prev = null;
        $next = null;
        $key = 0;
        $offset = 0;
       // dump(count($files));
        if (is_integer($folio) && $folio >= 0) {
            $this->setCount($files);
            $this->paginate = $this->doPaginate();
            $prev = $this->getPrev($folio) ?? null;
            $next = $this->getNext($folio) ?? null;
            $key = intval($folio - 1);
            $offset = $this->paginate[$key][1] ?? 0;
        }

        if (is_numeric($key) && !empty($this->paginate)) {
           // [$_, $o] = $this->paginate[$key] ?? 0;
        }

        return [$key, $prev, $next, $offset];
    }

    public function getPagination(){
        return $this->paginate;
    }

    public function onpersist($e, $id = "", $fail = "")
    {
        return $e;
    }

}