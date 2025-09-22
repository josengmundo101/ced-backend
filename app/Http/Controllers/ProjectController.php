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
        return response()->json(
            Project::with('creator')->latest()->get()
        );
    }

    /**
     * Store a newly created project
     */
   public function store(Request $request)
{
    $v = $request->validate([
        'project_id'      => 'required|string|unique:projects,project_id',
        'project_name'    => 'required|string|max:255',
        'status'          => 'nullable|in:ongoing,completed,terminated',
        'amount'          => 'nullable|numeric',
        'revised_amount'  => 'nullable|numeric',
        'image'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'document'        => 'nullable|file|mimes:pdf,doc,docx,xlsx|max:5120',
    ]);

    $data = $request->except(['image', 'document']);
    $data['created_by'] = Auth::id();

    // ✅ Save image and add path to DB
    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('projects/images', 'public');
        $data['image_path'] = $path;
    }

    // ✅ Save document and add path to DB
    if ($request->hasFile('document')) {
        $path = $request->file('document')->store('projects/documents', 'public');
        $data['document_path'] = $path;
    }

    $project = Project::create($data);

    return response()->json([
        'message' => 'Project created',
        'project' => $project
    ], 201);
}


    /**
     * Display a specific project
     */
    public function show($id)
    {
        return response()->json(
            Project::with('creator')->findOrFail($id)
        );
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
        'image'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'document'        => 'nullable|file|mimes:pdf,doc,docx,xlsx|max:5120',
    ]);

    $data = $request->except(['image', 'document']);

    // ✅ Replace image if new one uploaded
    if ($request->hasFile('image')) {
        if ($project->image_path) {
            Storage::disk('public')->delete($project->image_path);
        }
        $data['image_path'] = $request->file('image')->store('projects/images', 'public');
    }

    // ✅ Replace document if new one uploaded
    if ($request->hasFile('document')) {
        if ($project->document_path) {
            Storage::disk('public')->delete($project->document_path);
        }
        $data['document_path'] = $request->file('document')->store('projects/documents', 'public');
    }

    $project->update($data);

    return response()->json([
        'message' => 'Project updated',
        'project' => $project
    ]);
}


    /**
     * Remove the specified project
     */
    public function destroy($id)
    {
        $project = Project::findOrFail($id);

        // Delete files if exist
        if ($project->image_path) {
            Storage::disk('public')->delete($project->image_path);
        }

        if ($project->document_path) {
            Storage::disk('public')->delete($project->document_path);
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }
}