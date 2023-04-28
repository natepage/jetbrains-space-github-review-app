<?php
declare(strict_types=1);

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\Provider\GithubClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route(path: '/oauth/github/init', name: 'oauth_github_init', methods: ['GET'])]
final class OauthGithubInitController extends AbstractController
{
    public function __construct(
        private readonly CacheInterface $flysystemCache,
        private readonly GithubClient $githubClient
    ) {
    }

    public function __invoke(): Response
    {
        return $this->handleApiResponse(function (): Response {
            $scopes = ['user', 'repo'];

            $stateKeyId = \sprintf('oauth_github_state_%s_%s', \bin2hex(\random_bytes(16)), \time());
            $redirectResponse = $this->githubClient->redirect($scopes, [
                'redirect_uri' => $this->generateUrl('oauth_github_check', [
                    'state_key_id' => $stateKeyId,
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            // Because running in lambda, we need to cache the state
            $this->flysystemCache->get($stateKeyId, function (ItemInterface $item): string {
                $item->expiresAfter(300);

                return $this->githubClient->getOAuth2Provider()->getState();
            }, \INF);

            return $redirectResponse;
        });
    }
}