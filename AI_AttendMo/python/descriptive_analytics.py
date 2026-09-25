from pathlib import Path
import pandas as pd

# ============================================================
# AI AttendMo - DESCRIPTIVE ANALYTICS
# ============================================================

BASE_DIR = Path(__file__).resolve().parents[1]

CLEAN_FILE = BASE_DIR / "data" / "attendance_cleaned.csv"
STUDENT_FEATURE_FILE = BASE_DIR / "data" / "student_features.csv"

REPORTS_DIR = BASE_DIR / "reports"
REPORTS_DIR.mkdir(exist_ok=True)


# ============================================================
# 1. LOAD DATA
# ============================================================

attendance = pd.read_csv(CLEAN_FILE)
student_features = pd.read_csv(STUDENT_FEATURE_FILE)

attendance["Date"] = pd.to_datetime(
    attendance["Date"],
    format="%Y-%m-%d"
)

attendance["Present_Flag"] = (
    attendance["Attendance_Status"] == "Present"
).astype(int)

attendance["Absent_Flag"] = (
    attendance["Attendance_Status"] == "Absent"
).astype(int)


print("=" * 70)
print("AI AttendMo - DESCRIPTIVE ANALYTICS")
print("=" * 70)

print(f"\nAttendance records: {len(attendance)}")
print(f"Students: {len(student_features)}")


# ============================================================
# 2. OVERALL ATTENDANCE SUMMARY
# ============================================================

total_sessions = len(attendance)

total_present = int(
    attendance["Present_Flag"].sum()
)

total_absent = int(
    attendance["Absent_Flag"].sum()
)

overall_attendance_rate = (
    total_present / total_sessions
)

overall_absence_rate = (
    total_absent / total_sessions
)


overall_summary = pd.DataFrame({

    "Metric": [
        "Total Students",
        "Total Attendance Records",
        "Total Present",
        "Total Absent",
        "Overall Attendance Rate",
        "Overall Absence Rate"
    ],

    "Value": [
        student_features["Student_ID"].nunique(),
        total_sessions,
        total_present,
        total_absent,
        round(overall_attendance_rate, 4),
        round(overall_absence_rate, 4)
    ]
})


# ============================================================
# 3. ATTENDANCE RATE BY GRADE LEVEL
# ============================================================

grade_summary = (
    attendance
    .groupby("Grade_Level", as_index=False)
    .agg(
        Total_Sessions=("Attendance_Status", "count"),
        Present_Count=("Present_Flag", "sum"),
        Absence_Count=("Absent_Flag", "sum")
    )
)

grade_summary["Attendance_Rate"] = (
    grade_summary["Present_Count"]
    /
    grade_summary["Total_Sessions"]
)

grade_summary["Absence_Rate"] = (
    grade_summary["Absence_Count"]
    /
    grade_summary["Total_Sessions"]
)

grade_summary[
    ["Attendance_Rate", "Absence_Rate"]
] = grade_summary[
    ["Attendance_Rate", "Absence_Rate"]
].round(4)


# ============================================================
# 4. ATTENDANCE RATE BY SECTION
# ============================================================

section_summary = (
    attendance
    .groupby(
        ["Grade_Level", "Section"],
        as_index=False
    )
    .agg(
        Total_Sessions=("Attendance_Status", "count"),
        Present_Count=("Present_Flag", "sum"),
        Absence_Count=("Absent_Flag", "sum")
    )
)

section_summary["Attendance_Rate"] = (
    section_summary["Present_Count"]
    /
    section_summary["Total_Sessions"]
)

section_summary["Absence_Rate"] = (
    section_summary["Absence_Count"]
    /
    section_summary["Total_Sessions"]
)

section_summary[
    ["Attendance_Rate", "Absence_Rate"]
] = section_summary[
    ["Attendance_Rate", "Absence_Rate"]
].round(4)


# ============================================================
# 5. ATTENDANCE RATE BY SUBJECT
# ============================================================

subject_summary = (
    attendance
    .groupby("Subject", as_index=False)
    .agg(
        Total_Sessions=("Attendance_Status", "count"),
        Present_Count=("Present_Flag", "sum"),
        Absence_Count=("Absent_Flag", "sum")
    )
)

subject_summary["Attendance_Rate"] = (
    subject_summary["Present_Count"]
    /
    subject_summary["Total_Sessions"]
)

subject_summary["Absence_Rate"] = (
    subject_summary["Absence_Count"]
    /
    subject_summary["Total_Sessions"]
)

subject_summary[
    ["Attendance_Rate", "Absence_Rate"]
] = subject_summary[
    ["Attendance_Rate", "Absence_Rate"]
].round(4)


# ============================================================
# 6. DAILY ATTENDANCE TREND
# ============================================================

daily_trend = (
    attendance
    .groupby("Date", as_index=False)
    .agg(
        Total_Sessions=("Attendance_Status", "count"),
        Present_Count=("Present_Flag", "sum"),
        Absence_Count=("Absent_Flag", "sum")
    )
)

daily_trend["Attendance_Rate"] = (
    daily_trend["Present_Count"]
    /
    daily_trend["Total_Sessions"]
)

daily_trend["Attendance_Rate"] = (
    daily_trend["Attendance_Rate"]
    .round(4)
)


# ============================================================
# 7. MONTHLY ATTENDANCE TREND
# ============================================================

attendance["Month"] = (
    attendance["Date"]
    .dt.to_period("M")
    .astype(str)
)

monthly_trend = (
    attendance
    .groupby("Month", as_index=False)
    .agg(
        Total_Sessions=("Attendance_Status", "count"),
        Present_Count=("Present_Flag", "sum"),
        Absence_Count=("Absent_Flag", "sum")
    )
)

monthly_trend["Attendance_Rate"] = (
    monthly_trend["Present_Count"]
    /
    monthly_trend["Total_Sessions"]
)

monthly_trend["Attendance_Rate"] = (
    monthly_trend["Attendance_Rate"]
    .round(4)
)


# ============================================================
# 8. FREQUENTLY ABSENT STUDENTS
# ============================================================

frequently_absent = (
    student_features[
        [
            "Student_ID",
            "Grade_Level",
            "Section",
            "Total_Sessions",
            "Absence_Count",
            "Attendance_Rate",
            "Absence_Frequency",
            "Distinct_Absent_Days"
        ]
    ]
    .sort_values(
        by="Absence_Count",
        ascending=False
    )
    .reset_index(drop=True)
)

# Rank only — NO arbitrary absenteeism threshold
frequently_absent.insert(
    0,
    "Absence_Rank",
    range(1, len(frequently_absent) + 1)
)


# ============================================================
# 9. SAVE RESULTS
# ============================================================

overall_summary.to_csv(
    REPORTS_DIR / "overall_attendance_summary.csv",
    index=False
)

grade_summary.to_csv(
    REPORTS_DIR / "attendance_by_grade.csv",
    index=False
)

section_summary.to_csv(
    REPORTS_DIR / "attendance_by_section.csv",
    index=False
)

subject_summary.to_csv(
    REPORTS_DIR / "attendance_by_subject.csv",
    index=False
)

daily_trend.to_csv(
    REPORTS_DIR / "daily_attendance_trend.csv",
    index=False
)

monthly_trend.to_csv(
    REPORTS_DIR / "monthly_attendance_trend.csv",
    index=False
)

frequently_absent.to_csv(
    REPORTS_DIR / "frequently_absent_students.csv",
    index=False
)


# ============================================================
# 10. DISPLAY RESULTS
# ============================================================

print("\n" + "=" * 70)
print("OVERALL ATTENDANCE")
print("=" * 70)

print(overall_summary.to_string(index=False))


print("\n" + "=" * 70)
print("ATTENDANCE BY GRADE LEVEL")
print("=" * 70)

print(grade_summary.to_string(index=False))


print("\n" + "=" * 70)
print("ATTENDANCE BY SECTION")
print("=" * 70)

print(section_summary.to_string(index=False))


print("\n" + "=" * 70)
print("ATTENDANCE BY SUBJECT")
print("=" * 70)

print(subject_summary.to_string(index=False))


print("\n" + "=" * 70)
print("TOP 10 FREQUENTLY ABSENT STUDENTS")
print("=" * 70)

print(
    frequently_absent
    .head(10)
    .to_string(index=False)
)


print("\n" + "=" * 70)
print("FILES CREATED")
print("=" * 70)

print("""
reports/
├── overall_attendance_summary.csv
├── attendance_by_grade.csv
├── attendance_by_section.csv
├── attendance_by_subject.csv
├── daily_attendance_trend.csv
├── monthly_attendance_trend.csv
└── frequently_absent_students.csv
""")

print("Descriptive analytics completed successfully.")