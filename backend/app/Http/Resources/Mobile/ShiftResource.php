<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    private function hhmm(mixed $raw): string
    {
        if ($raw === null) {
            return '';
        }

        $value = (string) $raw;
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable) {
            if (preg_match('/(\d{1,2}:\d{2})/', $value, $matches) === 1) {
                $parts = explode(':', $matches[1]);
                return str_pad((string) ((int) $parts[0]), 2, '0', STR_PAD_LEFT).':'.$parts[1];
            }

            return '';
        }
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'start_time' => $this->hhmm($this->start_time),
            'end_time' => $this->hhmm($this->end_time),
        ];
    }
}
