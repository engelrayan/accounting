<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /**
     * Serve the file — inline for images, forced download for everything else.
     * Security: file must belong to the authenticated user's company.
     */
    public function show(Request $request, Attachment $attachment): Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless(
            $attachment->company_id === $request->user()->company_id,
            403,
            'ليس لديك صلاحية للوصول إلى هذا المرفق.'
        );

        $disk = Storage::disk('public');

        abort_unless($disk->exists($attachment->file_path), 404, 'الملف غير موجود.');

        $fullPath = $disk->path($attachment->file_path);
        $mime     = $disk->mimeType($attachment->file_path) ?: 'application/octet-stream';

        if ($attachment->isImage()) {
            return response()->file($fullPath, ['Content-Type' => $mime]);
        }

        return response()->download($fullPath, $attachment->file_name);
    }

    /**
     * Always force-download regardless of type.
     */
    public function download(Request $request, Attachment $attachment): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless(
            $attachment->company_id === $request->user()->company_id,
            403,
            'ليس لديك صلاحية للوصول إلى هذا المرفق.'
        );

        $disk = Storage::disk('public');
        abort_unless($disk->exists($attachment->file_path), 404, 'الملف غير موجود.');

        return response()->download(
            $disk->path($attachment->file_path),
            $attachment->file_name
        );
    }
}
