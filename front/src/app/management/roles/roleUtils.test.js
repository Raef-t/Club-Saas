import { describe, expect, it } from "vitest";
import {
  createWebPermissionSyncPayload,
  filterRoles,
  filterWebPermissions,
  getPermissionCollection,
  getPermissionsFromRoles,
  getRoleCollection,
  getRolePermissionNames,
  groupPermissions,
  mergePermissionCatalog,
  validateRoleName,
} from "./roleUtils";

describe("role utilities", () => {
  it("extracts roles from the backend contract", () => {
    const roles = getRoleCollection({ data: { roles: [{ id: 7, name: "super_admin" }] } });
    expect(roles).toEqual([{ id: 7, name: "super_admin" }]);
  });

  it("normalizes and deduplicates permission records", () => {
    const permissions = getPermissionCollection({
      data: {
        permissions: [
          { id: 18, name: "member.create", module: "member" },
          "member.view-any",
          { id: 18, name: "member.create", module: "member" },
        ],
      },
    });

    expect(permissions.map((permission) => permission.name)).toEqual([
      "member.create",
      "member.view-any",
    ]);
    expect(permissions[1].module).toBe("member");
  });

  it("extracts permission names from a role", () => {
    expect(
      getRolePermissionNames({ permissions: [{ name: "role.view" }, "role.view-any"] }),
    ).toEqual(["role.view", "role.view-any"]);
  });

  it("keeps permissions in the catalog when another role no longer has them", () => {
    const permissions = getPermissionsFromRoles([
      {
        name: "admin",
        permissions: [
          { name: "player-subscription.create", module: "player-subscription" },
          { name: "player-subscription.view", module: "player-subscription" },
        ],
      },
      {
        name: "reception",
        permissions: [{ name: "player-subscription.view", module: "player-subscription" }],
      },
    ]);

    expect(permissions.map((permission) => permission.name)).toContain(
      "player-subscription.create",
    );
  });

  it("groups permissions by their API module", () => {
    const groups = groupPermissions([
      { name: "member.create", module: "member" },
      { name: "role.view-any", module: "role" },
    ]);
    expect(groups).toHaveLength(2);
    expect(groups.flatMap((group) => group.permissions)).toHaveLength(2);
  });

  it("keeps web permissions and excludes mobile or unimplemented workflows", () => {
    const permissions = filterWebPermissions([
      { name: "member.create", module: "member" },
      { name: "notification.mark-read", module: "notification" },
      { name: "member.measurement.create", module: "member" },
      { name: "system.backup.download", module: "system" },
    ]);

    expect(permissions.map((permission) => permission.name)).toEqual([
      "member.create",
      "system.backup.download",
    ]);
  });

  it("preserves hidden mobile permissions when web permissions are saved", () => {
    const payload = createWebPermissionSyncPayload(
      ["member.view"],
      ["member.view", "member.create", "notification.mark-read"],
      [
        { name: "member.view", module: "member" },
        { name: "member.create", module: "member" },
      ],
    );

    expect(payload).toEqual(["member.view", "notification.mark-read"]);
  });

  it("filters roles using Arabic presentation labels", () => {
    const roles = [{ name: "accountant" }, { name: "coach" }];
    expect(filterRoles(roles, "المحاسب")).toEqual([{ name: "accountant" }]);
  });

  it("always retains standard system permissions in the catalog even when unassigned from role", () => {
    // Role only has member.view, but catalog must retain member.create so it remains visible with an unchecked box
    const catalog = mergePermissionCatalog([], {
      permissions: [{ name: "member.view", module: "member" }],
    });
    const names = catalog.map((p) => p.name);

    expect(names).toContain("member.create");
    expect(names).toContain("member.view");
    expect(names).toContain("attendance.check-in");
  });
});

