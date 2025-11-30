<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RevokeApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:revoke-api-token 
                           {--id= : Token ID to revoke}
                           {--user= : User email to revoke all tokens}
                           {--all : Revoke all admin tokens}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke API tokens for admin users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tokenId = $this->option('id');
        $userEmail = $this->option('user');
        $revokeAll = $this->option('all');

        // Validate arguments
        if (!$tokenId && !$userEmail && !$revokeAll) {
            $this->error('You must specify either --id, --user, or --all');
            return 1;
        }

        if (count([$tokenId, $userEmail, $revokeAll]) > 1) {
            $this->error('You can only specify one option at a time');
            return 1;
        }

        // Revoke specific token
        if ($tokenId) {
            $token = \Laravel\Sanctum\PersonalAccessToken::find($tokenId);
            
            if (!$token) {
                $this->error("Token with ID {$tokenId} not found");
                return 1;
            }

            $user = $token->tokenable;
            
            if (!$user || !($user instanceof User) || $user->role !== 'ADMIN') {
                $this->error("Token does not belong to an admin user");
                return 1;
            }

            $tokenName = $token->name;
            $token->delete();
            
            $this->info("Token '{$tokenName}' (ID: {$tokenId}) has been revoked");
            return 0;
        }

        // Revoke all tokens for a specific user
        if ($userEmail) {
            $user = User::where('email', $userEmail)->first();
            
            if (!$user) {
                $this->error("User with email '{$userEmail}' not found");
                return 1;
            }

            if ($user->role !== 'ADMIN') {
                $this->error("User '{$userEmail}' is not an admin user");
                return 1;
            }

            $tokenCount = $user->tokens()->count();
            $user->tokens()->delete();
            
            $this->info("{$tokenCount} token(s) have been revoked for user '{$userEmail}'");
            return 0;
        }

        // Revoke all admin tokens
        if ($revokeAll) {
            $this->warn('This will revoke ALL tokens for ALL admin users!');
            
            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled');
                return 0;
            }

            $tokenCount = 0;
            $adminUsers = User::where('role', 'ADMIN')->get();
            
            foreach ($adminUsers as $admin) {
                $tokenCount += $admin->tokens()->count();
                $admin->tokens()->delete();
            }
            
            $this->info("{$tokenCount} token(s) have been revoked for all admin users");
            return 0;
        }

        return 0;
    }
}
