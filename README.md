# AI Social Media Workflow

A small, reproducible PHP prototype for preparing and approving social-media content for a fictional optician. It uses synthetic data only and never publishes to real social accounts.

## Requirements

- PHP 8.2 or newer
- Composer

## Quick start

```bash
composer install
composer workflow
composer test
```

Run the workflow twice to demonstrate restart-safe idempotency:

```bash
composer workflow
composer workflow
```

The second run must print `Existing identical handover found; duplicate handover skipped.` It must not create a second `handover-IDEA-001.json` file.

To reset the local demonstration intentionally, remove only its generated handover and rerun:

```bash
rm -f output/handover-IDEA-001.json output/handover-IDEA-001.json.lock
composer workflow
```

The CLI also supports `WORKFLOW_OUTPUT_DIR=/path/to/temp composer workflow` so runs can be isolated from the committed sample artefacts.

## Generated artefacts

- `output/weekly-plan.json`: five ideas (three priorities and two reserves), each with a status, three synthetic inspiration references and workload forecast.
- `output/brief-IDEA-001.json`: full briefing with Instagram and TikTok variants.
- `output/approval-IDEA-001.json`: approval tied to the exact content fingerprint.
- `output/handover-IDEA-001.json`: simulated handover payload with idempotency key.
- `output/weekly-overview.txt`: text summary that could later be sent by email; no email is sent.

## Project structure

- `config/input.json`: fictional input data and declared team capacities.
- `src/ContentGeneratorInterface.php`: replaceable content-generation boundary.
- `src/MockContentGenerator.php`: deterministic mock output, not a live AI call.
- `src/Workflow.php`: planning, workload, approval fingerprint and handover rules.
- `src/HandoverStore.php`: durable local one-time handover store.
- `bin/run.php`: executable end-to-end demonstration.
- `tests/WorkflowTest.php`: automated tests for required success and failure cases.
- `docs/design-decisions.md`: architecture, assumptions and extension concepts.
- `docs/acceptance-test-evidence.md`: acceptance matrix and manual verification steps.

## Requirement coverage

| Assignment requirement | Evidence |
|---|---|
| Five weekly ideas: three priorities, two reserves, each with status | `output/weekly-plan.json`; `testPlanContainsThreePrioritiesAndTwoReserves` |
| Structured inputs, audiences, tone, season, inspiration | `config/input.json` |
| Three synthetic inspiration findings; no unsupported trend claims | `config/input.json`; plan selection note |
| Per-person capacity of 90 / 150 minutes and visible overrun | `output/weekly-plan.json`; `testCapacityOverrunIsReportedWithoutIncreasingAvailability` |
| One complete brief with production plan, overlays, cover, CTA and two channel variants | `output/brief-IDEA-001.json` |
| Human approval tied to exact version | `output/approval-IDEA-001.json`; fingerprint tests |
| Missing approval/media blocks handover | `testMissingApprovalBlocksHandover`; `testMissingMediaReferenceBlocksHandover` |
| Text change requires renewed approval | `testTextChangeAfterApprovalInvalidatesApproval` |
| Exactly-once handover, including a real CLI process restart | `HandoverStore`; `testCliWorkflowDoesNotDuplicateHandoverAfterProcessRestart` |
| Weekly text overview | `output/weekly-overview.txt` |
| Expansion concepts | `docs/design-decisions.md` |

## Executed, simulated and not implemented

Executed in the prototype: planning, workload calculation, deterministic brief generation, approval fingerprint checks and local JSON handover persistence.

Simulated: AI-generated content (fixed deterministic response), reviewer identity, media asset reference and publishing handover. The handover payload is not a publication.

Designed only: success dashboard, economics, human-reviewed comment drafts, scheduled central operation and extended capacity planning. These are documented but not implemented.

Not included by design: real social-platform APIs, real LLM credentials, media production, email delivery, production hosting or scraping.

## Tests and test evidence

Run:

```bash
composer test
```

The PHPUnit suite covers all four required acceptance scenarios, a real CLI restart test, and protection against overwriting a previously handed-over version. For a visible end-to-end demonstration, run `composer workflow` twice and retain the terminal output as a screenshot or log with the submission.

The tests are automated evidence; no test result is claimed here until the commands have actually been run in the submission environment.

## Assumptions and tools

All data is synthetic; URLs use `example.com`; inspiration findings are prompts rather than confirmed trends; unknown metrics remain null. Tools: PHP, Composer and PHPUnit. A deterministic mock implements `ContentGeneratorInterface`, so a real AI provider could be added later without changing the workflow rules.

## Time box and open points

The assignment limit is four hours including documentation. **Actual time spent: [fill in your measured time before submission; do not estimate].**

Open points: run the test suite and capture its output; record actual elapsed time; replace the file-based store with a transactional database and unique constraint for multi-host production; add real integrations only if the scope changes.
