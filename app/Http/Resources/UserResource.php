<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'firstname' => $this->first_name,
            'lastname' => $this->last_name,
            'email' => $this->email,
            'role_id' => $this->role_id, // Keeping this if you still want to show the role ID
            'role_libelle' => $this->whenLoaded('role', function () {
                return $this->role->libelle; // Directly access the Libelle property
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
