<?php

namespace tv\Entity;

abstract class Entity
{
    abstract public function onpersist(?Entity $e, $id = '', $key = ''); 

    protected function fetch(string $table, string $prop, mixed $val, mixed ...$rest)
    {
        $ret = [];
        if ($val) { //safeguard against missing values
            if (strtoupper($table) === $table) {
                $table = strtolower($table);
                $ret = $this->{$table}->find($prop, $val, null, 0, 0, \PDO::FETCH_ASSOC);
            } else {
                $ret = $this->{$table}->find($prop, $val, ...$rest);
            }
        }
        return empty($ret) ? null : $ret[0];
    }

    public function select(...$rest){
        return function(...$args) use ($rest){
            return $this->fetch(...$args, ...$rest);
        };
    }

    protected function getNameOfClass()
    {
        return static::class;
    }

    public function getClassName(string $prefix = '')
    {
        return $prefix . strtolower(substr(strrchr($this->getNameOfClass(), '\\'), 1));
    }

    public function persist(array $record, string $route = '', bool $insert = false, string $tablename = 'table')
    {
        $record = $this->onCheckAllowed($record, $route, $insert);
        $unset = doSetCookie(false);
        $set = doSetCookie(true);
        try {
            $unset('error');
            //$e = $this->table->save($record);
            $e = $this->{$tablename}->save($record);
        } catch (\Exception $e) {
            dump($e);
            $msg = "Error saving record: " . $e->getMessage();
            $set('error', $msg);
            $id =  $record['id'] ?? '';
            $route = $this->getClassName();
            reLocate("/$route/onpersist/_/$id/$msg");
        }
        return $this->onpersist($e);
    }

    protected function onCheckAllowed(array $payload, string $route = '', bool $insert = false, string $tablename = 'table')
        {
            $fail = '';
            $warnings = [];

            $id =  $payload['id'] ?? 0;
            $klas = $this->getClassName();

            if(!$insert && !isset($payload['id'])){
                reLocate("/$klas/onpersist/_/0/onupdate");
            }
            if($insert && isset($payload['id'])){
                reLocate("/$klas/onpersist/_/$id/oninsert");
            }

            //only column required by slot
            if(!isset($this->attributes[$tablename])){
                $attrs = ['title'];
            }
            $attrs = $this->attributes[$tablename] ?? [];
            if($attrs === []){
                return $payload;
            }
            foreach ($payload as $k => $v) {
                if (!in_array($k, $attrs)) {
                    $warn[] = $k;
                    unset($payload[$k]);
                }
            }
            if ($insert) {
                //id optional on insert
                $attrs = safeFilter($attrs, fn($a) => $a !== 'id');
                $payout = array_keys($payload);
                foreach ($attrs as $a) {
                    if (!in_array($a, $payout)) {
                        $fail = $a;
                        break;
                    }
                }
            }
            if($fail){
                $fail = "persist_$fail";
                $id =  $payload['id'] ?? 0;
               // dump('allowed');
                reLocate("/$klas/onpersist/_/$id/$fail");
            }
            if($warnings !== []){
                $set = doSetCookie(true);
                $set('persist', current($warnings));
            }
            return $payload;
        }
}