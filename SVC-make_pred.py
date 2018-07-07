import numpy as np
from train_utils import *
from sklearn.svm import SVC
import sys
import logging
import datetime

logging.basicConfig(filename='make_pred.log',level=logging.DEBUG)
logging.info("====================================================")
logging.info(str(datetime.datetime.now()))

__author__ = 'hsheth'
ERR_INPUT1 = -3
ERR_INPUT2 = -4

# TODO: add more extensive error checking
# TODO: add proper logging

# Parse Command Line Arguments
#  makes np.array called featureRaw
if len(sys.argv) != 11:
    print(ERR_INPUT1)
    logging.error(ERR_INPUT1)
    sys.exit(ERR_INPUT1)
else:
    args = sys.argv[1:]
    try:
        featureRaw = np.array(args).astype('float64')
    except:
        print(ERR_INPUT2)
        logging.error(ERR_INPUT2)
        sys.exit(ERR_INPUT2)
logging.debug("Parsed Input: %s", featureRaw)

# Perform feature scaling
scaler = dePickle("feature_scaler.pickle")
features = scaler.transform(featureRaw)
# print(features)
logging.debug("Features after scaling: %s", np.array_str(features, precision=4))
# features = np.array([ 0.51884176,  0.16121916, -0.84163707, -0.10023269,  0.09374209,
#    -0.65314605, -0.675373  , -0.52118902, -0.47920066, -0.40745617])

# Load Grid Search Model Results
gridModel = dePickle("grid_search_focus.pickle")
model = gridModel.best_estimator_

# Get Predition
prediction = model.predict(features)
logging.info("Predicition: %s", prediction)
# print(prediction)
if prediction[0] == 1:
    logging.info("Returning 1")
    print(1)
else:
    logging.info("Returning 0")
    print(-1)
