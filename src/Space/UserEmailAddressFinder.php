<?php
declare(strict_types=1);

namespace App\Space;

use App\Helper\NonEmptyStringHelper;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UserEmailAddressFinder
{
    private const URL = 'https://eonx.jetbrains.space/api/http/team-directory/profiles/id:%s?$fields=emails';

    public function __construct(
        private readonly AccessTokenProvider $accessTokenProvider,
        private readonly CacheInterface $flysystemCache,
        private readonly HttpClientInterface $httpClient
    ){
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findById(string $id): ?string
    {
        $key = \sprintf('space_user_email_%s', $id);

        return $this->flysystemCache->get($key, function (ItemInterface $item) use ($id): ?string {
            $response = $this->httpClient->request('GET', \sprintf(self::URL, $id), [
                'auth_bearer' => $this->accessTokenProvider->getAccessToken(),
            ])->toArray();

            $item->expiresAfter(3600);

            $email = $response['emails'][0]['email'] ?? null;

            if (NonEmptyStringHelper::valid($email)) {
                return $email;
            }

            throw new \RuntimeException(\sprintf('No email found for space user id "%s"', $id));
        });
    }
}