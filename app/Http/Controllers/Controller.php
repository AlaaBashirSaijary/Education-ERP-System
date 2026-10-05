<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** Parents may only see their own children; staff roles see everyone. */
    protected function authorizeStudent(\Illuminate\Http\Request $request, \App\Models\Student $student): void
    {
        $user = $request->user();
        abort_if($user->hasRole('parent') && $student->parent_id !== $user->id, 403);
    }
}
