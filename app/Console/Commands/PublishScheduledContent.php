<?php

namespace App\Console\Commands;

use App\Services\ContentPublisher;
use Illuminate\Console\Command;

class PublishScheduledContent extends Command
{
    protected $signature = 'content:publish-due';

    protected $description = 'Publish approved content whose scheduled time has arrived';

    public function handle(ContentPublisher $publisher): int
    {
        $this->info($publisher->publishDue().' entries published.');

        return self::SUCCESS;
    }
}
