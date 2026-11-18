# AI Social Media Workflow

A small PHP prototype for preparing and approving social-media content for a fictional optician. The prototype uses synthetic data only. It does not publish to real social accounts.

## Requirements

- PHP 8.2 or newer
- Composer

## Quick start

```bash
composer install
composer workflow
composer test
```

The workflow writes its artefacts to `output/`:
- `weekly-plan.json`: five ideas (three priorities and two reserves), inspiration references and workload forecast
- `brief-IDEA-001.json`: complete sample briefing with Instagram and TikTok variants
- `approval-IDEA-001.json`: approval tied to the exact content fingerprint
- `handover-IDEA-001.json`: simulated publishing payload
- `weekly-overview.txt`: email-ready weekly summary

Running the workflow again reuses an existing handover file instead of creating a second handover. Delete that generated file only when intentionally resetting the local demo.

## Project structure

- `config/input.json`: all fictional input data and team capacities
- `src/ContentGeneratorInterface.php`: replaceable content-generation boundary
- `src/MockContentGenerator.php`: deterministic AI simulation
- `src/Workflow.php`: planning, capacity, approval fingerprint and handover rules
- `bin/run.php`: executable demonstration
- `tests/WorkflowTest.php`: PHPUnit tests for the required safety and capacity cases
- `docs/design-decisions.md`: architecture, assumptions and limitations

## What is implemented

- Exactly three priority ideas and two reserve ideas are required.
- Required idea fields are validated.
- Planned minutes are compared with each person's available weekly time; overruns are visible.
- One priority idea receives a full production briefing.
- Approval is bound to the exact idea, copy, media reference, channels and date using SHA-256.
- Any change after approval invalidates the old approval.
- Missing approval or media reference blocks handover.
- A persisted local handover JSON file prevents the CLI demo from handing over the same item again on rerun.

## Simulated / not implemented

The generator is deterministic mock output, not a live AI call. The handover is a local JSON file, not a publication. Media files are fictional references. There is no real social platform integration, UI, database, analytics dashboard, comment moderation or scheduled execution.

The handover ledger is file-based for this time-boxed prototype. A production system should store handovers transactionally in a database and enforce a unique constraint on the idempotency key to protect against concurrent workers.

## Assumptions

All names, business details, source IDs, URLs and media references are synthetic. Inspiration entries are idea prompts only; no trend or performance claim is made. Missing metrics remain unknown. All five ideas, including reserves, count toward the team's planned workload.

## Time box

The assignment requests a maximum of four hours including documentation. Record the actual time spent here before submitting: **Actual time: [fill in]**. Remaining work is listed in the design decisions document.
