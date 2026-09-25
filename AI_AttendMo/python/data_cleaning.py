from pathlib import Path
import pandas as pd

# ============================================================
# AI AttendMo - DATA CLEANING AND VALIDATION
# ============================================================

BASE_DIR = Path(__file__).resolve().parents[1]

RAW_FILE = BASE_DIR / "data" / "attendance_raw_with_issues.csv"
STUDENTS_FILE = BASE_DIR / "data" / "students.csv"

CLEAN_FILE = BASE_DIR / "data" / "attendance_cleaned.csv"

REPORTS_DIR = BASE_DIR / "reports"
REPORTS_DIR.mkdir(exist_ok=True)

REJECTED_FILE = REPORTS_DIR / "rejected_records.csv"
REPORT_FILE = REPORTS_DIR / "cleaning_report.csv"


# ============================================================
# 1. LOAD DATA
# ============================================================

attendance = pd.read_csv(RAW_FILE)
students = pd.read_csv(STUDENTS_FILE)

# Keep untouched copy for rejected-record reporting
original = attendance.copy()

# Store original CSV row number
attendance["Original_Row"] = attendance.index + 2
original["Original_Row"] = original.index + 2


print("=" * 65)
print("AI AttendMo - DATA CLEANING")
print("=" * 65)

print(f"\nRaw attendance records: {len(attendance)}")
print(f"Registered students: {len(students)}")


# ============================================================
# 2. PREPARE REJECTION REASONS
# ============================================================

reasons = pd.Series("", index=attendance.index, dtype="string")


def flag_problem(mask, reason):
    """
    Add a rejection reason without deleting the record immediately.
    """

    for idx in attendance.index[mask]:

        if reasons.loc[idx] == "":
            reasons.loc[idx] = reason

        else:
            reasons.loc[idx] += "; " + reason


# ============================================================
# 3. CLEAN TEXT FIELDS
# ============================================================

text_columns = [
    "Student_ID",
    "Grade_Level",
    "Section",
    "Subject",
]

for column in text_columns:

    attendance[column] = (
        attendance[column]
        .astype("string")
        .str.strip()
    )


# ============================================================
# 4. CHECK MISSING STUDENT ID
# ============================================================

missing_student_id = (
    attendance["Student_ID"].isna()
    | attendance["Student_ID"].eq("")
)

flag_problem(
    missing_student_id,
    "Missing Student_ID"
)


# ============================================================
# 5. VALIDATE STUDENT ID
# ============================================================

students["Student_ID"] = (
    students["Student_ID"]
    .astype("string")
    .str.strip()
)

students["Grade_Level"] = (
    students["Grade_Level"]
    .astype("string")
    .str.strip()
)

students["Section"] = (
    students["Section"]
    .astype("string")
    .str.strip()
)

valid_student_ids = set(students["Student_ID"])

unknown_student = (
    ~missing_student_id
    & ~attendance["Student_ID"].isin(valid_student_ids)
)

flag_problem(
    unknown_student,
    "Student_ID not found in student master list"
)


# ============================================================
# 6. CHECK GRADE AND SECTION CONSISTENCY
# ============================================================

student_grade_map = students.set_index("Student_ID")["Grade_Level"]
student_section_map = students.set_index("Student_ID")["Section"]

expected_grade = attendance["Student_ID"].map(student_grade_map)
expected_section = attendance["Student_ID"].map(student_section_map)

known_student = attendance["Student_ID"].isin(valid_student_ids)

grade_mismatch = (
    known_student
    & attendance["Grade_Level"].ne(expected_grade)
)

section_mismatch = (
    known_student
    & attendance["Section"].ne(expected_section)
)

flag_problem(
    grade_mismatch,
    "Grade level does not match student master list"
)

flag_problem(
    section_mismatch,
    "Section does not match student master list"
)


# ============================================================
# 7. CHECK REQUIRED FIELDS
# ============================================================

missing_grade = (
    attendance["Grade_Level"].isna()
    | attendance["Grade_Level"].eq("")
)

missing_section = (
    attendance["Section"].isna()
    | attendance["Section"].eq("")
)

missing_subject = (
    attendance["Subject"].isna()
    | attendance["Subject"].eq("")
)

flag_problem(
    missing_grade,
    "Missing Grade_Level"
)

flag_problem(
    missing_section,
    "Missing Section"
)

flag_problem(
    missing_subject,
    "Missing Subject"
)


# ============================================================
# 8. CLEAN ATTENDANCE STATUS
# ============================================================

original_status = attendance["Attendance_Status"].copy()

status_text = (
    attendance["Attendance_Status"]
    .astype("string")
    .str.strip()
    .str.upper()
)

missing_status = (
    status_text.isna()
    | status_text.eq("")
)

flag_problem(
    missing_status,
    "Missing Attendance_Status"
)

status_mapping = {

    "PRESENT": "Present",
    "P": "Present",

    "ABSENT": "Absent",
    "A": "Absent",
}

valid_status = status_text.isin(status_mapping.keys())

invalid_status = (
    ~missing_status
    & ~valid_status
)

flag_problem(
    invalid_status,
    "Invalid Attendance_Status"
)

attendance["Attendance_Status"] = status_text.map(status_mapping)

# Count statuses that needed standardization
status_standardized = (
    ~missing_status
    & valid_status
    & ~original_status.isin(["Present", "Absent"])
)


# ============================================================
# 9. VALIDATE DATE
# ============================================================

date_text = (
    attendance["Date"]
    .astype("string")
    .str.strip()
)

missing_date = (
    date_text.isna()
    | date_text.eq("")
)

flag_problem(
    missing_date,
    "Missing Date"
)

parsed_date = pd.to_datetime(
    date_text,
    format="%Y-%m-%d",
    errors="coerce"
)

invalid_date = (
    ~missing_date
    & parsed_date.isna()
)

flag_problem(
    invalid_date,
    "Invalid Date"
)

# Standard date format
attendance["Date"] = parsed_date.dt.strftime("%Y-%m-%d")


# ============================================================
# 10. VALIDATE TIME
# ============================================================

time_text = (
    attendance["Time"]
    .astype("string")
    .str.strip()
)

missing_time = (
    time_text.isna()
    | time_text.eq("")
)

flag_problem(
    missing_time,
    "Missing Time"
)

parsed_time = pd.to_datetime(
    time_text,
    format="%H:%M",
    errors="coerce"
)

invalid_time = (
    ~missing_time
    & parsed_time.isna()
)

flag_problem(
    invalid_time,
    "Invalid Time"
)

attendance["Time"] = parsed_time.dt.strftime("%H:%M")


# ============================================================
# 11. DETECT EXACT DUPLICATES
# ============================================================

raw_columns = [
    "Student_ID",
    "Grade_Level",
    "Section",
    "Subject",
    "Date",
    "Time",
    "Attendance_Status",
]

# Exact duplicates from original raw file
exact_duplicate_original = (
    original[
        [
            "Student_ID",
            "Grade_Level",
            "Section",
            "Subject",
            "Date",
            "Time",
            "Attendance_Status",
        ]
    ]
    .duplicated(keep="first")
)

flag_problem(
    exact_duplicate_original,
    "Duplicate attendance record"
)


# ============================================================
# 12. CHECK NORMALIZED DUPLICATES
# ============================================================

# Only check rows that currently have no error
currently_valid = reasons.eq("")

normalized_duplicates = pd.Series(
    False,
    index=attendance.index
)

normalized_duplicates.loc[currently_valid] = (
    attendance.loc[currently_valid, raw_columns]
    .duplicated(keep="first")
)

flag_problem(
    normalized_duplicates,
    "Duplicate after standardization"
)


# ============================================================
# 13. SEPARATE VALID AND REJECTED RECORDS
# ============================================================

valid_mask = reasons.eq("")

cleaned = attendance.loc[
    valid_mask,
    raw_columns
].copy()

rejected = original.loc[
    ~valid_mask
].copy()

rejected["Rejection_Reason"] = reasons.loc[
    ~valid_mask
].values


# ============================================================
# 14. FINAL DUPLICATE SAFETY CHECK
# ============================================================

cleaned = cleaned.drop_duplicates()


# ============================================================
# 15. SAVE OUTPUT FILES
# ============================================================

cleaned.to_csv(
    CLEAN_FILE,
    index=False
)

rejected.to_csv(
    REJECTED_FILE,
    index=False
)


# ============================================================
# 16. CREATE CLEANING REPORT
# ============================================================

report = pd.DataFrame({

    "Metric": [

        "Raw records",
        "Missing Student_ID",
        "Unknown Student_ID",
        "Missing Date",
        "Invalid Date",
        "Missing Time",
        "Invalid Time",
        "Missing Attendance_Status",
        "Invalid Attendance_Status",
        "Attendance statuses standardized",
        "Grade mismatches",
        "Section mismatches",
        "Exact duplicate records",
        "Duplicate after standardization",
        "Rejected records",
        "Final clean records",
    ],

    "Count": [

        len(attendance),

        int(missing_student_id.sum()),

        int(unknown_student.sum()),

        int(missing_date.sum()),

        int(invalid_date.sum()),

        int(missing_time.sum()),

        int(invalid_time.sum()),

        int(missing_status.sum()),

        int(invalid_status.sum()),

        int(status_standardized.sum()),

        int(grade_mismatch.sum()),

        int(section_mismatch.sum()),

        int(exact_duplicate_original.sum()),

        int(normalized_duplicates.sum()),

        len(rejected),

        len(cleaned),
    ]
})

report.to_csv(
    REPORT_FILE,
    index=False
)


# ============================================================
# 17. DISPLAY RESULTS
# ============================================================

print("\n" + "=" * 65)
print("DATA CLEANING RESULTS")
print("=" * 65)

print(report.to_string(index=False))

print("\n" + "=" * 65)
print("FILES CREATED")
print("=" * 65)

print(f"\nClean dataset:")
print(CLEAN_FILE)

print(f"\nRejected records:")
print(REJECTED_FILE)

print(f"\nCleaning report:")
print(REPORT_FILE)

print("\nData cleaning completed successfully.")