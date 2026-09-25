from pathlib import Path
import pandas as pd

# ============================================================
# AI AttendMo - FEATURE ENGINEERING
# ============================================================

BASE_DIR = Path(__file__).resolve().parents[1]

CLEAN_FILE = BASE_DIR / "data" / "attendance_cleaned.csv"

STUDENT_FEATURE_FILE = (
    BASE_DIR / "data" / "student_features.csv"
)

SUBJECT_FEATURE_FILE = (
    BASE_DIR / "data" / "student_subject_attendance.csv"
)

REPORT_FILE = (
    BASE_DIR / "reports" / "feature_engineering_report.csv"
)


# ============================================================
# 1. LOAD CLEAN DATA
# ============================================================

attendance = pd.read_csv(CLEAN_FILE)

attendance["Date"] = pd.to_datetime(
    attendance["Date"],
    format="%Y-%m-%d"
)

print("=" * 65)
print("AI AttendMo - FEATURE ENGINEERING")
print("=" * 65)

print(f"\nClean attendance records: {len(attendance)}")


# ============================================================
# 2. CREATE PRESENT / ABSENT INDICATORS
# ============================================================

attendance["Present_Flag"] = (
    attendance["Attendance_Status"] == "Present"
).astype(int)

attendance["Absent_Flag"] = (
    attendance["Attendance_Status"] == "Absent"
).astype(int)


# ============================================================
# 3. BASIC STUDENT FEATURES
# ============================================================

student_features = (
    attendance
    .groupby(
        [
            "Student_ID",
            "Grade_Level",
            "Section"
        ],
        as_index=False
    )
    .agg(
        Total_Sessions=(
            "Attendance_Status",
            "count"
        ),

        Present_Count=(
            "Present_Flag",
            "sum"
        ),

        Absence_Count=(
            "Absent_Flag",
            "sum"
        )
    )
)


# ============================================================
# 4. ATTENDANCE RATE
# ============================================================

student_features["Attendance_Rate"] = (
    student_features["Present_Count"]
    /
    student_features["Total_Sessions"]
)


# ============================================================
# 5. ABSENCE FREQUENCY
# ============================================================

student_features["Absence_Frequency"] = (
    student_features["Absence_Count"]
    /
    student_features["Total_Sessions"]
)


# ============================================================
# 6. DISTINCT ABSENT DAYS
# ============================================================

absent_records = attendance[
    attendance["Attendance_Status"] == "Absent"
]

absent_days = (
    absent_records
    .groupby("Student_ID")["Date"]
    .nunique()
    .rename("Distinct_Absent_Days")
)

student_features = student_features.merge(
    absent_days,
    on="Student_ID",
    how="left"
)

student_features["Distinct_Absent_Days"] = (
    student_features["Distinct_Absent_Days"]
    .fillna(0)
    .astype(int)
)


# ============================================================
# 7. ATTENDANCE TREND
# ============================================================

unique_dates = sorted(
    attendance["Date"].unique()
)

midpoint = len(unique_dates) // 2

first_half_dates = set(
    unique_dates[:midpoint]
)

second_half_dates = set(
    unique_dates[midpoint:]
)


first_half = attendance[
    attendance["Date"].isin(first_half_dates)
]

second_half = attendance[
    attendance["Date"].isin(second_half_dates)
]


first_half_summary = (
    first_half
    .groupby("Student_ID")
    .agg(
        First_Half_Total=(
            "Attendance_Status",
            "count"
        ),

        First_Half_Present=(
            "Present_Flag",
            "sum"
        )
    )
)

first_half_summary[
    "First_Half_Attendance_Rate"
] = (
    first_half_summary["First_Half_Present"]
    /
    first_half_summary["First_Half_Total"]
)


second_half_summary = (
    second_half
    .groupby("Student_ID")
    .agg(
        Second_Half_Total=(
            "Attendance_Status",
            "count"
        ),

        Second_Half_Present=(
            "Present_Flag",
            "sum"
        )
    )
)

second_half_summary[
    "Second_Half_Attendance_Rate"
] = (
    second_half_summary["Second_Half_Present"]
    /
    second_half_summary["Second_Half_Total"]
)


student_features = student_features.merge(
    first_half_summary[
        ["First_Half_Attendance_Rate"]
    ],
    on="Student_ID",
    how="left"
)

student_features = student_features.merge(
    second_half_summary[
        ["Second_Half_Attendance_Rate"]
    ],
    on="Student_ID",
    how="left"
)


student_features["Attendance_Trend_Change"] = (
    student_features[
        "Second_Half_Attendance_Rate"
    ]
    -
    student_features[
        "First_Half_Attendance_Rate"
    ]
)


# ============================================================
# 8. SUBJECT ATTENDANCE FEATURES
# ============================================================

subject_summary = (
    attendance
    .groupby(
        [
            "Student_ID",
            "Subject"
        ],
        as_index=False
    )
    .agg(
        Subject_Total_Sessions=(
            "Attendance_Status",
            "count"
        ),

        Subject_Present_Count=(
            "Present_Flag",
            "sum"
        ),

        Subject_Absence_Count=(
            "Absent_Flag",
            "sum"
        )
    )
)


subject_summary[
    "Subject_Attendance_Rate"
] = (
    subject_summary["Subject_Present_Count"]
    /
    subject_summary["Subject_Total_Sessions"]
)


# ============================================================
# 9. LOWEST SUBJECT ATTENDANCE RATE
# ============================================================

lowest_subject = (
    subject_summary
    .sort_values(
        [
            "Student_ID",
            "Subject_Attendance_Rate"
        ]
    )
    .groupby("Student_ID")
    .first()
    .reset_index()
)


lowest_subject = lowest_subject[
    [
        "Student_ID",
        "Subject",
        "Subject_Attendance_Rate"
    ]
].rename(
    columns={
        "Subject":
            "Lowest_Attendance_Subject",

        "Subject_Attendance_Rate":
            "Lowest_Subject_Attendance_Rate"
    }
)


student_features = student_features.merge(
    lowest_subject,
    on="Student_ID",
    how="left"
)


# ============================================================
# 10. ROUND RATE VALUES
# ============================================================

rate_columns = [
    "Attendance_Rate",
    "Absence_Frequency",
    "First_Half_Attendance_Rate",
    "Second_Half_Attendance_Rate",
    "Attendance_Trend_Change",
    "Lowest_Subject_Attendance_Rate"
]

student_features[rate_columns] = (
    student_features[rate_columns]
    .round(4)
)

subject_summary[
    "Subject_Attendance_Rate"
] = (
    subject_summary[
        "Subject_Attendance_Rate"
    ]
    .round(4)
)


# ============================================================
# 11. SAVE OUTPUT
# ============================================================

student_features.to_csv(
    STUDENT_FEATURE_FILE,
    index=False
)

subject_summary.to_csv(
    SUBJECT_FEATURE_FILE,
    index=False
)


# ============================================================
# 12. FEATURE ENGINEERING REPORT
# ============================================================

report = pd.DataFrame({

    "Metric": [
        "Clean attendance records",
        "Students processed",
        "Subject attendance records",
        "Average attendance rate",
        "Average absence frequency",
        "Minimum attendance rate",
        "Maximum attendance rate"
    ],

    "Value": [
        len(attendance),

        len(student_features),

        len(subject_summary),

        round(
            student_features[
                "Attendance_Rate"
            ].mean(),
            4
        ),

        round(
            student_features[
                "Absence_Frequency"
            ].mean(),
            4
        ),

        round(
            student_features[
                "Attendance_Rate"
            ].min(),
            4
        ),

        round(
            student_features[
                "Attendance_Rate"
            ].max(),
            4
        )
    ]
})


report.to_csv(
    REPORT_FILE,
    index=False
)


# ============================================================
# 13. DISPLAY RESULTS
# ============================================================

print("\n" + "=" * 65)
print("FEATURE ENGINEERING RESULTS")
print("=" * 65)

print(report.to_string(index=False))

print("\nGenerated student features:")
print(student_features.head())

print("\n" + "=" * 65)
print("FILES CREATED")
print("=" * 65)

print(f"\nStudent features:")
print(STUDENT_FEATURE_FILE)

print(f"\nSubject attendance:")
print(SUBJECT_FEATURE_FILE)

print(f"\nFeature engineering report:")
print(REPORT_FILE)

print("\nFeature engineering completed successfully.")