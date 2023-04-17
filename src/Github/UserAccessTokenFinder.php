<?php
declare(strict_types=1);

namespace App\Github;

use EonX\EasyEncryption\Interfaces\EncryptorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class UserAccessTokenFinder
{
    public function __construct(
        private readonly string $githubAccessToken,
        private readonly CacheInterface $flysystemCache,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function findByUsername(string $username): GithubAccessToken
    {
        $key = \sprintf('github_token_%s', $username);
        $encryptedAccessToken = $this->flysystemCache->get($key, function (ItemInterface $item): ?string {
            $item->expiresAfter(5);
            // This closure shouldn't be executed, otherwise it means the cache is expired
            return null;
        });

        if ($encryptedAccessToken !== null) {
            return new GithubAccessToken($this->encryptor->decrypt($encryptedAccessToken)->getRawDecryptedString(), false);
        }

        return new GithubAccessToken($this->githubAccessToken, true);
    }
}