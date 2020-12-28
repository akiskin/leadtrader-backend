<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class SellCampaign extends JsonResource
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
            'leads_total' => $this->when(Arr::exists($this->resource,'leads_count'), $this->leads_count),
            'leads_sold' => 0,
            'leads_rejected' => 0,
            'earned' => 0.0,
        ];
    }
}
