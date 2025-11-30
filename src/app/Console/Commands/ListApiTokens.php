<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListApiTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-api-tokens 
                           {--user= : Optional user email to filter tokens}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List API tokens for admin users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userEmail = $this->option('user');

        // Get admin users
        $query = User::where('role', 'ADMIN')->where('active', true);
        if ($userEmail) {
            $query->where('email', $userEmail);
        }
        $admins = $query->get();

        if ($admins->isEmpty()) {
            if ($userEmail) {
                $this->error("No active admin user found with email '{$userEmail}'");
            } else {
                $this->error("No active admin users found");
            }
            return 1;
        }

        $headers = ['User', 'Email', 'Token Name', 'Token ID', 'Created', 'Last Used'];
        $rows = [];

        foreach ($admins as $admin) {
            $tokens = $admin->tokens;
            
            if ($tokens->isEmpty()) {
                $rows[] = [
                    $admin->name,
                    $admin->email,
                    'No tokens',
                    '-',
                    '-',
                    '-'
                ];
            } else {
                foreach ($tokens as $token) {
                    $rows[] = [
                        $admin->name,
                        $admin->email,
                        $token->name,
                        $token->id,
                        $token->created_at->format('Y-m-d H:i:s'),
                        $token->last_used_at ? $token->last_used_at->format('Y-m-d H:i:s') : 'Never'
                    ];
                }
            }
        }

        $this->table($headers, $rows);
        
        $totalTokens = $admins->reduce(function ($carry, $admin) {
            return $carry + $admin->tokens->count();
        }, 0);
        
        $this->info("Total tokens: {$totalTokens}");
        
        return 0;
    }
}
