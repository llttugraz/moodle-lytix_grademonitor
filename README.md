# lytix\_grademonitor

This is a tool for students to plan and estimate their performance by estimating their grades. Teachers see the development of students’ goals, estimations and grades over time.

## ARCHIVED PROJECT

This project has been archived and is no longer being maintained or developed by our team. We have made the decision to cease work on this plugin to focus our efforts on other projects that align more closely with our current goals and priorities.

What does this mean?
1. No More Updates: The plugin will no longer receive updates, including feature enhancements, bug fixes, or security patches.
2. No Support: We will not be providing official support for this plugin going forward. The repository will remain available in a read-only state for archival purposes.
3. Community Forking: While we will not be actively maintaining the project, we encourage the community to fork and continue its development if they find it useful. We hope this plugin can still serve as a valuable resource or inspiration for future projects.

## JSON

We need different data, depending on if the user is a teacher or a student.

### Teacher

#### Detailed View

`Count` provides these numbers:

- how many students have set *which grade* as their __goal__ (e. g. 20 students want a 1, 15 a 2, …)
- how many students __estimate__ which *final grade* (e. g. 10 students believe they will achieve a final grade of 1, 7 of 2, …)
- how many students currently have which __grade__ (e. e. 9 students currently have a 1, 30 a 2, …)

`Count` is a two-dimensional array where each nested array contains up to five entries, each entry corresponds to the count of a grade (`j+1` → grade).

Example code of how to access the number of students who had set their goal to *2* at the end of the fifth week:

```js
const GOAL = 0, ESTIMATE = 1, GRADE = 2;

week = 5;
target = GOAL;
grade = 2;

Count[Count.length - week * 3 + target][grade - 1];
```

`Percentage` is similar in structure to `Count`, but there are only two alternating kinds of value instead of three:

- Every *even* entry is meant to answer this question: _How many tasks does the median student include in their estimation?_ This **does not include** already graded tasks!
- Every *odd* entry answers this question: _How many of the median student’s tasks have already been graded?_

Both percentages **should consider the weight of each task!**

We get this value by first calculating the percentage for each student, then we pick the median from these values.

All entries should be sorted by **reverse chronological order**, the **first entries** should contain **today’s values!**

Also, a week starts with Monday and ends on Sunday, values should be taken from Sundays.

```
{
	// Any day of the latest week, exluding today.
	Start: <UNIX timestamp>

	// Number of students.
	Total: <int>,

	// This contains the counts in this order: Goal, Estimate, Grade.
	// Note that the arrays for Goal only have four entries, as 5 cannot be set as a goal.
	// A sub-array can also have fewer entries: If there are only values for grades 1 and 2 → [<int>, <int>].
	Count: [
		// today
		[ <int>, <int>, <int>, <int> ], // goals of today
		[ <int>, <int>, <int>, <int>, <int> ], // estimates of today
		[ <int>, <int>, <int>, <int>, <int> ], // grades of today
		// week 1
		[ <int>, <int>, <int>, <int> ], // goals of last week
		[ <int>, <int>, <int>, <int>, <int> ], // estimates of last week
		[ <int>, <int>, <int>, <int>, <int> ], // grades of last week
		// week 2
		[ <int>, <int>, <int>, <int> ], // goals of the week before last week
		[ <int>, <int>, <int>, <int>, <int> ], // estimates of the week before last week
		…
	],

	// This contains the percentages as described above, one for each week.
	Percentage: [
		// last week
		<int>, // median percent of activated tasks
		<int>, // median percent of graded tasks
	   // the week before last week
		<int>, // median percent of activated tasks
		<int>, // median percent of graded tasks
	…
	]
}
```

##### Example
```
{
	Start: 1673478000, // 2023-01-12, any day of the latest week excluding today.

	Total: 100, // Number of students.

	Count: [
		// today
		[ 7, 34, 52, 6 ], // goals
		[ 11, 33, 35, 13, 1 ], // estimates
		[ 7, 16, 34, 33, 10 ], // grades

		// week 4
		[ 7, 32, 54, 6 ], // goals
		[ 11, 33, 35, 12, 2 ], // estimates
		[ 7, 16, 34, 33, 10 ], // grades

		// week 3
		[ 6, 33, 52, 7 ], // goals
		[ 9, 20, 9, 31, 3 ], // estimates
		[ 4, 13, 35, 36, 12 ], // grades

		// week 2
		[ 10, 32, 42, 3 ], // goals
		[ 2, 9, 0, 31, 1 ], // estimates
		[ 2, 12, 30, 34, 10 ], // grades

		// week 1
		[ 0, 12, 30 ], // goals
		[], // estimates
		[] // grades
	],

	Percentage: [
		// estimate, grade
		81, 75, // today
		81, 75, // week 4
		54, 30, // week 3
		22, 22, // week 2
		0, 0 // week 1
	]
}
```


#### Overview

_This is currently not included in any dashboard._

The data only consists of three arrays, with five entries each. Each entry represents one grade, starting with 1.  
For example: `Goals[0]` is the count of students who have set grade 1 as their goal, `Goals[2]` is how many have grade 3 as their goal.

```js
{
	Goals: [ <int>, <int>, <int>, <int>, <int> ],
	Grades: [ <int>, <int>, <int>, <int>, <int> ],
	Estimations: [ <int>, <int>, <int>, <int>, <int> ]
}
```

### Student

`Items` is a struct of arrays: All arrays are connected by index, except for `OptionalIndexes` and `CheckedIndexes`, which contain the actual indexes instead.  
If `OptionalIndexes` looked like this `[ 3, 5 ]` it would mark the fourth and sixth task in `IDs`/`Names` as optional.

`Scores`, `ClassAvgs` or `Estimations` do not need to include all entries; for example, if only the first three items have been graded `Scores` only needs to contain the scores of these three entries.  
If instead only the second item has been graded `Scores` would have two entries: a negative number for the first item, and the score of the second item, for example: `[ -1, 42 ]`.

```js
{
	Items: {
		// ascending by date (earliest first)
		IDs: [ <int>, … ],
		Names: [ <string>, … ]
		MaxScores: [ <int>, … ], // in points

		Scores: [ <int>, … ], // in points, negative number if not yet graded
		ClassAvgs: [ <int>, … ], // in points, negative number if not yet graded

		// this number represents a percentage of points
		Estimations: [ <int>, … ], // 0–100, negative number if not yet set

		// if an item is checked and not yet graded add its index from Items to this array
		CheckedIndexes: [ <int>, … ], // ascending
		// if an item is optional (not mandatory) add its index to this array
		OptionalIndexes: [ <int>, … ], // ascending
	},

	Goal: <0–5>, // zero if not yet set

	// the first value describes how many percent are needed for a 4, the second for a 3, …
	Scheme: [ <float>, <float>, <float>, <float> ], // in percent

	// When was the last time the grading scheme has been updateted?
	// Do not include this property if the studen has already acknowledged the update. (== has actively closed the notification)
	LastSchemeUpdate: <timestamp>, // UNIX timestamp

	ShowAverage: <bool> // either: 0, 1, true, false
}
```


## Examples

### Student

```js
{
	Items: {
		IDs: [ 1, 2, 3, 4, 5, 6, 7 ],
		Names: [
			'Assignment 1', 'Quiz 1', 'Assignment 2', 'Bonus Quiz 1', 'Assignment 3', 'Bonus Quiz 2', 'Final Exam'
		],
		MaxScores: [ 15, 5, 20, 5, 20, 5, 30 ],

		Scores: [ 14, 3, 11, 4 ],
		ClassAvgs: [ 1, 3, 3 ],

		Estimations: [ 2, 3, 4, 0, 2, 90, 1 ],

		OptionalIndexes: [ 3, 5 ],
		CheckedIndexes: [ 5 ],
	},

	Goal: 2,

	Scheme: [ 50, 70, 80, 90 ],

	LastSchemeUpdate: 1641204725,
	ShowAverage: 1
}
```


## Notes

Optional items can only increase the total score: they never worsen the final grade.  
Optional items only contribute to the final grade if the total score of mandatory items is high enough to pass the course without optional items.

If optional items have already been graded they are always included in the calculation of the final grade.


## Updating Database

Each time students are about to leave the page we check if anything has to be updated. The sent JSON’s structure is similar to the received one’s:

```js
{
	// ‘estimations’ and ‘checked’ only contain items whose state has been changed
	estimations: {
		<item-id>: <0–100>,
			…
	 },
	checked: {
		<item-id>: <bool>,
		…
	},

	goal: <1–5>,

	schemeUpdateSeen: <bool>,
	showAverage: <bool>
}
```

Each key is optional: If a key is not present nothing has been changed.
