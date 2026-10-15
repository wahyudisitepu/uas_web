<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    protected $guarded = [];

    protected $casts = [
        'attempt' => 'integer',
        'is_passed' => 'boolean',
        'auto_submitted' => 'boolean',
        'answers_payload' => 'array',
        'result_details' => 'array',
        'completed_at' => 'datetime',
        'total_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'grade' => 'decimal:2',
        'threshold' => 'decimal:2',
    ];

    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->duration_seconds ?? 0;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d jam %d menit %d dtk', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%d menit %d dtk', $minutes, $remainingSeconds);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
