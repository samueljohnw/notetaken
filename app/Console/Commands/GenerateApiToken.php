<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiToken extends Command
{
    protected $signature = 'api:generate-token {name=ChatGPT}';

    protected $description = 'Generate a new API token';

    public function handle(): int
    {
        $name = $this->argument('name');

        // Generate a random token
        $plainToken = Str::random(40);
        $hashedToken = hash('sha256', $plainToken);

        // Save to database
        $apiToken = ApiToken::create([
            'name' => $name,
            'token' => $hashedToken,
        ]);

        $this->info('API Token Generated Successfully!');
        $this->newLine();
        $this->line('Token Name: ' . $apiToken->name);
        $this->line('Token ID: ' . $apiToken->id);
        $this->newLine();
        $this->warn('IMPORTANT: Copy this token now. It will not be shown again!');
        $this->line('Token: ' . $plainToken);
        $this->newLine();
        $this->info('Add this to your ChatGPT Custom GPT in the Authentication section:');
        $this->line('Authorization: Bearer ' . $plainToken);

        return self::SUCCESS;
    }
}
