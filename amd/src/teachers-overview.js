import Widget from 'lytix_helper/widget';
import Templates from 'core/templates';

const WIDGET_ID = 'grademonitor';

export const init = (contextid, userid, courseid) => {
    const dataPromise = Widget.getData(
        'local_lytix_lytix_grademonitor_grademonitor_get_table',
        {contextid: contextid, courseid: courseid}
    );

    const stringsPromise = Widget.getStrings({
        lytix_grademonitor: { // eslint-disable-line camelcase
            differing: {
                goal: 'th_goal',
                grade: 'th_grade',
                estimation: 'th_estimation',
            },
        },
    });

    Promise.all([stringsPromise, dataPromise])
    .then(values => {
        const
            strings = values[0],
            data = values[1],
            titles = [strings.goal, strings.estimation, strings.grade],
            propertyNames = ['Goals', 'Estimations', 'Grades'],
            view = {
                rows: new Array(3),
            },
            rows = view.rows;

        for (let i = 0; i < 3; ++i) {
            rows[i] = {
                title: titles[i],
                counts: data[propertyNames[i]],
            };
        }

        return Templates.render('lytix_grademonitor/teachers-overview', view);
    })
    .then(html => {
        Widget.getContentContainer(WIDGET_ID).insertAdjacentHTML('beforeend', html);
        return;
    })
    .finally(() => {
        document.getElementById(WIDGET_ID).classList.remove('loading');
    })
    .catch(error => {
        Widget.handleError(error, WIDGET_ID);
    });
};
