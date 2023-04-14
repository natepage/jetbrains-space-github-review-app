<?php
declare(strict_types=1);

namespace App\Space;

use App\Helper\NonEmptyStringHelper;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AccessTokenProvider
{
    private const URL = 'https://eonx.jetbrains.space/oauth/token';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly HttpClientInterface $httpClient,
        private readonly string $spaceClientId,
        private readonly string $spaceClientSecret
    ) {
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getAccessToken(): string
    {
        return $this->cache->get('space_access_token', function (ItemInterface $item): string {
            $response = $this->httpClient->request('POST', self::URL, [
                'auth_basic' => [$this->spaceClientId, $this->spaceClientSecret],
                'body' => [
                    'grant_type' => 'client_credentials',
                    'scope' => '**',
                ],
            ])->toArray();

            $item->expiresAfter(($response['expires_in'] ?? 300) - 10);

            $accessToken = $response['access_token'] ?? null;

            if (NonEmptyStringHelper::valid($accessToken)) {
                return $accessToken;
            }

            throw new \RuntimeException('No access token found in space response');
        });
    }
}