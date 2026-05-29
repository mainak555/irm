<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Returns the ID of the currently authenticated user, or null for system/unauthenticated operations.
 * Used to populate created_by / updated_by on every write.
 */
function audit_by(): ?int
{
    $u = current_user();
    return isset($u['id']) ? (int) $u['id'] : null;
}
