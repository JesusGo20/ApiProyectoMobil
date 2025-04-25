<?php
namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Events\TaskUpdated;
use Illuminate\Support\Facades\Http;


class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::all();

        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed',
            'project_id' => 'required|exists:projects,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $task = Task::create($request->all());


        return response()->json($task, 201);
    }

    public function show(Task $task)
    {
        return $task->load('project', 'user');
    }

    public function update(Request $request, Task $task)
    {
        $task->update($request->all());

        $playerId = $task?->user?->onesignal_id;

        $response = Http::withHeaders([
            'Authorization' => 'Key ' . env('ONESIGNAL_API_KEY'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://api.onesignal.com/notifications', [
            'app_id' => env('ONESIGNAL_APP_ID'), 
            'contents' => ['en' => "The task '{$task->title}' has been updated."],
            'headings' => ['en' => 'Task Updated'],
            'include_aliases' => ['onesignal_id' => [$playerId]],
            'target_channel' => "push" 
        ]);

        $result = $response->json();

        return response()->json([
            'task' => $task,
            'notification_result' => $result,
        ]);
    }

    public function destroy(Task $task)
    {
        $task->delete();


        return response()->json(null, 204);
    }

    public function assignUser(Request $request, Task $task)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $task->user_id = $request->user_id;
        $task->save();

        $playerId = $task?->user?->onesignal_id;

        $response = Http::withHeaders([
            'Authorization' => 'Key ' . env('ONESIGNAL_API_KEY'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://api.onesignal.com/notifications', [
            'app_id' => env('ONESIGNAL_APP_ID'), 
            'contents' => ['en' => "Fuiste asignado a la tarea'{$task->title}'."],
            'headings' => ['en' => 'Assigned task'],
            'include_aliases' => ['onesignal_id' => [$playerId]],
            'target_channel' => "push" 
        ]);

        $result = $response->json();

        return response()->json([
            'message' => 'User assigned to task successfully',
            'task' => $task,
            'notification_result' => $result,
        ], 200);
    }
}
