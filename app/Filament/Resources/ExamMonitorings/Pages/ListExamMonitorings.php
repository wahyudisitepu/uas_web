<?php

namespace App\Filament\Resources\ExamMonitorings\Pages;

use App\Filament\Resources\ExamMonitorings\ExamMonitoringResource;
use Filament\Resources\Pages\ListRecords;

class ListExamMonitorings extends ListRecords
{
    protected static string $resource = ExamMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
