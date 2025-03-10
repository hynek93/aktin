<?php

namespace App\Presenters;

use App\Model\Entity\Article;
use App\Model\Entity\User;
use Nette\Http\IResponse;

class ArticlesPresenter extends BasePresenter
{
    public function startup(): void
    {
        parent::startup();
        $this->apiUser = $this->verifyToken();

        if (!$this->apiUser) {
            $this->hasError('Invalid authorization token', IResponse::S401_Unauthorized);
        }
    }

    public function read(): void
    {
        $id = $this->getParameter('id');
        $payload = [];

        if ($id) {
            $article = $this->getArticleById();
            $payload[] = $article->getData();
        } else {
            $articles = $this->entityManager->getRepository(Article::class)->findAll();
            foreach ($articles as $article) {
                $payload[] = $article->getData();
            }
        }

        $this->send(['articles' => $payload]);
    }

    public function create(): void
    {
        if ($this->apiUser->hasRole(User::ROLE_READER)) {
            $this->hasError('Access denied', IResponse::S403_Forbidden);
        }

        $data = $this->getPostData();
        if (empty($data->title) || empty($data->content)) {
            $this->hasError('Title or content is empty');
        }

        $article = new Article($this->apiUser, $data->title, $data->content);

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $this->send([
            'article' => $article->getData()
        ], 'Article has been successfully created');
    }

    public function update(): void
    {
        $article = $this->getArticleById();

        if ($this->apiUser->hasRole(User::ROLE_READER) || ($this->apiUser->hasRole(User::ROLE_AUTHOR) && $article->getAuthor()->getId() !== $this->apiUser->getId())) {
            $this->hasError('Access denied', IResponse::S403_Forbidden);
        }

        $data = $this->getPostData();
        if (!empty($data->title)) {
            $article->setTitle($data->title);
        }

        if (!empty($data->content)) {
            $article->setContent($data->content);
        }

        $article->setUpdatedAt(new \DateTime());
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $this->send([
            'article' => $article->getData()
        ], 'Article has been successfully updated');
    }

    public function delete(): void
    {
        $article = $this->getArticleById();

        if ($this->apiUser->hasRole(User::ROLE_READER) || ($this->apiUser->hasRole(User::ROLE_AUTHOR) && $article->getAuthor()->getId() !== $this->apiUser->getId())) {
            $this->hasError('Access denied', IResponse::S403_Forbidden);
        }

        $this->entityManager->remove($article);
        $this->entityManager->flush();
        $this->send([], 'Article has been successfully deleted');
    }

    protected function getArticleById(): Article
    {
        $id = $this->getParameter('id');

        if (!$id) {
            $this->hasError('Article ID is empty');
        }

        $article = $this->entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            $this->hasError('Article not found', IResponse::S404_NotFound);
        }

        return $article;
    }
}