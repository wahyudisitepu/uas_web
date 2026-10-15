<?php

namespace App\Filament\Test\Resources\ExamHistories;

use App\Filament\Test\Resources\ExamHistories\Pages\ListExamHistories;
use App\Filament\Test\Resources\ExamHistories\Pages\ViewExamHistory;
use App\Models\ExamResult;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExamHistoryResource extends Resource
{
    protected static ?string $model = ExamResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Riwayat Ujian';

    protected static ?string $modelLabel = 'Riwayat Ujian';

    protected static ?string $pluralModelLabel = 'Riwayat Ujian';

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
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('user_id', auth()->id())
                    ->latest('completed_at');
            })
            ->columns([
                TextColumn::make('exam.title')
                    ->label('Ujian')
                    ->weight('bold')
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
                    ->weight('bold')
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
                TextColumn::make('summary')
                    ->label('Benar / Salah / Kosong')
                    ->state(fn (ExamResult $record) => "{$record->correct_answers} / {$record->incorrect_answers} / {$record->unanswered}"),
                TextColumn::make('duration_seconds')
                    ->label('Durasi Pengerjaan')
                    ->state(fn (ExamResult $record) => $record->formatted_duration),
                TextColumn::make('completed_at')
                    ->label('Waktu Selesai')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat Detail')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('primary')
                    ->modalWidth('7xl'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExamHistories::route('/'),
            'view' => ViewExamHistory::route('/{record}'),
        ];
    }
}
