import { describe, expect, it } from "vitest";
import {
  canAccessAllBranches,
  canAccessPath,
  getFirstAccessiblePath,
  getUserPermissionNames,
  getUserRoleNames,
  hasAllPermissions,
  hasAnyPermission,
  hasPermission,
  isSuperAdmin,
} from "@/lib/permissions";

const user = {
  roles: ["reception"],
  permissions: [
    { id: 38, name: "coach.view-any", module: "coach" },
    { id: 68, name: "activity.view-any", module: "activity" },
    { id: 16, name: "member.view-any", module: "member" },
  ],
};

describe("permission helpers", () => {
  it("normalizes role and permission objects from auth/me", () => {
    expect(getUserRoleNames({ roles: ["reception", { name: "manager" }] })).toEqual([
      "reception",
      "manager",
    ]);
    expect(getUserPermissionNames(user)).toEqual([
      "coach.view-any",
      "activity.view-any",
      "member.view-any",
    ]);
  });

  it("evaluates individual, any, and all permission requirements", () => {
    expect(hasPermission(user, "coach.view-any")).toBe(true);
    expect(hasPermission(user, "branch.view-any")).toBe(false);
    expect(hasAnyPermission(user, ["branch.view-any", "activity.view-any"])).toBe(true);
    expect(hasAllPermissions(user, ["coach.view-any", "branch.view-any"])).toBe(false);
  });

  it("allows the protected coach page only when every data dependency is available", () => {
    expect(canAccessPath(user, "/management/coaches")).toBe(false);

    const allowedUser = {
      ...user,
      permissions: [...user.permissions, "branch.view-any", "subscription-plan.view-any"],
    };

    expect(canAccessPath(allowedUser, "/management/coaches")).toBe(true);
    expect(canAccessPath(allowedUser, "/management/coaches/create")).toBe(false);

    const creator = {
      ...allowedUser,
      permissions: [
        ...allowedUser.permissions,
        "coach.create",
        "branch.shift.view-any",
        "branch.settings.view",
      ],
    };

    expect(canAccessPath(creator, "/management/coaches/create")).toBe(true);
  });

  it("gives super admin a full bypass based on the role returned by auth/me", () => {
    const superAdmin = { roles: ["super_admin"], permissions: [] };

    expect(isSuperAdmin(superAdmin)).toBe(true);
    expect(hasPermission(superAdmin, "permission.that.does.not.exist")).toBe(true);
    expect(canAccessPath(superAdmin, "/accounting/reports")).toBe(true);
    expect(getFirstAccessiblePath(superAdmin)).toBe("/management");
  });

  it("selects the first page the current user can actually open", () => {
    const membersOnly = {
      roles: ["member_manager"],
      permissions: ["member.view-any", "branch.view-any", "subscription-plan.view-any"],
    };

    expect(getFirstAccessiblePath(membersOnly)).toBe("/management/members");
  });

  it("protects management statistics with the dashboard permission only", () => {
    const scheduleUser = {
      roles: ["staff"],
      permissions: ["session-template.schedule"],
    };
    const dashboardUser = {
      roles: ["staff"],
      permissions: ["attendance.dashboard"],
    };

    expect(canAccessPath(scheduleUser, "/management/schedule")).toBe(true);
    expect(canAccessPath(scheduleUser, "/management")).toBe(false);
    expect(getFirstAccessiblePath(scheduleUser)).toBe("/management/schedule");
    expect(canAccessPath(dashboardUser, "/management")).toBe(true);
  });

  it("allows reception user with auth/me permissions to access operational pages while blocking administrative routes", () => {
    const receptionUser = {
      roles: ["reception"],
      permissions: [
        { id: 163, name: "attendance.check-in", module: "attendance" },
        { id: 164, name: "attendance.check-out", module: "attendance" },
        { id: 166, name: "attendance.history", module: "attendance" },
        { id: 169, name: "attendance.dashboard", module: "attendance" },
        { id: 175, name: "reception.qr-check-in", module: "reception" },
        { id: 173, name: "reception.deduct-session", module: "reception" },
        { id: 172, name: "reception.view-member-subscriptions", module: "reception" },
        { id: 16, name: "member.view-any", module: "member" },
        { id: 17, name: "member.view", module: "member" },
        { id: 18, name: "member.create", module: "member" },
        { id: 19, name: "member.update", module: "member" },
        { id: 98, name: "player-subscription.renew", module: "player-subscription" },
        { id: 112, name: "player-sub-item.view-any", module: "player-sub-item" },
        { id: 80, name: "session-template.view-any", module: "session-template" },
        { id: 159, name: "locker.get-by-holder", module: "locker" },
        { id: 55, name: "payroll.view-any", module: "payroll" },
      ],
    };

    // Operational routes accessible to reception
    expect(canAccessPath(receptionUser, "/management")).toBe(true);
    expect(canAccessPath(receptionUser, "/management/attendance")).toBe(true);
    expect(canAccessPath(receptionUser, "/management/members")).toBe(true);
    expect(canAccessPath(receptionUser, "/management/members/create")).toBe(true);
    expect(canAccessPath(receptionUser, "/management/payroll")).toBe(true);

    // Routes requiring permissions reception does not have
    expect(canAccessPath(receptionUser, "/management/subscriptions")).toBe(false);
    expect(canAccessPath(receptionUser, "/management/schedule")).toBe(false);
    expect(canAccessPath(receptionUser, "/management/lockers")).toBe(false);
    expect(canAccessPath(receptionUser, "/management/roles")).toBe(false);
    expect(canAccessPath(receptionUser, "/management/users")).toBe(false);
    expect(canAccessPath(receptionUser, "/management/branches")).toBe(false);
    expect(canAccessPath(receptionUser, "/management/clubs")).toBe(false);
    expect(canAccessPath(receptionUser, "/management/settings")).toBe(false);

    // Landing path must be valid and not null
    expect(getFirstAccessiblePath(receptionUser)).toBe("/management");
  });

  it("allows only super_admin and admin to access all branches selector", () => {
    expect(canAccessAllBranches({ roles: ["super_admin"] })).toBe(true);
    expect(canAccessAllBranches({ roles: ["admin"] })).toBe(true);
    expect(canAccessAllBranches({ roles: ["reception"], branch_id: 5 })).toBe(false);
    expect(canAccessAllBranches({ roles: ["coach"], branch_id: 2 })).toBe(false);
    expect(canAccessAllBranches(null)).toBe(false);
  });
});
