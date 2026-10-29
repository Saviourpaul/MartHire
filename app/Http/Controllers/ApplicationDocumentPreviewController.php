<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDocument;
use App\Services\ApplicationDocumentFileService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationDocumentPreviewController extends Controller
{
    public function __invoke(
        Request $request,
        ApplicationDocument $applicationDocument,
        ApplicationDocumentFileService $documents
    ): StreamedResponse {
        $documents->authorize($request->user(), $applicationDocument);

        $disk = $documents->diskFor($applicationDocument);
        $documents->assertPreviewable($applicationDocument, $disk);
        $documents->logView($request->user(), $applicationDocument, 'preview');

        return $disk->response(
            $applicationDocument->file_path,
            $applicationDocument->original_name,
            $documents->responseHeaders($applicationDocument, $disk),
            'inline'
        );
    }
}
