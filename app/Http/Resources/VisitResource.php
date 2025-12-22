<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
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
            'client_id' => $this->client_id,
            'user_id' => $this->user_id,
            'base_commerciale_id' => $this->base_commerciale_id,
            'zone_id' => $this->zone_id,
            'routing_item_id' => $this->routing_item_id,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'duration_seconds' => $this->duration_seconds,
            'status' => $this->status,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'termination_distance' => $this->termination_distance ? (float) $this->termination_distance : null,
            'terminated_outside_range' => $this->terminated_outside_range,
            'client' => $this->when($this->relationLoaded('client'), function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'type' => $this->client->type,
                    'city' => $this->client->city,
                ];
            }),
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'report' => $this->when($this->relationLoaded('report'), function () {
                return $this->report ? [
                    'id' => $this->report->id,
                    'notes' => $this->report->notes,
                ] : null;
            }),
            'photos' => $this->when($this->relationLoaded('photos'), function () {
                return $this->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => \Storage::url($photo->file_path),
                        'type' => $photo->type,
                        'title' => $photo->title,
                    ];
                });
            }, []),
            'alerts' => $this->when($this->relationLoaded('alerts'), function () {
                return $this->alerts->map(function ($alert) {
                    return [
                        'id' => $alert->id,
                        'type' => $alert->type,
                        'status' => $alert->status,
                    ];
                });
            }, []),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
