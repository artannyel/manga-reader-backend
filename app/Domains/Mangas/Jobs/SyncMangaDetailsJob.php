<?php

declare(strict_types=1);

namespace App\Domains\Mangas\Jobs;

use App\Domains\Mangas\Actions\SyncMangaAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncMangaDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $mangaId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SyncMangaAction $syncMangaAction): void
    {
        $syncMangaAction->execute($this->mangaId);
    }
}
