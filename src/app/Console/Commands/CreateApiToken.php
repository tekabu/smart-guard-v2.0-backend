<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-api-token 
                           {email : Email of the admin user}
                           {--name= : Name of the token (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an API token for an admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $tokenName = $this->option('name') ?? 'Admin API Token - ' . date('Y-m-d H:i:s');

        // Validate input
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $this->error('Invalid email address.');
            return 1;
        }

        // Find user
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        if ($user->role !== 'ADMIN') {
            $this->error("User '{$email}' is not an admin user. Role: {$user->role}");
            return 1;
        }

        if (!$user->active) {
            $this->error("User '{$email}' is not active.");
            return 1;
        }

        // Create token
        $token = $user->createToken($tokenName, ['*']);

        $this->info("API token created successfully!");
        $this->line("");
        $this->line("User: {$user->name} ({$user->email})");
        $this->line("Token Name: {$tokenName}");
        $this->line("Token ID: {$token->accessToken->id}");
        $this->line("");
        $this->warn("=== API TOKEN (keep this secure) ===");
        $this->info($token->plainTextToken);
        $this->warn("=====================================");
        $this->line("");
        $this->line("Usage example:");
        $this->line("curl -H 'Authorization: Bearer {$token->plainTextToken}' http://localhost/api/users");

        return 0;
    }
}
