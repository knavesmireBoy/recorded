<?php

namespace Ninja;

//include_once 'config.php';
//include_once FUNCTIONS;

class Authentication
{
    private $users;
    private $usernameColumn;
    private $passwordColumn;

    private function find($k, $v)
    {
        $user = $this->users->find($k, $v);

        if (!empty($user)) {
            return $user;
        } else {
            return null;
        }
    }

    public function __construct(DatabaseTable $users, string $usernameColumn, string $passwordColumn)
    {
        startSession();
        $this->users = $users;
        $this->usernameColumn = $usernameColumn;
        $this->passwordColumn = $passwordColumn;
    }

    public function login(string $username, string $password): bool
    {
        $user = $this->find($this->usernameColumn, $username);

        if ($user) {
            $user = $user[0];
            /*
            if($password  === 'thunder'){
                $password = password_hash($password, PASSWORD_DEFAULT);
                $user->{$this->passwordColumn} = $password;
                dump(password_verify($password, $user->{$this->passwordColumn}));
            }
                */

            if (!empty($user) && password_verify($password, $user->{$this->passwordColumn})) {
                session_regenerate_id();
                $_SESSION['username'] = $username;
                $_SESSION['password'] = $user->{$this->passwordColumn};
                return true;
            }
        }
        return false;
    }

    public function isLoggedIn(): ?object
    {
        if (empty($_SESSION['username'])) {
            return null;
        }
        $user = $this->find($this->usernameColumn, $_SESSION['username']);

        $user = $user[0] ?? null;

        if (!empty($user) && $user->{$this->passwordColumn} === $_SESSION['password']) {
            return $user;
        }
        return null;
    }
    public function logout()
    {
        unset($_SESSION['username']);
        unset($_SESSION['password']);
        unset($_SESSION['filestore']);
        session_regenerate_id();
    }
}

//https://itnext.io/how-to-implement-password-recovery-securely-in-php-db2275ab3560
//$token = bin2hex(random_bytes(16));
