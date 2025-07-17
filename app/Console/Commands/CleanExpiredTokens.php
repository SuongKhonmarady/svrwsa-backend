<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class CleanExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:cleanup 
                           {--days=7 : Delete tokens older than specified days}
                           {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired and old personal access tokens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("🔍 Scanning for expired tokens...");

        // Find expired tokens
        $expiredTokens = PersonalAccessToken::where('expires_at', '<', now())->get();
        
        // Find old unused tokens (older than specified days)
        $oldTokens = PersonalAccessToken::where('last_used_at', '<', now()->subDays($days))
            ->orWhere(function($query) use ($days) {
                $query->whereNull('last_used_at')
                      ->where('created_at', '<', now()->subDays($days));
            })->get();

        $expiredCount = $expiredTokens->count();
        $oldCount = $oldTokens->count();
        
        if ($expiredCount === 0 && $oldCount === 0) {
            $this->info("✅ No tokens to clean up!");
            return 0;
        }

        // Show what will be deleted
        if ($expiredCount > 0) {
            $this->warn("⏰ Found {$expiredCount} expired tokens:");
            $this->table(
                ['ID', 'Name', 'Expired At', 'Days Ago'],
                $expiredTokens->map(function ($token) {
                    return [
                        $token->id,
                        $token->name,
                        $token->expires_at?->format('Y-m-d H:i:s'),
                        $token->expires_at?->diffInDays(now()) . ' days'
                    ];
                })->toArray()
            );
        }

        if ($oldCount > 0) {
            $this->warn("🗑️  Found {$oldCount} old unused tokens (>{$days} days):");
            $this->table(
                ['ID', 'Name', 'Last Used', 'Days Ago'],
                $oldTokens->map(function ($token) {
                    $lastUsed = $token->last_used_at ?: $token->created_at;
                    return [
                        $token->id,
                        $token->name,
                        $lastUsed->format('Y-m-d H:i:s'),
                        $lastUsed->diffInDays(now()) . ' days'
                    ];
                })->toArray()
            );
        }

        if ($dryRun) {
            $this->info("🔍 Dry run complete. No tokens were deleted.");
            $this->info("💡 Run without --dry-run to actually delete these tokens.");
            return 0;
        }

        // Confirm deletion
        if (!$this->confirm("❓ Do you want to delete these tokens?")) {
            $this->info("❌ Operation cancelled.");
            return 0;
        }

        // Delete tokens
        $deletedExpired = 0;
        $deletedOld = 0;

        if ($expiredCount > 0) {
            $deletedExpired = PersonalAccessToken::where('expires_at', '<', now())->delete();
        }

        if ($oldCount > 0) {
            $deletedOld = PersonalAccessToken::where('last_used_at', '<', now()->subDays($days))
                ->orWhere(function($query) use ($days) {
                    $query->whereNull('last_used_at')
                          ->where('created_at', '<', now()->subDays($days));
                })->delete();
        }

        $totalDeleted = $deletedExpired + $deletedOld;

        $this->info("✅ Successfully deleted {$totalDeleted} tokens:");
        $this->line("   - {$deletedExpired} expired tokens");
        $this->line("   - {$deletedOld} old unused tokens");
        $this->info("🕒 Cleanup completed at: " . now()->format('Y-m-d H:i:s'));

        return 0;
    }
}
