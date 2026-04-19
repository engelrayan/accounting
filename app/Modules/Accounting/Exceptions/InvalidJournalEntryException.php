<?php

namespace App\Modules\Accounting\Exceptions;

use RuntimeException;

class InvalidJournalEntryException extends RuntimeException
{
    // --- Creation errors ---

    public static function unbalanced(string $totalDebit, string $totalCredit): self
    {
        return new self(
            "Journal entry is not balanced. Total debit: {$totalDebit}, Total credit: {$totalCredit}."
        );
    }

    public static function tooFewLines(int $count): self
    {
        return new self(
            "A journal entry requires at least 2 lines. {$count} provided."
        );
    }

    public static function invalidLine(int $index): self
    {
        return new self(
            "Line [{$index}] must have either a debit or a credit amount, not both and not zero."
        );
    }

    public static function foreignAccount(int $accountId, int $companyId): self
    {
        return new self(
            "Account [{$accountId}] does not belong to company [{$companyId}]."
        );
    }

    // --- Posting errors ---

    public static function alreadyPosted(string $entryNumber): self
    {
        return new self("Entry [{$entryNumber}] is already posted.");
    }

    public static function notDraft(string $entryNumber): self
    {
        return new self("Only draft entries can be posted. Entry [{$entryNumber}] is not a draft.");
    }

    // --- Reversal errors ---

    public static function notPostable(string $entryNumber): self
    {
        return new self("Only posted entries can be reversed. Entry [{$entryNumber}] is not posted.");
    }

    public static function alreadyReversed(string $entryNumber): self
    {
        return new self("Entry [{$entryNumber}] has already been reversed.");
    }
}
