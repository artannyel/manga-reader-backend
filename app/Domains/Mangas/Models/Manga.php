<?php

declare(strict_types=1);

namespace App\Domains\Mangas\Models;

use App\Domains\Chapters\Models\Chapter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manga extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mangas';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'title',
        'description',
        'cover_filename',
        'status',
        'last_synced_at',
        'last_viewed_at',
        'views_count',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'cover_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    /**
     * Get the cover URL.
     */
    public function getCoverUrlAttribute(): ?string
    {
        if (!$this->cover_filename) {
            return null;
        }

        $uploadsUrl = rtrim(config('services.mangadex.uploads_url', 'https://uploads.mangadex.org'), '/');

        return "{$uploadsUrl}/covers/{$this->id}/{$this->cover_filename}";
    }

    /**
     * Get the chapters for the manga.
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class, 'manga_id', 'id');
    }
}
