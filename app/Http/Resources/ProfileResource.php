<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'avatar' => $this->avatar ? Storage::disk('public')->url($this->avatar) : null,
            'nick_name' => $this->nick_name,
            'full_name' => $this->full_name,
            'birthday' => $this->date_of_birthday,
            'interests' => $this->interests,
            'is_private' => $this->is_private,
            'user' => new UserResource($this->user),
        ];
    }
}
