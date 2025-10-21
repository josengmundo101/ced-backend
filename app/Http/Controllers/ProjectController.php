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
            'project_name'    => 'sometimes|string|max:255',
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

    // ✅ Validate all fields for full update
    $validated = $request->validate([
        'project_id'           => 'sometimes|string|max:255',
        'contract_id'          => 'nullable|string|max:255',
        'project_name'         => 'sometimes|string|max:255',
        'category'             => 'nullable|string|max:255',
        'region'               => 'nullable|string|max:255',
        'lgu'                  => 'nullable|string|max:255',
        'department'           => 'nullable|string|max:255',
        'implementing_office'  => 'nullable|string|max:255',
        'fund_source'          => 'nullable|string|max:255',
        'implementation_type'  => 'nullable|string|max:255',
        'contractor'           => 'nullable|string|max:255',
        'project_engineer'     => 'nullable|string|max:255',
        'year_implemented'     => 'nullable|integer',
        'amount'               => 'nullable|numeric',
        'revised_amount'       => 'nullable|numeric',
        'location'             => 'nullable|string|max:255',
        'start_date'           => 'nullable|date',
        'end_date'             => 'nullable|date',
        'status'               => 'sometimes|in:ongoing,completed,terminated',
        'image.*'              => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'document.*'           => 'nullable|file|mimes:pdf,doc,docx,xlsx|max:5120',
    ]);

    // ✅ Copy non-file fields
    $data = $request->except(['image', 'document']);

    // ✅ Replace images only if new ones uploaded
    if ($request->hasFile('image')) {
        // Remove old images if exist
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
    } else {
        // keep existing images if no new upload
        $data['image_path'] = $project->image_path;
    }

    // ✅ Replace documents only if new ones uploaded
    if ($request->hasFile('document')) {
        // Remove old documents if exist
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
    } else {
        // keep existing documents if no new upload
        $data['document_path'] = $project->document_path;
    }

    // ✅ Update the project record
    $project->update($data);

    return response()->json([
        'message' => 'Project updated successfully',
        'project' => $this->transformProject($project->fresh('creator')),
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