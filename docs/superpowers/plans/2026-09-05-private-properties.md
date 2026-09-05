# Private property inheritance implementation plan

## Design

Fix #6 without merging independent private property slots. A parent can be loaded
before any descendant is known, so private declarations must remain private even
when no collision is currently visible. An explicit PropertyAccess API selects an
original declaring class
for ambiguous names and supports static properties. Public/protected properties and
method interception retain their existing behavior. Generated constructors must not
declare promoted properties a second time.

## Tasks

- [x] Add failing functional tests for different/same types, parent-first loading,
  promoted properties, property mutation, and explicit scoped access.
- [x] Preserve private property declarations in ProxiedClassModifier; remove
  promotion from generated forwarding constructors in WovenClassBuilder.
- [x] Add PropertyAccess, reporting ambiguous names instead of selecting silently.
  Independent review showed generated magic accessors could introduce child-method
  signature fatals; the safe candidate omits them and documents migration of advice.
- [x] Cover traits, static properties, errors, and existing magic behavior.
- [x] Document access semantics and migration and obtain independent review.
- [x] Run Tests and Performance on PHP 8.1–8.5: 73 functional/integration tests
  and 45 performance tests per version, no failures. Existing incomplete tests,
  PHP 8.1 readonly-class skip, and dependency deprecations remain.
- [ ] Verify origin and upstream CI after the publishing decision.
- [ ] Merge the upstream PR referencing #6 only after successful checks.

## Publishing decision

The user approved migration to `$invocation->properties($declaringClass)`.
The accessor wraps property access only; it never replaces the subject or adds
magic methods to the subject's inheritance hierarchy. Reads/writes, array mutation,
references, isset/unset, and static invocation access have functional coverage.
