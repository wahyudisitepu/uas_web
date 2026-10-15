<?php

namespace App\Filament\Test\Resources\ExamHistories\Pages;

use App\Filament\Test\Resources\ExamHistories\ExamHistoryResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewExamHistory extends ViewRecord
{
    protected static string $resource = ExamHistoryResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
