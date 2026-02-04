<?php
require_once __DIR__ . '/header.php';

$attempt_id = (int)($_GET['attempt_id'] ?? 0);
$student_id = $_SESSION['student']['id'];

$attempt = DB::queryFirstRow('
    SELECT exam_attempts.*, exams.title, exams.instructions, exams.duration_minutes, exams.show_result
    FROM exam_attempts
    JOIN exams ON exams.id=exam_attempts.exam_id
    WHERE exam_attempts.id=%i AND exam_attempts.student_id=%i
', $attempt_id, $student_id);

if (!$attempt) {
    redirect('/codexCbt/student/dashboard.php');
}

if ($attempt['status'] !== 'in_progress') {
    // Clear active attempt marker once exam is no longer in progress.
    unset($_SESSION['active_exam_attempt_id']);
    redirect('/codexCbt/student/results.php');
}

$questions = DB::query('
    SELECT questions.*
    FROM exam_attempt_questions
    JOIN questions ON questions.id=exam_attempt_questions.question_id
    WHERE exam_attempt_questions.attempt_id=%i
    ORDER BY questions.id
', $attempt_id);

$question_ids = array_map(function ($q) { return (int)$q['id']; }, $questions);
$options = [];
if ($question_ids) {
    $options = DB::query('SELECT * FROM question_options WHERE question_id IN %li ORDER BY id', $question_ids);
}
$option_map = [];
foreach ($options as $opt) {
    $option_map[$opt['question_id']][] = $opt;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_at = now_mysql();
    $score = 0;
    $total_marks = 0;
    $has_essay = false;

    // Grade auto-marked question types immediately.
    foreach ($questions as $question) {
        $qid = (int)$question['id'];
        $total_marks += (int)$question['marks'];

        if ($question['question_type'] === 'mcq') {
            $selected_option_id = (int)($_POST['answer'][$qid] ?? 0);
            $selected = null;
            if ($selected_option_id) {
                $selected = DB::queryFirstRow('SELECT * FROM question_options WHERE id=%i AND question_id=%i', $selected_option_id, $qid);
            }
            $is_correct = $selected && (int)$selected['is_correct'] === 1;
            $marks_awarded = $is_correct ? (int)$question['marks'] : 0;
            $score += $marks_awarded;

            DB::insert('exam_answers', [
                'attempt_id' => $attempt_id,
                'question_id' => $qid,
                'selected_option_id' => $selected_option_id ?: null,
                'answer_text' => $selected['option_text'] ?? null,
                'is_correct' => $is_correct ? 1 : 0,
                'marks_awarded' => $marks_awarded
            ]);
        } elseif ($question['question_type'] === 'fill') {
            $answer_text = trim($_POST['answer'][$qid] ?? '');
            $expected = trim($question['correct_answer'] ?? '');
            $is_correct = strcasecmp($answer_text, $expected) === 0;
            $marks_awarded = $is_correct ? (int)$question['marks'] : 0;
            $score += $marks_awarded;

            DB::insert('exam_answers', [
                'attempt_id' => $attempt_id,
                'question_id' => $qid,
                'answer_text' => $answer_text,
                'is_correct' => $is_correct ? 1 : 0,
                'marks_awarded' => $marks_awarded
            ]);
        } else {
            $has_essay = true;
            $answer_text = trim($_POST['answer'][$qid] ?? '');
            DB::insert('exam_answers', [
                'attempt_id' => $attempt_id,
                'question_id' => $qid,
                'answer_text' => $answer_text,
                'is_correct' => null,
                'marks_awarded' => null
            ]);
        }
    }

    $status = $has_essay ? 'submitted' : 'graded';
    DB::update('exam_attempts', [
        'submitted_at' => $submitted_at,
        'status' => $status,
        'score' => $score,
        'total_marks' => $total_marks
    ], 'id=%i', $attempt_id);

    // Clear active attempt marker on submission.
    unset($_SESSION['active_exam_attempt_id']);
    redirect('/codexCbt/student/results.php');
}

$end_time = strtotime($attempt['started_at']) + ((int)$attempt['duration_minutes'] * 60);
?>
<style>
    .exam-sticky-bar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1030;
        background: #f6f8fb;
        border-bottom: 1px solid #e5e7eb;
    }
    .exam-sticky-spacer {
        height: 70px;
    }
    .question-card {
        display: none;
    }
    .question-card.active {
        display: block;
    }
    .question-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 16px;
    }
    .question-nav button {
        min-width: 36px;
    }
    .question-nav .answered {
        background: #22c55e;
        color: #fff;
        border-color: #16a34a;
    }
</style>

<div class="exam-sticky-bar">
    <div class="container d-flex justify-content-between align-items-center py-2">
        <h4 class="mb-0"><?php echo htmlspecialchars($attempt['title']); ?></h4>
        <div class="timer-pill" id="examTimer" data-end="<?php echo $end_time; ?>">--:--</div>
    </div>
</div>
<!-- <div class="exam-sticky-spacer"></div> -->

<?php if (!empty($attempt['instructions'])): ?>
    <div class="alert alert-info">
        <strong>Instruction: </strong><?php echo nl2br(htmlspecialchars($attempt['instructions'])); ?>
    </div>
<?php endif; ?>

<form method="post" id="examForm">
    <div class="question-nav" id="questionNav"></div>
    <?php foreach ($questions as $index => $question): ?>
        <div class="exam-question question-card" data-question-id="<?php echo (int)$question['id']; ?>">
            <div class="fw-semibold mb-2">
                Q<?php echo $index + 1; ?>. <?php echo htmlspecialchars($question['question_text']); ?>
                <span class="badge bg-secondary ms-2">Marks: <?php echo (int)$question['marks']; ?></span>
            </div>
            <?php if ($question['question_type'] === 'mcq'): ?>
                <?php foreach ($option_map[$question['id']] ?? [] as $opt): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="answer[<?php echo (int)$question['id']; ?>]" value="<?php echo (int)$opt['id']; ?>">
                        <label class="form-check-label"><?php echo htmlspecialchars($opt['option_text']); ?></label>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($question['question_type'] === 'fill'): ?>
                <input type="text" name="answer[<?php echo (int)$question['id']; ?>]" class="form-control">
            <?php else: ?>
                <textarea name="answer[<?php echo (int)$question['id']; ?>]" class="form-control" rows="4"></textarea>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-outline-secondary" type="button" id="prevQuestionBtn">Previous</button>
        <button class="btn btn-outline-secondary" type="button" id="nextQuestionBtn">Next</button>
    </div>

    <button class="btn btn-primary" type="submit">Submit Exam</button>
</form>

<script>
(function () {
    const timer = document.getElementById('examTimer');
    const end = parseInt(timer.getAttribute('data-end'), 10) * 1000;
    const form = document.getElementById('examForm');
    let isAutoSubmit = false;
    const storageKey = 'cbt_attempt_' + <?php echo (int)$attempt_id; ?>;
    const questionCards = Array.from(document.querySelectorAll('.question-card'));
    const questionNav = document.getElementById('questionNav');
    const answers = {};
    const prevBtn = document.getElementById('prevQuestionBtn');
    const nextBtn = document.getElementById('nextQuestionBtn');
    let currentIndex = 0;

    function getAnswerValue(card) {
        const qid = card.getAttribute('data-question-id');
        const radios = card.querySelectorAll('input[type="radio"]');
        if (radios.length) {
            const checked = card.querySelector('input[type="radio"]:checked');
            return checked ? checked.value : '';
        }
        const textInput = card.querySelector('input[type="text"]');
        if (textInput) {
            return textInput.value.trim();
        }
        const textarea = card.querySelector('textarea');
        if (textarea) {
            return textarea.value.trim();
        }
        return '';
    }

    function setAnswerValue(card, value) {
        const radios = card.querySelectorAll('input[type="radio"]');
        if (radios.length) {
            radios.forEach(radio => {
                radio.checked = radio.value === value;
            });
            return;
        }
        const textInput = card.querySelector('input[type="text"]');
        if (textInput) {
            textInput.value = value || '';
            return;
        }
        const textarea = card.querySelector('textarea');
        if (textarea) {
            textarea.value = value || '';
        }
    }

    function saveAnswers() {
        questionCards.forEach(card => {
            const qid = card.getAttribute('data-question-id');
            answers[qid] = getAnswerValue(card);
        });
        localStorage.setItem(storageKey, JSON.stringify(answers));
        updateNav();
    }

    function loadAnswers() {
        const saved = localStorage.getItem(storageKey);
        if (!saved) {
            return;
        }
        const data = JSON.parse(saved);
        questionCards.forEach(card => {
            const qid = card.getAttribute('data-question-id');
            if (data[qid] !== undefined) {
                setAnswerValue(card, data[qid]);
            }
        });
    }

    function updateNav() {
        questionCards.forEach((card, idx) => {
            const qid = card.getAttribute('data-question-id');
            const btn = questionNav.querySelector('button[data-question-index="' + idx + '"]');
            const isAnswered = !!(answers[qid] || getAnswerValue(card));
            if (btn) {
                btn.classList.toggle('answered', isAnswered);
            }
        });
    }

    function showQuestion(index) {
        currentIndex = index;
        questionCards.forEach((card, idx) => {
            card.classList.toggle('active', idx === index);
        });
        questionNav.querySelectorAll('button').forEach((btn, idx) => {
            btn.classList.toggle('btn-primary', idx === index);
            btn.classList.toggle('btn-outline-secondary', idx !== index);
        });
        prevBtn.disabled = index === 0;
        nextBtn.disabled = index === (questionCards.length - 1);
    }

    questionCards.forEach((card, idx) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-secondary btn-sm';
        btn.textContent = (idx + 1);
        btn.setAttribute('data-question-index', idx);
        btn.addEventListener('click', function () {
            saveAnswers();
            showQuestion(idx);
        });
        questionNav.appendChild(btn);

        card.addEventListener('input', function () {
            saveAnswers();
        });
    });

    loadAnswers();
    saveAnswers();
    showQuestion(0);

    prevBtn.addEventListener('click', function () {
        if (currentIndex > 0) {
            saveAnswers();
            showQuestion(currentIndex - 1);
        }
    });

    nextBtn.addEventListener('click', function () {
        if (currentIndex < questionCards.length - 1) {
            saveAnswers();
            showQuestion(currentIndex + 1);
        }
    });

    form.addEventListener('submit', function (event) {
        if (!isAutoSubmit) {
            saveAnswers();
            const unanswered = questionCards.filter(card => !getAnswerValue(card)).length;
            let message = 'You are about to submit your exam. Continue?';
            if (unanswered > 0) {
                message = 'You have ' + unanswered + ' unanswered question(s). Submit anyway?';
            }
            const ok = confirm(message);
            if (!ok) {
                event.preventDefault();
            }
        }
    });

    window.addEventListener('beforeunload', function (event) {
        if (!isAutoSubmit) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    function tick() {
        const now = Date.now();
        const diff = end - now;
        if (diff <= 0) {
            timer.textContent = '00:00';
            isAutoSubmit = true;
            form.submit();
            return;
        }
        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        timer.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
    }

    tick();
    setInterval(tick, 1000);
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
