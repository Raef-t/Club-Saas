<?php

namespace Modules\Accounting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Accounting\Models\AccJournal;

class JournalEntryPosted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AccJournal $journal
    ) {}
}
