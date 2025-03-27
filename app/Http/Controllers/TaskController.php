<?php
namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return Task::with('project', 'user')->get();
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
        return response()->json($task);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(null, 204);
    }

    public function assignUser(Request $request, Task $task)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id', // Validar que el usuario exista
        ]);

        $task->user_id = $request->user_id;
        $task->save();

        return response()->json([
            'message' => 'User assigned to task successfully',
            'task' => $task,
        ], 200);
    }
}
