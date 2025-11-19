<?php

namespace Classroom\Controller;

use Dotenv\Dotenv;

class Controller
{
    protected $actions = [];
    protected $entityManager;
    protected $user;
    protected function __construct($entityManager, $user)
    {
        $dir  = is_file('/run/secrets/app_env') ? '/run/secrets' : __DIR__ . '/../';
        $file = is_file('/run/secrets/app_env') ? 'app_env'      : '.env';
        Dotenv::createImmutable($dir, $file)->safeLoad();
        $this->envVariables = $_ENV;
        $this->entityManager = $entityManager;
        $this->user = $user;
    }
    public function action($action, $data = [])
    {
        return call_user_func($this->actions[$action], $data);
    }
}
