from train_utils import *
from sklearn.grid_search import GridSearchCV
from sklearn.svm import SVC
import sys
# from sklearn.cross_validation import cross_val_predict
# from sklearn.metrics import accuracy_score

# Parse Command Line Arguments
graph = False
if 'plot' in sys.argv:
    graph = True

__author__ = 'hsheth'
CORES = 4

# Load the data
Xunscaled, yfull = load_data("training_data_FINAL.csv")

# Feature Scaling
Xfull, scaler = feature_scaling(Xunscaled)
enPickle(scaler, "feature_scaler.pickle")

# Partition data into training and testing
Xtrain, Xtest, ytrain, ytest = partition_data([.8, .2], Xfull, yfull)


def perform_grid_search(grid):
    gridModel = GridSearchCV(SVC(shrinking=False, cache_size=400),
                             param_grid=grid, scoring='accuracy', verbose=1, n_jobs=CORES)
    gridModel.fit(Xtrain, ytrain)
    return gridModel


# Grid Search for C and gamma (general)
gridFirst = [
    {
        'kernel': ['rbf'],
        'gamma': np.logspace(-6, 0, num=13),
        'C': np.logspace(-1, 9, num=21),
    },
    # {
    #     'kernel': ['linear'],
    #     'C': [1, 5, 10, 50, 100, 500, 1000, 5000, 10000],
    # },
]
# gridFirst = [{'kernel': ['rbf'], 'gamma': [5e-2, 1e-2, 5e-3, 1e-3, 5e-4, 1e-4], 'C': [1, 5, 10, 50, 100, 500, 1000]}]

# First Model (general)
gridModelGen = perform_grid_search(gridFirst)
print("Gen - Best Score on CV Data:")
print(gridModelGen.best_score_)
print("Gen - Best Parameters:")
print(gridModelGen.best_params_)
print("Gen - Best Score on Testing Data:")
print(gridModelGen.best_estimator_.score(Xtest, ytest))

# Make a Plot
if graph:
    plotGridSearchResults(gridFirst, gridModelGen, "Grid Search Model 1 Results")

# Grid Search (focused)
gridSecond = [{
    'kernel': ['rbf'],
    'gamma': np.logspace(-6, -5, num=9),
    'C': np.logspace(5, 7, num=9),
}]

# Second Model (focused)
gridModelFocus = perform_grid_search(gridSecond)
print("Focus - Best Score on CV Data:")
print(gridModelFocus.best_score_)
print("Focus - Best Parameters:")
print(gridModelFocus.best_params_)
print("Focus - Best Score on Testing Data:")
print(gridModelFocus.best_estimator_.score(Xtest, ytest))

# Plot the Focused/Zoomed In Grid Search Results
if graph:
    plotGridSearchResults(gridSecond, gridModelFocus, "Grid Search Zoomed Results")

# Save Results
enPickle(gridModelGen, "grid_search_gen.pickle")
enPickle(gridModelFocus, "grid_search_focus.pickle")
print("Saved modelGen and modelFocus to pickle files")

#interact(gl=globals(), lo=locals())
