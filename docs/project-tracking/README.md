# SendStack Project Tracking

This directory records point-in-time implementation audits and the solution
documents created from their findings. It is intended to answer two questions:

1. What was verified as working at a given point in time?
2. What remains broken, incomplete, or unverified, and how will it be fixed?

## Directory structure

- `audits/` contains evidence-based reviews of the repository and local runtime.
- `solutions/` contains proposed fix plans and completed implementation records.

Use the `YYYY-MM-DD-topic.md` naming convention so documents remain ordered and
their conclusions can be tied to a specific repository state.

## Audits

- [2026-07-17: Features 1-6 implementation audit](audits/2026-07-17-features-1-6-implementation-audit.md)

## Solutions

See the [solutions index](solutions/README.md).

- [2026-07-17: Canonical cron hooks and deactivation cleanup](solutions/2026-07-17-cron-hook-constants-solution.md)

## Status definitions

- **Verified:** Implemented, exercised successfully, and covered by meaningful checks.
- **Partial:** Important implementation exists, but integration, lifecycle, or test gaps remain.
- **Broken:** The primary user path fails or produces an incorrect result.
- **Unverified:** Code exists, but there is insufficient runtime or automated evidence.
