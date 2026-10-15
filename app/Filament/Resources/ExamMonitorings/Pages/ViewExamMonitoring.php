<?php

namespace App\Filament\Resources\ExamMonitorings\Pages;

use App\Filament\Resources\ExamMonitorings\ExamMonitoringResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ViewExamMonitoring extends ViewRecord
{
    protected static string $resource = ExamMonitoringResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $studentName = $record->student?->name ?? $record->user?->name ?? 'Siswa';
        $examTitle = $record->exam?->title ?? 'Ujian';

        return "Detail Hasil Ujian: {$studentName} — {$examTitle}";
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();
        $date = $record->completed_at ? $record->completed_at->format('d M Y, H:i') : '-';
        $grade = number_format((float) $record->grade, 1);
        $status = $record->is_passed ? 'Lulus KKM' : 'Belum Lulus KKM';
        $attempt = $record->attempt ?? 1;

        return "Nilai: {$grade} ({$status}) • Percobaan ke-{$attempt} • Selesai: {$date}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali ke Monitoring')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(static::getResource()::getUrl('index')),

            Action::make('print')
                ->label('Cetak Hasil')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('info')
                ->extraAttributes([
                    'onclick' => 'window.print()',
                ]),

            DeleteAction::make()
                ->label('Hapus Hasil Ujian')
                ->modalHeading('Hapus Hasil Ujian Siswa?')
                ->modalDescription('Apakah Anda yakin ingin menghapus data hasil ujian ini? Siswa dapat diizinkan untuk mengulang jika hasil dihapus.'),
        ];
    }
}
