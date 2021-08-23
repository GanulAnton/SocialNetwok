<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


use Illuminate\Support\Facades\Storage;

class PostResource extends JsonResource
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
            'text' => $this->text,
            'attaches' => AttachResource::collection($this->attaches),
            'user' => new UserResource($this->user),
            'likes_count' => $this->likes_count,
            $this->mergeWhen($this->relationLoaded('likes'), [
                'is_liked' => $this->likes->where('id', $request->user()->id)->isNotEmpty() ? true : false,
            ]),
        ];
    }
}
