<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\HistoricalMemorandum;
use App\Models\LampiranMemo;

class FileAccessController extends Controller
{
    public function memorandum(string $filename)
    {
        if (!FileHelper::isSafeFileName($filename)) {
            abort(404);
        }

        if (!HistoricalMemorandum::where('memorandum_file', $filename)->exists()) {
            abort(404);
        }

        return $this->stream(public_path('historical_memorandum/'.$filename));
    }

    public function lampiran(string $filename)
    {
        if (!FileHelper::isSafeFileName($filename)) {
            abort(404);
        }

        if (!LampiranMemo::where('lampiran_memo', $filename)->exists()) {
            abort(404);
        }

        return $this->stream(public_path('historical_memorandum/lampiran/'.$filename));
    }

    public function memorandumZip()
    {
        return $this->stream(public_path('file_historical_memorandum.zip'));
    }

    public function lampiranZip()
    {
        return $this->stream(public_path('file_lampiran_memo.zip'));
    }

    private function stream(string $path)
    {
        if (!is_file($path)) {
            abort(404);
        }

        $response = response()->file($path, ['Cache-Control' => 'private, no-store']);
        $response->setPrivate()->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
