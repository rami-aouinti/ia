# Technical concept

## Scope and architecture
This executable PHP prototype demonstrates weekly planning, a full content briefing, human approval, capacity checks and a simulated handover. The ContentGeneratorInterface isolates text generation. MockContentGenerator provides deterministic synthetic output and can be replaced by an LLM adapter without changing workflow rules.

## Approval and versioning
Approval stores a SHA-256 fingerprint over the idea, briefing, channels and publication date. Handover recalculates it. A text, media, channel or date change invalidates the old approval and requires review again.

## Duplicate prevention
The payload contains a deterministic idempotency key. The service also rejects an idea ID already present in supplied handover records. In production, this check and insert must be atomic in a database with a unique constraint. This short demo does not provide durable multi-process storage.

## Capacity
All five ideas, including reserves, count toward the forecast. Each person's planned minutes are compared with declared availability; overruns are reported and availability is never silently increased.

## Synthetic data and limitations
All people, business details, inspiration records, metrics and media references are fictional. Metrics are unknown; inspiration is not presented as a confirmed trend. The mock generator is not a real AI model. Handover means creating a JSON payload, not publishing to social networks.

Not implemented: real LLM integration, actual media production, platform APIs, persistent database transactions, UI, analytics, comment moderation or scheduled execution.
