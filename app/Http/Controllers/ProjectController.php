<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects
     */
    public function index()
    {
        $projects = Project::with('creator')->latest()->get();

        return response()->json(
            $projects->map(function ($project) {
                return $this->transformProject($project);
            })
        );
    }

    /**
     * Store a newly created project
     */
    public function store(Request $request)
    {
        $v = $request->validate([
            'project_id'      => 'sometimes|string|unique:projects,project_id',
            'project_name'    => 'required|string|max:255',
            'status'          => 'nullable|in:ongoing,completed,terminated',
            'amount'          => 'nullable|numeric',
            'revised_amount'  => 'nullable|numeric',
            'image.*'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'document.*'      => 'nullable|file|mimes:pdf,doc,docx,xlsx|max:5120',
        ]);

        $data = $request->except(['image', 'document']);
        $data['created_by'] = Auth::id();

        // Save multiple images
        $imagePaths = [];
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $file) {
                $imagePaths[] = $file->store('projects/images', 'public');
            }
        }
        $data['image_path'] = !empty($imagePaths) ? json_encode($imagePaths) : null;

        // Save multiple documents
        $documentPaths = [];
        if ($request->hasFile('document')) {
            foreach ($request->file('document') as $file) {
                $documentPaths[] = $file->store('projects/documents', 'public');
            }
        }
        $data['document_path'] = !empty($documentPaths) ? json_encode($documentPaths) : null;

        $project = Project::create($data);

        return response()->json([
            'message' => 'Project created',
            'project' => $this->transformProject($project->fresh('creator'))
        ], 201);
    }

    /**
     * Display a specific project
     */
    public function show($id)
    {
        $project = Project::with('creator')->findOrFail($id);

        return response()->json($this->transformProject($project));
    }

    /**
     * Update the specified project
     */
    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $v = $request->validate([
            'project_name'    => 'sometimes|string|max:255',
            'status'          => 'sometimes|in:ongoing,completed,terminated',
            'amount'          => 'sometimes|numeric',
            'revised_amount'  => 'sometimes|numeric',
            'image.*'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'document.*'      => 'nullable|file|mimes:pdf,doc,docx,xlsx|max:5120',
        ]);

        $data = $request->except(['image', 'document']);

        // Replace images
        if ($request->hasFile('image')) {
            if ($project->image_path) {
                foreach (json_decode($project->image_path, true) as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
            $imagePaths = [];
            foreach ($request->file('image') as $file) {
                $imagePaths[] = $file->store('projects/images', 'public');
            }
            $data['image_path'] = json_encode($imagePaths);
        }

        // Replace documents
        if ($request->hasFile('document')) {
            if ($project->document_path) {
                foreach (json_decode($project->document_path, true) as $oldDoc) {
                    Storage::disk('public')->delete($oldDoc);
                }
            }
            $documentPaths = [];
            foreach ($request->file('document') as $file) {
                $documentPaths[] = $file->store('projects/documents', 'public');
            }
            $data['document_path'] = json_encode($documentPaths);
        }

        $project->update($data);

        return response()->json([
            'message' => 'Project updated',
            'project' => $this->transformProject($project->fresh('creator'))
        ]);
    }

    /**
     * Remove the specified project
     */
    public function destroy($id)
    {
        $project = Project::findOrFail($id);

        if ($project->image_path) {
            foreach (json_decode($project->image_path, true) as $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }
        }

        if ($project->document_path) {
            foreach (json_decode($project->document_path, true) as $oldDoc) {
                Storage::disk('public')->delete($oldDoc);
            }
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }

    /**
     * Transform project to include full URLs
     */
    private function transformProject($project)
{
    $project->image_urls = $project->image_path
        ? collect(json_decode($project->image_path, true))
            ->map(fn($path) => url(Storage::url($path)))
        : [];

    $project->document_urls = $project->document_path
        ? collect(json_decode($project->document_path, true))
            ->map(fn($path) => url(Storage::url($path)))
        : [];

    return $project;
}

}