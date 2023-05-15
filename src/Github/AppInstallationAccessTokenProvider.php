<?php
declare(strict_types=1);

namespace App\Github;

use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AppInstallationAccessTokenProvider
{
    private const GITHUB_APP_INSTALLATION_TOKEN_URL = 'https://api.github.com/app/installations/%s/access_tokens';

    public function __construct(
        private readonly CacheInterface $flysystemCache,
        private readonly HttpClientInterface $githubClient,
        private readonly string $githubAppId,
        private readonly string $githubAppInstallId,
        private readonly string $githubAppSecretKey
    ) {
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function create(): GithubAccessToken
    {
        $key = \sprintf('github_app_installation_token_%s', $this->githubAppId);

        return $this->flysystemCache->get($key, function (ItemInterface $item): GithubAccessToken {
            $url = \sprintf(self::GITHUB_APP_INSTALLATION_TOKEN_URL, $this->githubAppInstallId);
            $response = $this->githubClient->request('POST', $url, [
                'auth_bearer' => $this->generateJwt(),
            ])->toArray();

            $item->expiresAt(CarbonImmutable::parse($response['expires_at']));

            return new GithubAccessToken($response['token'], true);
        });
    }

    private function generateJwt(): string
    {
        $payload = [
            'iat' => \time() - 60,
            'exp' => \time() + (10 * 60),
            'iss' => $this->githubAppId,
        ];

        return JWT::encode($payload, $this->githubAppSecretKey, 'RS256');
    }
}