# Edusfera Product Roadmap: 90 Days

## Goal

Transform Edusfera from a safe tutor marketplace into a results-driven education platform for Belarus, where tutor selection, payments, diagnostics, progress tracking, and AI-assisted preparation are connected in one product.

## Strategic Thesis

Edusfera already has a strong transactional base:

- tutor catalog
- booking flow
- protected checkout
- moderated chat
- tutor and student dashboards
- moderation and admin tools

This gives the platform a strong operational and trust foundation. The next stage is to add the missing learning layer:

- diagnostic entry point
- goal-based preparation tracks
- measurable student progress
- structured tutor workflows
- AI-generated support between lessons

The market advantage is not "more tutors", but "predictable academic outcomes".

## Product Direction

Edusfera should evolve into:

`Tutor marketplace + exam preparation OS + AI support layer`

The strongest positioning for Belarus is:

- localized for `ЦЭ` and `ЦТ`
- adapted to Belarusian school and exam realities
- safe payment and moderated communication
- measurable progress for parents and students
- tutor workflow tools that reduce manual overhead

## Current Product Base

The current codebase already covers the following areas:

- tutor discovery and catalog
- lesson booking and slot locking
- payment flow with packages
- moderated in-platform chat
- tutor onboarding and verification
- student balance and wallet logic
- admin analytics for moderation and GMV

This means the roadmap should extend the current core, not replace it.

## Main Product Gap

Today the platform mainly measures operational success:

- booked lessons
- paid lessons
- ratings
- tutor moderation
- monthly turnover

But it does not yet model educational success:

- starting level
- target score
- weak topics
- diagnostic history
- homework completion
- predicted score growth
- progress by topic

This is the core product gap to close.

## 90-Day Plan

### Phase 1: Weeks 1-3

Goal: introduce the minimum educational data model without disrupting existing booking, payment, and chat flows.

#### 1. Add core learning entities

Create the following entities:

- `student_goals`
- `exam_tracks`
- `diagnostic_attempts`
- `skill_gaps`
- `homework_assignments`
- `progress_snapshots`

Recommended responsibilities:

- `student_goals`: student target, exam type, target score, deadline
- `exam_tracks`: preparation format and active preparation track
- `diagnostic_attempts`: baseline and repeated assessment attempts
- `skill_gaps`: weak topics and severity
- `homework_assignments`: tutor- or AI-generated work between lessons
- `progress_snapshots`: weekly state of the student trajectory

#### 2. Create a goal after first successful payment

After successful checkout, create an initial preparation goal with fields such as:

- subject
- exam type: `ЦЭ` or `ЦТ`
- current level
- target score
- planned exam date
- study format

This should become the first step from "paid lesson" to "structured preparation".

#### 3. Add initial diagnostic flow

Introduce a short diagnostic before tutor choice or immediately after first payment.

The purpose:

- capture baseline level
- identify weak topics
- support better tutor matching
- create measurable progress later

#### 4. Extend tutor profile for outcomes

Add structured tutor attributes such as:

- `exam_specializations`
- `average_score_growth`
- `students_prepared_count`
- `max_recent_score`
- `diagnostic_supported`

This makes tutor profiles more outcome-driven and less reliant on free-text claims.

### Phase 2: Weeks 4-6

Goal: turn each lesson into a step inside a visible learning trajectory.

#### 1. Upgrade the student dashboard

The student cabinet should show more than lessons and balance.

Add:

- target score
- current progress
- weak topics
- assigned homework
- completed diagnostics
- next preparation step

The dashboard should answer:

"Where is the student now, and what is the next action?"

#### 2. Build tutor progress workflow

After each lesson, the tutor should be able to record:

- what was covered
- what errors were observed
- what homework was assigned
- what the next focus should be

This creates the structured data needed for progress reports and AI assistance.

#### 3. Add weekly progress snapshots

Store a regular summary of trajectory state:

- predicted score
- completed topics
- active weak topics
- consistency risk
- recommended next focus

This becomes the foundation for parent trust and student retention.

#### 4. Upgrade catalog filters from generic to goal-based

Extend tutor discovery with filters such as:

- `Подготовка к ЦЭ`
- `Подготовка к ЦТ`
- language of instruction
- intensive format
- long-term format
- diagnostic support
- proven score growth

This shifts the catalog from price-based selection to outcome-based selection.

### Phase 3: Weeks 7-9

Goal: introduce a narrow but useful AI layer that supports tutors and students between lessons.

#### 1. AI-generated post-lesson summary

From tutor notes, generate:

- short parent report
- list of weak areas
- homework assignment
- next lesson plan

This reduces tutor admin work and improves perceived platform quality.

#### 2. AI weak-topic analysis

Use diagnostic attempts and tutor notes to identify:

- repeated mistakes
- unstable topics
- topics needing revision

For Belarusian language preparation, this is especially valuable because mistakes cluster well by topic.

#### 3. AI daily mini-trainer

Launch a minimal between-lesson training mode:

- 5 to 10 questions per day
- instant feedback
- short explanation
- result saved into student progress

This creates engagement even when no live lesson is happening.

#### 4. AI-assisted tutor matching

Match students not only by subject and price, but also by:

- target exam
- weak topics
- urgency
- preferred preparation style

This becomes a strong differentiator in a narrow local market.

### Phase 4: Weeks 10-12

Goal: package the new capability into a clear market-facing advantage.

#### 1. Reposition the landing page

Move messaging from:

- safe payments
- verified tutors
- easy booking

Toward:

- diagnostics first
- personalized preparation track
- measurable score growth
- weekly visibility for parents

#### 2. Add proof-of-results blocks to tutor profiles

Show structured outcome indicators such as:

- average score increase
- number of exam-track students
- strongest topic areas
- completion rate of long tracks

This is more credible than generic achievement text.

#### 3. Upgrade admin analytics

Add educational metrics alongside operational ones:

- conversion to exam track
- diagnostic completion rate
- homework completion rate
- retention by subject
- score growth by tutor
- repeat package purchase

This allows product decisions to be based on learning outcomes, not only GMV.

#### 4. Repackage lesson bundles as trajectories

Current packages should evolve from simple discount bundles into named learning products:

- diagnostic + 4 lessons
- exam sprint
- 8-week score growth track

This will improve clarity, conversion, and pricing power.

## MVP Priority

If the roadmap must be reduced to the highest-value items, prioritize:

1. `student_goals` and `diagnostic_attempts`
2. tutor post-lesson report
3. student progress block in dashboard
4. catalog filters for exam preparation
5. AI-generated homework and short summaries

These items can change product perception fastest without requiring a full LMS build.

## Recommended Technical Order

### Data Layer

1. Create migrations for:
- `student_goals`
- `diagnostic_attempts`
- `skill_gaps`
- `homework_assignments`
- `progress_snapshots`

2. Add relationships to:
- `User`
- `Lesson`
- `TutorProfile`

3. Keep current booking, payment, and conversation flows isolated from the new learning layer.

### Admin and Back Office

1. Add Filament resources for:
- goals
- diagnostics
- homework
- progress snapshots

2. Extend site admin analytics with learning metrics.

3. Add tutor-facing forms for post-lesson reporting.

### Student Experience

1. Add goal onboarding after checkout.
2. Add diagnostic entry point.
3. Add progress dashboard.
4. Add homework block.

### AI Layer

1. Start with AI summaries and homework generation.
2. Then move to weak-topic detection.
3. Only then add adaptive daily training and AI matching.

## Success Metrics

The roadmap should be evaluated using both business and learning metrics.

### Business Metrics

- checkout conversion
- package conversion
- repeat booking rate
- monthly GMV
- tutor retention

### Learning Metrics

- diagnostic completion rate
- weekly active students on tracks
- homework completion rate
- share of students with measurable progress
- predicted score growth over time
- parent retention after first package

## Expected Strategic Outcome

If executed well, Edusfera can become more than a tutor marketplace.

It can become the leading Belarus-focused online learning platform for exam-oriented preparation by combining:

- trusted transactions
- local educational relevance
- structured preparation tracks
- measurable academic outcomes
- practical AI assistance

That combination is the strongest path to category leadership in the Belarusian online education market.
