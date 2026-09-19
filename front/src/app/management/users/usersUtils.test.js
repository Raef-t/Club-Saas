import { describe, expect, it } from "vitest";
import {
  createUserRoleOptions,
  buildUserRoleTabs,
  createUserStats,
  filterUsers,
  getPasswordStatus,
  getUsersCollection,
  isSuperAdminUser,
  getUserAccountStatus,
  canToggleUserStatus,
} from "./usersUtils";

const users = [
  {
    id: 1,
    name: "مدير النظام",
    username: "tec-adm-100",
    custom_username: null,
    roles: ["super_admin"],
    is_active: true,
    must_change_password: true,
    is_password_changed: false,
  },
  {
    id: 2,
    name: "سارة أحمد",
    username: "tec-coach-200",
    custom_username: "sara",
    roles: ["coach"],
    is_active: true,
    must_change_password: false,
    is_password_changed: true,
  },
  {
    id: 3,
    name: "علي حسن",
    username: "tec-ply-300",
    custom_username: null,
    roles: ["player"],
    is_active: false,
    must_change_password: false,
    is_password_changed: true,
  },
];

describe("usersUtils", () => {
  it("extracts users from the API envelope", () => {
    expect(getUsersCollection({ status: "success", data: users })).toEqual(users);
  });

  it("searches across names, usernames, custom usernames, and localized roles", () => {
    expect(filterUsers(users, "sara")).toEqual([users[1]]);
    expect(filterUsers(users, "مدير النظام")).toEqual([users[0]]);
  });

  it("creates role filter options from the returned roles", () => {
    expect(createUserRoleOptions(users)).toEqual([
      { value: "player", label: "لاعب" },
      { value: "coach", label: "مدرب" },
      { value: "super_admin", label: "مدير النظام" },
    ]);
  });

  it("creates readable role tabs", () => {
    expect(buildUserRoleTabs(users)).toEqual([
      { value: "all", label: "الكل" },
      { value: "player", label: "اللاعبون" },
      { value: "coach", label: "المدربون" },
      { value: "super_admin", label: "مديرو النظام" },
    ]);
  });


  it("summarizes accounts and resolves password state", () => {
    const stats = createUserStats(users);

    expect(stats[0].value).toBe((3).toLocaleString("ar"));
    expect(stats[4].value).toBe((1).toLocaleString("ar"));
    expect(getPasswordStatus(users[0]).label).toBe("مطلوب تغييرها");
    expect(getPasswordStatus(users[1]).label).toBe("تم تغييرها");
  });

  it("detects super_admin role accurately", () => {
    expect(isSuperAdminUser(users[0])).toBe(true);
    expect(isSuperAdminUser(users[1])).toBe(false);
    expect(isSuperAdminUser({ role: "super_admin" })).toBe(true);
    expect(isSuperAdminUser({ roles: [{ name: "super_admin" }] })).toBe(true);
  });

  it("resolves user account status label and styling", () => {
    expect(getUserAccountStatus(users[0])).toEqual({
      isActive: true,
      label: "نشط",
      className: "bg-[rgba(19,172,73,0.16)] text-app-green",
    });
    expect(getUserAccountStatus(users[2])).toEqual({
      isActive: false,
      label: "موقوف",
      className: "bg-[rgba(228,0,0,0.16)] text-app-red",
    });
  });

  describe("canToggleUserStatus protection rules", () => {
    const currentUser = { id: 99, roles: ["admin"] };

    it("prevents deactivating own account", () => {
      const ownUser = { id: 99, is_active: true, roles: ["admin"] };
      const result = canToggleUserStatus(ownUser, currentUser, true);
      expect(result.allowed).toBe(false);
      expect(result.reason).toBe("لا يمكنك تعطيل حسابك الشخصي.");
    });

    it("prevents deactivating super_admin account", () => {
      const superAdmin = { id: 1, is_active: true, roles: ["super_admin"] };
      const result = canToggleUserStatus(superAdmin, currentUser, true);
      expect(result.allowed).toBe(false);
      expect(result.reason).toBe("لا يمكن تعطيل حساب مدير النظام (super_admin).");
    });

    it("allows toggling regular active or inactive accounts", () => {
      const regularActive = { id: 2, is_active: true, roles: ["coach"] };
      const regularInactive = { id: 3, is_active: false, roles: ["player"] };

      expect(canToggleUserStatus(regularActive, currentUser, true).allowed).toBe(true);
      expect(canToggleUserStatus(regularInactive, currentUser, true).allowed).toBe(true);
    });

    it("blocks toggle if user lacks toggle permission", () => {
      const regularUser = { id: 2, is_active: true, roles: ["coach"] };
      const result = canToggleUserStatus(regularUser, currentUser, false);
      expect(result.allowed).toBe(false);
      expect(result.reason).toBe("ليس لديك صلاحية لتعديل حالة الحساب.");
    });
  });
});

