<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'name' => $this->name,
            'type' => $this->type,
            'manager_name' => $this->manager_name,
            'email' => $this->email,
            'phones' => array_filter([$this->phone, $this->whatsapp]),
            'city' => $this->city,
            'district' => $this->district,
            'address' => $this->address_description,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'zone_id' => $this->zone_id,
            'commercial_id' => $this->created_by,
            'potential' => $this->potential,
            'visit_frequency' => $this->visit_frequency,
            'last_visit_date' => $this->last_visit_date?->format('Y-m-d'),
            'has_open_alert' => $this->has_open_alert,
            'photos' => $this->when($this->relationLoaded('photos'), function () {
                return $this->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => \Storage::url($photo->file_path),
                        'file_name' => $photo->file_name,
                        'type' => $photo->type,
                        'title' => $photo->title,
                        'description' => $photo->description,
                        'taken_at' => $photo->taken_at?->toIso8601String(),
                    ];
                });
            }, []),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
