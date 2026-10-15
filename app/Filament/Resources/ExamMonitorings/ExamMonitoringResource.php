<?php

namespace App\Filament\Resources\ExamMonitorings;

use App\Filament\Resources\ExamMonitorings\Pages\ListExamMonitorings;
use App\Filament\Resources\ExamMonitorings\Pages\ViewExamMonitoring;
use App\Models\ExamResult;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ExamMonitoringResource extends Resource
{
    protected static ?string $model = ExamResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Monitoring Nilai Ujian';

    protected static ?string $modelLabel = 'Hasil Ujian Siswa';

    protected static ?string $pluralModelLabel = 'Monitoring Nilai Ujian';

    protected static string|UnitEnum|null $navigationGroup = 'Ujian';

    protected static ?int $navigationSort = 2;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                View::make('filament.resources.exam-results.view-detail')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->latest('completed_at'))
            ->columns([
                TextColumn::make('student.nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->state(fn (ExamResult $record) => $record->student?->name ?? $record->user?->name ?? '-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    })
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('exam.title')
                    ->label('Ujian')
                    ->weight('semibold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attempt')
                    ->label('Percobaan')
                    ->formatStateUsing(fn ($state) => "Ke-{$state}")
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('grade')
                    ->label('Nilai Akhir')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 1))
                    ->badge()
                    ->color(fn (ExamResult $record) => $record->is_passed ? 'success' : 'danger')
                    ->weight('black')
                    ->sortable(),
                TextColumn::make('threshold')
                    ->label('KKM')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 1))
                    ->sortable(),
                TextColumn::make('is_passed')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'LULUS' : 'TIDAK LULUS')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                TextColumn::make('score_stats')
                    ->label('B / S / K')
                    ->tooltip('Benar / Salah / Kosong')
                    ->state(fn (ExamResult $record) => "{$record->correct_answers} / {$record->incorrect_answers} / {$record->unanswered}"),
                TextColumn::make('duration_seconds')
                    ->label('Durasi')
                    ->state(fn (ExamResult $record) => $record->formatted_duration),
                TextColumn::make('auto_submitted')
                    ->label('Metode Selesai')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Waktu Habis' : 'Mandiri')
                    ->color(fn ($state) => $state ? 'warning' : 'gray'),
                TextColumn::make('completed_at')
                    ->label('Waktu Selesai')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('exam_id')
                    ->label('Filter Ujian')
                    ->relationship('exam', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('is_passed')
                    ->label('Status Kelulusan')
                    ->options([
                        1 => 'Lulus KKM',
                        0 => 'Belum Lulus KKM',
                    ]),
                SelectFilter::make('auto_submitted')
                    ->label('Cara Selesai')
                    ->options([
                        1 => 'Waktu Habis (Otomatis)',
                        0 => 'Kumpul Mandiri',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Detail Jawaban')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('primary')
                    ->modalWidth('7xl'),
                DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus Hasil Ujian Siswa?')
                    ->modalDescription('Apakah Anda yakin ingin menghapus data hasil ujian ini? Siswa dapat diizinkan untuk mengulang jika hasil dihapus.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExamMonitorings::route('/'),
            'view' => ViewExamMonitoring::route('/{record}'),
        ];
    }
}
