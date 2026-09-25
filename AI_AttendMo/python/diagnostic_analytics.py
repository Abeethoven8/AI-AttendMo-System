from pathlib import Path
import pandas as pd

# ============================================================
# AI AttendMo - DIAGNOSTIC ANALYTICS
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
print("AI AttendMo - DIAGNOSTIC ANALYTICS")
print("=" * 70)

print(f"\nAttendance records: {len(attendance)}")
print(f"Students: {student_features['Student_ID'].nunique()}")


# ============================================================
# 2. IDENTIFY DISTINCT ABSENT DAYS
# ============================================================

absent_records = attendance[
    attendance["Attendance_Status"] == "Absent"
].copy()

student_absent_days = (
    absent_records[
        ["Student_ID", "Date"]
    ]
    .drop_duplicates()
)


# ============================================================
# 3. WEEKLY ABSENCE PATTERNS
# ============================================================

attendance["Week"] = (
    attendance["Date"]
    .dt.to_period("W")
    .apply(lambda x: x.start_time)
)

weekly_pattern = (
    attendance
    .groupby(
        [
            "Student_ID",
            "Grade_Level",
            "Section",
            "Week"
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

weekly_pattern["Attendance_Rate"] = (
    weekly_pattern["Present_Count"]
    /
    weekly_pattern["Total_Sessions"]
)

weekly_pattern["Attendance_Rate"] = (
    weekly_pattern["Attendance_Rate"]
    .round(4)
)


# ============================================================
# 4. COUNT WEEKS WITH AT LEAST ONE ABSENCE
# ============================================================

weekly_pattern["Has_Absence"] = (
    weekly_pattern["Absence_Count"] > 0
).astype(int)

weeks_summary = (
    weekly_pattern
    .groupby("Student_ID", as_index=False)
    .agg(
        Total_Observed_Weeks=(
            "Week",
            "nunique"
        ),

        Weeks_With_Absence=(
            "Has_Absence",
            "sum"
        )
    )
)

weeks_summary["Proportion_Weeks_With_Absence"] = (
    weeks_summary["Weeks_With_Absence"]
    /
    weeks_summary["Total_Observed_Weeks"]
).round(4)


# ============================================================
# 5. LONGEST ABSENCE-DAY STREAK
# ============================================================

# Get ordered school dates from actual dataset.
school_dates = sorted(
    attendance["Date"].drop_duplicates()
)

school_date_position = {
    school_date: position
    for position, school_date in enumerate(school_dates)
}


def longest_absence_streak(student_dates):

    positions = sorted(
        school_date_position[d]
        for d in student_dates
        if d in school_date_position
    )

    if not positions:
        return 0

    longest = 1
    current = 1

    for i in range(1, len(positions)):

        if positions[i] == positions[i - 1] + 1:
            current += 1
            longest = max(longest, current)

        else:
            current = 1

    return longest


streak_rows = []

for student_id, group in student_absent_days.groupby(
    "Student_ID"
):

    longest_streak = longest_absence_streak(
        group["Date"].tolist()
    )

    streak_rows.append(
        {
            "Student_ID": student_id,
            "Longest_Consecutive_Days_With_Any_Absence":
                longest_streak
        }
    )


streak_summary = pd.DataFrame(streak_rows)


# ============================================================
# 6. ABSENCE PATTERN BY DAY OF WEEK
# ============================================================

attendance["Day_of_Week"] = (
    attendance["Date"]
    .dt.day_name()
)

weekday_pattern = (
    attendance
    .groupby(
        [
            "Student_ID",
            "Day_of_Week"
        ],
        as_index=False
    )
    .agg(
        Total_Sessions=(
            "Attendance_Status",
            "count"
        ),

        Absence_Count=(
            "Absent_Flag",
            "sum"
        )
    )
)

weekday_pattern["Absence_Rate"] = (
    weekday_pattern["Absence_Count"]
    /
    weekday_pattern["Total_Sessions"]
)

weekday_pattern["Absence_Rate"] = (
    weekday_pattern["Absence_Rate"]
    .round(4)
)


# ============================================================
# 7. MOST ABSENCE-CONCENTRATED WEEKDAY PER STUDENT
# ============================================================

top_weekday = (
    weekday_pattern
    .sort_values(
        [
            "Student_ID",
            "Absence_Rate"
        ],
        ascending=[True, False]
    )
    .groupby("Student_ID")
    .first()
    .reset_index()
)

top_weekday = top_weekday[
    [
        "Student_ID",
        "Day_of_Week",
        "Absence_Rate"
    ]
].rename(
    columns={
        "Day_of_Week":
            "Highest_Absence_Weekday",

        "Absence_Rate":
            "Highest_Weekday_Absence_Rate"
    }
)


# ============================================================
# 8. COMBINE STUDENT DIAGNOSTIC FEATURES
# ============================================================

diagnostic = student_features.copy()

diagnostic = diagnostic.merge(
    weeks_summary,
    on="Student_ID",
    how="left"
)

diagnostic = diagnostic.merge(
    streak_summary,
    on="Student_ID",
    how="left"
)

diagnostic = diagnostic.merge(
    top_weekday,
    on="Student_ID",
    how="left"
)


diagnostic["Longest_Consecutive_Days_With_Any_Absence"] = (
    diagnostic["Longest_Consecutive_Days_With_Any_Absence"]
    .fillna(0)
    .astype(int)
)


# ============================================================
# 9. RANK STUDENTS BY ABSENCE PATTERN
# ============================================================

diagnostic = diagnostic.sort_values(
    by=[
        "Weeks_With_Absence",
        "Absence_Count"
    ],
    ascending=False
).reset_index(drop=True)

diagnostic.insert(
    0,
    "Pattern_Rank",
    range(1, len(diagnostic) + 1)
)


# ============================================================
# 10. GROUP ABSENTEEISM PATTERNS
# ============================================================

grade_patterns = (
    attendance
    .groupby(
        "Grade_Level",
        as_index=False
    )
    .agg(
        Total_Sessions=(
            "Attendance_Status",
            "count"
        ),

        Absence_Count=(
            "Absent_Flag",
            "sum"
        )
    )
)

grade_patterns["Absence_Rate"] = (
    grade_patterns["Absence_Count"]
    /
    grade_patterns["Total_Sessions"]
).round(4)


section_patterns = (
    attendance
    .groupby(
        [
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

        Absence_Count=(
            "Absent_Flag",
            "sum"
        )
    )
)

section_patterns["Absence_Rate"] = (
    section_patterns["Absence_Count"]
    /
    section_patterns["Total_Sessions"]
).round(4)


subject_patterns = (
    attendance
    .groupby(
        "Subject",
        as_index=False
    )
    .agg(
        Total_Sessions=(
            "Attendance_Status",
            "count"
        ),

        Absence_Count=(
            "Absent_Flag",
            "sum"
        )
    )
)

subject_patterns["Absence_Rate"] = (
    subject_patterns["Absence_Count"]
    /
    subject_patterns["Total_Sessions"]
).round(4)


# ============================================================
# 11. SAVE OUTPUT
# ============================================================

diagnostic.to_csv(
    REPORTS_DIR /
    "diagnostic_student_patterns.csv",
    index=False
)

weekly_pattern.to_csv(
    REPORTS_DIR /
    "diagnostic_weekly_patterns.csv",
    index=False
)

weekday_pattern.to_csv(
    REPORTS_DIR /
    "diagnostic_weekday_patterns.csv",
    index=False
)

grade_patterns.to_csv(
    REPORTS_DIR /
    "diagnostic_grade_patterns.csv",
    index=False
)

section_patterns.to_csv(
    REPORTS_DIR /
    "diagnostic_section_patterns.csv",
    index=False
)

subject_patterns.to_csv(
    REPORTS_DIR /
    "diagnostic_subject_patterns.csv",
    index=False
)


# ============================================================
# 12. DISPLAY RESULTS
# ============================================================

print("\n" + "=" * 70)
print("TOP 10 REPEATED ABSENCE PATTERNS")
print("=" * 70)

display_columns = [
    "Pattern_Rank",
    "Student_ID",
    "Grade_Level",
    "Section",
    "Absence_Count",
    "Attendance_Rate",
    "Weeks_With_Absence",
    "Total_Observed_Weeks",
    "Proportion_Weeks_With_Absence",
    "Longest_Consecutive_Days_With_Any_Absence",
    "Lowest_Attendance_Subject",
    "Highest_Absence_Weekday"
]

print(
    diagnostic[
        display_columns
    ]
    .head(10)
    .to_string(index=False)
)


print("\n" + "=" * 70)
print("GRADE ABSENTEEISM PATTERN")
print("=" * 70)

print(
    grade_patterns
    .sort_values(
        "Absence_Rate",
        ascending=False
    )
    .to_string(index=False)
)


print("\n" + "=" * 70)
print("SUBJECT ABSENTEEISM PATTERN")
print("=" * 70)

print(
    subject_patterns
    .sort_values(
        "Absence_Rate",
        ascending=False
    )
    .to_string(index=False)
)


print("\n" + "=" * 70)
print("FILES CREATED")
print("=" * 70)

print("""
reports/
├── diagnostic_student_patterns.csv
├── diagnostic_weekly_patterns.csv
├── diagnostic_weekday_patterns.csv
├── diagnostic_grade_patterns.csv
├── diagnostic_section_patterns.csv
└── diagnostic_subject_patterns.csv
""")

print(
    "Diagnostic analytics completed successfully."
)

print(
    "\nNOTE: Pattern measures were generated, "
    "but no arbitrary threshold was used to "
    "label a student as having official "
    "'Repeated Absenteeism'."
)