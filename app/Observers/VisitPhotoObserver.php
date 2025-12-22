<?php

namespace App\Observers;

use App\Models\VisitPhoto;
use Illuminate\Support\Facades\Storage;

class VisitPhotoObserver
{
    /**
     * Handle the VisitPhoto "creating" event.
     */
    public function creating(VisitPhoto $visitPhoto): void
    {
        // Set default values if not set
        if (!$visitPhoto->taken_at) {
            $visitPhoto->taken_at = now();
        }

        if (!$visitPhoto->latitude) {
            $visitPhoto->latitude = 0;
        }

        if (!$visitPhoto->longitude) {
            $visitPhoto->longitude = 0;
        }

        // If file_path is set but metadata is missing, try to populate it
        if ($visitPhoto->file_path && !$visitPhoto->file_name) {
            // Try to get file info from storage
            $fullPath = storage_path('app/public/' . $visitPhoto->file_path);

            if (file_exists($fullPath)) {
                if (!$visitPhoto->file_name) {
                    $visitPhoto->file_name = basename($visitPhoto->file_path);
                }

                if (!$visitPhoto->mime_type) {
                    $visitPhoto->mime_type = mime_content_type($fullPath);
                }

                if (!$visitPhoto->file_size) {
                    $visitPhoto->file_size = filesize($fullPath);
                }
            }
        }
    }

    /**
     * Handle the VisitPhoto "updating" event.
     */
    public function updating(VisitPhoto $visitPhoto): void
    {
        // If file_path changed and metadata is missing, populate it
        if ($visitPhoto->isDirty('file_path') && $visitPhoto->file_path && !$visitPhoto->file_name) {
            $fullPath = storage_path('app/public/' . $visitPhoto->file_path);

            if (file_exists($fullPath)) {
                if (!$visitPhoto->file_name) {
                    $visitPhoto->file_name = basename($visitPhoto->file_path);
                }

                if (!$visitPhoto->mime_type) {
                    $visitPhoto->mime_type = mime_content_type($fullPath);
                }

                if (!$visitPhoto->file_size) {
                    $visitPhoto->file_size = filesize($fullPath);
                }
            }
        }
    }
}
