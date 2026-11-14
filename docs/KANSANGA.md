# Integrating the Kansanga Senior Secondary School (O-Level) timetable

Source: the timetable posted at the school (photo in `database/kansanga/source/`), transcribed to
`database/kansanga/kansanga_timetable_2026.csv` (S.1-S.4, Mon-Sat, 6 periods = 144 lessons).
Rebuild everything with `php database/make_kansanga.php` (or import `database/timetable with Kansanga data.sql`).

## What the poster tells us, and how the solver uses it

| On the poster | In the solver |
|---|---|
| Mon-Sat, 6 teaching slots (07:20-08:40, 08:40-10:00 | break | 10:30-11:50, 11:50-13:00 | lunch | 14:00-15:20, 15:20-16:40) | `settings.days` and `periods`; breaks after P2 and P4, so a double lesson never straddles a break |
| Lessons per class per subject (e.g. S.2 Maths = 6) | `allocations.periods_per_week`, inferred by counting |
| Back-to-back same subject (S.1 Art, S.3 Entrepreneurship) | `allocations.doubles` |
| Saturday P5-P6 "REV" for every class | **Fixed slots** + subject *Revision* flagged **no teacher** |
| Same subject at the same time in two classes (e.g. Maths S.1 and S.3 on Mon P4) | proves at least 2 teachers exist for that subject; inferred minimum pool (colouring of the clash graph): MTC 2, ENG 2, HIST 2, GEO 2, LUG 2, ENT 2, ART 2, others 1 = 20 teachers |
| The whole posted timetable | imported as a *historical* timetable, so the solver learns the school's habits (e.g. Maths early, Art/ICT late) |

Result of `make_kansanga.php` (12 s search): the posted timetable passes the independent validator with 0 hard issues; the generated
one has 0 hard issues, nothing unplaced, Saturday revision pinned, and 92% habit match versus 62% for a generic solver.

## Assumptions to confirm with the school
1. Abbreviations: MTC Mathematics, LIT Literature, LUG Luganda, REV Revision, ART Art, ICT Computer studies, ENT Entrepreneurship, KIS Kiswahili.
2. The TEACHER and TEL columns are blank, so teachers are placeholders ("MTC teacher A"). Rename them under *Teachers* and give real subjects, limits and availability.
3. No rooms/labs shown (Physics/Chemistry/ICT labs not modelled yet); no Biology on the poster; class sizes assumed 40.
4. Period times for the afternoon (14:00-15:20, 15:20-16:40) are read from a photo at an angle; check them under *Setting*.
5. Only one past timetable exists, so learning is based on a single sample. Import earlier terms (CSV) to sharpen it.
6. "Teacher idle periods" in the posted timetable are an artefact of placeholder teachers; they become meaningful once real teachers are entered.

## Questions for the school
- Which teacher takes which subject in each class, and who teaches several subjects?
- Are any teachers part-time or unavailable on certain days?
- Which subjects need a lab or computer room, and how many rooms exist?
- Are O-Level and A-Level timetables built together (shared teachers/rooms)?
