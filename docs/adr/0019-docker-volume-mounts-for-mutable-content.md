# ADR-0019: Docker volume mounts for mutable content

**Date**: 2026-05-30
**Status**: accepted
**Deciders**: carousel-config change

## Context

IRM may be deployed via Docker. The application includes two categories of content that change at runtime: instance-specific configuration (`/config/*.json`) and user-uploaded media (`/assets/img/carousel/`, and future `/assets/img/gallery/`). Without explicit handling, a container rebuild would discard all uploaded images and any config edits made through the admin UI.

## Decision

Docker deployments MUST volume-mount `/config` and `/assets/img/carousel/` (and `/assets/img/gallery/` when that feature ships). These directories are NOT baked into the image. The docker-compose example and deployment documentation SHALL list these mounts explicitly.

## Alternatives Considered

### Alternative 1: Bake config and uploads into the image
- **Pros**: Single artifact; no external volume management.
- **Cons**: Every config change or image upload requires a container rebuild and redeploy; uploaded files are lost on rebuild.
- **Why not**: Defeats the purpose of the admin UI — school staff cannot manage carousel images if every upload triggers a deploy.

### Alternative 2: Store uploads in a cloud object store (S3/GCS)
- **Pros**: Scales horizontally; no local disk dependency.
- **Cons**: Adds an external dependency; requires credentials management; over-engineered for a self-hosted school CMS.
- **Why not**: The project is explicitly self-hosted with zero external service dependencies. Local disk is appropriate.

### Alternative 3: Store captions in DB, uploads on disk
- **Pros**: Only disk needs mounting; DB handles captions transactionally.
- **Cons**: Splits carousel data across two persistence layers; DB table needed for captions; inconsistent with the JSON config model.
- **Why not**: Chosen JSON approach (ADR-0018) keeps all carousel metadata in `/config`; mounting `/config` covers both images metadata and branding config in one mount.

## Consequences

### Positive
- Container rebuilds are safe — uploads and config survive.
- Backup is straightforward: snapshot the two mounted directories.
- Consistent with twelve-factor app principles (config and state outside the image).

### Negative
- Adds deployment complexity: operators must configure volume mounts.
- Mount paths are a deployment contract — changing them later requires coordinating with all deployers.

### Risks
- Operator forgets to mount `/assets/img/carousel/` → uploaded images are lost on first rebuild. Mitigation: document prominently; consider a startup check that logs a warning if the folder is empty and the app has been running before.
