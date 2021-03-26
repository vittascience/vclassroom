<?php

namespace Classroom\Controller;

class Controller
{
    protected $actions = [];
    protected $entityManager;
    protected $user;
    protected function __construct($entityManager, $user)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
    }
    public function action($action, $data = [])
    {
        return call_user_func($this->actions[$action], $data);
    }
}
