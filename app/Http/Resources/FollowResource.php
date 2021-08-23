<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FollowResource extends JsonResource
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
            'username' => $this->username,
               'email' => $this->email,
              'status' => $this->whenPivotLoaded('followers', function () {
                  return $this->pivot->is_approve ? 'approved' : 'pending';
            })
        ];
    }
}
