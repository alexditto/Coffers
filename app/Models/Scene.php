<?php

namespace App\Models;

use App\Observers\ImageObserver;
use Database\Factories\SceneFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(ImageObserver::class)]
class Scene extends Model
{
    /** @use HasFactory<SceneFactory> */
    use HasFactory;

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
