<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaySlipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'month' => $this->month,
            'file_path' => $this->file_path,
            'generated_at' => optional($this->generated_at)?->toDateTimeString(),
            'download_url' => route('mobile.pay-slips.download', ['id' => $this->id]),
        ];
    }
}
