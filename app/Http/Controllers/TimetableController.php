<?php

namespace App\Http\Controllers;

use App\Models\TimetableEntry;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function forClass(int $classId)
    {
        return TimetableEntry::with(['subject:id,name', 'teacher:id,name'])
            ->where('school_class_id', $classId)
            ->orderBy('day_of_week')->orderBy('period')->get();
    }

    public function forTeacher(int $teacherId)
    {
        return TimetableEntry::with(['subject:id,name', 'schoolClass'])
            ->where('teacher_id', $teacherId)
            ->orderBy('day_of_week')->orderBy('period')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'day_of_week' => 'required|integer|between:0,6',
            'period' => 'required|integer|min:1',
            'starts_at' => 'required|date_format:H:i',
            'ends_at' => 'required|date_format:H:i|after:starts_at',
            'room' => 'nullable|string',
        ]);

        $slot = ['day_of_week' => $data['day_of_week'], 'period' => $data['period']];

        if (TimetableEntry::where($slot)->where('teacher_id', $data['teacher_id'])->exists()) {
            return response()->json(['message' => 'المعلم لديه حصة أخرى في نفس الوقت.'], 422);
        }
        if (TimetableEntry::where($slot)->where('school_class_id', $data['school_class_id'])->exists()) {
            return response()->json(['message' => 'الصف لديه حصة أخرى في نفس الوقت.'], 422);
        }

        return response()->json(TimetableEntry::create($data), 201);
    }
}
