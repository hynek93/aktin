<?php

namespace App\Presenters;

use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class BasePresenter extends Presenter
{
    public EntityManagerInterface $entityManager;

    protected ?User $user = null;

    protected string $jwtSecret = 'aktinJwtSecret';

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    public function send(array $data = [], string $message = '', string $code = IResponse::S200_OK, bool $success = true): void
    {
        $result = [
            'status' => $success ? 'success' : 'error',
            'message' => $message,
            'data' => $data,
        ];

        $this->getHttpResponse()->setCode($code);

        $this->sendJson($result);
    }

    public function hasError(string $message = '', int $httpCode = IResponse::S400_BadRequest, bool $success = false): void
    {
        $this->send([], $message, $httpCode, $success);
    }

    public function getPostData(): ?object
    {
        $params = $this->getHttpRequest()->getPost();

        if ($params) {
            $params = (object) $params;
        } else {
            $params = $this->getInputData();
        }

        return $params;
    }


    public function getInputData(): ?object
    {
        $input = $this->getHttpRequest()->getRawBody();

        if (!$input) {
            return null;
        }

        try {
            $data = Json::decode($input);
        } catch (JsonException $e) {
            return null;
        }

        return $data;
    }

    protected function verifyToken(): ?User
    {
        $authHeader = $this->getHttpRequest()->getHeader('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return $this->entityManager->getRepository(User::class)->find($decoded->userId);
        } catch (\Exception $e) {
            return null;
        }
    }
}