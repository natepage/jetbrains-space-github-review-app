<?php
declare(strict_types=1);

namespace App\Controller;

use EonX\EasyEncryption\Interfaces\EncryptorInterface;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use KnpU\OAuth2ClientBundle\Client\Provider\GithubClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route(path: '/oauth/github/check', name: 'oauth_github_check', methods: ['GET'])]
final class OauthGithubCheckController extends AbstractController
{
    public function __construct(
        private readonly CacheInterface $flysystemCache,
        private readonly GithubClient $githubClient,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function __invoke(Request $request): Response
    {
        return $this->handleApiResponse(function () use ($request): Response {
            $stateKeyId = $request->query->get('state_key_id');
            $state = $this->flysystemCache->get($stateKeyId, function (ItemInterface $item): ?string {
                $item->expiresAfter(5);

                // This closure shouldn't be executed, otherwise it means the cache is expired
                return null;
            });

            // Set state on session so that the OAuth2Client can validate it
            $request->getSession()->set(OAuth2Client::OAUTH2_SESSION_STATE_KEY, $state);

            $accessToken = $this->githubClient->getAccessToken();
            $encryptedToken = $this->encryptor->encrypt($accessToken->getToken());
            $githubUser = $this->githubClient->fetchUserFromToken($accessToken)->toArray();

            // Set encrypted token in cache to reuse it on webhooks
            $key = \sprintf('github_token_%s', $githubUser['login']);
            $this->flysystemCache->get($key, static function (ItemInterface $item) use ($encryptedToken): string {
                $item->expiresAfter(null);
                return $encryptedToken;
            }, \INF);

            return $this->redirect('https://github.com/eonx-com');
        });
    }
}