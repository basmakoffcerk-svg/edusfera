# Edusfera Business Plan 2026

## Executive Summary

Edusfera.by is positioned as Belarus's first national educational marketplace platform, connecting independent tutors with students and parents in a unified digital environment. The platform integrates tutor discovery, scheduling, secure payment processing, communication, diagnostics, post-session reports, homework assignments, and educational progress tracking.

The key strategic shift introduced in version 7.0 is the implementation of a Buyer-Pays model (similar to Airbnb's approach), where tutors receive 100% of their stated hourly rate, and the platform monetizes through a Service Fee paid by the buyer (student/parent) on top of the tutor's rate. This model addresses the core challenge of gig economy platforms in Belarus: legal compliance with tax regulations while maintaining tutor satisfaction.

### Key Value Propositions

**For Tutors:**
- 0% commission on income earned
- Access to student leads and a digital business infrastructure
- Built-in payment processing, scheduling, and communication tools
- Protection from payment disputes and cancellations
- Opportunity to import existing students via personal invite links

**For Parents/Students:**
- Secure payment processing with transparent pricing
- Educational outcome tracking and progress monitoring
- Access to verified tutors with proven results
- Transparent reporting and homework assignment systems
- Protection through standardized refund policies

## Strategic Vision and Mission

### Vision
To become the leading marketplace for individual learning in Belarus, where tutor discovery, transactions, scheduling, communication, and educational outcomes are integrated into a single digital infrastructure.

### Mission
To digitize and standardize the private tutoring market in Belarus so that legal platform-based work is simpler for tutors and safer/more transparent for families than informal arrangements.

### Strategic Idea
Edusfera competes not with other tutoring platforms but with the shadow tutoring market. The platform should be positioned as a combination of three interconnected layers:

1. **Marketplace Layer**: Curated tutor listings with verification, ranking, and search functionality
2. **Transactional Layer**: Booking, payment processing, communication, and anti-circumvention mechanisms
3. **Outcome Layer**: Diagnostic assessments, goal setting, progress tracking, and educational reporting

## Market Problem and Opportunity

The traditional tutoring market in Belarus is fragmented: tutor discovery often occurs through ads and chats, payments are unstandardized, and progress tracking depends on subjective evaluations. Families pay not for a "managed process" but for disconnected sessions.

Edusfera's market opportunity lies in industrializing private tutoring: establishing unified transaction rules, transparent financial flows, and demonstrable educational outcomes. This approach increases trust, reduces dispute risk, and increases repeat purchase rates.

### Target Segments and Pain Points

| Segment | Current Pain | Edusfera Solution | Monetization Meaning |
|---------|--------------|-------------------|---------------------|
| Parent | No transparency of results or payment protection | Diagnostics, reporting, refund policy, secure checkout | Willingness to pay Service Fee for risk reduction |
| Student | Weak discipline between sessions | Homework assignments and progress tracking | Retention and repeat purchases |
| Tutor | Commission losses and operational chaos | 100% of rates, scheduling, accounting, communication | Rapid supply expansion without discounting |

## Current Product State

Edusfera operates as an advanced operating MVP with a solid marketplace and educational foundation. Technology stack: Laravel 12, PHP 8.2, Filament 3.2, Vite, Tailwind CSS 4.

Key entities and scenarios exist in the data model and services for:
- User roles and tutor profiles
- Catalog, booking, and lessons
- Payments, balances, and transaction accounting
- Chat, moderation, and anti-circumvention
- Diagnostics, goals, gaps, homework, and progress

The presence of feature tests on public layers, catalogs, payments, chat, and educational modules reduces technological risk of scaling.

## Target Audiences

### 1. Students
Value: Quick tutor selection, clear preparation routes, homework assignments, and progress visibility.

### 2. Parents
Value: Legally clear payment flows, result control, communication transparency, and financial safety.

### 3. Tutors
Value: 0% commission on income, student flow, unified digital dashboard, legal work under NPD without commission losses.

### 4. Administrative and Operational Team
Value: Centralized control of quality, risk, refunds, and financial discipline.

## Value Proposition by Segment

**Student:**
- Problem solved: Difficult to select a tutor and maintain study discipline
- Edusfera value: Selection, booking, secure payment, preparation plan, progress, and homework

**Parent:**
- Problem solved: Unclear what money is being paid for and whether there is real academic effect
- Edusfera value: Service Fee for managed results, payment protection, refund rules, and transparent reporting

**Tutor:**
- Problem solved: Unstable leads, manual administration, informal payments
- Edusfera value: 100% of rate "to hand", 0% platform commission on income, built-in operational infrastructure

**Operational team:**
- Problem solved: Quality control and risk management at scale
- Edusfera value: Admin panel, risk metrics, sanctions, and managed SLAs

## Detailed Platform Functionality

### 1. Public Layer
Public pages establish trust, provide legal transparency, and separate offers for families and tutors.

### 2. Registration and Roles
Roles of tutor, student, parent, and administrator provide correct scenario routing and analytical purity of funnels.

### 3. Tutor Profile and Verification
Profile built as commercial competency card: subjects, experience, formats, cost, methodology, confirming data, preparation indicators.

### 4. Catalog and Search
Catalog ensures market liquidity: filters, sorting, availability by slots, quality signals, and managed ranking.

### 5. Booking and Slots
BookingService creates legally and operationally correct transition from intent to deal: role checks, verification, availability, conflicts, and time locks.

### 6. Checkout and Payment Flow (Buyer-Pays Model)
Checkout works on added value principle:
- Tutor sets rate "to hand" (e.g., 40 BYN)
- PaymentService automatically calculates platform Service Fee 15% (6 BYN)
- Client pays 46 BYN at checkout
- Tutor receives 40 BYN (net amount), from which 10% NPD is correctly accounted without loss of income to platform commission

**Calculation formula:**
- Tutor Rate = R
- Service Fee = R x 15%
- Checkout Total = R + Service Fee
- Platform Gross Contribution = Service Fee - acquiring - payment losses

Also implemented Split-Fee mode for tutor's own students (invite link):
- Service Fee may be zeroed when registering via invite
- Or reduced to acquiring and technical expense level

This mode accelerates migration of existing student base into Edusfera ecosystem and increases retention of turnover within platform.

### 7. Lesson Packages
Package purchases increase revenue predictability, improve cash flow, and increase probability of completing educational trajectory.

### 8. Internal Wallet and Ledger
Wallet and operation journal reduce friction of repeat payments, simplify refunds, and provide transparency for financial control.

### 9. Built-in Chat and Anti-Circumvention
Chat manages risk of payment leak "outside the register" through contact control and sanctions.

### 10. Student Dashboard and Parent Interface
Dashboard shows not just lesson fact but movement toward goal: diagnostics, progress, reports, and homework.

### 11. Diagnostics and Goal Setting
Diagnostics creates baseline, identifies gaps, and launches personalized preparation trajectory.

### 12. Progress Snapshots and Skill Gaps
System records dynamics, turning learning from session set into measurable process.

### 13. Post-Session Tutor Report
Standardized report after lesson increases family trust, helps retention, and strengthens demonstration of results.

### 14. Homework Assignments
Homework increases contact between sessions and increases conversion to repeat payments and package renewals.

### 15. Tutor Financial Flow
Pending-to-available model protects platform from disputed cases and ensures predictable payouts.

### 16. Ratings and Quality Control
Post-lesson scores and internal quality signals create manageable reputation system.

### 17. Notifications and Operational SLA
System notifications ensure timely actions by users and support team.

### 18. Multi-Account and Parent Mode
Family management scenario (parent + child) increases convenience and family audience LTV.

### 19. Admin Interface (Control Room)
Operational panel provides control over moderation, risks, disputed cases, and transaction discipline.

## Product Logic and User Funnels

### 1. Demand Funnel (Student/Parent)
1. User finds teacher in catalog
2. Compares competencies, format, and tutor rate
3. Books slot
4. Sees total in checkout: rate + Service Fee
5. Pays through secure channel
6. Gets access to communication and educational modules
7. Goes through diagnostics
8. Receives reports and homework assignments
9. Extends learning through repeat purchases and packages

### 2. Supply Funnel (Tutor)
1. Registration and profile completion
2. Verification
3. Publication in catalog
4. Receiving requests and bookings
5. Conducting lessons in secure environment
6. Receiving 100% of own rate
7. Maintaining student reporting
8. Rating growth, repeats, and predictable income

## Monetization and Unit Economics

Edusfera monetization completely transitions to Buyer-Pays: platform does not withhold commission from tutor income. Platform revenue is formed through Service Fee paid by buyer.

### 1. Basic Revenue
- Service Fee 15% of tutor rate in each transaction
- Revenue from repeat payments and package model
- Circulation leakage control through anti-circumvention and internal wallet

### 2. Additional Revenue Sources
- Premium tutor listing promotion
- Subscription services for tutors (enhanced analytics, AI tools)
- Paid diagnostic products
- Outcome packages for families

### 3. Future Revenue Sources
- Paid parent module for extended reporting
- AI support services between sessions
- B2B scenarios (schools, centers, teacher networks)

### 4. Illustrative Unit Economics (Updated)
Scenario: 1 lesson, tutor rate 40 BYN
- Client amount (Checkout): 46.00 BYN
- Platform Service Fee: 6.00 BYN
- Acquiring (approximately 2.2% + fixed from 46 BYN): 1.31 BYN
- Net tutor: 40.00 BYN
- Platform gross contribution before OPEX: 4.69 BYN

Scenario: Package of 4 lessons at rate 40 BYN
- Tutor rate for package: 160.00 BYN
- Service Fee 15%: 24.00 BYN
- Checkout total: 184.00 BYN
- Acquiring (estimated): 4.35 BYN
- Net tutor: 160.00 BYN
- Platform gross contribution before OPEX: 19.65 BYN

Key conclusion: Transition to Buyer-Pays increases platform attractiveness for supply-side (tutors) without destroying platform marginality.

## Legal and Operational Model

Edusfera's legal architecture builds on two independent offers.

1) **Agent agreement with tutor:**
- Platform acts as agent for receiving payments
- 100% of tutor rate transits in their favor without platform commission deduction from income
- This construction is essential for correct accounting of tutor tax base under NPD application

2) **User agreement with parent/student:**
- Agreement for provision of IT services
- Platform charges Service Fee for secure transaction infrastructure functionality: secure payment processing, refund rules, diagnostics, communication, and progress control

Critical legal requirements for launch:
- Formalized refund and cancellation rules
- Transparent breakdown of checkout amount (rate + Service Fee)
- User consent to personal data processing
- KYC/KYB, AML, and cash discipline procedures according to applicable legislation of Republic of Belarus

## Technology Architecture as Asset

Current architecture allows quick implementation of Buyer-Pays at business logic and interface levels, since payment layer already has dedicated service infrastructure.

Technology value:
- Domain service modularity
- Transparent financial accounting
- Critical scenario testability
- High speed of product change implementation

## Competitive Advantages
- 0% commission on tutor income (strong supply magnet)
- Legally separated "transit to tutor + Service Fee from buyer" model
- Combination of marketplace and outcome platform
- Localization for Belarus and exam prep segment
- Operational control room reducing chaos risk during scaling

## Limitations and Areas for Improvement
- Finalization of production payment gateway and payout procedures
- Completion of offer documents to final legal edition
- Strengthening parent-centric functionality for multi-child family scenarios
- Product discipline on diagnostics and post-session reporting requirements

## Risks and Mitigation Measures
Risk: Customer price sensitivity to Service Fee
Measures: Strong value packaging of Fee (protection, refunds, reporting, result control), A/B fee-level models, invite modes

Risk: Attempts to circumvent platform
Measures: Anti-circumvention mechanics in chat, sanctions, priority for compliant profiles, stimulation of repeat payments in ecosystem

Risk: Operational overload with transaction growth
Measures: SLA, automation of risk flags, scenario-based case handling procedures

Risk: Regulatory errors in payment and contract model
Measures: Legal audit of offers, tax validation, contractual service decomposition, consent and action logs

## Go-to-Market Strategy

Starting vertical: Exam preparation (mathematics, Russian, Belarusian) as segment with high payment motivation and clear result metric.

GTM priorities:
1. Form dense core of strong tutors in target subjects
2. Promote offer for tutor: 0% commission on income and legal work through platform
3. Promote offer for family: Managed result and payment protection
4. Use diagnostics as conversion trigger and retention
5. Increase package purchase share

Key offer for tutor:
0% commission on your income. Edusfera is an educational marketplace where you receive 100% of your rate. Platform monetizes through Service Fee from buyer, and you work legally under NPD without financial losses from deductible commission.

## Marketing Messages and Positioning

Offer for parent/student:
You pay Edusfera Service Fee for managed result: entrance diagnostics, payment protection from fraud, refund rules, transparent reports after each lesson, and homework assignment system.

Offer for tutor:
Import your current students through invite link, manage schedule, payments, and reporting from smartphone, receiving 100% of lesson rate.

Offer for partners:
Edusfera is infrastructure for managed educational marketplace with scalable unit economics and legally transparent monetization.

## KPI and Management Metrics

### 1. Supply KPI
- Number of new tutors in funnel
- Share of verified profiles
- Time to first paid lesson
- Share of tutors with repeat students
- Share of tutor's own student imports

### 2. Demand KPI
- Catalog to booking conversion
- Booking to payment conversion
- Package purchase share
- 30/60/90 day retention
- Wallet payment share

### 3. Monetization KPI
- Average Service Fee per transaction
- Platform gross per lesson
- Package contribution to gross margin
- Acquiring to Service Fee ratio

### 4. Trust and Outcomes KPI
- Share of active students with diagnostics
- Share of lessons with post-lesson report
- Share of homework completed on time
- Forecast result dynamics on active tracks

## Financial Model for 12 Months (Scenarios)

Conservative scenario:
- 150 active paying students by year-end
- 2.5 lessons per month
- Average tutor rate 40 BYN
- GMV by tutor rate ~15,000 BYN/month
- Service Fee platform revenue ~2,250 BYN/month before acquiring and OPEX

Basic scenario:
- 400 active paying students
- 3.5 lessons per month
- Average rate 45 BYN
- GMV ~63,000 BYN/month
- Service Fee revenue ~9,450 BYN/month before acquiring and OPEX

Ambitious scenario:
- 900 active paying students
- 4.5 lessons per month
- Average rate 50 BYN
- GMV ~202,500 BYN/month
- Service Fee revenue ~30,375 BYN/month before acquiring and OPEX

Key profit hypothesis: Growth in retention and package behavior contributes more than just primary traffic growth.

## Implementation Plan for 12 Months

Phase 1 (0-3 months):
- Launch Buyer-Pays in interfaces and payment service
- Legal finalization of two offers
- Update marketing messages

Phase 2 (3-6 months):
- Scale supply in target subjects
- Grow package sales
- Launch invite mechanism Split-Fee

Phase 3 (6-9 months):
- Automate operational control
- Enhance parent dashboard and reporting
- Experimental AI support features

Phase 4 (9-12 months):
- B2B pilots
- Expand subject verticals
- Institutionalize brand as leader in managed exam-prep platform

## Investment and Strategic Appeal

Transition to Buyer-Pays model (Airbnb model) solves key gig economy problem in Belarus: legal income formalization for tutors (NPD) without loss of marginality.

This creates structural competitive advantage:
- Edusfera aggressively attracts supply with 0% commission on income offer
- Tutors receive economically and legally clear work model
- Platform preserves profitability through Service Fee and repeat payment scaling

For investor this means more sustainable and scalable unit economics where main growth driver is combination of transaction control and retention mechanics.

## Conclusion

Edusfera version 7.0 is not a listing catalog but an operationally and legally structured marketplace business with transparent Buyer-Pays monetization model.

Strategic project bet:
- Tutor receives 100% of own rate
- Family pays Service Fee for secure and managed educational deal
- Platform grows through retention, package model, and digital result control

With disciplined implementation of payment, legal, and marketing circuits, Edusfera has potential to occupy leadership position in Belarusian educational marketplace sector.

## Appendix: Key Module Map
- Tutor catalog and cards: Demand conversion to booking
- BookingService: Slot management and pre-deal discipline
- PaymentService: Rate calculation, Service Fee, acquiring, transaction statuses
- Wallet and ledger: Repeat payments and transparent operations
- ChatService: Deal retention and anti-circumvention
- DiagnosticService: Baseline and educational trajectory launch
- StudentGoal/ExamTrack: Preparation personalization
- PostLessonReportService: Lesson result standardization
- HomeworkService: Retention between sessions
- Admin/Site-admin: Operational control and risk management