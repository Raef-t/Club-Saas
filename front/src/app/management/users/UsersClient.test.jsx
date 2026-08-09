import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import UsersClient from "./UsersClient";

const mockUsers = [
  {
    id: 1,
    name: "مدير النظام",
    username: "tec-adm-100",
    custom_username: "admin_custom",
    roles: ["super_admin"],
    must_change_password: false,
    is_password_changed: true,
  },
  {
    id: 2,
    name: "سارة أحمد",
    username: "tec-coach-200",
    custom_username: null,
    roles: ["coach"],
    must_change_password: true,
    is_password_changed: false,
  },
];

vi.mock("./useUsers", () => ({
  useUsers: ({ initialUsers }) => ({
    search: "",
    setSearch: vi.fn(),
    roleFilter: "",
    setRoleFilter: vi.fn(),
    users: initialUsers || mockUsers,
    stats: [
      { title: "إجمالي الحسابات", value: "2", tone: "yellow" },
    ],
    roleOptions: [],
    totalResults: 2,
    isLoading: false,
    isRefreshing: false,
    errorMessage: null,
    retry: vi.fn(),
  }),
}));

describe("UsersClient", () => {
  it("renders copyable usernames for both account username and custom username", () => {
    render(<UsersClient initialUsers={mockUsers} />);

    expect(screen.getAllByText("tec-adm-100").length).toBeGreaterThan(0);
    expect(screen.getAllByText("admin_custom").length).toBeGreaterThan(0);
    expect(screen.getAllByText("tec-coach-200").length).toBeGreaterThan(0);

    const copyButtons = screen.getAllByRole("button", { name: /نسخ اسم المستخدم/i });
    expect(copyButtons.length).toBeGreaterThanOrEqual(3);
  });
});
