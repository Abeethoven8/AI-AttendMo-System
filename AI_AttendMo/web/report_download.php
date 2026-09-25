<?php

/*
|--------------------------------------------------------------------------
| AI: Attend Mo - Secure Report Download
|--------------------------------------------------------------------------
*/

$projectRoot =
    dirname(__DIR__);

$reportsPath =
    $projectRoot
    . DIRECTORY_SEPARATOR
    . "reports";


// ============================================================
// ALLOWED REPORT FILES
// ============================================================

$allowedFiles = [

    "overall_attendance_summary.csv",

    "attendance_by_grade.csv",

    "attendance_by_section.csv",

    "attendance_by_subject.csv",

    "daily_attendance_trend.csv",

    "monthly_attendance_trend.csv",

    "frequently_absent_students.csv",

    "diagnostic_student_patterns.csv",

    "diagnostic_weekly_patterns.csv",

    "diagnostic_weekday_patterns.csv",

    "diagnostic_grade_patterns.csv",

    "diagnostic_section_patterns.csv",

    "diagnostic_subject_patterns.csv",

    "inferential_results.csv",

    "inferential_assumption_checks.csv",

    "subject_posthoc_results.csv",

    "decision_tree_metrics.csv",

    "decision_tree_confusion_matrix.csv",

    "decision_tree_classification_report.csv",

    "decision_tree_feature_importance.csv",

    "decision_tree_test_predictions.csv",

    "decision_tree_cross_validation.csv",

    "decision_tree_cross_validation_summary.csv",

    "student_recommendations.csv",

    "cleaning_report.csv",

    "feature_engineering_report.csv",

    "rejected_records.csv"
];


$fileName =
    basename(
        $_GET["file"] ?? ""
    );


if (
    $fileName === ""
    ||
    !in_array(
        $fileName,
        $allowedFiles,
        true
    )
) {

    http_response_code(400);

    exit(
        "Invalid report file."
    );
}


$filePath =
    $reportsPath
    . DIRECTORY_SEPARATOR
    . $fileName;


if (!file_exists($filePath)) {

    http_response_code(404);

    exit(
        "Report file not found."
    );
}


// ============================================================
// DOWNLOAD HEADERS
// ============================================================

header(
    "Content-Type: text/csv; charset=UTF-8"
);

header(
    'Content-Disposition: attachment; filename="'
    . $fileName
    . '"'
);

header(
    "Content-Length: "
    . filesize($filePath)
);

header(
    "X-Content-Type-Options: nosniff"
);


// ============================================================
// SEND FILE
// ============================================================

readfile(
    $filePath
);

exit;