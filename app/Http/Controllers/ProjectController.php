<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    /**
     * Display all projects
     */
    public function index()
    {
        $projects = Project::with('creator')->latest()->get();

        return response()->json(
            $projects->map(fn($project) => $this->transformProject($project))
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
            'status'          => 'nullable|in:ongoing,completed,terminated,suspended',
            'remarks'         => ['nullable', function ($attribute, $value, $fail) use ($request) {
                if ($request->status === 'suspended' && empty($value)) {
                    $fail('Remarks are required when the project is suspended.');
                }
            }],
            'amount'          => 'nullable|numeric',
            'revised_amount'  => 'nullable|numeric',
            'contract_id'     => 'nullable|string|max:255',
            'category'        => 'nullable|string|max:255',
            'region'          => 'nullable|string|max:255',
            'lgu'             => 'nullable|string|max:255',
            'department'      => 'nullable|string|max:255',
            'implementing_office' => 'nullable|string|max:255',
            'fund_source'     => 'nullable|string|max:255',
            'implementation_type' => 'nullable|string|max:255',
            'contractor'      => 'nullable|string|max:255',
            'project_engineer' => 'nullable|string|max:255',
            'year_implemented' => 'nullable|integer',
            'location'        => 'nullable|string|max:255',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date',

            // Files
            'image.*'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'document.*'      => 'nullable|file|mimes:pdf,doc,docx,xlsx|max:5120',
        ]);

        // Use validated data
        $data = $v;
        $data['created_by'] = Auth::id();

        /** AUTO-SET end date when completed */
        if ($request->status === 'completed') {
            $data['actual_end_date'] = now()->format('Y-m-d');
        }

        /** PROCESS IMAGES */
        $imagePaths = [];
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $file) {
                $imagePaths[] = $file->store('projects/images', 'public');
            }
        }
        $data['image_path'] = $imagePaths ? json_encode($imagePaths) : null;

        /** PROCESS DOCUMENTS */
        $documentPaths = [];
        if ($request->hasFile('document')) {
            foreach ($request->file('document') as $file) {
                $documentPaths[] = $file->store('projects/documents', 'public');
            }
        }
        $data['document_path'] = $documentPaths ? json_encode($documentPaths) : null;

        /** CREATE PROJECT */
        $project = Project::create($data);

        return response()->json([
            'message' => 'Project created',
            'project' => $this->transformProject($project->fresh('creator')),
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
     * Update a project
     */
    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $v = $request->validate([
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

            'status' => 'sometimes|in:ongoing,completed,terminated,suspended',
            'remarks' => ['nullable', function ($attribute, $value, $fail) use ($request) {
                if ($request->status === 'suspended' && empty($value)) {
                    $fail('Remarks are required when the project is suspended.');
                }
            }],

            'image.*'              => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'document.*'           => 'nullable|file|mimes:pdf,doc,docx,xlsx|max:5120',
        ]);

        $data = $v;

        /** AUTO-HANDLE actual_end_date */
        if ($request->status === 'completed') {
            if (!$project->actual_end_date) {
                $data['actual_end_date'] = now()->format('Y-m-d');
            }
        } else {
            $data['actual_end_date'] = null;
        }

        /** REPLACE IMAGES */
        if ($request->hasFile('image')) {
            if ($project->image_path) {
                foreach (json_decode($project->image_path, true) as $old) {
                    Storage::disk('public')->delete($old);
                }
            }
            $newImages = [];
            foreach ($request->file('image') as $file) {
                $newImages[] = $file->store('projects/images', 'public');
            }
            $data['image_path'] = json_encode($newImages);
        }

        /** REPLACE DOCUMENTS */
        if ($request->hasFile('document')) {
            if ($project->document_path) {
                foreach (json_decode($project->document_path, true) as $old) {
                    Storage::disk('public')->delete($old);
                }
            }
            $newDocs = [];
            foreach ($request->file('document') as $file) {
                $newDocs[] = $file->store('projects/documents', 'public');
            }
            $data['document_path'] = json_encode($newDocs);
        }

        $project->update($data);

        return response()->json([
            'message' => 'Project updated successfully',
            'project' => $this->transformProject($project->fresh('creator')),
        ]);
    }

    /**
     * Delete a project
     */
    public function destroy($id)
    {
        $project = Project::findOrFail($id);

        if ($project->image_path) {
            foreach (json_decode($project->image_path, true) as $old) {
                Storage::disk('public')->delete($old);
            }
        }

        if ($project->document_path) {
            foreach (json_decode($project->document_path, true) as $old) {
                Storage::disk('public')->delete($old);
            }
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }

    /**
     * Transform URLs for images & documents
     */
    private function transformProject($project)
    {
        $project->image_urls = $project->image_path
            ? collect(json_decode($project->image_path, true))->map(fn($p) => url(Storage::url($p)))
            : [];

        $project->document_urls = $project->document_path
            ? collect(json_decode($project->document_path, true))->map(fn($p) => url(Storage::url($p)))
            : [];

        return $project;
    }
}