<?php

namespace App\Presenters;

use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\UI\Presenter;

class BasePresenter extends Presenter
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }
}