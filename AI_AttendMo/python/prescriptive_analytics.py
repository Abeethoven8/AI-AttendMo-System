from pathlib import Path

import pandas as pd


# ============================================================
# AI AttendMo - PRESCRIPTIVE ANALYTICS
# Rule-Based Decision-Support Recommendations
# ============================================================

BASE_DIR = Path(__file__).resolve().parents[1]

PREDICTION_DATA_FILE = (
    BASE_DIR / "data" / "prediction_dataset.csv"
)

TEST_PREDICTIONS_FILE = (
    BASE_DIR / "reports" / "decision_tree_test_predictions.csv"
)

REPORTS_DIR = BASE_DIR / "reports"
REPORTS_DIR.mkdir(exist_ok=True)

OUTPUT_FILE = (
    REPORTS_DIR / "student_recommendations.csv"
)


# ============================================================
# 1. LOAD DATA
# ============================================================

data = pd.read_csv(PREDICTION_DATA_FILE)

predictions = pd.read_csv(
    TEST_PREDICTIONS_FILE
)


print("=" * 75)
print("AI AttendMo - PRESCRIPTIVE ANALYTICS")
print("=" * 75)

print(
    f"\nPrediction dataset records: {len(data)}"
)

print(
    f"Test prediction records   : {len(predictions)}"
)


# ============================================================
# 2. REQUIRED COLUMN CHECK
# ============================================================

required_prediction_columns = [

    "Student_ID",
    "Grade_Level",
    "Section",
    "Historical_Attendance_Rate",
    "Historical_Total_Absences",
    "Historical_Absence_Frequency",
    "Frequently_Absent_Days_Count",
    "Lowest_Subject_Attendance_Rate",
    "First_Half_Attendance_Rate",
    "Second_Half_Attendance_Rate",
    "Attendance_Trend_Change",
    "Dataset_Split"
]


required_model_columns = [

    "Student_ID",
    "Predicted_Risk"
]


missing_prediction_columns = [

    column
    for column in required_prediction_columns
    if column not in data.columns
]


missing_model_columns = [

    column
    for column in required_model_columns
    if column not in predictions.columns
]


if missing_prediction_columns:

    raise ValueError(
        "Missing columns in prediction_dataset.csv: "
        + ", ".join(missing_prediction_columns)
    )


if missing_model_columns:

    raise ValueError(
        "Missing columns in decision_tree_test_predictions.csv: "
        + ", ".join(missing_model_columns)
    )


# ============================================================
# 3. GET RESERVED TEST STUDENTS
# ============================================================

test_features = data[
    data["Dataset_Split"] == "Test"
].copy()


recommendation_data = test_features[
    [
        "Student_ID",
        "Grade_Level",
        "Section",
        "Historical_Attendance_Rate",
        "Historical_Total_Absences",
        "Historical_Absence_Frequency",
        "Frequently_Absent_Days_Count",
        "Lowest_Subject_Attendance_Rate",
        "First_Half_Attendance_Rate",
        "Second_Half_Attendance_Rate",
        "Attendance_Trend_Change"
    ]
].copy()


print(
    f"Reserved test students    : {len(recommendation_data)}"
)


# ============================================================
# 4. CHECK MODEL PREDICTIONS
# ============================================================

if predictions["Student_ID"].duplicated().any():

    raise ValueError(
        "Duplicate Student_ID detected in "
        "decision_tree_test_predictions.csv."
    )


# ============================================================
# 5. MERGE PREDICTED RISK
# ============================================================

recommendation_data = recommendation_data.merge(

    predictions[
        [
            "Student_ID",
            "Predicted_Risk"
        ]
    ],

    on="Student_ID",

    how="left",

    validate="one_to_one"
)


if recommendation_data[
    "Predicted_Risk"
].isnull().any():

    missing_students = recommendation_data.loc[
        recommendation_data[
            "Predicted_Risk"
        ].isnull(),
        "Student_ID"
    ].tolist()

    raise ValueError(
        "Some test students do not have predicted risk: "
        + ", ".join(missing_students[:10])
    )


# ============================================================
# 6. CHECK EXPECTED RISK LABELS
# ============================================================

EXPECTED_RISK_LABELS = {

    "Low Risk",
    "Moderate Risk",
    "High Risk"
}


actual_risk_labels = set(
    recommendation_data[
        "Predicted_Risk"
    ].dropna().unique()
)


unexpected_labels = (
    actual_risk_labels
    -
    EXPECTED_RISK_LABELS
)


if unexpected_labels:

    raise ValueError(
        "Unexpected risk labels detected: "
        + ", ".join(sorted(unexpected_labels))
    )


# ============================================================
# 7. RECOMMENDATION FUNCTION
# ============================================================
#
# IMPORTANT:
#
# The system provides DECISION-SUPPORT recommendations only.
#
# It does NOT automatically contact parents,
# teachers, students, or school personnel.
#
# Current Low / Moderate / High labels are SYNTHETIC
# development labels only.
#
# Final recommendation logic must be reviewed using the
# adviser-approved absenteeism risk definition when
# real ENHS data become available.
# ============================================================

def generate_recommendation(row):

    recommendations = []


    # --------------------------------------------------------
    # A. MODEL-PREDICTED ABSENTEEISM RISK
    # --------------------------------------------------------

    if row["Predicted_Risk"] == "High Risk":

        recommendations.append(
            "Refer for appropriate intervention"
        )


    elif row["Predicted_Risk"] == "Moderate Risk":

        recommendations.append(
            "Monitor attendance more closely"
        )


    elif row["Predicted_Risk"] == "Low Risk":

        recommendations.append(
            "Continue regular monitoring"
        )


    # --------------------------------------------------------
    # B. DECLINING ATTENDANCE TREND
    # --------------------------------------------------------
    #
    # Attendance_Trend_Change =
    # Second-half attendance rate
    # minus
    # First-half attendance rate
    #
    # A negative value means attendance declined.
    # --------------------------------------------------------

    if row["Attendance_Trend_Change"] < 0:

        recommendations.append(
            "Consider early intervention due to declining attendance trend"
        )


    # Remove duplicate recommendations while
    # preserving their original order.

    recommendations = list(
        dict.fromkeys(recommendations)
    )


    return "; ".join(recommendations)


# ============================================================
# 8. RECOMMENDATION BASIS FUNCTION
# ============================================================

def generate_recommendation_basis(row):

    basis = [
        "Decision Tree absenteeism risk classification"
    ]


    if row["Attendance_Trend_Change"] < 0:

        basis.append(
            "Declining historical attendance trend"
        )


    return "; ".join(basis)


# ============================================================
# 9. APPLY RECOMMENDATIONS
# ============================================================

recommendation_data[
    "Recommendation"
] = recommendation_data.apply(

    generate_recommendation,

    axis=1
)


recommendation_data[
    "Recommendation_Basis"
] = recommendation_data.apply(

    generate_recommendation_basis,

    axis=1
)


# ============================================================
# 10. RECOMMENDATION PRIORITY
# ============================================================
#
# No new attendance percentage threshold is introduced.
#
# Priority follows the current model-predicted
# synthetic risk classification.
# ============================================================

priority_map = {

    "High Risk": "High",

    "Moderate Risk": "Medium",

    "Low Risk": "Routine"
}


recommendation_data[
    "Recommendation_Priority"
] = recommendation_data[
    "Predicted_Risk"
].map(priority_map)


# ============================================================
# 11. SORT RESULTS
# ============================================================

priority_order = {

    "High": 1,

    "Medium": 2,

    "Routine": 3
}


recommendation_data[
    "_Priority_Order"
] = recommendation_data[
    "Recommendation_Priority"
].map(priority_order)


recommendation_data = (
    recommendation_data
    .sort_values(
        by=[
            "_Priority_Order",
            "Historical_Attendance_Rate"
        ],
        ascending=[
            True,
            True
        ]
    )
    .drop(
        columns=[
            "_Priority_Order"
        ]
    )
    .reset_index(
        drop=True
    )
)


# ============================================================
# 12. SAVE RESULTS
# ============================================================

recommendation_data.to_csv(

    OUTPUT_FILE,

    index=False
)


# ============================================================
# 13. SUMMARY
# ============================================================

print("\n" + "=" * 75)
print("RECOMMENDATION SUMMARY")
print("=" * 75)


print("\nPredicted Risk:")

print(
    recommendation_data[
        "Predicted_Risk"
    ].value_counts()
)


print("\nRecommendation Priority:")

print(
    recommendation_data[
        "Recommendation_Priority"
    ].value_counts()
)


declining_count = int(
    (
        recommendation_data[
            "Attendance_Trend_Change"
        ] < 0
    ).sum()
)


print(
    f"\nStudents with declining attendance trend: "
    f"{declining_count}"
)


# ============================================================
# 14. SAMPLE RESULTS
# ============================================================

print("\n" + "=" * 75)
print("SAMPLE RECOMMENDATIONS")
print("=" * 75)


display_columns = [

    "Student_ID",

    "Predicted_Risk",

    "Historical_Attendance_Rate",

    "Attendance_Trend_Change",

    "Recommendation_Priority",

    "Recommendation",

    "Recommendation_Basis"
]


print(

    recommendation_data[
        display_columns
    ]
    .head(15)
    .to_string(index=False)

)


# ============================================================
# 15. FILE CREATED
# ============================================================

print("\n" + "=" * 75)
print("FILE CREATED")
print("=" * 75)


print(
    OUTPUT_FILE
)


print(
    "\nPrescriptive analytics completed successfully."
)


print(
    "\nIMPORTANT:"
)


print(
    "These recommendations are based on "
    "SYNTHETIC development data and synthetic risk labels."
)


print(
    "Recommendations are decision-support suggestions only "
    "and do not automatically perform interventions."
)


print(
    "No official attendance percentage threshold was "
    "invented in this development script."
)


print(
    "Final recommendation rules must be reviewed with the "
    "adviser when real ENHS data and the official "
    "absenteeism risk definition are available."
)