<?php

namespace App\Filament\Test\Resources\Exams;

use App\Filament\Test\Resources\Exams\Pages\ManageExams;
use App\Filament\Test\Resources\Exams\Pages\StartingExam;
use App\Models\Exam;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Sesi Ujian';

    protected static ?string $modelLabel = 'Ujian';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('is_available', true);
            })
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Ujian')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('duration')
                    ->label('Durasi')
                    ->formatStateUsing(fn ($state) => "{$state} Menit")
                    ->sortable(),
                TextColumn::make('threshold')
                    ->label('KKM')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label('Jadwal Mulai')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status Pengerjaan')
                    ->state(function (Exam $record) {
                        $now = now();
                        $result = \App\Models\ExamResult::where('exam_id', $record->id)
                            ->where('user_id', auth()->id())
                            ->latest()
                            ->first();

                        if ($result) {
                            return $result->is_passed
                                ? "Selesai (Nilai: {$result->grade} - LULUS)"
                                : "Selesai (Nilai: {$result->grade} - TIDAK LULUS)";
                        }

                        $sessionFinished = session("exam_finished_{$record->id}_" . auth()->id(), false);
                        if ($sessionFinished) {
                            $res = session("exam_result_{$record->id}_" . auth()->id());
                            $grade = $res['final_grade'] ?? 0;
                            $passed = $res['is_passed'] ?? false;
                            return $passed
                                ? "Selesai (Nilai: {$grade} - LULUS)"
                                : "Selesai (Nilai: {$grade} - TIDAK LULUS)";
                        }

                        // Cek apakah jadwal belum mulai
                        if ($record->started_at && $record->started_at > $now) {
                            return 'Akan Dimulai';
                        }

                        // Cek apakah waktu ujian sudah lewat/berakhir
                        $isExpired = false;
                        if ($record->exact_time && $record->started_at) {
                            $endTime = \Carbon\Carbon::parse($record->started_at)->addMinutes((int) $record->duration);
                            if ($now->greaterThan($endTime)) {
                                $isExpired = true;
                            }
                        } elseif ($record->expired_at && $now->greaterThan($record->expired_at)) {
                            $isExpired = true;
                        }

                        if ($isExpired) {
                            return 'Waktu Berakhir';
                        }

                        return 'Siap Dikerjakan';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        if (str_contains($state, 'LULUS') && !str_contains($state, 'TIDAK')) {
                            return 'success';
                        }
                        if (str_contains($state, 'TIDAK LULUS') || $state === 'Waktu Berakhir') {
                            return 'danger';
                        }
                        if ($state === 'Siap Dikerjakan') {
                            return 'success';
                        }
                        return 'gray';
                    }),
            ])
            ->recordActions([
                Action::make('start')
                    ->label(function ($record) {
                        $now = now();
                        $finished = \App\Models\ExamResult::where('exam_id', $record->id)
                            ->where('user_id', auth()->id())
                            ->exists() || session("exam_finished_{$record->id}_" . auth()->id(), false);

                        if ($finished) {
                            return 'Lihat Hasil';
                        }

                        if ($record->started_at && $record->started_at > $now) {
                            return 'Belum Dimulai';
                        }

                        // Cek expired
                        if ($record->exact_time && $record->started_at) {
                            $endTime = \Carbon\Carbon::parse($record->started_at)->addMinutes((int) $record->duration);
                            if ($now->greaterThan($endTime)) {
                                return 'Sudah Berakhir';
                            }
                        } elseif ($record->expired_at && $now->greaterThan($record->expired_at)) {
                            return 'Sudah Berakhir';
                        }

                        return 'Mulai Ujian';
                    })
                    ->icon(function ($record) {
                        $now = now();
                        $finished = \App\Models\ExamResult::where('exam_id', $record->id)
                            ->where('user_id', auth()->id())
                            ->exists() || session("exam_finished_{$record->id}_" . auth()->id(), false);

                        if ($finished) {
                            return Heroicon::OutlinedDocumentCheck;
                        }

                        if ($record->started_at && $record->started_at > $now) {
                            return Heroicon::OutlinedClock;
                        }

                        if ($record->exact_time && $record->started_at) {
                            $endTime = \Carbon\Carbon::parse($record->started_at)->addMinutes((int) $record->duration);
                            if ($now->greaterThan($endTime)) {
                                return Heroicon::OutlinedXCircle;
                            }
                        } elseif ($record->expired_at && $now->greaterThan($record->expired_at)) {
                            return Heroicon::OutlinedXCircle;
                        }

                        return Heroicon::OutlinedPaperAirplane;
                    })
                    ->color(function ($record) {
                        $now = now();
                        $finished = \App\Models\ExamResult::where('exam_id', $record->id)
                            ->where('user_id', auth()->id())
                            ->exists() || session("exam_finished_{$record->id}_" . auth()->id(), false);

                        if ($finished) {
                            return 'success';
                        }

                        if ($record->started_at && $record->started_at > $now) {
                            return 'gray';
                        }

                        if ($record->exact_time && $record->started_at) {
                            $endTime = \Carbon\Carbon::parse($record->started_at)->addMinutes((int) $record->duration);
                            if ($now->greaterThan($endTime)) {
                                return 'danger';
                            }
                        } elseif ($record->expired_at && $now->greaterThan($record->expired_at)) {
                            return 'danger';
                        }

                        return 'primary';
                    })
                    ->button()
                    ->disabled(function ($record) {
                        $now = now();
                        $finished = \App\Models\ExamResult::where('exam_id', $record->id)
                            ->where('user_id', auth()->id())
                            ->exists() || session("exam_finished_{$record->id}_" . auth()->id(), false);

                        if ($finished) {
                            return false; // Bisa diklik untuk lihat hasil
                        }

                        if ($record->started_at && $record->started_at > $now) {
                            return true; // Belum mulai
                        }

                        // Cek expired
                        if ($record->exact_time && $record->started_at) {
                            $endTime = \Carbon\Carbon::parse($record->started_at)->addMinutes((int) $record->duration);
                            if ($now->greaterThan($endTime)) {
                                return true; // Sudah lewat
                            }
                        } elseif ($record->expired_at && $now->greaterThan($record->expired_at)) {
                            return true;
                        }

                        return false;
                    })
                    ->url(fn ($record) =>
                        route(
                            StartingExam::getRouteName(),
                            ['exam' => $record]
                        )
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageExams::route('/'),
            'mulai' => StartingExam::route('/{exam}/mulai')
        ];
    }
}
