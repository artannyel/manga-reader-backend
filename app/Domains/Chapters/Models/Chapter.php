<?php

declare(strict_types=1);

namespace App\Domains\Chapters\Models;

use App\Domains\Mangas\Models\Manga;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chapter extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chapters';

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
        'manga_id',
        'title',
        'chapter_number',
        'volume_number',
        'language',
        'pages_count',
        'hash',
        'pages',
        'pages_saver',
        'last_synced_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pages' => 'array',
            'pages_saver' => 'array',
            'pages_count' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Get the manga that owns the chapter.
     */
    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class, 'manga_id', 'id');
    }
}
