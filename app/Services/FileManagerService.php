<?php
// app/Services/FileManagerService.php
namespace App\Services;

use App\update_excel;
use Illuminate\Support\Facades\Storage;

class FileManagerService
{
    public function getActiveFiles()
    {
        // Get only status 1 files from database
        return update_excel::where('status', 1)->pluck('excel_name')->toArray();
    }
    
    /**
     * Get file organization status
     */
    public function getOrganizationStatus()
    {
        $storagePath = 'public/files/';
        $activeDir = $storagePath . 'active/';
        $inactiveDir = $storagePath . 'inactive/';
        
        return [
            'active_files_in_db' => $this->getActiveFiles(),
            'active_files_in_storage' => Storage::files($activeDir),
            'inactive_files_in_storage' => Storage::files($inactiveDir),
            'unorganized_files' => array_filter(Storage::files($storagePath), function($file) {
                return !Storage::directoryExists($file);
            })
        ];
    }
}