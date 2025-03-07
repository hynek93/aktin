<?php

namespace App\Presenters;

use App\Model\Entity\User;
use Nette\Http\IResponse;

class UsersPresenter extends BasePresenter
{
    public function startup(): void
    {
        parent::startup();
        $this->user = $this->verifyToken();

        if (!$this->user) {
            $this->hasError('Invalid authorization token', IResponse::S401_Unauthorized);
        }

        if ($this->user->getRole() !== User::ROLE_ADMIN) {
            $this->hasError('Access denied', IResponse::S403_Forbidden);
        }
    }
}