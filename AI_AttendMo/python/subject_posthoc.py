from pathlib import Path
from itertools import combinations

import pandas as pd
from scipy import stats


# ============================================================
# AI AttendMo - SUBJECT POST-HOC ANALYSIS
# Pairwise Wilcoxon + Holm Correction
# ============================================================

BASE_DIR = Path(__file__).resolve().parents[1]

SUBJECT_FILE = (
    BASE_DIR / "data" / "student_subject_attendance.csv"
)

REPORTS_DIR = BASE_DIR / "reports"
REPORTS_DIR.mkdir(exist_ok=True)

OUTPUT_FILE = (
    REPORTS_DIR / "subject_posthoc_results.csv"
)

ALPHA = 0.05


# ============================================================
# 1. LOAD DATA
# ============================================================

subject_data = pd.read_csv(SUBJECT_FILE)

print("=" * 75)
print("AI AttendMo - SUBJECT POST-HOC ANALYSIS")
print("=" * 75)


# ============================================================
# 2. CREATE STUDENT × SUBJECT MATRIX
# ============================================================

pivot = subject_data.pivot_table(
    index="Student_ID",
    columns="Subject",
    values="Subject_Attendance_Rate",
    aggfunc="mean"
)

pivot = pivot.dropna()

subjects = sorted(pivot.columns)

print(f"\nStudents included: {len(pivot)}")
print(f"Subjects: {subjects}")


# ============================================================
# 3. PAIRWISE WILCOXON TESTS
# ============================================================

results = []

for subject_a, subject_b in combinations(subjects, 2):

    values_a = pivot[subject_a]
    values_b = pivot[subject_b]

    statistic, p_value = stats.wilcoxon(
        values_a,
        values_b,
        zero_method="wilcox",
        alternative="two-sided"
    )

    mean_a = values_a.mean()
    mean_b = values_b.mean()

    results.append({
        "Subject_A": subject_a,
        "Subject_B": subject_b,
        "Mean_Attendance_A": mean_a,
        "Mean_Attendance_B": mean_b,
        "Statistic": float(statistic),
        "Raw_P_Value": float(p_value)
    })


results_df = pd.DataFrame(results)


# ============================================================
# 4. HOLM-BONFERRONI CORRECTION
# ============================================================

results_df = results_df.sort_values(
    "Raw_P_Value"
).reset_index(drop=True)

m = len(results_df)

adjusted_values = []

running_max = 0

for i, raw_p in enumerate(results_df["Raw_P_Value"]):

    adjusted = (m - i) * raw_p

    adjusted = min(adjusted, 1.0)

    running_max = max(
        running_max,
        adjusted
    )

    adjusted_values.append(running_max)


results_df["Adjusted_P_Value"] = adjusted_values


# ============================================================
# 5. DECISION
# ============================================================

results_df["Decision"] = results_df[
    "Adjusted_P_Value"
].apply(
    lambda p:
    "Significant"
    if p < ALPHA
    else "Not Significant"
)


# Round only for display/export
results_df["Mean_Attendance_A"] = (
    results_df["Mean_Attendance_A"]
    .round(4)
)

results_df["Mean_Attendance_B"] = (
    results_df["Mean_Attendance_B"]
    .round(4)
)

results_df["Statistic"] = (
    results_df["Statistic"]
    .round(4)
)

results_df["Raw_P_Value_Display"] = (
    results_df["Raw_P_Value"]
    .apply(
        lambda p:
        "<0.001"
        if p < 0.001
        else f"{p:.6f}"
    )
)

results_df["Adjusted_P_Value_Display"] = (
    results_df["Adjusted_P_Value"]
    .apply(
        lambda p:
        "<0.001"
        if p < 0.001
        else f"{p:.6f}"
    )
)


# ============================================================
# 6. SAVE
# ============================================================

output_columns = [
    "Subject_A",
    "Subject_B",
    "Mean_Attendance_A",
    "Mean_Attendance_B",
    "Statistic",
    "Raw_P_Value_Display",
    "Adjusted_P_Value_Display",
    "Decision"
]

results_df[
    output_columns
].to_csv(
    OUTPUT_FILE,
    index=False
)


# ============================================================
# 7. DISPLAY
# ============================================================

print("\n" + "=" * 75)
print("PAIRWISE SUBJECT COMPARISONS")
print("=" * 75)

print(
    results_df[
        output_columns
    ].to_string(index=False)
)

print("\n" + "=" * 75)
print("FILE CREATED")
print("=" * 75)

print(OUTPUT_FILE)

print(
    "\nPost-hoc analysis completed successfully."
)

print(
    "\nIMPORTANT: These are synthetic development results, "
    "not actual ENHS findings."
)