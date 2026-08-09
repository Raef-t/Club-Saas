import { describe, expect, it } from "vitest";
import {
  createUserRoleOptions,
  createUserStats,
  filterUsers,
  getPasswordStatus,
  getUsersCollection,
} from "./usersUtils";

const users = [
  {
    id: 1,
    name: "مدير النظام",
    username: "tec-adm-100",
    custom_username: null,
    roles: ["super_admin"],
    must_change_password: true,
    is_password_changed: false,
  },
  {
    id: 2,
    name: "سارة أحمد",
    username: "tec-coach-200",
    custom_username: "sara",
    roles: ["coach"],
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
      { value: "coach", label: "مدرب" },
      { value: "super_admin", label: "مدير النظام" },
    ]);
  });

  it("summarizes accounts and resolves password state", () => {
    const stats = createUserStats(users);

    expect(stats[0].value).toBe((2).toLocaleString("ar"));
    expect(stats[4].value).toBe((1).toLocaleString("ar"));
    expect(getPasswordStatus(users[0]).label).toBe("مطلوب تغييرها");
    expect(getPasswordStatus(users[1]).label).toBe("تم تغييرها");
  });
});
