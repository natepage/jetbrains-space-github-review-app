<?php
declare(strict_types=1);

namespace App\Infrastructure\Cache;

use League\Flysystem\FilesystemOperator;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Component\Cache\Marshaller\DefaultMarshaller;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;

final class FlysystemCacheAdapter extends AbstractAdapter
{
    private MarshallerInterface $marshaller;

    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
        ?int $defaultLifetime = null,
        ?MarshallerInterface $marshaller = null
    ) {
        $this->marshaller = $marshaller ?? new DefaultMarshaller();

        parent::__construct('', $defaultLifetime ?? 0);
    }

    /**
     * @throws \Exception
     */
    protected function doFetch(array $ids): iterable
    {
        $values = [];
        $now = \time();

        foreach ($ids as $id) {
            $filename = $this->getFilename($id);

            try {
                $h = $this->defaultStorage->readStream($filename);
            } catch (\Throwable $throwable) {
                continue;
            }

            if (($expiresAt = (int) \fgets($h)) && $now >= $expiresAt) {
                \fclose($h);

                try {
                    $this->defaultStorage->delete($filename);
                } catch (\Throwable $throwable) {
                }
            } else {
                $i = \rawurldecode(\rtrim(\fgets($h)));
                $value = \stream_get_contents($h);
                \fclose($h);

                if ($i === $id) {
                    $values[$id] = $this->marshaller->unmarshall($value);
                }
            }
        }

        return $values;
    }

    /**
     * @throws \Symfony\Component\Cache\Exception\CacheException
     */
    protected function doHave(string $id): bool
    {
        throw new CacheException('Not implemented (yet)');
    }

    /**
     * @throws \Symfony\Component\Cache\Exception\CacheException
     */
    protected function doClear(string $namespace): bool
    {
        throw new CacheException('Not implemented (yet)');
    }

    /**
     * @throws \Symfony\Component\Cache\Exception\CacheException
     */
    protected function doDelete(array $ids): bool
    {
        throw new CacheException('Not implemented (yet)');
    }

    /**
     * @throws \Symfony\Component\Cache\Exception\CacheException
     */
    protected function doSave(array $values, int $lifetime): array|bool
    {
        $expiresAt = $lifetime ? (\time() + $lifetime) : 0;
        $values = $this->marshaller->marshall($values, $failed);

        foreach ($values as $id => $value) {
            try {
                $this->defaultStorage->write(
                    $this->getFilename($id),
                    $expiresAt."\n".\rawurlencode($id)."\n".$value
                );
            } catch (\Throwable $throwable) {
                $failed[] = $id;
            }
        }

        if ($failed) {
            throw new CacheException(\sprintf('Could not save ids %s', \implode(', ', $failed)));
        }

        return $failed;
    }

    private function getFilename(string $id): string
    {
        $hash = \str_replace('/', '-', \base64_encode(\hash('md5', FlysystemCacheAdapter::class . $id, true)));
        $dir = \strtoupper($hash[0] . \DIRECTORY_SEPARATOR . $hash[1] . \DIRECTORY_SEPARATOR);

        return $dir . \substr($hash, 2, 20);
    }
}