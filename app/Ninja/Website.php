<?php

namespace Ninja;

interface Website
{
    public function getDefaultRoute(): string;
    public function getController(string $controllerName, array $args, array $optional_args): ?object;
    public function checkLogin(string $uri): array;
    public function getLayoutVariables(string $key): array;
    public function getScripts(string $key = ''): array;
    public function getControllerArgs(string $key): array;
    public function setNavBar();
    public function create(string $name): void;
    public function setHome(string $name): string;
    public function getCurrentPage(): mixed;
}
