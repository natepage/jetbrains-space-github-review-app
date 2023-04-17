<?php
declare(strict_types=1);

namespace App\Space;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CodeReviewDetailsFinder
{
    private const PROJECTS_URL = 'https://eonx.jetbrains.space/api/http/projects';

    private const GET_REVIEW_URL = 'https://eonx.jetbrains.space/api/http/projects/id:%s/code-reviews/id:%s';

    private const SHOW_REVIEW_URL = 'https://eonx.jetbrains.space/p/%s/reviews/%s/timeline';

    public function __construct(
        private readonly AccessTokenProvider $accessTokenProvider,
        private readonly CacheInterface $flysystemCache,
        private readonly HttpClientInterface $httpClient
    ){
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findById(string $id): array
    {
        $key = \sprintf('space_code_review_%s', $id);

        return $this->flysystemCache->get($key, function (ItemInterface $item) use ($id): array {
            $projects = $this->getProjects();

            foreach ($projects as $projectId) {
                $response = $this->httpClient->request('GET', \sprintf(self::GET_REVIEW_URL, $projectId, $id), [
                    'auth_bearer' => $this->accessTokenProvider->getAccessToken(),
                ]);

                if ($response->getContent() === 'null') {
                    continue;
                }

                $item->expiresAfter(3600);

                $response = $response->toArray();
                return [
                    'title' => $response['title'],
                    'github' => [
                        'repository' => $response['branchPairs'][0]['repository'],
                        'id' => \str_replace('#', '', $response['externalLink']['id']),
                    ],
                    'space' => [
                        'number' => $response['number'],
                        'url' => \sprintf(
                            self::SHOW_REVIEW_URL,
                            \strtolower($response['project']['key']),
                            $response['number']
                        ),
                    ]
                ];
            }

            throw new \RuntimeException(\sprintf('No code review found in space for id "%s"', $id));
        });
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getProjects(): array
    {
        return $this->flysystemCache->get('space_projects', function (ItemInterface $item): array {
            $response = $this->httpClient->request('GET', self::PROJECTS_URL, [
                'auth_bearer' => $this->accessTokenProvider->getAccessToken(),
            ])->toArray();

            $item->expiresAfter(3600);

            return \array_map(static fn (array $project): string => $project['id'], $response['data'] ?? []);
        });
    }
}