<?php

namespace tv\Controllers;

use Ninja\DatabaseTable;
use tv\Entity\Entity;
use tv\tvWebsite;

abstract class Controller {

    protected $attributes;

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
    /*
    public function fetch(string $t, string $prop, mixed $val, ...$rest)
    {
        $ret = [];
        $mod = false;
        if ($val) { //safeguard against missing values
            if (is_numeric(strpos($t, '_'))) {
                $t = substr($t, 1);
                $mod = true;
            }
            if (strtoupper($t) === $t) { //PDO::FETCH_ASSOC
                $t = strtolower($t);
                $ret = $this->{$t}->find($prop, $val, null, 0, 0, \PDO::FETCH_ASSOC);
            } else {
                $ret = $this->{$t}->find($prop, $val, ...$rest);
            }
        }
        return empty($ret) ? null : ($mod ? $ret : $ret[0]);
    }
*/

    protected function getNameOfClass()
    {
        return static::class;
    }

    public function getClassName(string $prefix = '')
    {
        return $prefix . strtolower(substr(strrchr($this->getNameOfClass(), '\\'), 1));
    }

    public function message($str = '', $i = 0)
    {
        $str = exclaim($str);
        if ($str) {
            return [
                'template' => 'accessdenied.html.php',
                'variables' => [
                    'str' => $str,
                    'accesslevel' => $this->getAccess($i),
                    'submitted' => false
                ]
            ];
        } else {
            reLocate(REG);
        }
    }

    abstract public function onpersist(?Entity $e, $id = '', $fail = '');

    protected function persist(array $record, string $route = 'edit', bool $insert = false, string $tablename = 'table')
    { 
        $unset = doSetCookie(false);
        $set = doSetCookie(true);
        $record = $this->onCheckAllowed($record, $insert, $tablename);
        try {
            $unset('error');
            $e = $this->table->save($record);
        } catch (\Exception $e) {
            dump($e);
            $msg = "Controller Persist: Error saving record: " . $e->getMessage();
            $set('error', $msg);
            $route = $this->getClassName();
            reLocate("/$route/onpersist/_/$id/$msg");
            //reLocate(PAGES_EDIT . $id);
        }
        return $this->onpersist($e);
    }

    public function destroy()
    {
        return $this->delete();
    }

    public function setAllowed(array $attributes)
    {
        $this->attributes = $attributes;
    }

    protected function onCheckAllowed(array $payload, bool $insert = false, string $tablename = 'table')
        {
            $fail = '';
            $warnings = [];
            ///!!WE ASSUME that an the unique identifier is named id
            //IDEALLY this function should be returned from another that supplies the name of the identifier in a closure
            $id =  $payload['id'] ?? 0;
            $route = $this->getClassName();

            if(!$insert && !isset($payload['id'])){
                reLocate("/$route/onpersist/_/0/onupdate");
            }
            if($insert && isset($payload['id'])){
                reLocate("/$route/onpersist/_/$id/oninsert");
            }

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
                $route = $this->getClassName();
                $fail = "persist_$fail";
                $id =  $payload['id'] ?? 0;
                reLocate("/$route/onpersist/_/$id/$fail");
            }
            if($warnings !== []){
                $set = doSetCookie(true);
                $set('persist', current($warnings));
            }
            return $payload;
        }
        protected function groundZero(array $post)
        {
            if (empty($post)) {
                reLocate(PAGES_DISPLAY);
            }
        }
}