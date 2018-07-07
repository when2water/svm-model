import csv
import numpy as np
from sklearn.preprocessing import StandardScaler
import itertools
from sklearn.cross_validation import train_test_split
import matplotlib.pyplot as plt
import pickle

__author__ = 'hsheth'
RandomState = 0  # for data partitioning; should be set to 'None' for production


def interact(gl=globals(), lo=locals()):
    import readline # optional, will allow Up/Down/History in the console
    import code
    vars = gl.copy()
    vars.update(lo)
    shell = code.InteractiveConsole(vars)
    shell.interact()


def load_data(file):
    # Read the CSV file
    reader = csv.reader(open(file, 'r'), delimiter=',')
    csvD = [x[1:] for x in list(reader)[1:]]  # remove header and dates
    # print(csvD)

    # Convert to a numpy array
    csvX = [x[:-1] for x in csvD]
    csvY = [x[-1] for x in csvD]

    Xnumpy = np.array(csvX).astype('float64')
    ynumpy = np.array(csvY).astype('int')
    return Xnumpy, ynumpy


def feature_scaling(X):
    sc = StandardScaler()
    Xnew = sc.fit(X).transform(X)
    return Xnew, sc


def partition_data(partitions, *arrays):
    # Reset the partition sizing to sum to 1
    partsFull = np.asarray(partitions).astype('float', casting='safe')
    parts = partsFull / partsFull.sum()

    # Partition
    arrays = list(arrays)
    outputs = [[] for _ in itertools.repeat(None, len(arrays))]
    it = np.nditer(parts[:-1], flags=["f_index"])
    while not it.finished:
        index, first = it.index, it[0]
        other = np.sum(parts[(index+1):])
        split = train_test_split(*arrays, train_size=first, test_size=other, random_state=RandomState)
        for ind, val in enumerate(split):
            if ind % 2 == 0:
                outputs[ind//2].append(val)
            else:
                arrays[ind//2] = val

        it.iternext()

    # Add leftover values
    for ind, val in enumerate(arrays):
        outputs[ind].append(val)

    return (arrPt for output in outputs for arrPt in output)


def resAdjust(ax, xres=None, yres=None):
    """
    Send in an axis and I fix the resolution as desired.
    """

    if xres:
        start, stop = ax.get_xlim()
        ticks = np.arange(start, stop + xres, xres)
        ax.set_xticks(ticks)
    if yres:
        start, stop = ax.get_ylim()
        ticks = np.arange(start, stop + yres, yres)
        ax.set_yticks(ticks)


def plotGridSearchResults(grid, model, title):
    plt.close()

    # Prepare the data for plotting
    data = [c.mean_validation_score for c in model.grid_scores_]
    data = np.array(data)
    data.shape = (len(grid[0]['C']), len(grid[0]['gamma']))

    # Make a Plot
    fig = plt.figure()
    ax = plt.subplot()
    heatmap = ax.pcolor(data, cmap=plt.cm.Blues)
    cbar = plt.colorbar(heatmap)
    ax.set_xticklabels(grid[0]['gamma'], rotation='vertical'), ax.set_xlabel("Gamma")
    ax.set_yticklabels(grid[0]['C']), ax.set_ylabel("C")
    resAdjust(ax, 1, 1)
    ax.set_title(title)
    ax.set_axis_bgcolor('white')
    plt.show()
    plt.close()


def enPickle(obj, file):
    with open(file, "wb") as fd:
        pickle.dump(obj, fd)


def dePickle(file):
    with open(file, "rb") as fd:
        return pickle.load(fd)
