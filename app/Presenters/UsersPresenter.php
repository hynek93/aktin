<?php

namespace App\Presenters;

use App\Model\Entity\User;
use Nette\Http\IResponse;
use Nette\Security\Passwords;

class UsersPresenter extends BasePresenter
{
    public function startup(): void
    {
        parent::startup();
        $this->apiUser = $this->verifyToken();

        if (!$this->apiUser) {
            $this->hasError('Invalid authorization token', IResponse::S401_Unauthorized);
        }

        if ($this->apiUser->getRole() !== User::ROLE_ADMIN) {
            $this->hasError('Access denied', IResponse::S403_Forbidden);
        }
    }

    public function read(): void
    {
        $id = $this->getParameter('id');
        $payload = [];

        if ($id) {
            $user = $this->getUserById();
            $payload[] = $user->getData();
        } else {
            $users = $this->entityManager->getRepository(User::class)->findAll();
            foreach ($users as $user) {
                $payload[] = $user->getData();
            }
        }

        $this->send(['users' => $payload]);
    }

    public function create(): void
    {
        $data = $this->getPostData();
        if (empty($data->email) || empty($data->password) || empty($data->role)) {
            $this->hasError('Missing required fields');
        }

        if ($this->entityManager->getRepository(User::class)->findBy(['email' => $data->email])) {
            $this->hasError('E-mail already exists');
        }

        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $this->hasError('Enter valid e-mail address');
        }

        if (!in_array($data->role, User::getRolesSelection())) {
            $this->hasError('Not allowed role');
        }

        $user = new User();
        $user->setEmail($data->email);
        $user->setPasswordHash($data->password);
        $user->setRole($data->role);
        $user->setName($data->name ?? null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->send([
            'user' => $user->getData()
        ], 'User has been successfully created');
    }

    public function update(): void
    {
        $user = $this->getUserById();
        $data = $this->getPostData();

        if (!empty($data->email)) {
            if ($this->entityManager->getRepository(User::class)->findBy(['email' => $data->email])) {
                $this->hasError('E-mail already exists');
            }

            if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
                $this->hasError('Enter valid e-mail address');
            }

            $user->setEmail($data->email);
        }

        if (!empty($data->role)) {
            if (!in_array($data->role, User::getRolesSelection())) {
                $this->hasError('Not allowed role');
            }

            $user->setRole($data->role);
        }

        if (!empty($data->name)) {
            $user->setName($data->name);
        }

        if (!empty($data->password)) {
            $passwords = new Passwords();
            $user->setPasswordHash($passwords->hash($data->password));
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->send([
            'user' => $user->getData()
        ], 'User has been successfully updated');
    }

    public function delete(): void
    {
        $user = $this->getUserById();

        $this->entityManager->remove($user);
        $this->entityManager->flush();
        $this->send([], 'User has been successfully deleted');
    }

    protected function getUserById(): User
    {
        $id = $this->getParameter('id');

        if (!$id) {
            $this->hasError('User ID is empty');
        }

        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            $this->hasError('User not found', IResponse::S404_NotFound);
        }

        return $user;
    }
}