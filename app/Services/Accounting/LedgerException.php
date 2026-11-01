<?php

namespace App\Services\Accounting;

use RuntimeException;

/** Batch 43: a posting the ledger refused — the message is safe to show. */
class LedgerException extends RuntimeException
{
}
