<?php

namespace App\Console\Commands;

use App\Models\Fee;
use App\Services\Messaging\ParentNotifier;
use Illuminate\Console\Command;

class SendFeeReminders extends Command
{
    protected $signature = 'fees:remind {--days=3 : Also remind this many days before the due date}';

    protected $description = 'Remind parents of overdue and soon-due unpaid instalments';

    public function handle(ParentNotifier $notifier): int
    {
        $count = 0;

        Fee::with(['payments', 'student.parent'])
            ->where('due_date', '<=', today()->addDays((int) $this->option('days')))
            ->each(function (Fee $fee) use ($notifier, &$count) {
                if ((float) $fee->balance <= 0) {
                    return;
                }
                $when = $fee->due_date->isPast() ? 'متأخر' : "يستحق في {$fee->due_date->toDateString()}";
                if ($notifier->notify($fee->student, 'fee_reminder',
                    "تذكير: {$fee->title} للطالب {$fee->student->name} {$when}. المتبقي: {$fee->balance}")) {
                    $count++;
                }
            });

        $this->info("Sent {$count} reminders.");

        return self::SUCCESS;
    }
}
