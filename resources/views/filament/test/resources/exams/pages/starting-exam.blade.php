<x-filament-panels::page>
    <div class="cbt-wrapper -mt-6 -mx-4 sm:-mx-6 lg:-mx-8">
        @if ($isFinished)
            {{-- ======================================================== --}}
            {{-- TAMPILAN HASIL UJIAN (EXAM RESULT VIEW)                  --}}
            {{-- ======================================================== --}}
            <div class="cbt-container px-4 py-6 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
                {{-- Hero Banner --}}
                <div class="cbt-hero relative overflow-hidden rounded-3xl p-6 sm:p-8 text-white shadow-xl {{ $examResult['is_passed'] ? 'cbt-hero-passed bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600' : 'cbt-hero-failed bg-gradient-to-r from-rose-600 via-pink-600 to-amber-600' }}">
                    <div class="cbt-blob-1 absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="cbt-blob-2 absolute -left-10 -top-10 w-48 h-48 bg-black/10 rounded-full blur-xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="space-y-2 text-center md:text-left">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-semibold uppercase tracking-wider">
                                @if ($examResult['is_passed'])
                                    <svg class="cbt-icon-sm" width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Hasil Ujian: Lulus</span>
                                @else
                                    <svg class="cbt-icon-sm" width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span>Hasil Ujian: Belum Mencapai KKM</span>
                                @endif
                            </div>
                            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight">
                                {{ $examResult['exam_title'] ?? 'Ujian Selesai' }}
                            </h1>
                            <p class="text-white/80 text-sm sm:text-base max-w-2xl">
                                @if ($examResult['is_passed'])
                                    Selamat! Anda telah menyelesaikan ujian dengan baik dan melampaui batas nilai kelulusan minimal (KKM {{ $examResult['threshold'] }}).
                                @else
                                    Ujian telah selesai. Nilai Anda belum memenuhi batas minimal kelulusan (KKM {{ $examResult['threshold'] }}). Tetap semangat dan tingkatkan belajarmu!
                                @endif
                            </p>
                        </div>

                        {{-- Final Grade Badge --}}
                        <div class="cbt-grade-box flex flex-col items-center justify-center p-6 bg-white/15 backdrop-blur-md rounded-2xl border border-white/20 min-w-[200px] text-center shadow-inner">
                            <span class="text-xs uppercase font-medium tracking-widest text-white/80">Nilai Akhir</span>
                            <span class="text-5xl font-black tracking-tight my-1">{{ number_format($examResult['final_grade'], 1) }}</span>
                            <div class="text-xs font-semibold px-3 py-1 rounded-full {{ $examResult['is_passed'] ? 'bg-emerald-400/30 text-emerald-100 border border-emerald-300/40' : 'bg-rose-400/30 text-rose-100 border border-rose-300/40' }}">
                                KKM: {{ $examResult['threshold'] }} &bull; {{ $examResult['is_passed'] ? 'LULUS' : 'TIDAK LULUS' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Metrics Grid --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 cbt-metrics-grid">
                    {{-- Benar --}}
                    <div class="cbt-metric-card p-5 rounded-2xl bg-white dark:bg-gray-800 border border-emerald-200 dark:border-emerald-900/50 shadow-sm flex items-center gap-4">
                        <div class="cbt-metric-icon w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                            <svg class="cbt-icon-lg" width="24" height="24" style="width:24px;height:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $examResult['correct_count'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Jawaban Benar</div>
                        </div>
                    </div>

                    {{-- Salah --}}
                    <div class="cbt-metric-card p-5 rounded-2xl bg-white dark:bg-gray-800 border border-rose-200 dark:border-rose-900/50 shadow-sm flex items-center gap-4">
                        <div class="cbt-metric-icon w-12 h-12 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                            <svg class="cbt-icon-lg" width="24" height="24" style="width:24px;height:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $examResult['incorrect_count'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Jawaban Salah</div>
                        </div>
                    </div>

                    {{-- Tidak Dijawab --}}
                    <div class="cbt-metric-card p-5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                        <div class="cbt-metric-icon w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 flex items-center justify-center shrink-0">
                            <svg class="cbt-icon-lg" width="24" height="24" style="width:24px;height:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $examResult['unanswered_count'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Tidak Dijawab</div>
                        </div>
                    </div>

                    {{-- Total Soal & Akurasi --}}
                    <div class="cbt-metric-card p-5 rounded-2xl bg-white dark:bg-gray-800 border border-indigo-200 dark:border-indigo-900/50 shadow-sm flex items-center gap-4">
                        <div class="cbt-metric-icon w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <svg class="cbt-icon-lg" width="24" height="24" style="width:24px;height:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $examResult['total_questions'] > 0 ? round(($examResult['correct_count'] / $examResult['total_questions']) * 100) : 0 }}%
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Tingkat Akurasi ({{ $examResult['total_questions'] }} Soal)</div>
                        </div>
                    </div>
                </div>

                {{-- Detail Pelaksanaan & Statistik Mata Pelajaran --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Informasi Peserta & Ujian --}}
                    <div class="cbt-card bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
                        <h3 class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2 border-b border-gray-100 dark:border-gray-750 pb-3">
                            <svg class="cbt-icon text-indigo-500" width="20" height="20" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Informasi Peserta & Pengerjaan
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-center py-1 border-b border-gray-100 dark:border-gray-700/50">
                                <span class="text-gray-500 dark:text-gray-400">Nama Siswa:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $examResult['student_name'] }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100 dark:border-gray-700/50">
                                <span class="text-gray-500 dark:text-gray-400">NIS (Username):</span>
                                <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ $examResult['student_nis'] }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100 dark:border-gray-700/50">
                                <span class="text-gray-500 dark:text-gray-400">Durasi Pengerjaan:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $examResult['duration_formatted'] }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100 dark:border-gray-700/50">
                                <span class="text-gray-500 dark:text-gray-400">Waktu Selesai:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $examResult['completed_at'] }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100 dark:border-gray-700/50">
                                <span class="text-gray-500 dark:text-gray-400">Metode Pengumpulan:</span>
                                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-0.5 rounded-full font-medium {{ $examResult['auto_submitted'] ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
                                    {{ $examResult['auto_submitted'] ? 'Otomatis (Waktu Habis)' : 'Selesai Manual' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-500 dark:text-gray-400">Poin Diperoleh:</span>
                                <span class="font-semibold text-indigo-600 dark:text-indigo-400">
                                    {{ $examResult['total_earned_score'] }} / {{ $examResult['max_possible_score'] }} Poin
                                </span>
                            </div>
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="pt-4 flex flex-col gap-2 print:hidden">
                            <a href="{{ \App\Filament\Test\Resources\Exams\ExamResource::getUrl('index') }}"
                               class="cbt-btn-primary w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/20 transition">
                                <svg class="cbt-icon-sm" width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Kembali ke Daftar Ujian
                            </a>

                            <a href="{{ \App\Filament\Test\Resources\ExamHistories\ExamHistoryResource::getUrl('index') }}"
                               class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl font-medium text-xs text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 transition">
                                <svg class="cbt-icon-sm" width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Lihat Semua Riwayat Nilai & Ujian
                            </a>

                            <div class="flex gap-2">
                                <button type="button" onclick="window.print()"
                                        class="cbt-btn-secondary flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    <svg class="cbt-icon-sm" width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Cetak Hasil
                                </button>

                                <button type="button"
                                        wire:click="retakeExam"
                                        wire:confirm="Apakah Anda yakin ingin mengulang ujian ini? Jawaban sebelumnya akan dihapus."
                                        class="cbt-btn-retake inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 transition"
                                        title="Uji Ulang (Reset)">
                                    <svg class="cbt-icon-sm" width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Uji Ulang
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Performa per Mata Pelajaran --}}
                    <div class="cbt-card lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
                        <h3 class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2 border-b border-gray-100 dark:border-gray-750 pb-3">
                            <svg class="cbt-icon text-indigo-500" width="20" height="20" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Performa per Mata Pelajaran
                        </h3>

                        <div class="space-y-4">
                            @forelse ($examResult['subject_stats'] ?? [] as $sub)
                                <div class="cbt-subject-item p-4 rounded-xl bg-gray-50 dark:bg-gray-750/50 border border-gray-100 dark:border-gray-700/60 space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-gray-900 dark:text-white">{{ $sub['name'] }}</span>
                                        <span class="font-extrabold text-sm {{ $sub['percentage'] >= ($examResult['threshold'] ?? 50) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                            {{ $sub['percentage'] }}%
                                        </span>
                                    </div>
                                    <div class="w-full h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $sub['percentage'] >= ($examResult['threshold'] ?? 50) ? 'bg-emerald-500' : 'bg-rose-500' }}"
                                             style="width: {{ $sub['percentage'] }}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1">
                                        <span>Total: {{ $sub['total'] }} Soal</span>
                                        <div class="flex items-center gap-3">
                                            <span class="text-emerald-600 dark:text-emerald-400 font-medium">✓ {{ $sub['correct'] }} Benar</span>
                                            <span class="text-rose-600 dark:text-rose-400 font-medium">✗ {{ $sub['incorrect'] }} Salah</span>
                                            <span class="text-gray-400 font-medium">- {{ $sub['unanswered'] }} Kosong</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 italic">Tidak ada rincian mata pelajaran.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Rincian & Pembahasan Jawaban (Review Section) --}}
                <div class="cbt-card bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 dark:border-gray-750 pb-4">
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-lg flex items-center gap-2">
                                <svg class="cbt-icon text-indigo-500" width="20" height="20" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                Pembahasan & Rincian Jawaban Soal
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Periksa kembali jawaban Anda beserta kunci jawaban yang benar
                            </p>
                        </div>

                        {{-- Filter Tabs --}}
                        <div class="cbt-tabs-group flex items-center gap-1.5 p-1 bg-gray-100 dark:bg-gray-700/80 rounded-xl text-xs print:hidden">
                            <button type="button" wire:click="setFilterReview('all')"
                                    class="cbt-tab px-3 py-1.5 rounded-lg font-medium transition {{ $filterResultReview === 'all' ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                                Semua ({{ count($examResult['review_questions'] ?? []) }})
                            </button>
                            <button type="button" wire:click="setFilterReview('correct')"
                                    class="cbt-tab px-3 py-1.5 rounded-lg font-medium transition {{ $filterResultReview === 'correct' ? 'bg-white dark:bg-gray-800 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                                Benar ({{ $examResult['correct_count'] }})
                            </button>
                            <button type="button" wire:click="setFilterReview('incorrect')"
                                    class="cbt-tab px-3 py-1.5 rounded-lg font-medium transition {{ $filterResultReview === 'incorrect' ? 'bg-white dark:bg-gray-800 text-rose-600 dark:text-rose-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                                Salah ({{ $examResult['incorrect_count'] }})
                            </button>
                            <button type="button" wire:click="setFilterReview('unanswered')"
                                    class="cbt-tab px-3 py-1.5 rounded-lg font-medium transition {{ $filterResultReview === 'unanswered' ? 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                                Kosong ({{ $examResult['unanswered_count'] }})
                            </button>
                        </div>
                    </div>

                    {{-- List of Review Questions --}}
                    <div class="space-y-6">
                        @php
                            $filteredReview = collect($examResult['review_questions'] ?? [])->filter(function ($q) use ($filterResultReview) {
                                if ($filterResultReview === 'correct') return $q['status'] === 'correct';
                                if ($filterResultReview === 'incorrect') return $q['status'] === 'incorrect';
                                if ($filterResultReview === 'unanswered') return $q['status'] === 'unanswered';
                                return true;
                            });
                        @endphp

                        @forelse ($filteredReview as $item)
                            <div class="cbt-review-card p-5 rounded-2xl border transition-all duration-200 {{ $item['status'] === 'correct' ? 'border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/20 dark:bg-emerald-950/10' : ($item['status'] === 'incorrect' ? 'border-rose-200 dark:border-rose-900/60 bg-rose-50/20 dark:bg-rose-950/10' : 'border-gray-200 dark:border-gray-700 bg-gray-50/40 dark:bg-gray-800/40') }}">
                                {{-- Question Header --}}
                                <div class="flex flex-wrap items-center justify-between gap-2 pb-3 mb-3 border-b border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg font-bold text-sm {{ $item['status'] === 'correct' ? 'bg-emerald-600 text-white' : ($item['status'] === 'incorrect' ? 'bg-rose-600 text-white' : 'bg-gray-500 text-white') }}">
                                            {{ $item['number'] }}
                                        </span>
                                        <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800">
                                            {{ $item['subject_name'] }}
                                        </span>
                                    </div>

                                    <div>
                                        @if ($item['status'] === 'correct')
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300/60">
                                                <svg class="cbt-icon-xs" width="14" height="14" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Benar (+{{ $item['score'] }} Poin)
                                            </span>
                                        @elseif ($item['status'] === 'incorrect')
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-300/60">
                                                <svg class="cbt-icon-xs" width="14" height="14" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Salah (0 Poin)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border border-gray-300/60">
                                                Tidak Dijawab (0 Poin)
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Question Text --}}
                                <div class="cbt-question-text prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-200 text-base mb-4">
                                    {!! $item['payload'] !!}
                                </div>

                                {{-- Options List --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                                    @foreach ($item['answers'] as $optIdx => $opt)
                                        @php
                                            $letter = chr(65 + $optIdx);
                                            $isCorrectAns = !empty($opt['is_correct']);
                                            $isSelectedAns = ($opt['id'] == $item['selected_answer_id']);
                                        @endphp
                                        <div class="cbt-option-item relative flex items-start gap-3 p-3.5 rounded-xl border text-sm transition-all
                                            {{ $isCorrectAns
                                                ? 'border-emerald-500 bg-emerald-500/10 dark:bg-emerald-950/30 font-semibold ring-1 ring-emerald-500/50'
                                                : ($isSelectedAns && !$isCorrectAns
                                                    ? 'border-rose-400 bg-rose-500/10 dark:bg-rose-950/30 ring-1 ring-rose-400/50'
                                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80') }}">

                                            <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs shrink-0
                                                {{ $isCorrectAns
                                                    ? 'bg-emerald-600 text-white'
                                                    : ($isSelectedAns && !$isCorrectAns
                                                        ? 'bg-rose-600 text-white'
                                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300') }}">
                                                {{ $letter }}
                                            </div>

                                            <div class="flex-1 min-w-0 pt-0.5">
                                                <span class="text-gray-800 dark:text-gray-200">{{ $opt['text'] }}</span>
                                                <div class="mt-1 flex flex-wrap gap-1.5">
                                                    @if ($isSelectedAns)
                                                        <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded {{ $isCorrectAns ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200' }}">
                                                            Pilihan Anda
                                                        </span>
                                                    @endif
                                                    @if ($isCorrectAns)
                                                        <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-600 text-white">
                                                            ✓ Kunci Jawaban
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Pembahasan / Description if available --}}
                                @if (!empty($item['description']))
                                    <div class="cbt-explanation p-3.5 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50 text-xs space-y-1">
                                        <div class="font-bold text-indigo-900 dark:text-indigo-300 flex items-center gap-1.5">
                                            <svg class="cbt-icon-xs text-indigo-600" width="14" height="14" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Pembahasan:
                                        </div>
                                        <div class="text-indigo-800 dark:text-indigo-200 leading-relaxed">
                                            {{ $item['description'] }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada soal pada kategori filter ini.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        @else
            {{-- ======================================================== --}}
            {{-- HALAMAN PENGERJAAN UJIAN (ONGOING EXAM INTERFACE)        --}}
            {{-- ======================================================== --}}
            <div x-data="cbtExamHandler({
                initialRemaining: {{ $remainingSeconds }},
                onExpire: () => {
                    $wire.finishExam(true);
                }
            })" x-init="initTimer()" class="min-h-screen flex flex-col cbt-ongoing-wrapper">

                {{-- TOPBAR STICKY CBT HEADER --}}
                <header class="cbt-topbar sticky top-0 z-30 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm px-4 py-3 sm:px-6">
                    <div class="cbt-topbar-inner max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                        {{-- Left: Exam Title & Student Info --}}
                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <div class="cbt-topbar-logo w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-indigo-500/20" style="width:40px;height:40px;min-width:40px;min-height:40px;">
                                <svg class="cbt-icon" width="20" height="20" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">
                                        {{ $exam->title ?? 'Sesi Ujian Siswa' }}
                                    </h2>
                                    <span class="cbt-badge-kkm hidden md:inline-flex text-[11px] font-semibold px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                        KKM: {{ $exam->threshold ?? 50 }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ auth()->user()->name }}</span>
                                    <span>&bull;</span>
                                    <span class="font-mono">NIS: {{ auth()->user()->username }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Center: COUNTDOWN TIMER PILL --}}
                        <div class="flex items-center justify-center">
                            <div :class="{
                                    'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 shadow-sm': secondsLeft > 300,
                                    'bg-amber-50 dark:bg-amber-950/60 border-amber-400 dark:border-amber-700 text-amber-800 dark:text-amber-300 animate-pulse shadow-md': secondsLeft <= 300 && secondsLeft > 60,
                                    'bg-rose-50 dark:bg-rose-950/70 border-rose-500 dark:border-rose-700 text-rose-700 dark:text-rose-300 animate-pulse shadow-lg ring-2 ring-rose-400/40': secondsLeft <= 60
                                 }"
                                 class="cbt-timer-box px-4 py-1.5 rounded-2xl border flex items-center gap-2 font-mono transition-all duration-300">
                                <svg class="cbt-icon" width="20" height="20" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex flex-col text-left">
                                    <span class="text-[9px] uppercase tracking-wider font-sans font-bold opacity-80 -mb-0.5">Sisa Waktu</span>
                                    <span class="text-lg sm:text-xl font-black tracking-tight" x-text="timeDisplay">00:00:00</span>
                                </div>
                            </div>
                        </div>

                        {{-- Right: Controls (Font & View Mode) --}}
                        <div class="flex items-center gap-2">
                            {{-- Font Size Adjuster --}}
                            <div class="cbt-font-controls flex items-center p-1 bg-gray-100 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 text-xs">
                                <button type="button" wire:click="setFontSize('small')"
                                        class="px-2 py-1 rounded-lg font-bold transition {{ $fontSize === 'small' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-gray-500 hover:text-gray-800' }}"
                                        title="Ukuran Tulisan Kecil">A-</button>
                                <button type="button" wire:click="setFontSize('normal')"
                                        class="px-2 py-1 rounded-lg font-bold transition {{ $fontSize === 'normal' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-gray-500 hover:text-gray-800' }}"
                                        title="Ukuran Tulisan Normal">A</button>
                                <button type="button" wire:click="setFontSize('large')"
                                        class="px-2 py-1 rounded-lg font-bold transition {{ $fontSize === 'large' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-gray-500 hover:text-gray-800' }}"
                                        title="Ukuran Tulisan Besar">A+</button>
                            </div>

                            {{-- View Mode Toggle --}}
                            <button type="button"
                                    wire:click="setViewMode('{{ $viewMode === 'focus' ? 'all' : 'focus' }}')"
                                    class="cbt-btn-mode hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 transition">
                                @if ($viewMode === 'focus')
                                    <svg class="cbt-icon-xs text-indigo-500" width="14" height="14" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                    </svg>
                                    Mode Semua Soal
                                @else
                                    <svg class="cbt-icon-xs text-indigo-500" width="14" height="14" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Mode Fokus
                                @endif
                            </button>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    @php
                        $answeredCount = count($selectedAnswers);
                        $doubtfulCount = count(array_filter($doubtfulAnswers));
                        $unansweredCount = max(0, count($questions) - $answeredCount);
                        $progressPercent = count($questions) > 0 ? (int) round(($answeredCount / count($questions)) * 100) : 0;
                    @endphp
                    <div class="cbt-progress-wrapper mt-2 max-w-7xl mx-auto">
                        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 mb-1">
                            <span>Progress Pengerjaan: <strong class="text-indigo-600 dark:text-indigo-400">{{ $answeredCount }}</strong> dari {{ count($questions) }} Soal Terjawab</span>
                            <span class="font-semibold">{{ $progressPercent }}%</span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full transition-all duration-300"
                                 style="width: {{ $progressPercent }}%"></div>
                        </div>
                    </div>
                </header>

                {{-- MAIN EXAM WORKSPACE --}}
                <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8">
                    @if (empty($questions))
                        {{-- Empty state if no questions available --}}
                        <div class="cbt-card bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700 shadow-sm max-w-lg mx-auto space-y-4">
                            <div class="w-16 h-16 rounded-2xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 mx-auto flex items-center justify-center" style="width:64px;height:64px;">
                                <svg class="cbt-icon-xl" width="32" height="32" style="width:32px;height:32px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Tidak Ada Soal Tersedia</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Sesi ujian ini belum memiliki daftar pertanyaan yang aktif atau mata pelajaran belum dikaitkan.
                            </p>
                            <a href="{{ \App\Filament\Test\Resources\Exams\ExamResource::getUrl('index') }}"
                               class="cbt-btn-primary inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition">
                                Kembali ke Daftar Ujian
                            </a>
                        </div>
                    @else
                        <div class="cbt-workspace-grid grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                            {{-- LEFT / MAIN COLUMN: QUESTION CARD --}}
                            <div class="cbt-main-col lg:col-span-8 xl:col-span-9 space-y-6">
                                @if ($viewMode === 'focus')
                                    {{-- FOCUS MODE: SINGLE QUESTION AT A TIME --}}
                                    @php
                                        $currentQ = $questions[$currentQuestionIndex] ?? $questions[0];
                                        $isDoubtful = !empty($doubtfulAnswers[$currentQ['id']]);
                                        $selectedAnswerId = $selectedAnswers[$currentQ['id']] ?? null;
                                    @endphp

                                    <div class="cbt-card bg-white dark:bg-gray-800 rounded-3xl border border-gray-200/90 dark:border-gray-700/80 shadow-md overflow-hidden transition-all">
                                        {{-- Question Card Top Header --}}
                                        <div class="p-5 sm:px-6 bg-gray-50/70 dark:bg-gray-850 border-b border-gray-100 dark:border-gray-750 flex flex-wrap items-center justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex items-center px-3.5 py-1.5 rounded-xl font-black text-sm bg-indigo-600 text-white shadow-xs">
                                                    Soal {{ $currentQ['number'] }}
                                                </span>
                                                <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                                    {{ $currentQ['subject_name'] }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                                    Bobot: {{ $currentQ['score'] }} Poin
                                                </span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                @if ($isDoubtful)
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300/80 animate-pulse">
                                                        <svg class="cbt-icon-xs" width="14" height="14" style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" />
                                                        </svg>
                                                        Ragu-ragu
                                                    </span>
                                                @endif
                                                @if ($selectedAnswerId !== null)
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300/80">
                                                        ✓ Terjawab
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Question Payload --}}
                                        <div class="p-6 sm:p-8">
                                            <div class="cbt-question-payload prose dark:prose-invert max-w-none text-gray-900 dark:text-gray-100 leading-relaxed font-sans
                                                {{ $fontSize === 'small' ? 'text-sm' : ($fontSize === 'large' ? 'text-lg' : 'text-base') }}">
                                                {!! $currentQ['payload'] !!}
                                            </div>

                                            {{-- Options List --}}
                                            <div class="mt-8 space-y-3.5 cbt-options-container">
                                                <div class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                                                    Pilihan Jawaban:
                                                </div>

                                                @foreach ($currentQ['answers'] as $optIdx => $jawaban)
                                                    @php
                                                        $letter = chr(65 + $optIdx);
                                                        $isSelected = ($selectedAnswerId == $jawaban['id']);
                                                    @endphp
                                                    <div wire:click="selectAnswer({{ $currentQ['id'] }}, {{ $jawaban['id'] }})"
                                                         class="cbt-option-card group relative flex items-start gap-4 p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200 select-none
                                                            {{ $isSelected
                                                                ? 'cbt-option-selected border-indigo-600 bg-indigo-50/60 dark:bg-indigo-950/40 ring-4 ring-indigo-500/10 shadow-sm'
                                                                : 'border-gray-200 dark:border-gray-700/80 hover:border-indigo-300 dark:hover:border-indigo-600 bg-white dark:bg-gray-800/80 hover:bg-gray-50/50 dark:hover:bg-gray-750/50' }}">

                                                        {{-- Letter Badge --}}
                                                        <div class="cbt-letter-badge w-9 h-9 rounded-xl flex items-center justify-center font-extrabold text-sm shrink-0 transition-transform duration-200 group-hover:scale-105" style="width:36px;height:36px;min-width:36px;min-height:36px;"
                                                            class="{{ $isSelected
                                                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/60 group-hover:text-indigo-700 dark:group-hover:text-indigo-300' }}">
                                                            {{ $letter }}
                                                        </div>

                                                        {{-- Option Text --}}
                                                        <div class="flex-1 min-w-0 pt-1.5 text-gray-800 dark:text-gray-200 font-medium leading-relaxed
                                                            {{ $fontSize === 'small' ? 'text-sm' : ($fontSize === 'large' ? 'text-lg' : 'text-base') }}">
                                                            {{ $jawaban['text'] }}
                                                        </div>

                                                        {{-- Radio check circle icon --}}
                                                        <div class="cbt-radio-circle w-6 h-6 rounded-full border-2 flex items-center justify-center shrink-0 mt-1 transition" style="width:24px;height:24px;min-width:24px;min-height:24px;"
                                                            class="{{ $isSelected
                                                                ? 'border-indigo-600 bg-indigo-600 text-white'
                                                                : 'border-gray-300 dark:border-gray-600 group-hover:border-indigo-400' }}">
                                                            @if ($isSelected)
                                                                <svg class="cbt-icon-xs" width="14" height="14" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Card Footer Navigation Controls --}}
                                        <div class="p-4 sm:px-6 bg-gray-50/70 dark:bg-gray-850 border-t border-gray-100 dark:border-gray-750 flex flex-wrap items-center justify-between gap-3">
                                            {{-- Prev Button --}}
                                            <div>
                                                <button type="button"
                                                        wire:click="previousQuestion"
                                                        @if ($currentQuestionIndex <= 0) disabled @endif
                                                        class="cbt-nav-btn inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border transition
                                                            {{ $currentQuestionIndex <= 0
                                                                ? 'opacity-40 cursor-not-allowed border-gray-200 dark:border-gray-700 text-gray-400'
                                                                : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 shadow-xs' }}">
                                                    <svg class="cbt-icon-sm" width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                                    </svg>
                                                    Sebelumnya
                                                </button>
                                            </div>

                                            {{-- Middle Action Buttons --}}
                                            <div class="flex items-center gap-2">
                                                {{-- Toggle Ragu-ragu --}}
                                                <button type="button"
                                                        wire:click="toggleDoubtful({{ $currentQ['id'] }})"
                                                        class="cbt-nav-btn inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold border transition
                                                            {{ $isDoubtful
                                                                ? 'bg-amber-500 border-amber-600 text-white shadow-md shadow-amber-500/20'
                                                                : 'bg-white dark:bg-gray-800 border-amber-300 dark:border-amber-700/70 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40' }}">
                                                    <svg class="cbt-icon-sm" width="16" height="16" style="width:16px;height:16px;" fill="{{ $isDoubtful ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                                    </svg>
                                                    {{ $isDoubtful ? 'Ragu-ragu Aktif' : 'Tandai Ragu-ragu' }}
                                                </button>

                                                {{-- Clear Answer --}}
                                                @if ($selectedAnswerId !== null)
                                                    <button type="button"
                                                            wire:click="clearAnswer({{ $currentQ['id'] }})"
                                                            class="cbt-nav-btn inline-flex items-center gap-1 px-3 py-2.5 rounded-xl text-xs font-semibold text-gray-600 dark:text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                                            title="Kosongkan Pilihan">
                                                        <svg class="cbt-icon-sm" width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        Kosongkan
                                                    </button>
                                                @endif
                                            </div>

                                            {{-- Next / Submit Button --}}
                                            <div>
                                                @if ($currentQuestionIndex < count($questions) - 1)
                                                    <button type="button"
                                                            wire:click="nextQuestion"
                                                            class="cbt-btn-primary inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 transition">
                                                        Selanjutnya
                                                        <svg class="cbt-icon-sm" width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                        </svg>
                                                    </button>
                                                @else
                                                    <button type="button"
                                                            wire:click="openFinishModal"
                                                            class="cbt-btn-finish inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-lg shadow-emerald-600/25 transition">
                                                        <span>Selesai Ujian</span>
                                                        <svg class="cbt-icon-sm" width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                @else
                                    {{-- ALL QUESTIONS SCROLL MODE --}}
                                    <div class="space-y-6">
                                        @foreach ($questions as $q)
                                            @php
                                                $isDoubtful = !empty($doubtfulAnswers[$q['id']]);
                                                $selectedAnswerId = $selectedAnswers[$q['id']] ?? null;
                                            @endphp
                                            <div id="soal-{{ $q['index'] }}" class="cbt-card bg-white dark:bg-gray-800 rounded-3xl border border-gray-200/90 dark:border-gray-700/80 shadow-sm p-6 space-y-4">
                                                <div class="flex items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 pb-3">
                                                    <div class="flex items-center gap-3">
                                                        <span class="inline-flex items-center px-3 py-1 rounded-xl font-black text-sm bg-indigo-600 text-white">
                                                            Soal {{ $q['number'] }}
                                                        </span>
                                                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">
                                                            {{ $q['subject_name'] }}
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <button type="button" wire:click="toggleDoubtful({{ $q['id'] }})"
                                                                class="px-2.5 py-1 rounded-lg text-xs font-semibold border transition
                                                                    {{ $isDoubtful ? 'bg-amber-500 text-white border-amber-600' : 'text-amber-700 border-amber-300 hover:bg-amber-50' }}">
                                                            {{ $isDoubtful ? '★ Ragu-ragu' : 'Tandai Ragu' }}
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="cbt-question-payload prose dark:prose-invert max-w-none text-gray-900 dark:text-gray-100">
                                                    {!! $q['payload'] !!}
                                                </div>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                                    @foreach ($q['answers'] as $optIdx => $jawaban)
                                                        @php
                                                            $letter = chr(65 + $optIdx);
                                                            $isSelected = ($selectedAnswerId == $jawaban['id']);
                                                        @endphp
                                                        <div wire:click="selectAnswer({{ $q['id'] }}, {{ $jawaban['id'] }})"
                                                             class="cbt-option-card flex items-start gap-3 p-3.5 rounded-2xl border-2 cursor-pointer transition select-none
                                                                {{ $isSelected ? 'border-indigo-600 bg-indigo-50/60 dark:bg-indigo-950/40 ring-2 ring-indigo-500/20' : 'border-gray-200 dark:border-gray-700 hover:border-indigo-300' }}">
                                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs shrink-0" style="width:32px;height:32px;min-width:32px;min-height:32px;"
                                                                class="{{ $isSelected ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                                                                {{ $letter }}
                                                            </div>
                                                            <div class="flex-1 text-sm pt-1 text-gray-800 dark:text-gray-200">
                                                                {{ $jawaban['text'] }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- RIGHT COLUMN: QUESTION PALETTE & SUBMISSION SIDEBAR --}}
                            <div class="cbt-sidebar-col lg:col-span-4 xl:col-span-3 space-y-5 lg:sticky lg:top-24">
                                <div class="cbt-card bg-white dark:bg-gray-800 rounded-3xl border border-gray-200/90 dark:border-gray-700/80 shadow-md p-5 space-y-4">
                                    {{-- Sidebar Title --}}
                                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-750 pb-3">
                                        <h3 class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2">
                                            <svg class="cbt-icon text-indigo-600" width="20" height="20" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                            </svg>
                                            Nomor Soal
                                        </h3>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                            Total: {{ count($questions) }}
                                        </span>
                                    </div>

                                    {{-- Legend --}}
                                    <div class="grid grid-cols-3 gap-1.5 text-[11px] font-medium text-gray-600 dark:text-gray-400 py-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0" style="width:12px;height:12px;"></span>
                                            <span>Dijawab</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0" style="width:12px;height:12px;"></span>
                                            <span>Ragu-ragu</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-3 h-3 rounded-full bg-gray-200 dark:bg-gray-700 shrink-0" style="width:12px;height:12px;"></span>
                                            <span>Belum</span>
                                        </div>
                                    </div>

                                    {{-- Number Palette Grid --}}
                                    <div class="cbt-palette-grid grid grid-cols-5 gap-2 max-h-[360px] overflow-y-auto p-1 pr-2">
                                        @foreach ($questions as $q)
                                            @php
                                                $qIdx = $q['index'];
                                                $isAnswered = isset($selectedAnswers[$q['id']]);
                                                $isDoubt = !empty($doubtfulAnswers[$q['id']]);
                                                $isCurrent = ($currentQuestionIndex === $qIdx && $viewMode === 'focus');
                                            @endphp
                                            <button type="button"
                                                    wire:click="goToQuestion({{ $qIdx }})"
                                                    class="cbt-palette-btn relative h-11 rounded-xl flex items-center justify-center font-bold text-xs transition-all duration-150 select-none
                                                        {{ $isCurrent ? 'ring-3 ring-indigo-500 ring-offset-2 dark:ring-offset-gray-800 scale-105 z-10' : '' }}
                                                        {{ $isDoubt
                                                            ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-xs'
                                                            : ($isAnswered
                                                                ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs'
                                                                : 'bg-gray-100 dark:bg-gray-700/80 hover:bg-gray-200 dark:hover:bg-gray-650 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-650') }}"
                                                    title="Pindah ke Soal {{ $q['number'] }}">
                                                {{ $q['number'] }}

                                                @if ($isDoubt)
                                                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-white border border-amber-600" style="width:10px;height:10px;"></span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>

                                    {{-- Quick Stats Summary --}}
                                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                        <div class="flex justify-between">
                                            <span>Sudah Terjawab:</span>
                                            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $answeredCount }} Soal</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Ragu-ragu:</span>
                                            <span class="font-bold text-amber-600 dark:text-amber-400">{{ $doubtfulCount }} Soal</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Belum Terjawab:</span>
                                            <span class="font-bold text-gray-700 dark:text-gray-300">{{ $unansweredCount }} Soal</span>
                                        </div>
                                    </div>

                                    {{-- Big Selesaikan Ujian Button --}}
                                    <div class="pt-2">
                                        <button type="button"
                                                wire:click="openFinishModal"
                                                class="cbt-btn-finish-lg w-full inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-2xl font-bold text-sm text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-lg shadow-emerald-600/30 transition transform hover:-translate-y-0.5">
                                            <svg class="cbt-icon" width="20" height="20" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Kumpulkan Jawaban
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </main>

                {{-- SUBMISSION CONFIRMATION MODAL --}}
                @if ($showFinishConfirmModal)
                    <div class="cbt-modal-backdrop fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
                        <div class="cbt-modal-card relative w-full max-w-md bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-7 shadow-2xl border border-gray-100 dark:border-gray-700 space-y-5">
                            {{-- Modal Header --}}
                            <div class="text-center space-y-2">
                                <div class="w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 mx-auto flex items-center justify-center" style="width:56px;height:56px;">
                                    <svg class="cbt-icon-lg" width="28" height="28" style="width:28px;height:28px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-extrabold text-gray-900 dark:text-white">Konfirmasi Selesai Ujian</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Apakah Anda yakin ingin mengakhiri sesi ujian dan mengumpulkan seluruh jawaban Anda?
                                </p>
                            </div>

                            {{-- Summary Box --}}
                            <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-750/60 border border-gray-100 dark:border-gray-700/60 space-y-2.5 text-sm">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">Total Pertanyaan:</span>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ count($questions) }} Soal</span>
                                </div>
                                <div class="flex justify-between items-center text-emerald-600 dark:text-emerald-400">
                                    <span class="font-medium">Sudah Dijawab:</span>
                                    <span class="font-bold">{{ $answeredCount }} Soal</span>
                                </div>
                                <div class="flex justify-between items-center text-amber-600 dark:text-amber-400">
                                    <span class="font-medium">Masih Ragu-ragu:</span>
                                    <span class="font-bold">{{ $doubtfulCount }} Soal</span>
                                </div>
                                <div class="flex justify-between items-center text-rose-600 dark:text-rose-400">
                                    <span class="font-medium">Belum Dijawab:</span>
                                    <span class="font-bold">{{ $unansweredCount }} Soal</span>
                                </div>
                            </div>

                            {{-- Warning Note if unfinished questions remain --}}
                            @if ($unansweredCount > 0 || $doubtfulCount > 0)
                                <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-200 space-y-1">
                                    <span class="font-bold flex items-center gap-1">
                                        <svg class="cbt-icon-xs text-amber-600" width="14" height="14" style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        Peringatan:
                                    </span>
                                    <span>
                                        Masih ada <strong>{{ $unansweredCount }} soal belum dijawab</strong>. Soal yang tidak dijawab tidak akan mendapatkan nilai.
                                    </span>
                                </div>
                            @endif

                            {{-- Actions --}}
                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <button type="button"
                                        wire:click="closeFinishModal"
                                        class="cbt-modal-btn-cancel w-full inline-flex items-center justify-center py-2.5 px-4 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-650 transition">
                                    Periksa Kembali
                                </button>
                                <button type="button"
                                        wire:click="finishExam"
                                        wire:loading.attr="disabled"
                                        class="cbt-modal-btn-submit w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/30 transition disabled:opacity-50">
                                    <span wire:loading.remove>Ya, Kumpulkan</span>
                                    <span wire:loading class="inline-flex items-center gap-1">
                                        <svg class="cbt-icon-sm animate-spin" width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        Memproses...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        @endif
    </div>

    {{-- CLIENT-SIDE JAVASCRIPT & TIMER HANDLER --}}
    <script>
        function cbtExamHandler(config) {
            return {
                secondsLeft: config.initialRemaining || 0,
                timeDisplay: '00:00:00',
                timerInterval: null,
                hasExpired: false,

                initTimer() {
                    this.updateDisplay();

                    if (this.secondsLeft <= 0) {
                        this.triggerExpire();
                        return;
                    }

                    this.timerInterval = setInterval(() => {
                        if (this.secondsLeft > 0) {
                            this.secondsLeft--;
                            this.updateDisplay();

                            if (this.secondsLeft <= 0) {
                                clearInterval(this.timerInterval);
                                this.triggerExpire();
                            }
                        }
                    }, 1000);
                },

                updateDisplay() {
                    const total = Math.max(0, this.secondsLeft);
                    const hours = Math.floor(total / 3600);
                    const minutes = Math.floor((total % 3600) / 60);
                    const seconds = total % 60;

                    this.timeDisplay =
                        String(hours).padStart(2, '0') + ':' +
                        String(minutes).padStart(2, '0') + ':' +
                        String(seconds).padStart(2, '0');
                },

                triggerExpire() {
                    if (this.hasExpired) return;
                    this.hasExpired = true;
                    alert('Waktu ujian telah berakhir! Sistem akan secara otomatis mengumpulkan jawaban Anda.');
                    if (typeof config.onExpire === 'function') {
                        config.onExpire();
                    }
                }
            };
        }
    </script>

    {{-- CSS dimuat secara eksternal via public/css/cbt-exam.css (terdaftar di TestPanelProvider) --}}
</x-filament-panels::page>
