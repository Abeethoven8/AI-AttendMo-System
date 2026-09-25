from pathlib import Path
import pandas as pd
from scipy import stats


# ============================================================
# AI AttendMo - INFERENTIAL ANALYTICS
# ============================================================

BASE_DIR = Path(__file__).resolve().parents[1]

STUDENT_FILE = BASE_DIR / "data" / "student_features.csv"
SUBJECT_FILE = BASE_DIR / "data" / "student_subject_attendance.csv"

REPORTS_DIR = BASE_DIR / "reports"
REPORTS_DIR.mkdir(exist_ok=True)

RESULT_FILE = REPORTS_DIR / "inferential_results.csv"
ASSUMPTION_FILE = REPORTS_DIR / "inferential_assumption_checks.csv"

ALPHA = 0.05


# ============================================================
# 1. LOAD DATA
# ============================================================

students = pd.read_csv(STUDENT_FILE)
subject_data = pd.read_csv(SUBJECT_FILE)

print("=" * 75)
print("AI AttendMo - INFERENTIAL ANALYTICS")
print("=" * 75)

print(f"\nStudents: {len(students)}")
print(f"Student-subject records: {len(subject_data)}")


results = []
assumptions = []


# ============================================================
# 2. FUNCTION FOR INDEPENDENT GROUPS
# ============================================================

def compare_independent_groups(
    dataframe,
    group_column,
    value_column,
    comparison_name
):

    print("\n" + "=" * 75)
    print(comparison_name.upper())
    print("=" * 75)

    groups = {}

    for group_name, group_data in dataframe.groupby(group_column):

        values = (
            group_data[value_column]
            .dropna()
            .astype(float)
        )

        groups[group_name] = values


    # --------------------------------------------------------
    # SHAPIRO-WILK NORMALITY TEST
    # --------------------------------------------------------

    all_normal = True

    print("\nShapiro-Wilk Normality Check:")

    for group_name, values in groups.items():

        statistic, p_value = stats.shapiro(values)

        passed = p_value >= ALPHA

        if not passed:
            all_normal = False

        assumptions.append({
            "Comparison": comparison_name,
            "Assumption": "Normality",
            "Group": group_name,
            "N": len(values),
            "Statistic": round(float(statistic), 6),
            "P_Value": round(float(p_value), 6),
            "Passed": passed
        })

        print(
            f"{group_name}: "
            f"p = {p_value:.6f} | "
            f"{'PASS' if passed else 'FAIL'}"
        )


    # --------------------------------------------------------
    # LEVENE TEST FOR EQUAL VARIANCE
    # --------------------------------------------------------

    levene_stat, levene_p = stats.levene(
        *groups.values(),
        center="median"
    )

    equal_variance = levene_p >= ALPHA

    assumptions.append({
        "Comparison": comparison_name,
        "Assumption": "Homogeneity of Variance",
        "Group": "All Groups",
        "N": sum(len(x) for x in groups.values()),
        "Statistic": round(float(levene_stat), 6),
        "P_Value": round(float(levene_p), 6),
        "Passed": equal_variance
    })

    print("\nLevene Variance Check:")

    print(
        f"p = {levene_p:.6f} | "
        f"{'PASS' if equal_variance else 'FAIL'}"
    )


    # --------------------------------------------------------
    # CHOOSE STATISTICAL TEST
    # --------------------------------------------------------

    if all_normal and equal_variance:

        test_name = "One-Way ANOVA"

        statistic, p_value = stats.f_oneway(
            *groups.values()
        )

    else:

        test_name = "Kruskal-Wallis"

        statistic, p_value = stats.kruskal(
            *groups.values()
        )


    # --------------------------------------------------------
    # DECISION
    # --------------------------------------------------------

    if p_value < ALPHA:

        decision = "Reject H0"

        interpretation = (
            "Statistically significant difference detected."
        )

        posthoc = "Yes"

    else:

        decision = "Fail to Reject H0"

        interpretation = (
            "No statistically significant difference detected."
        )

        posthoc = "No"


    results.append({
        "Comparison": comparison_name,
        "Test_Used": test_name,
        "Statistic": round(float(statistic), 6),
        "P_Value": round(float(p_value), 6),
        "Alpha": ALPHA,
        "Decision": decision,
        "Interpretation": interpretation,
        "Posthoc_Needed": posthoc
    })


    print("\nSelected Test:")
    print(test_name)

    print(f"Statistic = {statistic:.6f}")
    print(f"P-value = {p_value:.6f}")
    print(f"Decision = {decision}")
    print(f"Interpretation = {interpretation}")


# ============================================================
# 3. GRADE LEVEL COMPARISON
# ============================================================

compare_independent_groups(
    dataframe=students,
    group_column="Grade_Level",
    value_column="Attendance_Rate",
    comparison_name="Attendance Rate by Grade Level"
)


# ============================================================
# 4. SECTION COMPARISON
# ============================================================

students["Class_Section"] = (
    students["Grade_Level"]
    + " - "
    + students["Section"]
)

compare_independent_groups(
    dataframe=students,
    group_column="Class_Section",
    value_column="Attendance_Rate",
    comparison_name="Attendance Rate by Section"
)


# ============================================================
# 5. SUBJECT COMPARISON
# ============================================================

print("\n" + "=" * 75)
print("ATTENDANCE RATE BY SUBJECT")
print("=" * 75)

# Same students appear across multiple subjects.
# Therefore, subject attendance is treated as repeated measures.

subject_pivot = subject_data.pivot_table(
    index="Student_ID",
    columns="Subject",
    values="Subject_Attendance_Rate",
    aggfunc="mean"
)

subject_pivot = subject_pivot.dropna()

subjects = sorted(subject_pivot.columns)

subject_groups = [
    subject_pivot[subject].values
    for subject in subjects
]

friedman_stat, friedman_p = stats.friedmanchisquare(
    *subject_groups
)


if friedman_p < ALPHA:

    decision = "Reject H0"

    interpretation = (
        "Statistically significant difference detected."
    )

    posthoc = "Yes"

else:

    decision = "Fail to Reject H0"

    interpretation = (
        "No statistically significant difference detected."
    )

    posthoc = "No"


results.append({
    "Comparison": "Attendance Rate by Subject",
    "Test_Used": "Friedman Test",
    "Statistic": round(float(friedman_stat), 6),
    "P_Value": round(float(friedman_p), 6),
    "Alpha": ALPHA,
    "Decision": decision,
    "Interpretation": interpretation,
    "Posthoc_Needed": posthoc
})


print("\nSelected Test:")
print("Friedman Test")

print(f"Statistic = {friedman_stat:.6f}")
print(f"P-value = {friedman_p:.6f}")
print(f"Decision = {decision}")
print(f"Interpretation = {interpretation}")


# ============================================================
# 6. SAVE RESULTS
# ============================================================

results_df = pd.DataFrame(results)
assumptions_df = pd.DataFrame(assumptions)

results_df.to_csv(
    RESULT_FILE,
    index=False
)

assumptions_df.to_csv(
    ASSUMPTION_FILE,
    index=False
)


# ============================================================
# 7. FINAL SUMMARY
# ============================================================

print("\n" + "=" * 75)
print("INFERENTIAL ANALYSIS SUMMARY")
print("=" * 75)

print(
    results_df.to_string(
        index=False
    )
)


print("\nFiles created:")

print(
    "\nreports/"
    "\n├── inferential_results.csv"
    "\n└── inferential_assumption_checks.csv"
)

print(
    "\nInferential analysis completed successfully."
)

print(
    "\nIMPORTANT: Results are based on synthetic "
    "development data, not actual ENHS findings."
)