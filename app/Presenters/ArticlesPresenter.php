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
        $this->user = $this->verifyToken();

        if (!$this->user) {
            $this->hasError('Invalid authorization token', IResponse::S401_Unauthorized);
        }
    }

    public function actionDefault(): void
    {
        if ($this->getRequest()->isMethod('GET')) {
            $id = $this->getParameter('id');
            if ($id) {
                $this->article();
            } else {
                $this->articles();
            }
        } elseif ($this->getRequest()->isMethod('POST')) {
            $this->addArticle();
        } elseif ($this->getRequest()->isMethod('PUT')) {
            $this->editArticle();
        } elseif ($this->getRequest()->isMethod('DELETE')) {
            $this->deleteArticle();
        }

        $this->hasError('Wrong request method', IResponse::S405_MethodNotAllowed);
    }

    protected function articles(): void
    {
        $articles = $this->entityManager->getRepository(Article::class)->findAll();
        $payload = [];

        foreach ($articles as $article) {
            $payload[] = $article->getData();
        }

        $this->send([
            'articles' => $payload
        ]);
    }

    protected function article(): void
    {
        $article = $this->getArticleById();

        $this->send([
            'article' => $article->getData()
        ]);
    }

    protected function addArticle(): void
    {
        if ($this->user->hasRole(User::ROLE_READER)) {
            $this->hasError('Access denied', IResponse::S403_Forbidden);
        }

        $data = $this->getPostData();
        if (empty($data->title) || empty($data->content)) {
            $this->hasError('Title or content is empty');
        }

        $article = new Article($this->user, $data->title, $data->content);

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $this->send([
            'article' => $article->getData()
        ], 'Article has been successfully created');
    }

    protected function editArticle(): void
    {
        $article = $this->getArticleById();

        if ($this->user->hasRole(User::ROLE_READER) || ($this->user->hasRole(User::ROLE_AUTHOR) && $article->getAuthor()->getId() !== $this->user->getId())) {
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

    protected function deleteArticle(): void
    {
        $article = $this->getArticleById();

        if ($this->user->hasRole(User::ROLE_READER) || ($this->user->hasRole(User::ROLE_AUTHOR) && $article->getAuthor()->getId() !== $this->user->getId())) {
            $this->hasError('Access denied', IResponse::S403_Forbidden);
        }

        $this->entityManager->remove($article);
        $this->entityManager->flush();
        $this->send([], 'Article has been successfully deleted');
    }

    protected function getArticleById(): ?Article
    {
        $id = $this->getParameter('id');
        $article = $this->entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            $this->hasError('Article not found', IResponse::S404_NotFound);
        }

        return $article;
    }
}