<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadForSeller extends JsonResource
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
            'created_at' => $this->created_at,
            'status' => $this->status,


            $this->mergeWhen($this->whenLoaded('transactions') && ($this->transaction), function() { return ['transaction' => [
                //'id' => $this->transaction->id,
                'created_at' => $this->transaction->created_at,
                'price' => $this->transaction->amounts['price'],
                'commission' => $this->transaction->amounts['seller_commission'],
            ]];})

        ];
    }
}
