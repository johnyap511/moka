<?php
// app/Http/Controllers/Admin/FileManagerController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FileManagerService;
use Illuminate\Http\Request;

class FileManagerController extends Controller
{
    protected $fileService;
    
    public function __construct(FileManagerService $fileService)
    {
        $this->fileService = $fileService;
    }
    
    public function index()
    {
        // Get only status 1 files
        $fileStats = $this->fileService->getActiveFiles();
        return view('admin.home.filemanager', compact('fileStats'));
    }
    
}