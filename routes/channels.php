<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('projects.{projectId}', function ($user, $projectId) {
    return $user->tasks()->where('project_id', $projectId)->exists();
    //return true;
});