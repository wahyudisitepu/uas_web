<?php

namespace App\Filament\Resources\Exams\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul Ujian')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('subjects_summary')
                    ->label('Pelajaran')
                    ->state(function ($record) {
                        $subjects = $record->subjects;
                        if ($subjects->isEmpty()) {
                            return 'Belum Ditentukan';
                        }
                        return $subjects->pluck('name')->join(', ');
                    })
                    ->badge()
                    ->color(fn ($record) => $record->subjects->isEmpty() ? 'danger' : 'info')
                    ->wrap(),
                TextColumn::make('total_questions')
                    ->label('Total Soal Siap')
                    ->state(function ($record) {
                        $total = 0;
                        foreach ($record->subjects as $s) {
                            $activeCount = $s->questions()->where('is_active', true)->count();
                            $take = min((int) ($s->pivot->qty ?? 0), $activeCount);
                            $total += $take;
                        }
                        return $total > 0 ? "{$total} Soal" : '0 Soal (Kosong)';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $total = 0;
                        foreach ($record->subjects as $s) {
                            $activeCount = $s->questions()->where('is_active', true)->count();
                            $take = min((int) ($s->pivot->qty ?? 0), $activeCount);
                            $total += $take;
                        }
                        return $total > 0 ? 'success' : 'danger';
                    }),
                TextColumn::make('duration')
                    ->label('Durasi')
                    ->formatStateUsing(fn ($state) => "{$state} Menit")
                    ->sortable(),
                TextColumn::make('threshold')
                    ->label('KKM')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('exact_time')
                    ->label('Waktu Ketat')
                    ->boolean(),
                TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('expired_at')
                    ->label('Batas Akhir')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                IconColumn::make('is_available')
                    ->label('Tersedia')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
