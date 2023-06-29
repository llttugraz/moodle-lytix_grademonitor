/** @module grademonitor */

import Widget from 'lytix_helper/widget';
import Templates from 'core/templates';
import PercentRounder from 'lytix_helper/percent_rounder';
import {makeLoggingFunction} from 'lytix_logs/logs';
import {wwwroot} from 'core/config';

// INTRODUCTION
//
// Most of the algorithms in this widget revolve around keeping track of scores, and calculating percentages.
//
// The more confusing parts consist of the conditions deciding what to do depending on the following questions:
// - Is an item optional or mandatory?
// - Is an item graded?
// - Is there an average score for the item?
// - Has an item been estimated by the student?
//
// Determining if the student’s goal is reachable, or if the student can even pass, is also slightly more complex.
//
// Also, there is a kind of buffering mechanism in place, designed to prevent frequent calls to the WS, batching them instead.
//
// Generally, the index of an item in the received JSON is used to identify it. The ID is only used for saving back to backend.


const WIDGET_ID = 'grademonitor';

export const init = (contextid, userid, courseid, locale) => {


    const dataPromise = Widget.getData(
        'local_lytix_lytix_grademonitor_grademonitor_get',
        {contextid: contextid, courseid: courseid}
    );

    const stringsPromise = Widget.getStrings({
        lytix_grademonitor: { // eslint-disable-line camelcase
            differing: {
                empty: 'goal_empty',
                likely: 'goal_likely',
                unlikely: 'goal_unlikely',
                unachievable: 'goal_unachievable',
                fail: 'goal_fail',
                schemeUpdate: 'scheme_updated',
            },
            identical: [
                'points',
            ],
        }
    });

    locale = Widget.convertLocale(locale);

    Promise.all([stringsPromise, dataPromise])
    .then(values => { // eslint-disable-line complexity
        const
            strings = values[0],
            data = values[1],
            items = data.Items,
            itemIDs = items.IDs,
            itemCount = itemIDs.length,
            itemNames = items.Names,
            maxScores = items.MaxScores,
            scores = items?.Scores,
            averages = items?.ClassAvgs,
            estimations = items?.Estimations,
            optionalIndexes = items?.OptionalIndexes,
            checkedIndexes = items?.CheckedIndexes;

        // These two should be constants after their initial calculation.
        let
            totalPoints = 0, // The sum of all points.
            totalMandatoryPoints = 0; // The sum of all mandatory points (total minus optional).
        {
            // This block only calculates totalPoints and totalMandatoryPoints.
            const optionalsIterator = optionalIndexes?.values();
            let optionalIndex = optionalsIterator?.next().value;
            for (let i = 0; i < itemCount; ++i) {
                totalPoints += maxScores[i];
                if (i != optionalIndex) {
                    totalMandatoryPoints += maxScores[i];
                } else {
                    optionalIndex = optionalsIterator?.next().value;
                }
            }
        }

        const view = {
            table: new Array(itemCount),
            showAverage: data.ShowAverage,
            gradeCompletion: 0,
            currentGrade: 0,
            selfEstimation: 0,
            average: 0,
            // Using a section in the template with an array literal of object literals is likely
            // the most efficient way of dealing with this kind of repetition.
            // The selected grade (the goal) is ‘marked’ in the condition below (by adding a property).
            goalSelection: [
                {grade: 1}, {grade: 2}, {grade: 3}, {grade: 4} // Skip 5, it would’nt make much sense.
            ],
        };
        if (data.Goal) {
            view.goalSelection[data.Goal - 1].selected = true;
        }
        if (data.LastSchemeUpdate) {
            view.schemeNotification = strings.schemeUpdate + (new Date(data.LastSchemeUpdate * 1000)).toLocaleDateString(locale);
        }

        const
            /**
             * The grading scheme as received from backend.
             * The first entry describes the percentage needed for a 4, the second for a 3, …
             * @var {Array} scheme
             */
            scheme = data.Scheme,

            /**
             * Determine a grade from a percentage according to the {@link scheme} received from backend.
             * @function gradeFromPercent
             * @param {number} percent The percentage of achieved points for a certain items or sum of items.
             * @return {number} 1–5
             */
            gradeFromPercent = percent => {
                if (percent >= scheme[3]) {
                    return 1;
                }
                let i = 0;
                while (percent >= scheme[i]) {
                    ++i;
                }
                return 5 - i;
            },

            /**
             * Calculate the grade from points. This calculates the percentage and calls {@link gradeFromPercent}.
             * @function gradeFromScore
             * @param {number} score
             * @param {number} maxScore
             * @return {number} 1–5
             */
            gradeFromScore = (score, maxScore) => {
                return gradeFromPercent(score / maxScore * 100);
            };

        const format = (new Intl.NumberFormat(locale, {maximumFractionDigits: 1})).format;


        // For better readability the template data is created using two loops instead of a single large one.
        // TODO: Profile this, maybe the performance difference is worth slightly less readable code.
        // The first loop below generates the data for each item (== each table row).

        const weightRounder = new PercentRounder();

        // Iterators are not const because they will be reset (→ re-defined) later on, for the second large loop.
        // The same goes for the corresponding values (optionalIndex, checkedIndex).
        let
            optionalsIterator = optionalIndexes?.values(),
            checkedIterator = checkedIndexes?.values(),
            optionalIndex = optionalsIterator?.next().value,
            checkedIndex = checkedIterator?.next().value;

        for (let i = 0; i < itemCount; ++i) {
            const
                maxScore = maxScores[i],
                score = scores?.[i],
                average = averages?.[i],
                estimation = estimations?.[i],
                assessed = score !== undefined && score >= 0,
                weight = maxScore / totalMandatoryPoints * 100,
                optional = i === optionalIndex,
                checked = i === checkedIndex;

            const item = view.table[i] = {
                itemName: itemNames[i],
                index: i,
                assessed: assessed,
                checked: assessed || checked,
                optional: optional,
                value: 0,
            };

            if (checked) {
                checkedIndex = checkedIterator?.next().value;
            }

            if (average !== undefined && average >= 0) {
                item.average = optional ?
                    format(average) + strings.points : format(gradeFromScore(average, maxScore));
            }

            if (optional) {
                optionalIndex = optionalsIterator?.next().value;

                item.weight = Math.round(weight);

                if (estimation !== undefined && estimation >= 0) {
                    item.estimation = format(maxScore / 100 * estimation);
                    item.value = estimation;
                }
                if (assessed) {
                    item.result = format(score);
                    item.value = score / maxScore * 100;
                }
            } else {
                item.weight = weightRounder.round(weight);
                item.estimation = estimation !== undefined && estimation >= 0 ? gradeFromPercent(estimation) : false;

                if (assessed) {
                    view.gradeCompletion += weight;

                    const percent = item.value = score / maxScore * 100;
                    item.result = format(gradeFromPercent(percent));
                } else if (estimation !== undefined && estimation >= 0) {
                    item.value = estimation;
                    item.estimation = gradeFromPercent(estimation);
                }
            }
        }

        view.gradeCompletion = format(view.gradeCompletion);


        // Here we start preparing the second big loop, which calculates the values displayed below the table:
        // - course completion (how much has been graded)
        // - current grade
        // - overall class average
        // - the self-estimated final grade

        // TODO: Maybe unpack this into separate variables for better minification?
        // This keeps track of the sums of the total number of points achievable for …
        const accumulatedMax = {
            score: 0, // … all graded items,
            estimate: 0, // … all estimated items,  ← This is a superset of scores.
            average: 0, // … all items that have a class average.
        };

        // These keep track of the total scores (→ in points, not percent); they are essential for later grade calculation.
        let
            accumulatedScore = 0, // The actual score of the user.
            accumulatedOptionalScore = 0, // The score of optional items.
            accumulatedAverage = 0, // The sum of the course average.
            accumulatedEstimation = 0, // The estimated score.
            accumulatedOptionalEstimation = 0, // The estimated score of optional items.
            remainingPoints = totalPoints, // The sum of points of all remaining (ungraded) items.
            remainingMandatoryPoints = totalMandatoryPoints; // The sum of points of all remaining mandatory items.

        // Use item indexes as property names, each referring to an estimate (percentage).
        const indexToEstimate = {};

        // Reset the iterators that had first been declared for the first big loop.
        optionalsIterator = optionalIndexes?.values();
        optionalIndex = optionalsIterator?.next().value;
        checkedIterator = checkedIndexes?.values();
        checkedIndex = checkedIterator?.next().value;

        for (let i = 0; i < itemCount; ++i) {
            const
                maxScore = maxScores[i],
                score = scores?.[i],
                average = averages?.[i],
                optional = i === optionalIndex;

            if (optional) {
                optionalIndex = optionalsIterator.next().value;
            }

            if (average !== undefined && average >= 0) {
                accumulatedAverage += average;
                if (!optional) {
                    accumulatedMax.average += maxScore;
                }
            }

            if (score !== undefined && score >= 0) {
                accumulatedScore += score;
                accumulatedEstimation += score;
                if (optional) {
                    accumulatedOptionalScore += score;
                    accumulatedOptionalEstimation += score;
                } else {
                    accumulatedMax.score += maxScore;
                    accumulatedMax.estimate += maxScore;
                    remainingMandatoryPoints -= maxScore;
                }
                remainingPoints -= maxScore;
            } else {
                const
                    estimation = estimations?.[i],
                    estimated = estimation !== undefined && estimation >= 0;
                if (estimated) {
                    indexToEstimate[i] = estimation;
                }
                if (i === checkedIndex) {
                    checkedIndex = checkedIterator.next().value;
                    // This check might be redundant: An item should not be able to be checked but not have an estimate.
                    if (estimated) {
                        const estimatedScore = maxScore / 100 * estimation;
                        accumulatedEstimation += estimatedScore;
                        if (optional) {
                            accumulatedOptionalEstimation += estimatedScore;
                        } else {
                            accumulatedMax.estimate += maxScore;
                        }
                    }
                }
            }
        }

        view.average = averages !== undefined && averages.length > 0 ?
            format(gradeFromScore(accumulatedAverage, accumulatedMax.average)) : '–';

        // Has any item been graded?
        const anyScores = scores !== undefined && scores.length > 0;

        /**
        /* The effective grade only considers optional items if enough mandatory items are positive.
         * @function getEffectiveGrade
         * @param {number} accumulatedPoints How many points are considered?
         * @param {number} accumulatedOptionalPoints How many of the considered points are optional?
         * @param {number} accumulatedMaxPoints The total possible score of considered items.
         * @return {number} The effective grade (1–5).
         */
        const getEffectiveGrade = (accumulatedPoints, accumulatedOptionalPoints, accumulatedMaxPoints) => {
            // The grade without considering optional items.
            const mandatoryGrade = gradeFromScore(accumulatedPoints - accumulatedOptionalPoints, accumulatedMaxPoints);
            return mandatoryGrade < 5 ? gradeFromScore(accumulatedPoints, accumulatedMaxPoints) : mandatoryGrade;
        };

        view.currentGrade = anyScores ?
            format(getEffectiveGrade(accumulatedScore, accumulatedOptionalScore, accumulatedMax.score)) : '–';

        let currentEstimatedGrade;

        if (checkedIndexes?.length > 0 && estimations?.length > 0 || anyScores) {
            const effectiveEstimation =
                getEffectiveGrade(accumulatedEstimation, accumulatedOptionalEstimation, accumulatedMax.estimate);
            view.selfEstimation = format(currentEstimatedGrade = effectiveEstimation);
        } else {
            view.selfEstimation = '–';
        }


        // Keeps track of the current CSS class of the goal status message.
        let currentGoalClass = '';

        let
            bestPossibleGrade,
            bestPossibleMandatoryGrade =
                gradeFromScore(accumulatedScore - accumulatedOptionalScore + remainingMandatoryPoints, totalMandatoryPoints);
        if (bestPossibleMandatoryGrade < 5) {
            bestPossibleGrade = gradeFromScore(accumulatedScore + remainingPoints, totalMandatoryPoints);
        } else {
            bestPossibleGrade = bestPossibleMandatoryGrade;
        }

        /**
         * Determines if the given goal is achievable or if the student can pass at all;
         * returns an object containing the message and the CSS class for the goal status banner.
         * @function getGoalStatus
         * @param {number} goal The desired grade (1–4).
         * @return {Object} An object with the properties ‘message’ and ‘class’.
         */
        const getGoalStatus = goal => {
            if (goal === 0) {
                return {
                    message: strings.empty,
                    'class': 'alert-warning',
                };
            } else if (goal >= bestPossibleGrade) {
                if (currentEstimatedGrade !== undefined && goal >= currentEstimatedGrade) {
                    return {
                        message: strings.likely,
                        'class': 'alert-success',
                    };
                }
                return {
                    message: strings.unlikely,
                    'class': 'alert-warning',
                };
            } else if (bestPossibleGrade < 5) {
                return {
                    message: strings.unachievable,
                    'class': 'alert-danger',
                };
            }
            return {
                message: strings.fail,
                'class': 'alert-danger',
            };
        };
        {
            const status = getGoalStatus(data.Goal ?? 0);
            view.goalStatus = status.message;
            view.goalClass = currentGoalClass = status.class;
        }


        // Finally render the template.

        return Templates.render('lytix_grademonitor/grademonitor', view)
        .then(html => {
            Widget.getContentContainer(WIDGET_ID).insertAdjacentHTML('beforeend', html);
            const
                widget = document.getElementById('grademonitor'),
                finalEstimationLabel = widget.querySelector('.final-estimation'),
                checkboxes = widget.querySelectorAll('td input[type=checkbox]'),
                itemEstimationLabels = widget.querySelectorAll('td.estimation');

            const
                // This is used to track if data concerning individual tasks has changed.
                // Global changes (showAverage, goal, schemeUpdateSeen) do not need any additional tracking,
                // the presence of their respective property in ’updated’ is sufficient to indicates changes.
                hasChanged = {
                    estimations: false,
                    checked: false,
                },
                // This stores all values that have been updated since last saving to backend.
                updated = {
                    estimations: {},
                    checked: {},
                    // The other keys (showAverage, goal, schemeUpdateSeen) are simple values, they will be created on the fly.
                };

            /** This stores logging entries until they are sent to backend, afterwards it will be replaced with an empty array. */
            let logs = [];

            /**
             * Save data from {@link updated} and loggin entries from {@link logs} back to backend.
             * This is usually called by {@link queueUpdate} or when the page is left (via EventListener).
             * @function saveData
             */
            const
                log = makeLoggingFunction(userid, courseid, contextid, 'grademonitor'),
                complexKeys = ['estimations', 'checked'],
                simpleKeys = data.LastSchemeUpdate ? ['goal', 'showAverage', 'schemeUpdateSeen'] : ['goal', 'showAverage'],
                beaconUrl = wwwroot + '/local/lytix/modules/grademonitor/endpoint/grademonitor_update.php',
                saveData = () => {
                    if (timeoutId === null) {
                        return;
                    }
                    const changes = {};
                    // We could not use a for-of-loop because Babel would mess it up.
                    for (let i = 0; i < 2; ++i) {
                        const key = complexKeys[i];
                        if (hasChanged[key]) {
                            changes[key] = updated[key];
                            updated[key] = {};
                            hasChanged[key] = false;
                        }
                    }
                    // … same here.
                    for (let i = simpleKeys.length - 1; i >= 0; --i) {
                        const key = simpleKeys[i];
                        if (updated[key] !== undefined) {
                            changes[key] = updated[key];
                            updated[key] = undefined;
                        }
                    }

                    const logCount = logs.length;
                    for (let i = 0; i < logCount; ++i) {
                        const l = logs[i];
                        log(l[0], l[1], l[2], l[3]);
                    }
                    logs = [];

                    // Use sendBeacon as it is guaranteed to be completed, even after the user has left the page.
                    navigator.sendBeacon(
                        beaconUrl,
                        JSON.stringify({contextid, courseid, changes})
                    );

                    timeoutId = null;
                };

            let timeoutId = null;
            /**
             * This starts or resets a timer to call {@link saveData}.
             * @function queueUpdate
             */
            const queueUpdate = () => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(saveData, 120000); // Wait 2 minutes.
            };


            const optionalIndexeSet = new Set(optionalIndexes);

            // This is the sum of total scores of all checked mandatory tasks.
            let accumulatedMaxScore = accumulatedMax.estimate;

            // This is the central event listener, that deals with all parameter changes thtat affect the estimation.
            // An important detail: When the range input is changed, the item is automatically checked.
            widget.querySelector('table').addEventListener('input', event => {
                const target = event.target;

                // First, find the affected row element.
                let row = target;
                do {
                    row = row.parentElement;
                } while (row.dataset.index == undefined);

                const
                    itemIndex = parseInt(row.dataset.index),
                    itemId = itemIDs[itemIndex],
                    maxScore = maxScores[itemIndex],
                    currentEstimation = indexToEstimate[itemIndex] ?? 0,
                    optional = optionalIndexeSet.has(itemIndex),
                    checkbox = checkboxes[itemIndex],
                    isCheckboxEvent = target.type == 'checkbox';

                let checkboxChanged = isCheckboxEvent;

                // The estimation (range input) changes, but the item was previously unchecked.
                if (!isCheckboxEvent && !checkbox.checked) {
                    checkboxChanged = checkbox.checked = true;
                }

                if (checkboxChanged) {
                    hasChanged.checked = true;
                    const sign = (updated.checked[itemId] = checkbox.checked) && 1 || -1;
                    if (!optional) {
                        accumulatedMaxScore += maxScore * sign;
                    }
                    const scoreDelta = maxScore / 100 * currentEstimation * sign;
                    accumulatedEstimation += scoreDelta;

                    if (checkbox.checked) {
                        row.classList.remove('unchecked');
                    } else {
                        row.classList.add('unchecked');
                    }
                    logs.push([checkbox.checked ? 'INCLUDE' : 'EXCLUDE', 'ITEM', null, itemId]);
                }

                // The estimation (range input) changes.
                if (!isCheckboxEvent) {
                    hasChanged.estimations = true;
                    const
                        newEstimation = indexToEstimate[itemIndex] = updated.estimations[itemId] = parseInt(target.value),
                        scoreDelta = maxScore / 100 * (newEstimation - currentEstimation);
                    accumulatedEstimation += scoreDelta;

                    if (optional) {
                        itemEstimationLabels[itemIndex].innerText =
                            format(maxScore / 100 * newEstimation) + strings.points;
                        accumulatedOptionalEstimation += scoreDelta;
                    } else {
                        itemEstimationLabels[itemIndex].innerText = format(gradeFromPercent(newEstimation));
                    }
                    logs.push(['CHANGE', 'ESTIMATION', newEstimation, itemId]);
                }

                const previousEstimatedGrade = currentEstimatedGrade;

                // See if there even are any (mandatory) items checked.
                if (accumulatedMaxScore > 0) {
                    const effectiveEstimation =
                        getEffectiveGrade(accumulatedEstimation, accumulatedOptionalEstimation, accumulatedMaxScore);
                    finalEstimationLabel.innerText = format(effectiveEstimation);
                    currentEstimatedGrade = effectiveEstimation;
                } else {
                    finalEstimationLabel.innerText = '–';
                    currentEstimatedGrade = undefined;
                }

                if (currentGoal && currentEstimatedGrade !== previousEstimatedGrade) {
                    updateGoalStatus();
                }

                queueUpdate();
            });


            // In order to hide the class average column AND control column width with <colgroup> we have to get tricky.
            // The averageCol is inserted after nameCol, which is why we need references to both.
            // We do not use ‘visibility: collapse’ because it wastes space and causes layout problems.

            const
                classAverages = widget.querySelectorAll('.class-average'),
                nameCol = document.querySelector('col.name'),
                averageCol = data.ShowAverage ? document.querySelector('col.average') : document.createElement('col');
            if (!data.ShowAverage) {
                averageCol.classList.add('average');
            }
            document.getElementById('show-average-control').addEventListener('change', event => {
                for (let i = classAverages.length - 1; i >= 0; --i) {
                    classAverages[i].classList.toggle('d-none');
                }
                if ((updated.showAverage = data.ShowAverage = !data.ShowAverage)) {
                    nameCol.insertAdjacentElement('afterend', averageCol);
                } else {
                    averageCol.parentElement.removeChild(averageCol);
                }
                logs.push([event.target.checked ? 'SHOW' : 'HIDE', 'COURSE AVERAGE']);
                queueUpdate();
            });


            let currentGoal = data.Goal;
            const
                goalElement = document.getElementById('grade-goal'),
                goalStatus = document.getElementById('goal-status'),
                updateGoalStatus = (goal = currentGoal) => {
                    const status = getGoalStatus(goal);

                    goalStatus.innerText = status.message;

                    goalElement.classList.remove(currentGoalClass);
                    goalElement.classList.add(currentGoalClass = status.class);
                };
            goalElement.addEventListener('change', event => {
                updateGoalStatus(currentGoal = updated.goal = parseInt(event.target.value));
                logs.push(['CHANGE', 'GOAL', currentGoal]);
                queueUpdate();
            });

            document.getElementById('dismiss-scheme-update')?.addEventListener('click', event => {
                updated.schemeUpdateSeen = true;
                queueUpdate();
                event.target.parentElement.remove();
            }, {once: true});

            window.addEventListener('beforeunload', saveData);
            return;
        });
    })
    .finally(() => {
        document.getElementById(WIDGET_ID).classList.remove('loading');
    })
    .catch(error => Widget.handleError(error, WIDGET_ID));
};
