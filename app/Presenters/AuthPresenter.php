<?php

namespace App\Presenters;

use App\Model\Entity\User;
use Firebase\JWT\JWT;
use Nette\Http\IResponse;
use Nette\Security\Passwords;

class AuthPresenter extends BasePresenter
{
    public function actionRegister(): void
    {
        if (!$this->getRequest()->isMethod('POST')) {
            $this->hasError('Method now allowed', IResponse::S405_MethodNotAllowed);
        }

        $data = $this->getPostData();

        if (empty($data->email) || empty($data->password) || empty($data->role)) {
            $this->hasError('Missing required fields');
        }

        if (!in_array($data->role, $this->getRolesSelection())) {
            $this->hasError('Not allowed role');
        }

        if ($this->entityManager->getRepository(User::class)->findBy(['email' => $data->email])) {
            $this->hasError('E-mail already exists');
        }

        $user = new User();
        $user->setEmail($data->email);
        $user->setRole($data->role);
        $user->setPasswordHash($data->password);
        $user->setName($data->name);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->send(['user' => $user->getData()], 'User has been created');
    }

    public function actionLogin(): void
    {
        if (!$this->getRequest()->isMethod('POST')) {
            $this->hasError('Method now allowed', IResponse::S405_MethodNotAllowed);
        }

        $data = $this->getPostData();

        if (empty($data->email) || empty($data->password)) {
            $this->hasError('Missing required fields');
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data->email]);
        if (!$user) {
            $this->hasError('Incorrect e-mail or password', IResponse::S401_Unauthorized);
        }

        $passwords = new Passwords();
        if (!$passwords->verify($data->password, $user->getPasswordHash())) {
            $this->hasError('Incorrect e-mail or password.', IResponse::S401_Unauthorized);
        }

        $token = JWT::encode(
            ['userId' => $user->getId(), 'email' => $user->getEmail(), 'exp' => time() + 3600],
            $this->jwtSecret,
            'HS256'
        );

        $this->send([
            'user' => $user->getData(),
            'token' => $token
        ], 'User has been successfully logged in');
    }

    protected function getRolesSelection(): array
    {
        return [
            User::ROLE_ADMIN,
            User::ROLE_AUTHOR,
            User::ROLE_READER
        ];
    }

    protected function generateToken(): string
    {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }
}