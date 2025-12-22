<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'visit_id' => $this->visit_id,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'manager_present' => $this->manager_present,
            'order_made' => $this->order_made,
            'needs_order' => $this->needs_order,
            'order_reference' => $this->order_reference,
            'order_estimated_amount' => $this->order_estimated_amount ? (float) $this->order_estimated_amount : null,
            'stock_issues' => $this->stock_issues,
            'stock_shortage_observed' => $this->stock_shortage_observed,
            'competitor_activity' => $this->competitor_activity,
            'competitor_activity_observed' => $this->competitor_activity_observed,
            'comments' => $this->comments,
            'validated_at' => $this->validated_at?->toIso8601String(),
            'is_validated' => $this->isValidated(),
            'photos' => $this->when($this->relationLoaded('photos'), function () {
                return $this->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => \Storage::url($photo->file_path),
                        'file_name' => $photo->file_name,
                        'type' => $photo->type,
                        'title' => $photo->title,
                        'description' => $photo->description,
                        'latitude' => $photo->latitude ? (float) $photo->latitude : null,
                        'longitude' => $photo->longitude ? (float) $photo->longitude : null,
                        'taken_at' => $photo->taken_at?->toIso8601String(),
                    ];
                });
            }, []),
            'visit' => $this->when($this->relationLoaded('visit'), function () {
                return [
                    'id' => $this->visit->id,
                    'client_id' => $this->visit->client_id,
                    'status' => $this->visit->status,
                    'started_at' => $this->visit->started_at?->toIso8601String(),
                    'ended_at' => $this->visit->ended_at?->toIso8601String(),
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
