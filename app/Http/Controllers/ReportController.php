<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function attendanceCsv(Request $request, ReportService $reports)
    {
        $d = $request->validate(['class' => 'nullable|integer', 'month' => 'required|date_format:Y-m']);
        $rows = $reports->attendanceSummary($d['class'] ?? null, $d['month']);

        return $this->csv("attendance-{$d['month']}.csv",
            [__('Student'), __('Class'), __('present'), __('late'), __('absent'), __('Attendance rate').' %'],
            $rows->map(fn ($r) => [$r['student'], $r['class'], $r['present'], $r['late'], $r['absent'], $r['rate']])->all());
    }

    public function financeCsv(ReportService $reports)
    {
        return $this->csv('finance-by-class.csv',
            [__('Class'), __('Billed'), __('Collected'), __('Outstanding'), __('Overdue')],
            $reports->financeByClass()->map(fn ($r) => [$r['class'], $r['billed'], $r['collected'], $r['outstanding'], $r['overdue']])->all());
    }

    public function receipt(Request $request, Payment $payment)
    {
        $payment->load('fee.student', 'fee.payments');
        $user = $request->user();
        abort_if($user->hasRole('teacher'), 403);
        abort_if($user->hasRole('parent') && $payment->fee->student->parent_id !== $user->id, 403);

        return view('receipt', ['payment' => $payment, 'fee' => $payment->fee, 'student' => $payment->fee->student,
            'receiver' => \App\Models\User::find($payment->received_by)]);
    }

    /** UTF-8 CSV with BOM (Excel-friendly for Arabic); cells starting with = + - @ are neutralised against formula injection. */
    private function csv(string $filename, array $header, array $rows)
    {
        $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;

        return response()->streamDownload(function () use ($header, $rows, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map($safe, $header));
            foreach ($rows as $row) {
                fputcsv($out, array_map($safe, $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
