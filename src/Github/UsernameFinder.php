<?php
declare(strict_types=1);

namespace App\Github;

use App\Helper\NonEmptyStringHelper;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UsernameFinder
{
    private const URL = 'https://api.github.com/repos/eonx-com/github-repositories/contents/config/people/users/users.yml';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly HttpClientInterface $githubClient
    ) {
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findByEmail(string $email): ?string
    {
        $username = $this->getMapping()[$email] ?? null;

        if (NonEmptyStringHelper::valid($username)) {
            return $username;
        }

        throw new \RuntimeException(\sprintf('No github username found for email "%s"', $email));
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getMapping(): array
    {
        return $this->cache->get('github_username_mapping', function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            $response = $this->githubClient->request('GET', self::URL);

            return Yaml::parse(\base64_decode($response->toArray()['content']));
        });
    }
}