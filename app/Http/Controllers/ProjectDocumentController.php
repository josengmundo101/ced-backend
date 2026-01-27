<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectDocumentController extends Controller
{
    /**
     * Force authentication for all actions
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum'); 
    }

    /**
     * List documents for a project
     */
    public function index(Project $project)
    {
        return $project->documents()
            ->with('uploader')
            ->orderByDesc('is_latest')
            ->orderByDesc('version')
            ->get();
    }

    /**
     * Upload a document (with versioning)
     */
    public function store(Request $request, Project $project)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // 20MB
            'document_category' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        // 🚨 Safety: auth is guaranteed by middleware
        $userId = Auth::id();

        abort_unless($userId, 401, 'Unauthorized');

        $file = $request->file('file');

        $originalName = $file->getClientOriginalName();
        $extension    = $file->getClientOriginalExtension();
        $fileSize     = $file->getSize();

        // Get latest version of the same document
        $latest = ProjectDocument::where('project_id', $project->id)
            ->where('original_name', $originalName)
            ->where('is_latest', true)
            ->first();

        $nextVersion = $latest ? $latest->version + 1 : 1;

        // Mark previous version as not latest
        if ($latest) {
            $latest->update(['is_latest' => false]);
        }

        // Store file
        $storedName = (string) Str::uuid() . '.' . $extension;
        $path = $file->storeAs(
            "projects/{$project->id}",
            $storedName
        );

        $document = $project->documents()->create([
            'uploaded_by'       => $userId,
            'original_name'     => $originalName,
            'stored_name'       => $storedName,
            'file_type'         => $extension,
            'file_size'         => $fileSize,
            'file_path'         => $path,
            'version'           => $nextVersion,
            'is_latest'         => true,
            'document_category' => $request->document_category,
            'remarks'           => $request->remarks,
        ]);

        return response()->json($document, 201);
    }

    /**
     * Download a document
     */
    public function download(ProjectDocument $document)
    {
        abort_unless(Storage::exists($document->file_path), 404);

        return Storage::download(
            $document->file_path,
            $document->original_name
        );
    }

    /**
     * Delete a document
     */
    public function destroy(ProjectDocument $document)
    {
        // Optional: add role / policy check later
        // $this->authorize('delete', $document);

        if (Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }

        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully'
        ]);
    }
}