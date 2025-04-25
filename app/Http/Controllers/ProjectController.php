<?php
namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Events\ProjectUpdated;
use Illuminate\Support\Facades\Http;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::all();

        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create($request->all());


        return response()->json($project, 201);
    }

    public function show(Project $project)
    {
        return $project;
    }

    public function update(Request $request, Project $project)
    {
        $project->update($request->all());

        $task = $project->tasks()->first();
        $playerId = $task?->user?->onesignal_id;

        $response = Http::withHeaders([
            'Authorization' => 'Key ' . env('ONESIGNAL_API_KEY'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://api.onesignal.com/notifications', [
            'app_id' => env('ONESIGNAL_APP_ID'), 
            'contents' => ['en' => "The project '{$project->name}' has been updated."],
            'headings' => ['en' => 'Project Updated'],
            'include_aliases' => ['onesignal_id' => [$playerId]],
            'target_channel' => "push" 
        ]);

   
        $result = $response->json();

        return response()->json([
            'project' => $project,
            'notification_result' => $result,
        ]);
    }

    public function destroy(Project $project)
    {
        $projectId = $project->id;
        $project->delete();
    
        return response()->json(null, 204);
    }
}
