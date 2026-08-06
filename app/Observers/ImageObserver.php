<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes a model's previously-uploaded S3 image whenever it's replaced with a
 * new one, or the record itself is deleted. Attach to any model with an `image`
 * column holding a full S3 URL via #[ObservedBy(ImageObserver::class)].
 */
class ImageObserver
{
    /**
     * Fires only after the update has actually been persisted, so a failed
     * save never deletes an image that's still in use.
     */
    public function updated(Model $model): void
    {
        if (! $model->wasChanged('image')) {
            return;
        }

        $this->deleteImage($model->getOriginal('image'));
    }

    public function deleted(Model $model): void
    {
        $this->deleteImage($model->image);
    }

    protected function deleteImage(?string $url): void
    {
        if (! $url) {
            return;
        }

        $path = $this->pathFromUrl($url);

        if ($path !== null) {
            Storage::disk('s3')->delete($path);
        }
    }

    /**
     * Recover the s3 disk path from a stored image url, but only if the url's
     * host matches our own bucket's virtual-hosted-style domain. Legacy/external
     * image urls that predate S3 uploads won't match and are correctly left alone.
     */
    protected function pathFromUrl(string $url): ?string
    {
        if (parse_url($url, PHP_URL_HOST) !== $this->bucketHost()) {
            return null;
        }

        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        return $path !== '' ? $path : null;
    }

    protected function bucketHost(): string
    {
        return sprintf(
            '%s.s3.%s.amazonaws.com',
            config('filesystems.disks.s3.bucket'),
            config('filesystems.disks.s3.region'),
        );
    }
}
