# Technical concept and extension plan

## Scope and architecture

This executable PHP prototype demonstrates weekly planning, a full content briefing, human approval, capacity checks and a simulated handover.

- `config/input.json`: synthetic business, audience, tone, seasonal context, team capacity, inspiration records and five ideas.
- `ContentGeneratorInterface`: replaceable boundary for content generation.
- `MockContentGenerator`: deterministic local substitute for an LLM. No external AI request is made.
- `Workflow`: planning, workload calculation, content fingerprint, approval and handover guards.
- `HandoverStore`: durable local idempotency store with an exclusive lock and atomic file write.
- `bin/run.php`: runnable end-to-end demonstration; `WORKFLOW_OUTPUT_DIR` can isolate its output for tests.
- `tests/WorkflowTest.php`: automated evidence for required positive and negative cases, including two real CLI process runs.
- `output/`: example plan, briefing, approval, handover payload and weekly text overview.

## Workflow states and approval

Every planned idea carries a status. The intended state path is `draft -> pending_review -> approved -> handed_over`.

Approval is bound to a SHA-256 fingerprint over the idea, brief, channels and publication date. The workflow recalculates the fingerprint before handover. A text, media reference, channel or date change therefore makes the old approval invalid.

Only an explicitly approved, matching version with a non-empty media reference can be handed over. The generated payload is always marked `SIMULATED_ONLY`; it is not a successful social-media publication.

## Duplicate prevention and restart behavior

The payload has a deterministic idempotency key based on idea ID and content fingerprint. `HandoverStore` persists one JSON file per content ID. It locks the write, returns the existing payload when the same key is submitted again, and rejects a different version rather than silently overwriting a previous handover.

The PHPUnit integration test launches `bin/run.php` twice as separate PHP processes against the same temporary output directory. It asserts that the second process reports a skipped duplicate and that exactly one handover JSON exists. A separate store-level test checks that a different version cannot overwrite an existing handover.

This is appropriate for a single-machine prototype. A production system with multiple hosts should use a database transaction and a unique constraint on the idempotency key and content ID, not a shared local filesystem.

## Capacity and selection

The plan contains exactly five ideas: three priorities and two reserves. All five count toward workload. Person A has 90 minutes and Person B 150 minutes. An overrun is visible in the plan and weekly overview; declared availability is never increased automatically. The prototype reports the overrun but does not automatically reschedule ideas.

The three priorities cover screen-time eye breaks, personal frame advice and a behind-the-scenes view. The remaining two ideas are reserves. Three synthetic inspiration records are treated as prompts, not verified trends. Metrics are unknown and remain null.

## Executed, simulated and designed functions

| Function | Status |
|---|---|
| Read structured synthetic input | Executed |
| Generate five-idea weekly plan, including status per idea | Executed |
| Calculate per-person workload and warn on overrun | Executed |
| Generate one full production brief | Executed with deterministic mock output |
| Human approval and version fingerprint | Executed as a workflow rule; reviewer is a synthetic CLI actor |
| Persist simulated handover exactly once across separate CLI runs | Executed locally through locked JSON storage |
| Social-media publishing | Not implemented; explicitly excluded |
| Real LLM generation | Not implemented; mock adapter only |
| Real media production | Not implemented; media references are synthetic |
| Email delivery | Not implemented; weekly text file is email-ready only |
| Analytics, comments and scheduled operation | Designed below; not implemented |

## Extension concepts (not implemented)

### Success measurement
Add campaign- and channel-specific UTM parameters to each booking link. Collect reach, clicks, attributed inquiries, appointments and purchases with source, timestamp and campaign identifiers. A dashboard should show missing values as missing (not zero), deduplicate events by stable event IDs, and document the attribution window and model. Attributed outcomes indicate an observed association; they do not prove that social media alone caused incremental sales.

### Economics
Calculate cost per inquiry or customer only when the denominator is greater than zero and the data is sufficiently complete. Keep revenue separate from contribution margin: revenue is sales value, while contribution margin deducts variable costs. If the denominator is zero, show “not calculable” rather than a misleading ratio.

### Comments
Generate reply drafts only. A team member must review and approve every response. Complaints, personal data, health-related questions, legal issues, sensitive topics or unclear intent should be routed to a human. No automatic replies.

### Operations
Run the workflow centrally on a schedule (for example, weekly). Store platform credentials in a secrets manager, never in source control or input JSON. Add structured logs, retry limits, failure alerts and a manual fallback when an API is unavailable. Publishing stays disabled until explicit production approval and platform integration exist.

### Capacity planning
Link production estimates to each content item and sum production, variants, coordination and approval time per person. Keep this separate from appointment availability: production capacity does not imply free customer consultation slots, and vice versa.

## Assumptions and limitations

All people, business details, topics, source IDs, URLs, metrics and media references are fictional. Inspiration items do not establish a trend. No real company, customer account, personal data or social account is used. There is no production database, UI, external API, analytics, comment moderation, email sending, scheduled execution or real publishing.

The file store protects concurrent runs on one local filesystem, but it is not a distributed lock or a substitute for a transactional database.

## Tools and AI use

- PHP 8.2+ and Composer.
- PHPUnit 11 for automated tests.
- A deterministic fixed mock response is used instead of a paid AI service or API key.
- The interface allows a future LLM adapter without changing approval and handover rules.
