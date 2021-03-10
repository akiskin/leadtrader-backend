<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientBalanceDetails extends JsonResource
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
            'transaction_id' => $this->transaction_id,
            'period' => $this->period,
            'client_id' => $this->client_id,
            'amount' => $this->amount,
            $this->mergeWhen($this->whenLoaded('transaction'), ['transaction' => [
                'id' => $this->transaction->id,
                'created_at' => $this->transaction->created_at,
                'lead_id' => $this->transaction->lead_id,
                'reference' => $this->transaction->reference,
                'type' => $this->transaction->type,
            ]])
        ];
    }
}
