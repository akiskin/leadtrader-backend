<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class BuyCampaign extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request) +
        [
            'product' => Product::make($this->product),
            'leads_bought' => $this->when(Arr::exists($this->resource,'transactions_total'), $this->transactions_total),
            'budget_spent' => $this->whenLoaded('totals', fn() => $this->resource->totals->amount),
        ];
    }
}
