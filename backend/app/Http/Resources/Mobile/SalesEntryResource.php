<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'employee_id' => $this->employee_id,
            'date' => optional($this->date)->toDateString(),
            'amount' => (float) $this->amount,
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}
