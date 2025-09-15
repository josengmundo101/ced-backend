<?php
namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index()
    {
        return response()->json(Project::with('creator')->get());
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'project_id'=>'required|string|unique:projects,project_id',
            'project_name'=>'required|string',
            // ... add other validations you want
        ]);

        $v['created_by'] = Auth::id();
        $project = Project::create($v);

        return response()->json(['message'=>'Project created','project'=>$project],201);
    }

    public function show($id)
    {
        return response()->json(Project::with('creator')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $v = $request->validate([
            'project_name'=>'sometimes|string',
            'status'=>'sometimes|in:ongoing,completed,terminated',
            // ... other fields
        ]);

        $project->update($v);
        return response()->json(['message'=>'Project updated','project'=>$project]);
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();
        return response()->json(['message'=>'Project deleted']);
    }
}