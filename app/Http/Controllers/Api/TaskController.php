<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Task;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tasks = auth()->user()->tasks;
        if ($tasks == null) {
            return response()->json(['message'=> "No task found"] );
        }

        $taskData = [];

        foreach ($tasks as $task) {
            $taskData[] = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'completed' => $task->completed ? true : false,
            ];
        }

        return response()->json(['status' => "success", 'data' => $taskData], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = validator($request->all(), [
            "title" => "required|max:255|string",
            "description" => "nullable|string",
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        DB::beginTransaction();

        try {
            $validatedData = $request->all();
            $validatedData['user_id'] = auth()->id();
            $task = Task::create($validatedData);
            DB::commit();
            return response()->json(['status' => "success", 'message' => "Task created successfully", 'data' => [
                'id'=> $task->id,
                'title'=> $task->title,
                'description'=> $task->description,
                'completed'=>$task->completed? true : false,
            ]], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $task = Task::findOrFail($id);
        return response()->json(['data' => $task]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = validator($request->all(), [
            "title" => "nullable|max:255|string",
            "description" => "nullable|string",
            "completed" => "boolean",
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        //dd($request->all());

        $task = Task::findOrFail($id);
        if ($task == null) {
            return response()->json(['message' => "No task found"]);
        }

        $task->update($request->only(['title', 'description', 'completed']));
        return response()->json(['status' => "success", 'message' => "Task updated successfully", 'data' => [
            'id'=> $task->id,
            'title'=> $task->title,
            'description'=> $task->description,
            'completed'=>$task->completed? true : false,
        ]], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return response()->json(null, 204);
    }
}
