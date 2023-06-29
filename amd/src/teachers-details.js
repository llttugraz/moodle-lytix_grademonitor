import Widget from 'lytix_helper/widget';
import Templates from 'core/templates';
import {getDaysPerMonth} from 'lytix_helper/date_helper';
import PercentRounder from 'lytix_helper/percent_rounder';

const WIDGET_ID = 'grademonitor';

export const init = (contextid, userid, courseid, locale) => {
    locale = Widget.convertLocale(locale);
    // The number of values: (weeks + 1) * 3.
    // This will be assigned a value that is only available in the scope of the first promise.
    // To make it accessible down the promise chain we declare it in an outer scope.
    let valueCount = 0;

    // The promise returned by ‘getData’ is only a fake promise (made by jQuery): It does not have ‘finally(…)’.
    // By wrapping it in a native promise we can work around this issue.
    Promise.resolve(Widget.getData(
        'local_lytix_lytix_grademonitor_grademonitor_get_history',
        {contextid: contextid, courseid: courseid}
    ))
    .then(data => {
        const counts = data.Count;
        valueCount = counts.length;
        const
            studentCount = data.Total,
            weekCount = Math.round(valueCount / 3), // This is actually the number of weeks plus one (today’s values).
            context = {
                counts: Array(weekCount),
                labels: Array(weekCount).fill(true),
                percentages: Array(weekCount),
                weeks: Array(weekCount - 1), // Calendar weeks according to ISO 8601.
                valueCount: valueCount - 3, // Exclude today’s values.
            };

        // First, we extract and calculate all values, later we cut off today’s data.

        // Returns the sum of all entries of an erray.
        const sumArray = array => {
            let sum = 0;
            for (let i = array.length - 1; i >= 0; --i) {
                sum += array[i];
            }
            return sum;
        };

        const rounder = new PercentRounder();
        // Populate segments (percent, count, grade), calculate goal percentage.
        for (let dataColumIndex = 0, percentIndex = 0; dataColumIndex < valueCount; ++dataColumIndex) {
            const
                currentCounts = counts[dataColumIndex],
                currentCountsLength = currentCounts.length;
            let countSum; // Declare here so we can calculate and check it only when needed.

            if (currentCountsLength > 0 && (countSum = sumArray(currentCounts)) > 0) {
                const segments = context.counts[dataColumIndex] = [];
                let gradeIndex = currentCountsLength - 1;
                // We use a do-while loop to skip the redundant first conditional of a for loop,
                // because we already know that it will run at least once, due to the length check.
                do {
                    const currentGradeCount = currentCounts[gradeIndex];
                    if (currentGradeCount > 0) {
                        segments.push({
                            count: currentGradeCount,
                            percent: rounder.round(currentGradeCount / countSum * 100),
                            grade: gradeIndex + 1,
                        });
                    }
                } while (--gradeIndex >= 0);

                // If we are at a Goal segment, we calculate the percentage; otherwise we retrieve it and increment the index.
                context.percentages[dataColumIndex] = Math.round(dataColumIndex % 3 == 0 ?
                    countSum / studentCount * 100 : data.Percentage[percentIndex++]);
            } else {
                context.counts[dataColumIndex] = false; // Has to be something falsy.
                context.percentages[dataColumIndex] = false;
                if (dataColumIndex % 3) {
                   percentIndex++;
                }
            }
            rounder.reset();
        }

        // The algorithm for calculating the week number has been provided by:
        // https://stackoverflow.com/a/6117889

        const startDate = new Date(data.Start * 1000);
        // Set date to closest Thursday (the week with the first Thursday of the year is CW 1).
        startDate.setUTCDate(startDate.getUTCDate() + 4 - (startDate.getUTCDay() || 7));
        const
            // First day of the year we start in.
            yearStart = new Date(Date.UTC(startDate.getUTCFullYear(), 0, 1)),
            getCalendarWeek = () => Math.ceil((((startDate - yearStart) / 86400000) + 1) / 7);

        for (let i = 0, calendarWeek = getCalendarWeek(), dstIndex = weekCount; i < weekCount; ++i) {
            // A year has either 52 or 53 weeks. If we reach that limit, we re-calculate.
            if (calendarWeek > 52) {
                startDate.setUTCDate(startDate.getUTCDate() + i * 7);
                yearStart.setUTCFullYear(yearStart.getUTCFullYear() + 1);
                calendarWeek = getCalendarWeek();
            }
            context.weeks[--dstIndex] = calendarWeek++;
        }

        // Get name and width of months.

        // Reset start.
        startDate.setTime(data.Start * 1000);
        // Set to Monday of its week.
        startDate.setDate(startDate.getDate() + 1 - (startDate.getDay() || 7));

        const endDate = new Date(startDate.getTime());
        // Set to Sunday (+6) of last week.
        // We subtract 2 from weekCount to exclude today and the current (first) week.
        endDate.setDate(endDate.getDate() + 6 + (weekCount - 2) * 7);

        const
            {daysPerMonth, monthNames} = getDaysPerMonth(startDate, endDate, new Intl.DateTimeFormat(locale, {month: 'short'})),
            dayCount = sumArray(daysPerMonth),
            monthCount = monthNames.length,
            months = context.months = new Array(monthCount);

        for (let i = 0, dstIndex = monthCount; i < monthCount; ++i) {
            months[--dstIndex] = {
                width: rounder.round(daysPerMonth[i] / dayCount * 100),
                month: monthNames[i],
            };
        }

        return Templates.render('lytix_grademonitor/teachers-details', context);
    })
    .then(html => {
        const container = Widget.getContentContainer(WIDGET_ID);
        container.insertAdjacentHTML('beforeend', html);

        // Make overflowing segment labels invisible.
        const segments = container.querySelectorAll('.grade-segment');
        for (let i = segments.length - 1; i >= 0; --i) {
            const segment = segments[i];
            // Check for vertical overflow.
                if (segment.scrollHeight > segment.clientHeight) {
                    // Make label invisible.
                        segment.style.color = 'transparent';
                }
        }

        return;
    })
    .finally(() => {
        document.getElementById(WIDGET_ID).classList.remove('loading');
    })
    .catch(error => {
        Widget.handleError(error, WIDGET_ID);
    });
};
