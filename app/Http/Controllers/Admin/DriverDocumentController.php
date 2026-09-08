<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriverDocument;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DriverDocumentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:drivers,write'),
        ];
    }

    public function approve(string $id, string $docId)
    {
        $document = DriverDocument::where('driver_id', $id)->findOrFail($docId);

        $document->update([
            'status' => 'approved',
            'rejection_reason' => null,
            'verified_at' => now(),
            'verified_by' => auth('admin')->id(),
        ]);

        return $this->respond('Document approved.', $document);
    }

    public function reject(Request $request, string $id, string $docId)
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $document = DriverDocument::where('driver_id', $id)->findOrFail($docId);

        $document->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'verified_at' => now(),
            'verified_by' => auth('admin')->id(),
        ]);

        // Inform the driver of the rejection (in-app + push).
        app(\App\Services\NotificationService::class)->sendPush('driver', (int) $id, 'Document rejected',
            ucfirst(str_replace('_', ' ', $document->type)) . ' reject হয়েছে। কারণ: ' . $request->reason, 'document_expiry');

        return $this->respond('Document rejected.', $document);
    }

    // Return JSON for AJAX requests, otherwise redirect back.
    private function respond(string $message, DriverDocument $document)
    {
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $document->status,
                'reason' => $document->rejection_reason,
            ]);
        }

        return back()->with('success', $message);
    }
}
