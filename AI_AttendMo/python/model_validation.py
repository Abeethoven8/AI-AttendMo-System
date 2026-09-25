from pathlib import Path

import pandas as pd

from sklearn.tree import DecisionTreeClassifier
from sklearn.model_selection import StratifiedKFold, cross_validate


# ============================================================
# AI AttendMo - MODEL VALIDATION
# Stratified 5-Fold Cross-Validation
# ============================================================

BASE_DIR = Path(__file__).resolve().parents[1]

DATA_FILE = (
    BASE_DIR / "data" / "prediction_dataset.csv"
)

REPORTS_DIR = BASE_DIR / "reports"
REPORTS_DIR.mkdir(exist_ok=True)

CV_RESULTS_FILE = (
    REPORTS_DIR / "decision_tree_cross_validation.csv"
)

CV_SUMMARY_FILE = (
    REPORTS_DIR / "decision_tree_cross_validation_summary.csv"
)


# ============================================================
# 1. LOAD DATA
# ============================================================

data = pd.read_csv(DATA_FILE)

print("=" * 75)
print("AI AttendMo - DECISION TREE MODEL VALIDATION")
print("=" * 75)

print(f"\nTotal records: {len(data)}")


# ============================================================
# 2. USE TRAINING DATA ONLY
# ============================================================
#
# IMPORTANT:
# The 120 test students remain untouched.
# Cross-validation is performed ONLY on the training set.
# ============================================================

train_data = data[
    data["Dataset_Split"] == "Train"
].copy()

test_data = data[
    data["Dataset_Split"] == "Test"
].copy()


print(f"Training records for CV: {len(train_data)}")
print(f"Reserved test records   : {len(test_data)}")


# ============================================================
# 3. FEATURES AND TARGET
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


X_train = train_data[FEATURES]
y_train = train_data[TARGET]


# ============================================================
# 4. DATA CHECKS
# ============================================================

missing_features = int(
    X_train.isnull().sum().sum()
)

missing_target = int(
    y_train.isnull().sum()
)


print("\nMissing values:")

print(
    f"Features: {missing_features}"
)

print(
    f"Target  : {missing_target}"
)


if missing_features > 0 or missing_target > 0:

    raise ValueError(
        "Missing values found in training data."
    )


print("\nTraining target distribution:")

print(
    y_train.value_counts()
)


# ============================================================
# 5. CREATE SAME DECISION TREE
# ============================================================
#
# We validate the SAME model configuration used in
# decision_tree_model.py.
# ============================================================

model = DecisionTreeClassifier(

    criterion="gini",

    max_depth=4,

    min_samples_leaf=12,

    class_weight="balanced",

    random_state=42
)


# ============================================================
# 6. STRATIFIED 5-FOLD CROSS-VALIDATION
# ============================================================

cv = StratifiedKFold(

    n_splits=5,

    shuffle=True,

    random_state=42
)


scoring = {

    "accuracy": "accuracy",

    "precision_macro": "precision_macro",

    "recall_macro": "recall_macro",

    "f1_macro": "f1_macro"
}


print("\nRunning Stratified 5-Fold Cross-Validation...")


cv_results = cross_validate(

    estimator=model,

    X=X_train,

    y=y_train,

    cv=cv,

    scoring=scoring,

    return_train_score=True
)


# ============================================================
# 7. CREATE FOLD RESULTS
# ============================================================

fold_results = pd.DataFrame({

    "Fold": [1, 2, 3, 4, 5],

    "Train_Accuracy":
        cv_results["train_accuracy"],

    "Validation_Accuracy":
        cv_results["test_accuracy"],

    "Validation_Precision_Macro":
        cv_results["test_precision_macro"],

    "Validation_Recall_Macro":
        cv_results["test_recall_macro"],

    "Validation_F1_Macro":
        cv_results["test_f1_macro"]
})


fold_results[
    "Train_Validation_Gap"
] = (

    fold_results["Train_Accuracy"]

    -

    fold_results["Validation_Accuracy"]
)


# ============================================================
# 8. ROUND DISPLAY VALUES
# ============================================================

numeric_columns = [

    "Train_Accuracy",

    "Validation_Accuracy",

    "Validation_Precision_Macro",

    "Validation_Recall_Macro",

    "Validation_F1_Macro",

    "Train_Validation_Gap"
]


fold_results[
    numeric_columns
] = fold_results[
    numeric_columns
].round(4)


# ============================================================
# 9. CROSS-VALIDATION SUMMARY
# ============================================================

summary = pd.DataFrame({

    "Metric": [

        "Mean Training Accuracy",

        "Mean Validation Accuracy",

        "Validation Accuracy Std",

        "Mean Macro Precision",

        "Mean Macro Recall",

        "Mean Macro F1-Score",

        "Mean Train-Validation Gap"
    ],

    "Value": [

        cv_results[
            "train_accuracy"
        ].mean(),

        cv_results[
            "test_accuracy"
        ].mean(),

        cv_results[
            "test_accuracy"
        ].std(),

        cv_results[
            "test_precision_macro"
        ].mean(),

        cv_results[
            "test_recall_macro"
        ].mean(),

        cv_results[
            "test_f1_macro"
        ].mean(),

        (
            cv_results["train_accuracy"]

            -

            cv_results["test_accuracy"]
        ).mean()
    ]
})


summary["Value"] = (
    summary["Value"]
    .round(4)
)


# ============================================================
# 10. STABILITY CHECK
# ============================================================

mean_accuracy = (
    cv_results[
        "test_accuracy"
    ].mean()
)

accuracy_std = (
    cv_results[
        "test_accuracy"
    ].std()
)

mean_gap = (

    cv_results[
        "train_accuracy"
    ]

    -

    cv_results[
        "test_accuracy"
    ]

).mean()


if accuracy_std <= 0.05 and mean_gap <= 0.10:

    stability_note = (
        "Cross-validation performance appears reasonably "
        "stable for this synthetic prototype."
    )

elif mean_gap > 0.10:

    stability_note = (
        "A relatively large train-validation gap was detected. "
        "Review the model for possible overfitting."
    )

else:

    stability_note = (
        "Cross-validation performance varies across folds. "
        "Further model review may be needed."
    )


# ============================================================
# 11. SAVE RESULTS
# ============================================================

fold_results.to_csv(

    CV_RESULTS_FILE,

    index=False
)


summary.to_csv(

    CV_SUMMARY_FILE,

    index=False
)


# ============================================================
# 12. DISPLAY RESULTS
# ============================================================

print("\n" + "=" * 75)
print("CROSS-VALIDATION RESULTS")
print("=" * 75)

print(
    fold_results.to_string(
        index=False
    )
)


print("\n" + "=" * 75)
print("CROSS-VALIDATION SUMMARY")
print("=" * 75)

print(
    summary.to_string(
        index=False
    )
)


print("\n" + "=" * 75)
print("MODEL STABILITY CHECK")
print("=" * 75)

print(stability_note)


print("\n" + "=" * 75)
print("FILES CREATED")
print("=" * 75)

print(
    "\nreports/"
    "\n├── decision_tree_cross_validation.csv"
    "\n└── decision_tree_cross_validation_summary.csv"
)


print(
    "\nModel validation completed successfully."
)

print(
    "\nIMPORTANT: Cross-validation results are based on "
    "SYNTHETIC development data only."
)

print(
    "The reserved test set was NOT used during "
    "cross-validation."
)