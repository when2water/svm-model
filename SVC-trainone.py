from train_utils import *
from sklearn.svm import SVC
from sklearn.cross_validation import cross_val_predict
from sklearn.metrics import accuracy_score

__author__ = 'hsheth'

# Load the data
Xunscaled, yfull = load_data("training_data_FINAL.csv")

# Feature Scaling
Xfull, scaler = feature_scaling(Xunscaled)

# Partition data into training and testing
Xtrain, Xtest, ytrain, ytest = partition_data([.75, .25], Xfull, yfull)

# Create and train the SVM
model = SVC()
model.fit(Xtrain, ytrain)

# Evaluate performance with the testing data set
predicted = cross_val_predict(model, Xtrain, ytrain, cv=3, n_jobs=-1)
accuracy = accuracy_score(ytrain, predicted)
print(accuracy)

interact(gl=globals(), lo=locals())
