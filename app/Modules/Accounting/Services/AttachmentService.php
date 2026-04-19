<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    /**
     * Validate (via MIME), store, and persist an attachment record.
     *
     * @throws \RuntimeException if the physical store fails
     */
    public function store(
        UploadedFile $file,
        string       $entityType,
        int          $entityId,
        int          $companyId,
    ): Attachment {
        $fileType = $this->detectType($file);
        $dir      = "attachments/{$companyId}";

        $path = $file->store($dir, 'public');

        if ($path === false) {
            throw new \RuntimeException('فشل حفظ الملف على القرص.');
        }

        return Attachment::create([
            'company_id'  => $companyId,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'file_path'   => $path,
            'file_name'   => $file->getClientOriginalName(),
            'file_type'   => $fileType,
            'uploaded_by' => Auth::id(),
        ]);
    }

    /**
     * Delete the physical file and its database record.
     */
    public function delete(Attachment $attachment): void
    {
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
    }

    // -------------------------------------------------------------------------

    private function detectType(UploadedFile $file): string
    {
        $mime = $file->getMimeType() ?? '';

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if ($mime === 'application/pdf') {
            return 'pdf';
        }

        // xlsx, csv, ods, etc.
        return 'excel';
    }
}
