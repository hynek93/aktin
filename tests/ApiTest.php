<?php

use PHPUnit\Framework\TestCase;
use \Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use App\Model\Entity\User;

class ApiTest extends TestCase
{
    private Client $client;
    private EntityManagerInterface $em;
    private string $readerToken;
    private string $authorToken;
    private string $adminToken;
    private array $testUsers = [];
    private array $testArticles = [];

    protected function setUp(): void
    {
        $container = \App\Booting::boot()->createContainer();
        $this->em = $container->getByType(EntityManagerInterface::class);

        $this->client = new Client([
            'base_uri' => 'http://app/',
            'http_errors' => false,
        ]);

        $this->testUsers[User::ROLE_READER] = $this->createTestUser(User::ROLE_READER, 'reader123', 'ctenar@test.cz', 'Ctenar');
        $this->testUsers[User::ROLE_AUTHOR] = $this->createTestUser(User::ROLE_AUTHOR, 'author123', 'author@test.cz', 'Autor');
        $this->testUsers[User::ROLE_ADMIN] = $this->createTestUser(User::ROLE_ADMIN, 'admin123', 'admin@test.cz', 'Admin');

        $this->readerToken = $this->getToken('ctenar@test.cz', 'reader123');
        $this->authorToken = $this->getToken('author@test.cz', 'author123');
        $this->adminToken = $this->getToken('admin@test.cz', 'admin123');

        $this->testArticles[User::ROLE_AUTHOR] = $this->createTestArticle('Autorův článek', 'Obsah článku', $this->testUsers[User::ROLE_AUTHOR], $this->authorToken);
        $this->testArticles[User::ROLE_ADMIN] = $this->createTestArticle('Cizí článek', 'Obsah jiného autora', $this->testUsers[User::ROLE_ADMIN], $this->adminToken);
    }

    private function createTestUser(string $role, string $password, string $email, string $name): ?int
    {
        $response = $this->client->request('POST', '/auth/register', [
            'json' => [
                'email' => $email,
                'name' => $name,
                'password' => $password,
                'role' => $role
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['data']['user']['id'] ?? null;
    }

    private function createTestArticle(string $title, string $content, int $authorId, string $token): ?int
    {
        $response = $this->client->request('POST', '/articles', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'json' => [
                'title' => $title,
                'content' => $content,
                'author_id' => $authorId,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['data']['article']['id'] ?? null;
    }

    private function getToken(string $username, string $password): string
    {
        $response = $this->client->request('POST', '/auth/login', [
            'json' => ['email' => $username, 'password' => $password],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['data']['token'] ?? '';
    }

    public function testReaderCannotManageUsers(): void
    {
        $response = $this->client->request('GET', '/users', [
            'headers' => ['Authorization' => 'Bearer ' . $this->readerToken],
        ]);

        $this->assertEquals(\Nette\Http\IResponse::S403_Forbidden, $response->getStatusCode());
    }

    public function testAuthorCanEditOnlyOwnArticle(): void
    {
        $authorArticleId = $this->testArticles[User::ROLE_AUTHOR];
        $foreignArticleId = $this->testArticles[User::ROLE_ADMIN];

        $response = $this->client->request('PUT', "/articles/{$foreignArticleId}", [
            'headers' => ['Authorization' => 'Bearer ' . $this->authorToken],
            'json' => [
                'title' => 'Neautorizovaná změna',
                'content' => 'Tento text by neměl být změněn',
            ],
        ]);
        $this->assertEquals(\Nette\Http\IResponse::S403_Forbidden, $response->getStatusCode());

        $response = $this->client->request('PUT', "/articles/{$authorArticleId}", [
            'headers' => ['Authorization' => 'Bearer ' . $this->authorToken],
            'json' => [
                'title' => 'Můj článek - upraveno',
                'content' => 'Nový obsah',
            ],
        ]);
        $this->assertEquals(\Nette\Http\IResponse::S200_OK, $response->getStatusCode());
    }

    public function testAdminCanEditUser(): void
    {
        $userId = $this->testUsers[User::ROLE_READER];

        $response = $this->client->request('PUT', "/users/{$userId}", [
            'headers' => ['Authorization' => 'Bearer ' . $this->adminToken],
            'json' => [
                'email' => 'reader@reader.cz',
            ],
        ]);

        $this->assertEquals(\Nette\Http\IResponse::S200_OK, $response->getStatusCode());
    }

    public function testReaderCannotManageArticles(): void
    {
        $response = $this->client->request('POST', "/articles", [
            'headers' => ['Authorization' => 'Bearer ' . $this->readerToken],
            'json' => [
                'title' => 'Článek čtenáře',
                'content' => 'Text článku',
            ],
        ]);

        $this->assertEquals(\Nette\Http\IResponse::S403_Forbidden, $response->getStatusCode());

        $authorArticleId = $this->testArticles[User::ROLE_AUTHOR];
        $response = $this->client->request('PUT', "/articles/{$authorArticleId}", [
            'headers' => ['Authorization' => 'Bearer ' . $this->readerToken],
            'json' => [
                'title' => 'Článek čtenáře'
            ],
        ]);

        $this->assertEquals(\Nette\Http\IResponse::S403_Forbidden, $response->getStatusCode());

        $response = $this->client->request('DELETE', "/articles/{$authorArticleId}", [
            'headers' => ['Authorization' => 'Bearer ' . $this->readerToken]
        ]);

        $this->assertEquals(\Nette\Http\IResponse::S403_Forbidden, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        foreach ($this->testArticles as $articleId) {
            $this->client->request('DELETE', "/articles/{$articleId}", [
                'headers' => ['Authorization' => 'Bearer ' . $this->adminToken],
            ]);
        }

        foreach ($this->testUsers as $userId) {
            $this->client->request('DELETE', "/users/{$userId}", [
                'headers' => ['Authorization' => 'Bearer ' . $this->adminToken],
            ]);
        }
    }
}