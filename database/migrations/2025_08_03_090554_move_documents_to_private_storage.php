<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing service requests to use private storage paths
        $serviceRequests = \App\Models\ServiceRequest::whereNotNull('id_card')
            ->orWhereNotNull('family_book')
            ->get();
            
        foreach ($serviceRequests as $request) {
            $updated = false;
            
            // Update ID card paths
            if ($request->id_card) {
                $newIdCardPaths = [];
                foreach ($request->id_card as $index => $oldUrl) {
                    $side = $index === 0 ? 'front' : 'back';
                    $extension = pathinfo(parse_url($oldUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                    $newPath = "id_docs/{$request->id}/{$side}.{$extension}";
                    $newIdCardPaths[] = $newPath;
                }
                $request->id_card = $newIdCardPaths;
                $updated = true;
            }
            
            // Update family book paths  
            if ($request->family_book) {
                $newFamilyBookPaths = [];
                foreach ($request->family_book as $index => $oldUrl) {
                    $extension = pathinfo(parse_url($oldUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                    $newPath = "family_books/{$request->id}/page" . ($index + 1) . ".{$extension}";
                    $newFamilyBookPaths[] = $newPath;
                }
                $request->family_book = $newFamilyBookPaths;
                $updated = true;
            }
            
            if ($updated) {
                $request->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be fully reversed as it changes storage structure
        // Manual intervention would be required to restore public URLs
    }
};
