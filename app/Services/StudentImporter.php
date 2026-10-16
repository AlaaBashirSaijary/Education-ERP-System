<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-load students from a CSV exported by Excel / Google Sheets.
 * Columns (English or Arabic headings, any order): student number, name, class, section, parent phone.
 * Only name and class are required; missing student numbers are generated.
 */
class StudentImporter
{
    private const ALIASES = [
        'student_no' => ['studentno', 'studentnumber', 'number', 'no', 'id', 'الرقم', 'رقمالطالب', 'الرقمالمدرسي', 'رقمالتسجيل', 'رقم'],
        'name' => ['name', 'fullname', 'studentname', 'الاسم', 'اسمالطالب', 'اسم'],
        'class' => ['class', 'grade', 'classname', 'الصف', 'الفصل'],
        'section' => ['section', 'division', 'الشعبة', 'القسم'],
        'parent_phone' => ['parentphone', 'phone', 'mobile', 'whatsapp', 'هاتفولیالأمر', 'هاتفولي الأمر', 'هاتفولیالامر', 'هاتفولي', 'هاتفولىالأمر', 'الهاتف', 'رقمالهاتف', 'الجوال', 'رقمالجوال', 'هاتف'],
    ];

    public const MAX_ROWS = 5000;

    /**
     * @return array{rows: array<int,array>, missing: array<int,string>, total: int}
     *   Each row: line, status (new|update|skip|error), message, data[student_no,name,class,section,parent_phone]
     */
    public function analyse(string $path, bool $updateExisting = false, bool $createClasses = false): array
    {
        $table = $this->readTable($path);
        $header = array_shift($table) ?? [];
        $map = $this->mapColumns($header);

        $missing = array_values(array_diff(['name', 'class'], array_keys($map)));
        if ($missing) {
            return ['rows' => [], 'missing' => $missing, 'total' => 0];
        }

        $classes = SchoolClass::all()->mapWithKeys(fn ($c) => [$this->norm(trim($c->name.' '.$c->section)) => $c, $this->norm($c->name).'|'.$this->norm((string) $c->section) => $c]);
        $existing = Student::pluck('id', 'student_no');
        $seenNo = [];
        $nextNo = $this->nextNumber();
        $rows = [];

        foreach (array_slice($table, 0, self::MAX_ROWS) as $i => $cells) {
            $line = $i + 2; // header is line 1
            $get = fn (string $k) => isset($map[$k]) ? trim((string) ($cells[$map[$k]] ?? '')) : '';
            if (! array_filter($cells, fn ($c) => trim((string) $c) !== '')) {
                continue; // blank line
            }

            $d = ['student_no' => $get('student_no'), 'name' => $get('name'), 'class' => $get('class'), 'section' => $get('section'), 'parent_phone' => $get('parent_phone')];
            $status = null;
            $msg = null;

            if ($d['name'] === '') {
                [$status, $msg] = ['error', __('Name is empty')];
            } elseif ($d['class'] === '') {
                [$status, $msg] = ['error', __('Class is empty')];
            }

            $class = null;
            if (! $status) {
                $class = $classes[$this->norm($d['class']).'|'.$this->norm($d['section'])]
                    ?? $classes[$this->norm(trim($d['class'].' '.$d['section']))]
                    ?? ($d['section'] === '' ? $classes[$this->norm($d['class'])] ?? null : null);
                if (! $class && ! $createClasses) {
                    [$status, $msg] = ['error', __('Unknown class: :c', ['c' => trim($d['class'].' '.$d['section'])])];
                }
            }

            $d['class_id'] = $class?->id;

            if (! $status && $d['parent_phone'] !== '') {
                $phone = PhoneNumber::normalize($d['parent_phone']);
                if (! PhoneNumber::isValid($phone)) {
                    [$status, $msg] = ['error', __('Invalid phone: :p', ['p' => $d['parent_phone']])];
                } else {
                    $d['parent_phone'] = $phone;
                }
            }

            if (! $status) {
                if ($d['student_no'] === '') {
                    $d['student_no'] = 'S'.$nextNo++;
                }
                if (isset($seenNo[$d['student_no']])) {
                    [$status, $msg] = ['error', __('Number :n is repeated in the file (line :l)', ['n' => $d['student_no'], 'l' => $seenNo[$d['student_no']]])];
                } elseif (isset($existing[$d['student_no']])) {
                    [$status, $msg] = $updateExisting ? ['update', __('Exists: will be updated')] : ['skip', __('Exists: skipped')];
                } else {
                    $status = 'new';
                }
                $seenNo[$d['student_no']] ??= $line;
            }

            $rows[] = ['line' => $line, 'status' => $status, 'message' => $msg, 'data' => $d];
        }

        return ['rows' => $rows, 'missing' => [], 'total' => count($rows)];
    }

    /** Writes the "new" (and "update") rows in one transaction. Returns counts. */
    public function commit(array $rows, bool $createClasses = false): array
    {
        $n = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        DB::transaction(function () use ($rows, $createClasses, &$n) {
            foreach ($rows as $r) {
                if ($r['status'] === 'error') { $n['errors']++; continue; }
                if ($r['status'] === 'skip') { $n['skipped']++; continue; }

                $d = $r['data'];
                $section = $d['section'] !== '' ? $d['section'] : null;
                $class = $d['class_id'] ? SchoolClass::find($d['class_id'])
                    : ($createClasses ? SchoolClass::firstOrCreate(['name' => $d['class'], 'section' => $section]) : null);
                if (! $class) { $n['errors']++; continue; }

                $attrs = ['name' => $d['name'], 'school_class_id' => $class->id, 'parent_phone' => $d['parent_phone'] ?: null];
                if ($r['status'] === 'update') {
                    Student::where('student_no', $d['student_no'])->first()?->update($attrs);
                    $n['updated']++;
                } else {
                    Student::create($attrs + ['student_no' => $d['student_no']]);
                    $n['created']++;
                }
            }
        });

        return $n;
    }

    public static function template(): string
    {
        $lines = [
            ['student_no', 'name', 'class', 'section', 'parent_phone'],
            ['S2001', 'Sara Ahmad', 'Grade 5', 'A', '+962791234567'],
            ['S2002', 'Omar Hassan', 'Grade 5', 'B', '0792345678'],
        ];

        return "\xEF\xBB\xBF".implode("\r\n", array_map(fn ($l) => implode(',', $l), $lines))."\r\n";
    }

    /** @return array<int,array<int,string>> */
    private function readTable(string $path): array
    {
        $raw = (string) file_get_contents($path);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        if (! mb_check_encoding($raw, 'UTF-8')) {
            // Arabic Excel "CSV" saved without UTF-8 (mbstring has no CP1256, iconv does).
            $raw = function_exists('iconv') ? (iconv('CP1256', 'UTF-8//IGNORE', $raw) ?: $raw) : $raw;
        }

        $first = strtok($raw, "\n") ?: '';
        $delims = [',' => substr_count($first, ','), ';' => substr_count($first, ';'), "\t" => substr_count($first, "\t")];
        arsort($delims);
        $delimiter = array_key_first($delims);

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $raw);
        rewind($fh);
        $rows = [];
        while (($cells = fgetcsv($fh, 0, $delimiter, '"', '')) !== false) {
            $rows[] = $cells;
        }
        fclose($fh);

        return $rows;
    }

    /** @return array<string,int> field => column index */
    private function mapColumns(array $header): array
    {
        $map = [];
        foreach ($header as $i => $h) {
            $key = $this->norm((string) $h);
            foreach (self::ALIASES as $field => $aliases) {
                if (! isset($map[$field]) && in_array($key, array_map(fn ($a) => $this->norm($a), $aliases), true)) {
                    $map[$field] = $i;
                }
            }
        }

        return $map;
    }

    /** Lower-case, no spaces/underscores/dashes, أإآ → ا, ى → ي, ة → ه, no diacritics. */
    private function norm(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/[\x{064B}-\x{065F}\x{0640}]/u', '', $s);
        $s = strtr($s, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ی' => 'ي', 'ة' => 'ه']);

        return preg_replace('/[\s_\-.]+/u', '', $s);
    }

    private function nextNumber(): int
    {
        $max = 1000;
        foreach (Student::pluck('student_no') as $no) {
            if (preg_match('/(\d+)$/', (string) $no, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $max + 1;
    }
}
