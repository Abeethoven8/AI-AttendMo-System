from pathlib import Path

import joblib
import pandas as pd

from sklearn.tree import DecisionTreeClassifier
from sklearn.metrics import (
    accuracy_score,
    precision_score,
    recall_score,
    f1_score,
    confusion_matrix,
    classification_report
)


# ============================================================
# AI AttendMo - DECISION TREE MODEL
# ============================================================

BASE_DIR = Path(__file__).resolve().parents[1]

DATA_FILE = (
    BASE_DIR / "data" / "prediction_dataset.csv"
)

REPORTS_DIR = BASE_DIR / "reports"
REPORTS_DIR.mkdir(exist_ok=True)

MODELS_DIR = BASE_DIR / "models"
MODELS_DIR.mkdir(exist_ok=True)


# ============================================================
# OUTPUT FILES
# ============================================================

METRICS_FILE = (
    REPORTS_DIR / "decision_tree_metrics.csv"
)

CONFUSION_FILE = (
    REPORTS_DIR / "decision_tree_confusion_matrix.csv"
)

CLASSIFICATION_FILE = (
    REPORTS_DIR / "decision_tree_classification_report.csv"
)

IMPORTANCE_FILE = (
    REPORTS_DIR / "decision_tree_feature_importance.csv"
)

PREDICTIONS_FILE = (
    REPORTS_DIR / "decision_tree_test_predictions.csv"
)

MODEL_FILE = (
    MODELS_DIR / "decision_tree_model.joblib"
)


# ============================================================
# 1. LOAD PREDICTION DATASET
# ============================================================

data = pd.read_csv(DATA_FILE)

print("=" * 75)
print("AI AttendMo - DECISION TREE PREDICTIVE ANALYTICS")
print("=" * 75)

print(f"\nTotal student records: {len(data)}")

print("\nTarget distribution:")
print(
    data[
        "Synthetic_Absenteeism_Risk_Target"
    ].value_counts()
)

print("\nDataset split:")
print(
    data[
        "Dataset_Split"
    ].value_counts()
)


# ============================================================
# 2. SELECT HISTORICAL FEATURES ONLY
# ============================================================
#
# IMPORTANT:
# We intentionally EXCLUDE:
#
# Student_ID
# Grade_Level
# Section
# Target_Window_Attendance_Rate
# Target_Window_Absence_Frequency
# Synthetic_Absenteeism_Risk_Target
# Dataset_Split
#
# Target-window variables belong to the later period and
# must not be used as input because that would cause leakage.
# ============================================================

FEATURES = [

    "Historical_Attendance_Rate",

    "Historical_Total_Absences",

    "Historical_Absence_Frequency",

    "Frequently_Absent_Days_Count",

    "Lowest_Subject_Attendance_Rate",

    "Subject_Attendance_Rate_Range",

    "First_Half_Attendance_Rate",

    "Second_Half_Attendance_Rate",

    "Attendance_Trend_Change"
]


TARGET = "Synthetic_Absenteeism_Risk_Target"


# ============================================================
# 3. SPLIT TRAIN AND TEST
# ============================================================

train_data = data[
    data["Dataset_Split"] == "Train"
].copy()

test_data = data[
    data["Dataset_Split"] == "Test"
].copy()


X_train = train_data[FEATURES]
y_train = train_data[TARGET]

X_test = test_data[FEATURES]
y_test = test_data[TARGET]


print("\n" + "=" * 75)
print("TRAIN / TEST DATA")
print("=" * 75)

print(f"Training students: {len(X_train)}")
print(f"Testing students : {len(X_test)}")
print(f"Number of features: {len(FEATURES)}")


# ============================================================
# 4. CHECK FOR MISSING VALUES
# ============================================================

train_missing = int(
    X_train.isnull().sum().sum()
)

test_missing = int(
    X_test.isnull().sum().sum()
)


print("\nMissing feature values:")

print(
    f"Training: {train_missing}"
)

print(
    f"Testing : {test_missing}"
)


if train_missing > 0 or test_missing > 0:

    raise ValueError(
        "Missing values detected in prediction features. "
        "Check the prediction dataset before model training."
    )


# ============================================================
# 5. CREATE DECISION TREE
# ============================================================
#
# These parameters intentionally constrain the tree to reduce
# the chance of overfitting during the synthetic prototype.
#
# max_depth = 4
# min_samples_leaf = 12
# class_weight = balanced
#
# These are DEVELOPMENT settings, not guaranteed final values.
# ============================================================

model = DecisionTreeClassifier(

    criterion="gini",

    max_depth=4,

    min_samples_leaf=12,

    class_weight="balanced",

    random_state=42
)


# ============================================================
# 6. TRAIN MODEL
# ============================================================

model.fit(
    X_train,
    y_train
)


print("\nDecision Tree training completed.")


# ============================================================
# 7. GENERATE PREDICTIONS
# ============================================================

train_predictions = model.predict(
    X_train
)

test_predictions = model.predict(
    X_test
)


# ============================================================
# 8. MODEL EVALUATION
# ============================================================

train_accuracy = accuracy_score(
    y_train,
    train_predictions
)

test_accuracy = accuracy_score(
    y_test,
    test_predictions
)


precision = precision_score(
    y_test,
    test_predictions,
    average="macro",
    zero_division=0
)

recall = recall_score(
    y_test,
    test_predictions,
    average="macro",
    zero_division=0
)

f1 = f1_score(
    y_test,
    test_predictions,
    average="macro",
    zero_division=0
)


accuracy_gap = (
    train_accuracy
    -
    test_accuracy
)


# ============================================================
# 9. OVERFITTING CHECK
# ============================================================
#
# There is no single universal cutoff for overfitting.
# We only use the train-test gap as a diagnostic indicator.
# ============================================================

if accuracy_gap > 0.10:

    fit_note = (
        "Large train-test gap detected. "
        "Review tree complexity for possible overfitting."
    )

elif test_accuracy < 0.60:

    fit_note = (
        "Low test performance detected. "
        "Review features, target definition, and model settings."
    )

else:

    fit_note = (
        "No large train-test accuracy gap detected "
        "in this synthetic prototype."
    )


# ============================================================
# 10. MODEL METRICS
# ============================================================

metrics = pd.DataFrame({

    "Metric": [

        "Training Accuracy",

        "Testing Accuracy",

        "Macro Precision",

        "Macro Recall",

        "Macro F1-Score",

        "Train-Test Accuracy Gap"
    ],

    "Value": [

        round(train_accuracy, 4),

        round(test_accuracy, 4),

        round(precision, 4),

        round(recall, 4),

        round(f1, 4),

        round(accuracy_gap, 4)
    ]
})


metrics.to_csv(
    METRICS_FILE,
    index=False
)


# ============================================================
# 11. CONFUSION MATRIX
# ============================================================

CLASS_LABELS = [
    "Low Risk",
    "Moderate Risk",
    "High Risk"
]


cm = confusion_matrix(
    y_test,
    test_predictions,
    labels=CLASS_LABELS
)


confusion_df = pd.DataFrame(

    cm,

    index=[
        f"Actual_{label}"
        for label in CLASS_LABELS
    ],

    columns=[
        f"Predicted_{label}"
        for label in CLASS_LABELS
    ]
)


confusion_df.to_csv(
    CONFUSION_FILE
)


# ============================================================
# 12. CLASSIFICATION REPORT
# ============================================================

classification = classification_report(

    y_test,

    test_predictions,

    labels=CLASS_LABELS,

    output_dict=True,

    zero_division=0
)


classification_df = (
    pd.DataFrame(classification)
    .transpose()
)


classification_df.to_csv(
    CLASSIFICATION_FILE
)


# ============================================================
# 13. FEATURE IMPORTANCE
# ============================================================

importance_df = pd.DataFrame({

    "Feature": FEATURES,

    "Importance":
        model.feature_importances_

})


importance_df = (
    importance_df
    .sort_values(
        "Importance",
        ascending=False
    )
    .reset_index(drop=True)
)


importance_df[
    "Importance"
] = importance_df[
    "Importance"
].round(4)


importance_df.to_csv(
    IMPORTANCE_FILE,
    index=False
)


# ============================================================
# 14. SAVE TEST PREDICTIONS
# ============================================================

prediction_output = test_data[
    [
        "Student_ID",
        "Grade_Level",
        "Section",
        TARGET,
        "Dataset_Split"
    ]
].copy()


prediction_output[
    "Predicted_Risk"
] = test_predictions


prediction_output[
    "Correct_Prediction"
] = (
    prediction_output[TARGET]
    ==
    prediction_output["Predicted_Risk"]
)


prediction_output.to_csv(
    PREDICTIONS_FILE,
    index=False
)


# ============================================================
# 15. SAVE TRAINED MODEL
# ============================================================

joblib.dump(
    {
        "model": model,

        "features": FEATURES,

        "target": TARGET,

        "class_labels": CLASS_LABELS
    },

    MODEL_FILE
)


# ============================================================
# 16. DISPLAY RESULTS
# ============================================================

print("\n" + "=" * 75)
print("MODEL PERFORMANCE")
print("=" * 75)

print(
    metrics.to_string(
        index=False
    )
)


print("\n" + "=" * 75)
print("CONFUSION MATRIX")
print("=" * 75)

print(
    confusion_df.to_string()
)


print("\n" + "=" * 75)
print("FEATURE IMPORTANCE")
print("=" * 75)

print(
    importance_df.to_string(
        index=False
    )
)


print("\n" + "=" * 75)
print("MODEL FIT CHECK")
print("=" * 75)

print(fit_note)


print("\n" + "=" * 75)
print("FILES CREATED")
print("=" * 75)

print(
    "\nreports/"
    "\n├── decision_tree_metrics.csv"
    "\n├── decision_tree_confusion_matrix.csv"
    "\n├── decision_tree_classification_report.csv"
    "\n├── decision_tree_feature_importance.csv"
    "\n└── decision_tree_test_predictions.csv"
)

print(
    "\nmodels/"
    "\n└── decision_tree_model.joblib"
)


print(
    "\nDecision Tree predictive analytics "
    "completed successfully."
)

print(
    "\nIMPORTANT: Risk labels and model results "
    "are based on SYNTHETIC development data."
)

print(
    "The final real-data model must use the "
    "adviser-approved absenteeism risk definition."
)