<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;

/** Basic setup data: classes, subjects, students. */
class RosterController extends Controller
{
    public function storeClass(Request $request)
    {
        $data = $request->validate(['name' => 'required|string', 'section' => 'nullable|string']);

        return response()->json(SchoolClass::create($data), 201);
    }

    public function storeSubject(Request $request)
    {
        $data = $request->validate(['name' => 'required|string', 'code' => 'required|string|unique:subjects,code']);

        return response()->json(Subject::create($data), 201);
    }

    public function students(Request $request)
    {
        $q = Student::with('schoolClass')->orderBy('name');
        if ($request->user()->hasRole('parent')) {
            $q->where('parent_id', $request->user()->id);
        }

        return $q->paginate(50);
    }

    public function storeStudent(Request $request)
    {
        $data = $request->validate([
            'student_no' => 'required|string|unique:students,student_no',
            'name' => 'required|string',
            'school_class_id' => 'required|exists:school_classes,id',
            'parent_id' => 'nullable|exists:users,id',
            'parent_phone' => 'nullable|string',
            'fingerprint_id' => 'nullable|string|unique:students,fingerprint_id',
        ]);

        return response()->json(Student::create($data), 201);
    }

    /** Printable QR (SVG) for the student's ID card. */
    public function qr(Student $student)
    {
        $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(300)->generate($student->qr_token);

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}
