<?php

namespace tv\Entity;

class User extends Entity
{
  //browser is simply a registered user who has access to privileged PUBLIC content
  // so eg a user who is a content editor with access to privileged content needs a value of 3 in permissions column

    public const BROWSER = 1; // 00000001
    public const CONTENT_EDITOR = 2; // 00000010
    public const PHOTO_EDITOR = 4; // 00000100
    public const CHIEF_EDITOR = 8; // 00001000
    public const ACCOUNT_EDITOR = 16; // 00010000; edit user permissions
    public const ADMIN = 32; // 00100000; ; edit user permissions AND delete user (must ALSO be account_editor) ie 48
    public const SUPERADMIN = 64; // 01000000 (use permissions : 80)
//to do everything 127
    public $permissions;
    public $password;
    public $id;
    public $name;
    public $email;

    public function __construct(protected \Ninja\DatabaseTable $table)
    {
    }

    public function onpersist(?Entity $e, $id = '', $key = ''){}

    public function hasPermission(int $permission)
    {
        return $this->permissions & $permission;
    }

    public function checkPermission(int $permission)
    {
        return $this->hasPermission($permission) && $this->permissions >= $permission;
    }

    public function canEdit()
    {
        return $this->permissions >= 2;
    }


    public function getPermission()
    {
        return $this->permissions;
    }
}
