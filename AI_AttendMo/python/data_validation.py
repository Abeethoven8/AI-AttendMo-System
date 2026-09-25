from pathlib import Path
import pandas as pd

# ============================================================
# AI AttendMo - CLEAN DATA VALIDATION
# ============================================================

BASE_DIR = Path(__file__).resolve().parents[1]

CLEAN_FILE = BASE_DIR / "data" / "attendance_cleaned.csv"
STUDENTS_FILE = BASE_DIR / "data" / "students.csv"

attendance = pd.read_csv(CLEAN_FILE)
students = pd.read_csv(STUDENTS_FILE)

print("=" * 65)
print("AI AttendMo - CLEAN DATA VALIDATION")
print("=" * 65)

print(f"\nClean attendance records: {len(attendance)}")


# ============================================================
# 1. CHECK MISSING VALUES
# ============================================================

missing = attendance.isnull().sum()
total_missing = int(missing.sum())

print("\n1. MISSING VALUES")
print("-" * 65)

print(missing)
print(f"\nTotal missing values: {total_missing}")


# ============================================================
# 2. CHECK DUPLICATES
# ============================================================

duplicates = int(attendance.duplicated().sum())

print("\n2. DUPLICATE CHECK")
print("-" * 65)

print(f"Duplicate records: {duplicates}")


# ============================================================
# 3. CHECK ATTENDANCE STATUS
# ============================================================

allowed_status = {"Present", "Absent"}

actual_status = set(
    attendance["Attendance_Status"]
    .dropna()
    .unique()
)

invalid_status = actual_status - allowed_status

print("\n3. ATTENDANCE STATUS CHECK")
print("-" * 65)

print("Status values found:")
print(attendance["Attendance_Status"].value_counts())

print(f"\nInvalid status values: {invalid_status}")


# ============================================================
# 4. VALIDATE DATES
# ============================================================

parsed_dates = pd.to_datetime(
    attendance["Date"],
    format="%Y-%m-%d",
    errors="coerce"
)

invalid_dates = int(parsed_dates.isna().sum())

print("\n4. DATE CHECK")
print("-" * 65)

print(f"Invalid dates: {invalid_dates}")


# ============================================================
# 5. VALIDATE TIMES
# ============================================================

parsed_times = pd.to_datetime(
    attendance["Time"],
    format="%H:%M",
    errors="coerce"
)

invalid_times = int(parsed_times.isna().sum())

print("\n5. TIME CHECK")
print("-" * 65)

print(f"Invalid times: {invalid_times}")


# ============================================================
# 6. CHECK STUDENT IDs
# ============================================================

valid_students = set(students["Student_ID"])

unknown_students = attendance[
    ~attendance["Student_ID"].isin(valid_students)
]

unknown_count = len(unknown_students)

print("\n6. STUDENT ID CHECK")
print("-" * 65)

print(f"Unknown Student IDs: {unknown_count}")


# ============================================================
# 7. FINAL VALIDATION RESULT
# ============================================================

passed = (
    total_missing == 0
    and duplicates == 0
    and len(invalid_status) == 0
    and invalid_dates == 0
    and invalid_times == 0
    and unknown_count == 0
)

print("\n" + "=" * 65)
print("FINAL VALIDATION RESULT")
print("=" * 65)

if passed:

    print("PASSED ✅")
    print("Dataset is ready for transformation and analytics.")

else:

    print("FAILED ❌")
    print("The dataset still contains data-quality problems.")