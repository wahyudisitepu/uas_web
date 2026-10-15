<?php

namespace App\Filament\Test\Resources\Exams\Pages;

use App\Filament\Test\Resources\Exams\ExamResource;
use App\Models\Exam;
use App\Models\ExamResult;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StartingExam extends Page
{
    protected static string $resource = ExamResource::class;

    protected string $view = 'filament.test.resources.exams.pages.starting-exam';

    public ?Exam $exam = null;

    /**
     * Grouped collections by subject (for backward compatibility)
     */
    public array $collections = [];

    /**
     * Flat array of all questions with global continuous numbering
     */
    public array $questions = [];

    /**
     * Array of selected answers [question_id => answer_id]
     */
    public array $selectedAnswers = [];

    /**
     * Array of doubtful questions [question_id => true]
     */
    public array $doubtfulAnswers = [];

    /**
     * Current active question index (0-based) for focus mode
     */
    public int $currentQuestionIndex = 0;

    /**
     * View mode: 'focus' (soal per soal) or 'all' (semua soal)
     */
    public string $viewMode = 'focus';

    /**
     * Font size for question payload: 'small', 'normal', 'large'
     */
    public string $fontSize = 'normal';

    /**
     * Remaining seconds for the countdown timer
     */
    public int $remainingSeconds = 0;

    /**
     * Total exam duration in seconds
     */
    public int $totalDurationSeconds = 0;

    /**
     * Formatted start time string
     */
    public string $startTimeFormatted = '';

    /**
     * Whether the exam has been completed/submitted
     */
    public bool $isFinished = false;

    /**
     * Evaluated exam results summary and question-by-question review
     */
    public array $examResult = [];

    /**
     * Confirmation modal state before final submission
     */
    public bool $showFinishConfirmModal = false;

    /**
     * Filter review in result view: 'all', 'correct', 'incorrect', 'unanswered'
     */
    public string $filterResultReview = 'all';

    public function mount(Exam $exam): void
    {
        $this->exam = $exam;
        $userId = Auth::id();

        // 1. Check if the student has already completed this exam
        $existingDbResult = ExamResult::where('exam_id', $exam->id)
            ->where('user_id', $userId)
            ->latest()
            ->first();

        $sessionFinished = session("exam_finished_{$exam->id}_{$userId}", false);
        $sessionResult = session("exam_result_{$exam->id}_{$userId}");

        if (($existingDbResult && $existingDbResult->result_details) || ($sessionFinished && $sessionResult)) {
            $this->isFinished = true;
            $this->examResult = $existingDbResult ? $existingDbResult->result_details : $sessionResult;
            $this->questions = $this->examResult['questions_snapshot'] ?? [];
            $this->selectedAnswers = $this->examResult['selected_answers_snapshot'] ?? [];
            return;
        }

        // 2. Load or initialize question list for this exam session
        $sessionQuestionsKey = "exam_questions_{$exam->id}_{$userId}";
        $sessionCollectionsKey = "exam_collections_{$exam->id}_{$userId}";

        $cachedQuestions = session($sessionQuestionsKey);
        if (!empty($cachedQuestions) && is_array($cachedQuestions) && count($cachedQuestions) > 0) {
            $this->questions = $cachedQuestions;
            $this->collections = session($sessionCollectionsKey, []);
        } else {
            $this->questions = [];
            $this->collections = [];
            $globalIndex = 0;

            $pelajarans = $exam->subjects ?? [];
            foreach ($pelajarans as $mapel) {
                $qty = (int) ($mapel->pivot->qty ?? 100);
                if ($qty <= 0) $qty = 100;

                $soals = $mapel
                    ->questions()
                    ->where('is_active', true)
                    ->with([
                        'answers' => fn ($query) => $query->where('is_active', true)->inRandomOrder(),
                    ])
                    ->inRandomOrder()
                    ->limit($qty)
                    ->get();

                $soalsArray = $soals->toArray();

                $this->collections[] = [
                    'id'    => $mapel->id,
                    'name'  => $mapel->name,
                    'soals' => $soalsArray,
                ];

                foreach ($soalsArray as $s) {
                    $this->questions[] = [
                        'index' => $globalIndex,
                        'number' => $globalIndex + 1,
                        'id' => $s['id'],
                        'subject_id' => $mapel->id,
                        'subject_name' => $mapel->name,
                        'payload' => $s['payload'],
                        'score' => (float) ($s['score'] ?? 1),
                        'description' => $s['description'] ?? null,
                        'answers' => $s['answers'],
                    ];
                    $globalIndex++;
                }
            }

            // Only persist to session if we actually got questions
            if (!empty($this->questions)) {
                session([
                    $sessionQuestionsKey => $this->questions,
                    $sessionCollectionsKey => $this->collections,
                ]);
            } else {
                session()->forget([$sessionQuestionsKey, $sessionCollectionsKey]);
            }
        }

        // 3. Restore previous answers and flags if any
        $this->selectedAnswers = session("exam_answers_{$exam->id}_{$userId}", []);
        $this->doubtfulAnswers = session("exam_doubtful_{$exam->id}_{$userId}", []);

        // 4. Calculate timing and countdown
        $sessionStartKey = "exam_start_{$exam->id}_{$userId}";
        $sessionEndKey = "exam_end_{$exam->id}_{$userId}";

        // If there are no questions available, do not start or burn exam time
        if (count($this->questions) === 0) {
            session()->forget([$sessionStartKey, $sessionEndKey]);
            $this->remainingSeconds = 0;
            return;
        }

        $startTimeStr = session($sessionStartKey);
        if (!$startTimeStr) {
            $startTimeStr = now()->toIso8601String();
            session([$sessionStartKey => $startTimeStr]);
        }
        $this->startTimeFormatted = Carbon::parse($startTimeStr)->format('d F Y, H:i');

        $durationMinutes = max(1, (int) $exam->duration);
        $this->totalDurationSeconds = $durationMinutes * 60;

        $endTimeStr = session($sessionEndKey);
        if (!$endTimeStr) {
            $calculatedEnd = Carbon::parse($startTimeStr)->addMinutes($durationMinutes);

            if ($exam->exact_time && $exam->started_at) {
                $exactEnd = Carbon::parse($exam->started_at)->addMinutes($durationMinutes);
                if ($exactEnd->lt($calculatedEnd)) {
                    $calculatedEnd = $exactEnd;
                }
            }

            if ($exam->expired_at) {
                $expiredAt = Carbon::parse($exam->expired_at);
                if ($expiredAt->lt($calculatedEnd)) {
                    $calculatedEnd = $expiredAt;
                }
            }

            $endTimeStr = $calculatedEnd->toIso8601String();
            session([$sessionEndKey => $endTimeStr]);
        }

        $endCarbon = Carbon::parse($endTimeStr);
        $this->remainingSeconds = max(0, $endCarbon->timestamp - now()->timestamp);

        // Auto submit if time is already expired when opening
        if ($this->remainingSeconds <= 0 && count($this->questions) > 0) {
            $this->finishExam(true);
        }
    }

    public function selectAnswer(int $questionId, int $answerId): void
    {
        if ($this->isFinished) {
            return;
        }

        $this->selectedAnswers[$questionId] = $answerId;
        $userId = Auth::id();
        session(["exam_answers_{$this->exam->id}_{$userId}" => $this->selectedAnswers]);
    }

    public function clearAnswer(int $questionId): void
    {
        if ($this->isFinished) {
            return;
        }

        if (isset($this->selectedAnswers[$questionId])) {
            unset($this->selectedAnswers[$questionId]);
            $userId = Auth::id();
            session(["exam_answers_{$this->exam->id}_{$userId}" => $this->selectedAnswers]);
        }
    }

    public function toggleDoubtful(int $questionId): void
    {
        if ($this->isFinished) {
            return;
        }

        $current = $this->doubtfulAnswers[$questionId] ?? false;
        $this->doubtfulAnswers[$questionId] = !$current;

        $userId = Auth::id();
        session(["exam_doubtful_{$this->exam->id}_{$userId}" => $this->doubtfulAnswers]);
    }

    public function goToQuestion(int $index): void
    {
        if ($index >= 0 && $index < count($this->questions)) {
            $this->currentQuestionIndex = $index;
        }
    }

    public function nextQuestion(): void
    {
        if ($this->currentQuestionIndex < count($this->questions) - 1) {
            $this->currentQuestionIndex++;
        }
    }

    public function previousQuestion(): void
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        }
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['focus', 'all'])) {
            $this->viewMode = $mode;
        }
    }

    public function setFontSize(string $size): void
    {
        if (in_array($size, ['small', 'normal', 'large'])) {
            $this->fontSize = $size;
        }
    }

    public function setFilterReview(string $filter): void
    {
        if (in_array($filter, ['all', 'correct', 'incorrect', 'unanswered'])) {
            $this->filterResultReview = $filter;
        }
    }

    public function openFinishModal(): void
    {
        $this->showFinishConfirmModal = true;
    }

    public function closeFinishModal(): void
    {
        $this->showFinishConfirmModal = false;
    }

    public function finishExam(bool $autoSubmitted = false): void
    {
        if ($this->isFinished) {
            return;
        }

        $totalQuestions = count($this->questions);
        $correctCount = 0;
        $incorrectCount = 0;
        $unansweredCount = 0;
        $totalEarnedScore = 0.0;
        $maxPossibleScore = 0.0;

        $subjectStats = [];
        $reviewQuestions = [];

        foreach ($this->questions as $q) {
            $qId = $q['id'];
            $qScore = (float) ($q['score'] ?? 1);
            $maxPossibleScore += $qScore;

            $subId = $q['subject_id'];
            $subName = $q['subject_name'];

            if (!isset($subjectStats[$subId])) {
                $subjectStats[$subId] = [
                    'id' => $subId,
                    'name' => $subName,
                    'total' => 0,
                    'correct' => 0,
                    'incorrect' => 0,
                    'unanswered' => 0,
                    'earned_score' => 0.0,
                    'max_score' => 0.0,
                ];
            }

            $subjectStats[$subId]['total']++;
            $subjectStats[$subId]['max_score'] += $qScore;

            $selectedAnsId = $this->selectedAnswers[$qId] ?? null;
            $selectedAnsText = null;
            $correctAnsId = null;
            $correctAnsText = null;

            foreach ($q['answers'] as $ans) {
                if (!empty($ans['is_correct'])) {
                    $correctAnsId = $ans['id'];
                    $correctAnsText = $ans['text'];
                }
                if ($ans['id'] == $selectedAnsId) {
                    $selectedAnsText = $ans['text'];
                }
            }

            $isCorrect = false;
            $status = 'unanswered';

            if ($selectedAnsId === null) {
                $unansweredCount++;
                $subjectStats[$subId]['unanswered']++;
            } elseif ($selectedAnsId == $correctAnsId) {
                $isCorrect = true;
                $status = 'correct';
                $correctCount++;
                $totalEarnedScore += $qScore;
                $subjectStats[$subId]['correct']++;
                $subjectStats[$subId]['earned_score'] += $qScore;
            } else {
                $status = 'incorrect';
                $incorrectCount++;
                $subjectStats[$subId]['incorrect']++;
            }

            $reviewQuestions[] = [
                'id' => $qId,
                'number' => $q['number'],
                'subject_id' => $subId,
                'subject_name' => $subName,
                'payload' => $q['payload'],
                'score' => $qScore,
                'description' => $q['description'] ?? null,
                'status' => $status,
                'is_correct' => $isCorrect,
                'selected_answer_id' => $selectedAnsId,
                'selected_answer_text' => $selectedAnsText,
                'correct_answer_id' => $correctAnsId,
                'correct_answer_text' => $correctAnsText,
                'answers' => $q['answers'],
            ];
        }

        $finalGrade = $maxPossibleScore > 0
            ? round(($totalEarnedScore / $maxPossibleScore) * 100, 2)
            : 0.0;

        $threshold = (float) ($this->exam->threshold ?? 50.0);
        $isPassed = $finalGrade >= $threshold;

        $now = now();
        $userId = Auth::id();
        $startTimeStr = session("exam_start_{$this->exam->id}_{$userId}");
        $startCarbon = $startTimeStr ? Carbon::parse($startTimeStr) : $now;
        $durationSeconds = max(1, $now->diffInSeconds($startCarbon));

        $hours = floor($durationSeconds / 3600);
        $mins = floor(($durationSeconds % 3600) / 60);
        $secs = $durationSeconds % 60;
        $durationFormatted = $hours > 0
            ? sprintf('%02d jam %02d menit %02d detik', $hours, $mins, $secs)
            : sprintf('%02d menit %02d detik', $mins, $secs);

        // Subject stats percentage calculation
        foreach ($subjectStats as &$stat) {
            $stat['percentage'] = $stat['total'] > 0
                ? round(($stat['correct'] / $stat['total']) * 100, 1)
                : 0.0;
        }
        unset($stat);

        $this->examResult = [
            'exam_id' => $this->exam->id,
            'exam_title' => $this->exam->title,
            'student_name' => Auth::user()->name,
            'student_nis' => Auth::user()->username,
            'auto_submitted' => $autoSubmitted,
            'started_at' => $startCarbon->format('d M Y, H:i:s'),
            'completed_at' => $now->format('d M Y, H:i:s'),
            'duration_formatted' => $durationFormatted,
            'duration_seconds' => $durationSeconds,
            'total_questions' => $totalQuestions,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'unanswered_count' => $unansweredCount,
            'total_earned_score' => $totalEarnedScore,
            'max_possible_score' => $maxPossibleScore,
            'final_grade' => $finalGrade,
            'threshold' => $threshold,
            'is_passed' => $isPassed,
            'subject_stats' => array_values($subjectStats),
            'review_questions' => $reviewQuestions,
            'questions_snapshot' => $this->questions,
            'selected_answers_snapshot' => $this->selectedAnswers,
        ];

        // 1. Save in session
        session([
            "exam_result_{$this->exam->id}_{$userId}" => $this->examResult,
            "exam_finished_{$this->exam->id}_{$userId}" => true,
        ]);

        // 2. Persist to database (save in history)
        try {
            $previousAttempts = ExamResult::where('exam_id', $this->exam->id)
                ->where('user_id', $userId)
                ->count();
            $attemptNumber = $previousAttempts + 1;

            ExamResult::create([
                'exam_id' => $this->exam->id,
                'user_id' => $userId,
                'student_id' => Auth::user()->student?->id,
                'attempt' => $attemptNumber,
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctCount,
                'incorrect_answers' => $incorrectCount,
                'unanswered' => $unansweredCount,
                'total_score' => $totalEarnedScore,
                'max_score' => $maxPossibleScore,
                'grade' => $finalGrade,
                'threshold' => $threshold,
                'is_passed' => $isPassed,
                'auto_submitted' => $autoSubmitted,
                'duration_seconds' => $durationSeconds,
                'answers_payload' => $this->selectedAnswers,
                'result_details' => $this->examResult,
                'completed_at' => $now,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to persist exam result: ' . $e->getMessage());
        }

        $this->isFinished = true;
        $this->showFinishConfirmModal = false;
    }

    /**
     * Retake / reset exam (useful for testing or if retake is permitted)
     */
    public function retakeExam(): void
    {
        $userId = Auth::id();
        $examId = $this->exam->id;

        try {
            ExamResult::where('exam_id', $examId)->where('user_id', $userId)->delete();
        } catch (\Throwable $e) {
            // Ignore
        }

        session()->forget([
            "exam_result_{$examId}_{$userId}",
            "exam_finished_{$examId}_{$userId}",
            "exam_questions_{$examId}_{$userId}",
            "exam_collections_{$examId}_{$userId}",
            "exam_answers_{$examId}_{$userId}",
            "exam_doubtful_{$examId}_{$userId}",
            "exam_start_{$examId}_{$userId}",
            "exam_end_{$examId}_{$userId}",
        ]);

        $this->isFinished = false;
        $this->examResult = [];
        $this->selectedAnswers = [];
        $this->doubtfulAnswers = [];
        $this->currentQuestionIndex = 0;
        $this->showFinishConfirmModal = false;

        $this->mount($this->exam);
    }

    public function getAnsweredCount(): int
    {
        return count($this->selectedAnswers);
    }

    public function getDoubtfulCount(): int
    {
        return count(array_filter($this->doubtfulAnswers));
    }

    public function getUnansweredCount(): int
    {
        return max(0, count($this->questions) - $this->getAnsweredCount());
    }

    public function getProgressPercentage(): int
    {
        $total = count($this->questions);
        if ($total <= 0) return 0;
        return (int) round(($this->getAnsweredCount() / $total) * 100);
    }

    public function getTitle(): string | Htmlable
    {
        if ($this->isFinished) {
            return 'Hasil Ujian: ' . ($this->exam?->title ?? 'Selesai');
        }

        return $this->exam ? $this->exam->title : 'Halaman Ujian';
    }

    public function getHeading(): string | Htmlable
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [
            ExamResource::getUrl('index') => 'Sesi Ujian',
            '#' => $this->exam?->title ?? 'Ujian',
        ];
    }
}
