<?php

declare(strict_types=1);

namespace App\Domains\Mangas\Console\Commands;

use App\Domains\Mangas\Jobs\SyncMangaDetailsJob;
use App\Domains\Mangas\Services\MangaDexService;
use Illuminate\Console\Command;

class DailySyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manga:daily-sync {--limit=100 : The maximum number of recently updated mangas to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch recently updated mangas from MangaDex and dispatch sync jobs.';

    /**
     * Execute the console command.
     */
    public function handle(MangaDexService $mangaDexService): int
    {
        $limit = (int) $this->option('limit');
        $this->info('Fetching recently updated mangas from MangaDex...');

        try {
            $mangaIds = $mangaDexService->fetchRecentlyUpdated($limit);
        } catch (\Throwable $e) {
            $this->error('Failed to fetch recently updated mangas: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info(sprintf('Found %d updated mangas. Dispatching sync jobs...', count($mangaIds)));

        foreach ($mangaIds as $id) {
            SyncMangaDetailsJob::dispatch($id);
        }

        $this->info('All sync jobs have been dispatched to the queue.');

        return Command::SUCCESS;
    }
}
