<?php

declare(strict_types=1);

namespace App\Domains\Mangas\Console\Commands;

use App\Domains\Mangas\Jobs\SyncMangaDetailsJob;
use App\Domains\Mangas\Repositories\MangaRepository;
use Illuminate\Console\Command;

class HourlySyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manga:hourly-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch all local mangas viewed within the last 7 days and dispatch sync jobs.';

    /**
     * Execute the console command.
     */
    public function handle(MangaRepository $mangaRepository): int
    {
        $this->info('Fetching local mangas viewed within the last 7 days...');

        $mangas = $mangaRepository->getRecentlyViewed(7);

        $this->info(sprintf('Found %d mangas. Dispatching sync jobs...', $mangas->count()));

        foreach ($mangas as $manga) {
            SyncMangaDetailsJob::dispatch($manga->id);
        }

        $this->info('All sync jobs have been dispatched to the queue.');

        return Command::SUCCESS;
    }
}
