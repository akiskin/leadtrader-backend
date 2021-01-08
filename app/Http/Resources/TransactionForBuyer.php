<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionForBuyer extends JsonResource
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
            'reference' => $this->reference,
            'created_at' => $this->created_at,
            'total_price' => $this->amounts['buyer_total'],
            'commission' => $this->amounts['buyer_commission'],
            $this->mergeWhen($this->whenLoaded('lead'), ['lead' => [
                'id' => $this->lead->id,
                'created_at' => $this->lead->created_at
            ]])
        ];
    }
}
