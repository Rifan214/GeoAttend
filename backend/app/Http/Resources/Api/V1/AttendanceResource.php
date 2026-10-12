<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusValue = isset($this->evaluated_status)
            ? $this->evaluated_status->value
            : (is_object($this->status) ? $this->status->value : (string) $this->status);

        return [
            'id' => $this->id,
            'attendance_date' => $this->attendance_date instanceof Carbon ? $this->attendance_date->toDateString() : (string) $this->attendance_date,
            'status' => $statusValue,
            'check_in_at' => $this->check_in_at?->toIso8601String(),
            'check_in_latitude' => $this->check_in_latitude !== null ? (float) $this->check_in_latitude : null,
            'check_in_longitude' => $this->check_in_longitude !== null ? (float) $this->check_in_longitude : null,
            'check_in_accuracy_meters' => $this->check_in_accuracy_meters !== null ? (float) $this->check_in_accuracy_meters : null,
            'check_in_distance_meters' => $this->check_in_distance_meters !== null ? (float) $this->check_in_distance_meters : null,
            'check_out_at' => $this->check_out_at?->toIso8601String(),
            'check_out_latitude' => $this->check_out_latitude !== null ? (float) $this->check_out_latitude : null,
            'check_out_longitude' => $this->check_out_longitude !== null ? (float) $this->check_out_longitude : null,
            'check_out_accuracy_meters' => $this->check_out_accuracy_meters !== null ? (float) $this->check_out_accuracy_meters : null,
            'check_out_distance_meters' => $this->check_out_distance_meters !== null ? (float) $this->check_out_distance_meters : null,
            'work_location' => $this->workLocation ? [
                'id' => $this->workLocation->id,
                'name' => $this->workLocation->name,
                'address' => $this->workLocation->address,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
