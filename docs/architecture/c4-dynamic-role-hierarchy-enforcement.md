# Role Hierarchy Enforcement — Dynamic Flow

Shows how a mutation request (e.g. toggle active, delete, reset password) is
validated against role hierarchy after the `fix-role-edit-protection` change.

```mermaid
C4Dynamic
  title Dynamic Diagram — Role Hierarchy Enforcement (mutation request)

  Person(actor, "Admin Browser", "Logged-in admin-role user")

  Container_Boundary(admin, "Admin Panel (PHP)") {
    Component(usersPhp, "users.php", "PHP", "Renders table; computes $locked via role_rank()")
    Component(ajaxPhp, "users_ajax.php", "PHP", "Handles AJAX mutations; runs guard_target()")
    Component(authPhp, "includes/auth.php", "PHP", "role_rank(), current_user()")
    Component(dbUsers, "includes/db_users.php", "PHP", "user_get(), user_create(), mutations")
  }

  ContainerDb(db, "auth_users", "MySQL", "User rows with role column")

  Rel(actor, usersPhp, "1. GET /admin/users.php")
  Rel(usersPhp, authPhp, "2. role_rank(target) vs role_rank(actor)")
  Rel(usersPhp, actor, "3. Renders locked rows for higher-role targets")
  Rel(actor, ajaxPhp, "4. POST action=toggle_active|delete|reset_password|…")
  Rel(ajaxPhp, authPhp, "5. guard_target() → role_rank check → 403 if target outranks actor")
  Rel(ajaxPhp, dbUsers, "6. Mutation only if guard passes")
  Rel(dbUsers, db, "7. UPDATE / DELETE")
  Rel(ajaxPhp, actor, "8. JSON {ok} or {ok:false, msg}")

  UpdateRelStyle(actor, usersPhp, $offsetY="-10")
  UpdateRelStyle(ajaxPhp, authPhp, $textColor="red", $offsetX="-10")
```

## Notes

- Steps 2–3 are UI-only (render time); step 5 is the authoritative server guard.
- `role_rank()` lives in `includes/auth.php` — see ADR-0013.
- The `add_user` path has its own hierarchy check in `users_ajax.php` (not shown
  above, as it has no `guard_target` call — it validates the *new* role against
  the actor's rank before inserting).
