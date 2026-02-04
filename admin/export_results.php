<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_admin_permission('view_results');

$format = $_GET['format'] ?? 'csv';
$exam_id = (int)($_GET['exam_id'] ?? 0);

if ($exam_id) {
    $attempts = DB::query('
        SELECT exam_attempts.*, exams.title, students.full_name, students.reg_no
        FROM exam_attempts
        JOIN exams ON exams.id=exam_attempts.exam_id
        JOIN students ON students.id=exam_attempts.student_id
        WHERE exam_attempts.exam_id=%i
        ORDER BY exam_attempts.started_at DESC
    ', $exam_id);
} else {
    $attempts = DB::query('
        SELECT exam_attempts.*, exams.title, students.full_name, students.reg_no
        FROM exam_attempts
        JOIN exams ON exams.id=exam_attempts.exam_id
        JOIN students ON students.id=exam_attempts.student_id
        ORDER BY exam_attempts.started_at DESC
    ');
}

if ($format === 'pdf') {
    require_once __DIR__ . '/../vendor/tcpdf/tcpdf.php';

    // Generate a simple PDF report for results.
    $pdf = new TCPDF('L', 'mm', 'A4');
    $pdf->SetCreator('Codex CBT');
    $pdf->SetAuthor('Codex CBT');
    $pdf->SetTitle('CBT Results');
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'CBT Results Export', 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 10);
    $html = '<table border="1" cellpadding="4">'
        . '<tr>'
        . '<th>Student</th><th>Reg No</th><th>Exam</th><th>Status</th><th>Score</th><th>Submitted</th>'
        . '</tr>';

    foreach ($attempts as $attempt) {
        $score = number_format($attempt['score'], 2) . ' / ' . number_format($attempt['total_marks'], 2);
        $html .= '<tr>'
            . '<td>' . htmlspecialchars($attempt['full_name']) . '</td>'
            . '<td>' . htmlspecialchars($attempt['reg_no']) . '</td>'
            . '<td>' . htmlspecialchars($attempt['title']) . '</td>'
            . '<td>' . htmlspecialchars($attempt['status']) . '</td>'
            . '<td>' . htmlspecialchars($score) . '</td>'
            . '<td>' . htmlspecialchars(format_dt($attempt['submitted_at'])) . '</td>'
            . '</tr>';
    }

    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('cbt-results.pdf', 'D');
    exit;
}

// Default to CSV export.
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="cbt-results.csv"');

$fh = fopen('php://output', 'w');

fputcsv($fh, ['Student', 'Reg No', 'Exam', 'Status', 'Score', 'Submitted']);
foreach ($attempts as $attempt) {
    $score = number_format($attempt['score'], 2) . ' / ' . number_format($attempt['total_marks'], 2);
    fputcsv($fh, [
        $attempt['full_name'],
        $attempt['reg_no'],
        $attempt['title'],
        $attempt['status'],
        $score,
        format_dt($attempt['submitted_at'])
    ]);
}

fclose($fh);
