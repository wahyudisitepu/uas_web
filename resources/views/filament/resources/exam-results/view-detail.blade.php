@php
    $record = $getRecord();
    $details = $record->result_details ?? [];
    $reviewQuestions = $details['review_questions'] ?? [];
    $subjectStats = $details['subject_stats'] ?? [];
    $isPassed = (bool) $record->is_passed;
    $grade = (float) $record->grade;
    $threshold = (float) ($record->threshold ?? 50.0);
    $studentName = $record->student?->name ?? $record->user?->name ?? 'Siswa';
    $studentNis = $record->student?->nis ?? '-';
    $examTitle = $record->exam?->title ?? ($details['exam_title'] ?? 'Ujian');
@endphp

<style>
    /* ==========================================================================
       CBT Result Detail — Clean, Compact, Self-Contained Dashboard Styles
       ========================================================================== */
    .cbt-dash-root {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        gap: 1.125rem;
        font-family: inherit;
    }
    .cbt-dash-root * {
        box-sizing: border-box;
    }

    /* 1. Compact Hero Banner */
    .cbt-banner {
        border-radius: 1rem;
        padding: 1.25rem 1.5rem;
        color: #ffffff;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.12);
    }
    .cbt-banner-pass {
        background: linear-gradient(135deg, #059669 0%, #0d9488 50%, #0284c7 100%);
    }
    .cbt-banner-fail {
        background: linear-gradient(135deg, #e11d48 0%, #db2777 50%, #d97706 100%);
    }
    .cbt-banner-left {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        flex: 1;
        min-width: 250px;
    }
    .cbt-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.2rem 0.65rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.22);
        backdrop-filter: blur(4px);
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        width: fit-content;
    }
    .cbt-banner-title {
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1.2;
        margin: 0.15rem 0;
    }
    .cbt-banner-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8125rem;
    }
    .cbt-meta-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: rgba(255, 255, 255, 0.18);
        padding: 0.2rem 0.55rem;
        border-radius: 0.375rem;
        font-weight: 600;
    }
    .cbt-score-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1.25rem;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 0.875rem;
        min-width: 140px;
        text-align: center;
    }
    .cbt-score-title {
        font-size: 0.6875rem;
        text-transform: uppercase;
        font-weight: 800;
        letter-spacing: 0.06em;
        opacity: 0.9;
    }
    .cbt-score-num {
        font-size: 2.25rem;
        font-weight: 900;
        line-height: 1;
        margin: 0.25rem 0;
    }
    .cbt-score-badge {
        font-size: 0.6875rem;
        font-weight: 800;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.3);
    }

    /* 2. Compact 4-Column KPI Grid */
    .cbt-kpi-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.875rem;
        width: 100%;
    }
    @media (max-width: 900px) {
        .cbt-kpi-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 500px) {
        .cbt-kpi-row {
            grid-template-columns: 1fr;
        }
    }
    .cbt-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        padding: 0.875rem 1rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 0.5rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    .dark .cbt-card {
        background: #1e293b;
        border-color: #334155;
        color: #f1f5f9;
    }
    .cbt-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .cbt-card-tag {
        font-size: 0.6875rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .cbt-card-icon {
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .cbt-card-mid {
        display: flex;
        align-items: baseline;
        gap: 0.375rem;
    }
    .cbt-big-num {
        font-size: 1.75rem;
        font-weight: 900;
        line-height: 1;
        color: #0f172a;
    }
    .dark .cbt-big-num {
        color: #ffffff;
    }
    .cbt-num-sub {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 600;
    }
    .dark .cbt-num-sub {
        color: #94a3b8;
    }
    .cbt-card-bot {
        padding-top: 0.375rem;
        border-top: 1px solid #f1f5f9;
        font-size: 0.6875rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #64748b;
    }
    .dark .cbt-card-bot {
        border-top-color: #334155;
        color: #94a3b8;
    }

    /* Colors */
    .cbt-c-green { color: #059669; }
    .cbt-bg-green { background: #d1fae5; color: #059669; }
    .dark .cbt-bg-green { background: rgba(5, 150, 105, 0.2); color: #34d399; }
    .cbt-c-red { color: #e11d48; }
    .cbt-bg-red { background: #ffe4e6; color: #e11d48; }
    .dark .cbt-bg-red { background: rgba(225, 29, 72, 0.2); color: #fb7185; }
    .cbt-c-amber { color: #d97706; }
    .cbt-bg-amber { background: #fef3c7; color: #d97706; }
    .dark .cbt-bg-amber { background: rgba(217, 119, 6, 0.2); color: #fbbf24; }
    .cbt-c-indigo { color: #4f46e5; }
    .cbt-bg-indigo { background: #e0e7ff; color: #4f46e5; }
    .dark .cbt-bg-indigo { background: rgba(79, 70, 229, 0.2); color: #818cf8; }

    /* 3. Section Container */
    .cbt-section-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        padding: 1.125rem 1.25rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    .dark .cbt-section-box {
        background: #1e293b;
        border-color: #334155;
        color: #f1f5f9;
    }
    .cbt-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 0.875rem;
        padding-bottom: 0.625rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .dark .cbt-section-header {
        border-bottom-color: #334155;
    }
    .cbt-header-title {
        font-size: 0.9375rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
    }

    /* 4. Subject Stats */
    .cbt-sub-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.625rem;
        padding: 0.75rem 1rem;
        margin-bottom: 0.625rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .dark .cbt-sub-item {
        background: #0f172a;
        border-color: #334155;
    }
    .cbt-sub-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
        font-size: 0.8125rem;
    }
    .cbt-bar-track {
        height: 6px;
        background: #e2e8f0;
        border-radius: 9999px;
        overflow: hidden;
        display: flex;
    }
    .dark .cbt-bar-track {
        background: #334155;
    }

    /* 5. Filter Toolbar */
    .cbt-filters {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        padding: 0.2rem;
        border-radius: 0.5rem;
        gap: 0.2rem;
    }
    .dark .cbt-filters {
        background: #0f172a;
    }
    .cbt-f-btn {
        padding: 0.3rem 0.65rem;
        font-size: 0.7rem;
        font-weight: 700;
        border: none;
        background: transparent;
        border-radius: 0.375rem;
        cursor: pointer;
        color: #64748b;
        transition: all 0.15s;
    }
    .dark .cbt-f-btn {
        color: #94a3b8;
    }
    .cbt-f-btn.active {
        background: #ffffff;
        color: #4f46e5;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
    }
    .dark .cbt-f-btn.active {
        background: #1e293b;
        color: #818cf8;
    }

    /* 6. Question Review Cards (Compact) */
    .cbt-q-item {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.875rem 1rem;
        margin-bottom: 0.75rem;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }
    .dark .cbt-q-item {
        background: #1e293b;
        border-color: #334155;
    }
    .cbt-q-correct {
        border-left: 4px solid #10b981 !important;
    }
    .cbt-q-incorrect {
        border-left: 4px solid #ef4444 !important;
    }
    .cbt-q-unanswered {
        border-left: 4px solid #f59e0b !important;
    }
    .cbt-q-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding-bottom: 0.375rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.75rem;
    }
    .dark .cbt-q-topbar {
        border-bottom-color: #334155;
    }
    .cbt-q-badge-num {
        background: #4f46e5;
        color: #ffffff;
        font-weight: 800;
        padding: 0.15rem 0.45rem;
        border-radius: 0.3rem;
        font-size: 0.7rem;
    }
    .cbt-q-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.6875rem;
        font-weight: 800;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
    }
    .cbt-q-payload {
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.5;
        color: #0f172a;
    }
    .dark .cbt-q-payload {
        color: #f1f5f9;
    }

    /* Options 2x2 Grid */
    .cbt-opt-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }
    @media (max-width: 640px) {
        .cbt-opt-grid {
            grid-template-columns: 1fr;
        }
    }
    .cbt-opt-card {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        font-size: 0.8125rem;
        background: #f8fafc;
    }
    .dark .cbt-opt-card {
        background: #0f172a;
        border-color: #334155;
    }
    .cbt-opt-chosen-correct {
        background: #ecfdf5 !important;
        border-color: #10b981 !important;
        color: #065f46 !important;
    }
    .dark .cbt-opt-chosen-correct {
        background: rgba(16, 185, 129, 0.15) !important;
        border-color: #059669 !important;
        color: #6ee7b7 !important;
    }
    .cbt-opt-chosen-wrong {
        background: #fef2f2 !important;
        border-color: #ef4444 !important;
        color: #991b1b !important;
    }
    .dark .cbt-opt-chosen-wrong {
        background: rgba(239, 68, 68, 0.15) !important;
        border-color: #dc2626 !important;
        color: #fca5a5 !important;
    }
    .cbt-opt-key {
        border: 1px dashed #10b981 !important;
        background: #f0fdf4 !important;
    }
    .dark .cbt-opt-key {
        background: rgba(16, 185, 129, 0.08) !important;
        border-color: #10b981 !important;
    }
    .cbt-opt-lbl {
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.6875rem;
        flex-shrink: 0;
        background: #e2e8f0;
        color: #334155;
    }
    .dark .cbt-opt-lbl {
        background: #334155;
        color: #f1f5f9;
    }

    /* Explanation */
    .cbt-exp-box {
        background: #f0fdfa;
        border-left: 3px solid #0d9488;
        padding: 0.5rem 0.75rem;
        border-radius: 0 0.375rem 0.375rem 0;
        font-size: 0.75rem;
        color: #134e4a;
    }
    .dark .cbt-exp-box {
        background: rgba(13, 148, 136, 0.15);
        border-left-color: #14b8a6;
        color: #99f6e4;
    }
</style>

<div class="cbt-dash-root" x-data="{ filter: 'all' }">
    {{-- 1. Compact Hero Summary Banner --}}
    <div class="cbt-banner {{ $isPassed ? 'cbt-banner-pass' : 'cbt-banner-fail' }}">
        <div class="cbt-banner-left">
            <span class="cbt-status-pill">
                @if ($isPassed)
                    &#10003; Status: Lulus KKM
                @else
                    &#9888; Status: Belum Lulus KKM
                @endif
            </span>
            <div class="cbt-banner-title">{{ $examTitle }}</div>
            <div class="cbt-banner-meta">
                <span class="cbt-meta-tag">
                    &#128100; {{ $studentName }} (NIS: {{ $studentNis }})
                </span>
                <span class="cbt-meta-tag">
                    Percobaan ke-{{ $record->attempt ?? 1 }}
                </span>
                <span class="cbt-meta-tag">
                    &#128197; {{ $record->completed_at ? $record->completed_at->format('d M Y, H:i') : '-' }}
                </span>
            </div>
        </div>

        <div class="cbt-score-box">
            <span class="cbt-score-title">Nilai Akhir</span>
            <span class="cbt-score-num">{{ number_format($grade, 1) }}</span>
            <span class="cbt-score-badge">KKM: {{ number_format($threshold, 1) }} &bull; {{ $isPassed ? 'LULUS' : 'TIDAK LULUS' }}</span>
        </div>
    </div>

    {{-- 2. Compact 4-Column KPI Grid (Fits horizontally in 1 row) --}}
    <div class="cbt-kpi-row">
        {{-- Benar --}}
        <div class="cbt-card">
            <div class="cbt-card-top">
                <span class="cbt-card-tag cbt-c-green">Jawaban Benar</span>
                <span class="cbt-card-icon cbt-bg-green">&#10003;</span>
            </div>
            <div class="cbt-card-mid">
                <span class="cbt-big-num">{{ $record->correct_answers }}</span>
                <span class="cbt-num-sub">/ {{ $record->total_questions }} Soal</span>
            </div>
            <div class="cbt-card-bot">
                <span>Akurasi</span>
                <strong class="cbt-c-green">{{ $record->total_questions > 0 ? round(($record->correct_answers / $record->total_questions) * 100, 1) : 0 }}% Benar</strong>
            </div>
        </div>

        {{-- Salah --}}
        <div class="cbt-card">
            <div class="cbt-card-top">
                <span class="cbt-card-tag cbt-c-red">Jawaban Salah</span>
                <span class="cbt-card-icon cbt-bg-red">&#10005;</span>
            </div>
            <div class="cbt-card-mid">
                <span class="cbt-big-num">{{ $record->incorrect_answers }}</span>
                <span class="cbt-num-sub">Soal Keliru</span>
            </div>
            <div class="cbt-card-bot">
                <span>Kesalahan</span>
                <strong class="cbt-c-red">{{ $record->total_questions > 0 ? round(($record->incorrect_answers / $record->total_questions) * 100, 1) : 0 }}% Salah</strong>
            </div>
        </div>

        {{-- Kosong --}}
        <div class="cbt-card">
            <div class="cbt-card-top">
                <span class="cbt-card-tag cbt-c-amber">Kosong / Lewat</span>
                <span class="cbt-card-icon cbt-bg-amber">&#63;</span>
            </div>
            <div class="cbt-card-mid">
                <span class="cbt-big-num">{{ $record->unanswered }}</span>
                <span class="cbt-num-sub">Belum Terjawab</span>
            </div>
            <div class="cbt-card-bot">
                <span>Status</span>
                <strong class="cbt-c-amber">{{ $record->total_questions > 0 ? round(($record->unanswered / $record->total_questions) * 100, 1) : 0 }}% Kosong</strong>
            </div>
        </div>

        {{-- Waktu --}}
        <div class="cbt-card">
            <div class="cbt-card-top">
                <span class="cbt-card-tag cbt-c-indigo">Waktu Pengerjaan</span>
                <span class="cbt-card-icon cbt-bg-indigo">&#9201;</span>
            </div>
            <div class="cbt-card-mid">
                <span class="cbt-big-num" style="font-size: 1.35rem;">{{ $record->formatted_duration }}</span>
            </div>
            <div class="cbt-card-bot">
                <span>Metode</span>
                <strong class="cbt-c-indigo">{{ $record->auto_submitted ? 'Waktu Habis' : 'Kumpul Mandiri' }}</strong>
            </div>
        </div>
    </div>

    {{-- 3. Performa per Mata Pelajaran --}}
    @if (!empty($subjectStats))
        <div class="cbt-section-box">
            <div class="cbt-section-header">
                <h3 class="cbt-header-title">&#128218; Performa per Mata Pelajaran</h3>
            </div>
            @foreach ($subjectStats as $stat)
                @php
                    $subPercent = $stat['percentage'] ?? ($stat['total'] > 0 ? round(($stat['correct'] / $stat['total']) * 100, 1) : 0);
                @endphp
                <div class="cbt-sub-item">
                    <div class="cbt-sub-row">
                        <strong>{{ $stat['name'] }}</strong>
                        <div style="display: flex; gap: 0.5rem; align-items: center; font-size: 0.75rem;">
                            <span class="cbt-c-green font-bold">{{ $stat['correct'] }} Benar</span> &bull;
                            <span class="cbt-c-red font-bold">{{ $stat['incorrect'] }} Salah</span> &bull;
                            <span style="color: #64748b;">{{ $stat['unanswered'] }} Kosong</span>
                            <span style="background: #4f46e5; color: #fff; padding: 0.15rem 0.5rem; border-radius: 0.35rem; font-weight: 800;">{{ $subPercent }}%</span>
                        </div>
                    </div>
                    <div class="cbt-bar-track">
                        <div style="background: #10b981; width: {{ $stat['total'] > 0 ? ($stat['correct'] / $stat['total']) * 100 : 0 }}%;"></div>
                        <div style="background: #ef4444; width: {{ $stat['total'] > 0 ? ($stat['incorrect'] / $stat['total']) * 100 : 0 }}%;"></div>
                        <div style="background: #94a3b8; width: {{ $stat['total'] > 0 ? ($stat['unanswered'] / $stat['total']) * 100 : 0 }}%;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- 4. Detail Jawaban & Pembahasan Lengkap --}}
    <div class="cbt-section-box">
        <div class="cbt-section-header">
            <div>
                <h3 class="cbt-header-title">&#128221; Detail Jawaban &amp; Pembahasan Soal</h3>
                <p style="font-size: 0.75rem; color: #64748b; margin: 0.25rem 0 0 0;">
                    Periksa pilihan jawaban peserta dibandingkan dengan kunci jawaban resmi dan pembahasannya.
                </p>
            </div>

            {{-- Filter Tabs --}}
            <div class="cbt-filters">
                <button type="button" @click="filter = 'all'" :class="filter === 'all' ? 'active' : ''" class="cbt-f-btn">
                    Semua ({{ count($reviewQuestions) }})
                </button>
                <button type="button" @click="filter = 'correct'" :class="filter === 'correct' ? 'active' : ''" class="cbt-f-btn">
                    Benar ({{ $record->correct_answers }})
                </button>
                <button type="button" @click="filter = 'incorrect'" :class="filter === 'incorrect' ? 'active' : ''" class="cbt-f-btn">
                    Salah ({{ $record->incorrect_answers }})
                </button>
                <button type="button" @click="filter = 'unanswered'" :class="filter === 'unanswered' ? 'active' : ''" class="cbt-f-btn">
                    Kosong ({{ $record->unanswered }})
                </button>
            </div>
        </div>

        @if (empty($reviewQuestions))
            <div style="text-align: center; padding: 2rem; color: #64748b; font-size: 0.8125rem;">
                Snapshot detail soal tidak tersedia untuk riwayat ini.
            </div>
        @else
            <div>
                @foreach ($reviewQuestions as $idx => $q)
                    @php
                        $status = $q['status'] ?? 'unanswered';
                        $statusBorder = match($status) {
                            'correct' => 'cbt-q-correct',
                            'incorrect' => 'cbt-q-incorrect',
                            default => 'cbt-q-unanswered',
                        };
                        $selectedId = $q['selected_answer_id'] ?? null;
                        $correctId = $q['correct_answer_id'] ?? null;
                        $optionLetters = ['A', 'B', 'C', 'D', 'E', 'F'];
                    @endphp

                    <div 
                        x-show="filter === 'all' || filter === '{{ $status }}'" 
                        class="cbt-q-item {{ $statusBorder }}"
                    >
                        {{-- Question Topbar --}}
                        <div class="cbt-q-topbar">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="cbt-q-badge-num">{{ $q['number'] ?? ($idx + 1) }}</span>
                                <strong>{{ $q['subject_name'] ?? 'Mata Pelajaran' }}</strong>
                                <span style="color: #64748b;">(Bobot: {{ $q['score'] ?? 1 }} Poin)</span>
                            </div>

                            <div>
                                @if ($status === 'correct')
                                    <span class="cbt-q-status-badge cbt-bg-green">&#10003; BENAR (+{{ $q['score'] ?? 1 }} Poin)</span>
                                @elseif ($status === 'incorrect')
                                    <span class="cbt-q-status-badge cbt-bg-red">&#10005; SALAH (0 Poin)</span>
                                @else
                                    <span class="cbt-q-status-badge cbt-bg-amber">&#63; TIDAK DIJAWAB (0 Poin)</span>
                                @endif
                            </div>
                        </div>

                        {{-- Question Content --}}
                        <div class="cbt-q-payload">
                            {!! $q['payload'] ?? '-' !!}
                        </div>

                        {{-- Options in 2x2 Grid --}}
                        <div class="cbt-opt-grid">
                            @foreach ($q['answers'] ?? [] as $optIdx => $ans)
                                @php
                                    $isChosen = ($ans['id'] == $selectedId);
                                    $isCorrect = !empty($ans['is_correct']) || ($ans['id'] == $correctId);
                                    $optLetter = $optionLetters[$optIdx] ?? ($optIdx + 1);

                                    $optClass = '';
                                    if ($isChosen && $isCorrect) {
                                        $optClass = 'cbt-opt-chosen-correct';
                                    } elseif ($isChosen && !$isCorrect) {
                                        $optClass = 'cbt-opt-chosen-wrong';
                                    } elseif ($isCorrect) {
                                        $optClass = 'cbt-opt-key';
                                    }
                                @endphp

                                <div class="cbt-opt-card {{ $optClass }}">
                                    <span class="cbt-opt-lbl">{{ $optLetter }}</span>
                                    <div style="flex: 1; line-height: 1.4;">
                                        {!! $ans['text'] !!}
                                    </div>
                                    <div style="font-size: 0.6875rem; font-weight: 800; display: flex; flex-direction: column; align-items: flex-end; gap: 0.15rem;">
                                        @if ($isChosen)
                                            <span style="background: rgba(0,0,0,0.08); padding: 0.1rem 0.35rem; border-radius: 0.25rem;">Pilihan Siswa</span>
                                        @endif
                                        @if ($isCorrect)
                                            <span style="color: #059669;">&#10003; Kunci Benar</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Explanation Box --}}
                        @if (!empty($q['description']))
                            <div class="cbt-exp-box">
                                <strong>&#128161; Pembahasan:</strong>
                                <div style="margin-top: 0.2rem; line-height: 1.4;">
                                    {!! $q['description'] !!}
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
