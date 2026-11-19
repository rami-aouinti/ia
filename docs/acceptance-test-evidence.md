# Acceptance test evidence

Run `composer test` from the repository root. The PHPUnit suite contains the following acceptance scenarios.

| Required case | Input / action | Expected result | Automated test |
|---|---|---|---|
| 1. Approved content handed over once, including after restart | Launch the CLI workflow twice as separate PHP processes against the same temporary output directory | First process creates the payload; second process reports a skipped duplicate; exactly one handover JSON exists | `testCliWorkflowDoesNotDuplicateHandoverAfterProcessRestart` |
| 2a. Missing approval | Submit a brief with an empty approval record | Exception; no handover payload is produced | `testMissingApprovalBlocksHandover` |
| 2b. Missing media reference | Submit an approved brief with an empty media reference | Exception; no handover payload is produced | `testMissingMediaReferenceBlocksHandover` |
| 3. Text changed after approval | Approve a brief, then change its caption before handover | Fingerprint mismatch; handover is blocked and a new review is required | `testTextChangeAfterApprovalInvalidatesApproval` |
| 4. Capacity exceeded | Increase Person A's estimate so total workload exceeds 90 minutes | `within_capacity=false`; warning shows the exact overrun; availability remains 90 minutes | `testCapacityOverrunIsReportedWithoutIncreasingAvailability` |

## Manual restart demonstration

```bash
composer workflow
composer workflow
```

Expected first run: `Workflow completed. Outputs written to output/.`

Expected second run: `Existing identical handover found; duplicate handover skipped.`

The handover file remains a single simulated JSON payload. No real post is published. Save the two terminal outputs as a screenshot or log for the submission.
