# School Timetable Solver PHP 1.1

A web application that builds a school's timetable automatically and **learns this school's own habits** from timetables it has already accepted, so the result looks like something the staff will actually accept rather than a generic optimum.

*Project 15: Automated school timetable and teacher constraint solver.*

## The problem it solves

Building the timetable takes the deputy head teacher weeks every term and still produces clashes, idle rooms and teachers scheduled in two places. This system recovers those weeks and removes the clashes.

## Features

### Core Functionality

- **User Authentication**: Secure login (hashed passwords, prepared statements, session management)
- **Role-Based Access Control**: Administrators change everything; Staff accounts can only view timetables and reports
- **Dashboard**: Setup counts, accepted timetable, lessons to place, learning status, time recovered, pre-flight warnings
- **Teacher / Subject / Class / Room Management**: Complete CRUD with modals (same pattern as the reference inventory system)
- **Allocations**: Who teaches which subject to which class, periods per week and number of double lessons; only qualified teachers are offered; copy a class's allocations to another
- **Teacher Availability**: Click-to-cycle grid: available, *prefer to avoid* (soft), *unavailable* (hard). Handles part-time teachers
- **Timetable Generation**: One click, with optional comparison against a generic solver
- **Timetable Viewer**: By class, by teacher or by room; print; CSV export
- **Manual Adjustment**: Click a lesson to move it; swaps within a class; any move that creates a clash is blocked and explained
- **Reports**: Independent quality report, teacher workload, class completeness, lab usage

### The AI component

- **Constraint solving**: pure PHP solver (no external services). Hard rules are never broken; a lesson that cannot be placed legally is reported with the reason instead of producing a clash
- **Learned preferences**: from past accepted timetables it learns when each subject is usually taught (period and day), where double lessons start, each teacher's habitual pattern, and how much teacher idle time and repetition the school tolerates
- **Explainable**: everything it learns is plain counting, shown as heat-maps on the *AI Learning* page
- **Tunable**: the staff set how much each soft rule matters on the *Rules & Weights* page

### Advanced Features

- **History import**: upload past timetables as CSV (template provided); unmatched rows are listed, never silently dropped
- **Learning control**: choose which past timetables the solver learns from
- **Independent validator**: re-checks every hard rule straight from the database, after generation and after every manual move
- **Pre-flight checks**: warns before solving if a teacher, room or class is over capacity or a lab does not exist
- **Fixed slots** (v1.1): lessons the school pins (e.g. Saturday-afternoon revision) are placed first and never moved
- **Teacher-less subjects** (v1.1): class-supervised periods such as revision need no teacher and never cause teacher clashes
- **Special rooms**: labs, computer rooms and fields with type and capacity; a subject can need its room for every lesson or for doubles only (practicals)

## Technology Stack

### Backend
- **PHP 7.4+** (developed on 8.3)
- **MySQL 5.7+ / MariaDB 10.3+** via `mysqli` prepared statements

### Frontend
- **Tailwind CSS** (local file), **jQuery**, **jQuery UI**, **DataTables**, **Font Awesome**, the same offline `assests/` bundle and modal/dropdown shim as the reference project

No Composer, no Node, no internet needed at run time.

## Requirements

- **PHP**: 7.4 or higher with `mysqli` and `mbstring`
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+, Nginx, or `php -S` for a quick demo
- **Browser**: Any modern browser

## How the solver works

1. **Construct** - every lesson (a single period or a double) is placed without breaking a hard rule. If a lesson has no free slot it temporarily displaces the lessons in its way, which are re-queued (a tabu list prevents ping-pong).
2. **Improve** - simulated annealing relocates and swaps lessons, only ever moving between legal states, accepting changes that lower the soft cost.

**Hard rules**: one lesson per class, teacher and special room per slot; teacher unavailable slots; teacher maximum per day and per week; lab type and capacity; doubles never straddle a break.

**Soft cost**: learned subject/period, subject/day, double-start and teacher/period habits; teacher "prefer to avoid" slots; teacher idle gaps (and gaps longer than the school tolerates); balanced teacher days; no repeated subject in a day; class free periods at the end of the day; no back-to-back demanding subjects; demanding subjects not in the last two periods.

**Learning**: for each subject it counts how often it was taught in each period/day in accepted timetables (Laplace-smoothed). The cost of a slot is `ln(chance / probability)`: negative when the school uses that slot more than chance, positive when it almost never does. With no history the cost is 0 and the generic rules apply.

### Measured on the bundled demo school

| | Learned solver | Generic solver |
|---|---|---|
| Lessons placed | 284 / 284 | 284 / 284 |
| Hard-rule violations | 0 | 0 |
| Lessons in a period the school normally uses for that subject | **82%** | 60% |

(8 classes, 15 teachers, 7 labs/fields, 92 allocations; 10 s search time; history = 3 past terms.)

## Database Schema

- `users`: accounts (hashed passwords, role)
- `settings`: school name, teaching days, baseline hours
- `periods`: lesson periods per day with break flags
- `teachers`, `teacher_subjects`, `teacher_slots`: staff, qualifications, availability
- `subjects`, `classes`, `rooms`: curriculum, groups, special rooms
- `fixed_slots`: pinned lessons (class 0 = every class that has the subject)
- `allocations`: class + subject + teacher + periods per week + doubles
- `rules`: hard rules (listed) and soft-rule weights
- `timetables`, `timetable_lessons`: generated, imported and accepted timetables

## Installation

1. **Database Setup**
   - Import `database/timetable.sql` for an empty system (creates the `timetable` database), **or**
   - Import `database/timetable with demo data.sql` for the demo school with three past terms, **or**
   - Import `database/timetable with Kansanga data.sql` for Kansanga Senior Secondary School (O-Level) built from its posted timetable (see `docs/KANSANGA.md`)
   - Upgrading a 1.0 database: run `database/upgrade_1_1.sql`

2. **Configuration**
   - Edit `php_action/db_connect.php` if needed, or set `TT_DB_HOST`, `TT_DB_USER`, `TT_DB_PASS`, `TT_DB_NAME`
   - Defaults: host `localhost`, user `root`, password empty, database `timetable`

3. **Run**
   - Put the folder in your web root, or for a quick try: `php -S localhost:8080` in the project folder

4. **Login**
   - Username: `admin`  Password: `admin` (change it under Setting)

## Project Structure

```
school-timetable-solver/
├── index.php                   # Login page
├── dashboard.php               # Main dashboard
├── teachers.php                # Teacher management
├── subjects.php                # Subject management
├── classes.php                 # Class management
├── rooms.php                   # Rooms & labs
├── allocations.php             # Who teaches what to whom
├── availability.php            # Teacher availability grid
├── constraints.php             # Rules & weights
├── generate.php                # Run the solver
├── timetable.php               # View / adjust / accept timetables
├── history.php                 # Import past timetables, choose what to learn from
├── ai_learning.php             # What the solver has learned (heat-maps)
├── report.php                  # Quality report
├── setting.php, user.php       # Settings and users
├── assests/                    # Static assets (Tailwind, jQuery, DataTables, Font Awesome)
├── custom/                     # Custom CSS and JavaScript (crud.js is shared by the manage pages)
├── database/                   # SQL files and make_demo.php (rebuilds the demo data)
├── includes/
│   ├── header.php, footer.php  # Layout
│   ├── functions.php           # Helpers, problem loader, pre-flight checks
│   ├── solver.php              # Constraint solver
│   ├── learner.php             # Learns the school's habits from past timetables
│   ├── validator.php           # Independent clash checker
│   └── ui.php                  # Modal / form helpers
└── php_action/                 # Backend endpoints (one file per entity: ?action=fetch|get|create|edit|remove)
```

## Usage

1. **Setup**: school days and periods (Setting), subjects, classes, rooms and teachers, then allocations
2. **Availability**: mark part-time days, unavailable and preferred-to-avoid slots
3. **History**: import 2-3 past accepted timetables (CSV) so the solver can learn the school's habits
4. **Generate**: choose a search time (10 s is plenty for ~300 lessons) and run it
5. **Review**: open the timetable by class/teacher/room, adjust by clicking a lesson, check the report
6. **Accept**: accepting a timetable also teaches the solver for next term

### CSV format for past timetables

`class, subject, teacher, day, period, room` - `teacher` and `room` are optional (a blank teacher is taken from the class's allocation) - day as `Mon` or `1`; names match class name, subject name or code, teacher name or code. *Timetables > CSV* exports the same format.

## Security notes

Prepared statements everywhere, hashed passwords, role checks on every endpoint. It is still a demo-grade deployment: serve it over HTTPS, change the default password, and add CSRF tokens before exposing it to the internet.

## License

MIT License.
