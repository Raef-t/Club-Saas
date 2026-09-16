import { cleanup, fireEvent, render, screen, waitFor, within } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import UsersClient from "./UsersClient";

const { resetPassword, resetPasswordUnwrap, toastSuccess, toastError } = vi.hoisted(() => {
  const resetPasswordUnwrap = vi.fn();

  return {
    resetPasswordUnwrap,
    resetPassword: vi.fn(() => ({ unwrap: resetPasswordUnwrap })),
    toastSuccess: vi.fn(),
    toastError: vi.fn(),
  };
});

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
    stats: [{ title: "إجمالي الحسابات", value: "2", tone: "yellow" }],
    roleOptions: [],
    totalResults: 2,
    isLoading: false,
    isRefreshing: false,
    errorMessage: null,
    retry: vi.fn(),
  }),
}));

vi.mock("@/lib/PermissionContext", () => ({
  usePermissions: () => ({ can: () => true }),
}));

vi.mock("@/lib/api/authApi", () => ({
  useResetPasswordMutation: () => [resetPassword, { isLoading: false }],
}));

vi.mock("@/components/ui/Toast", () => ({
  useToast: () => ({ success: toastSuccess, error: toastError }),
}));

vi.mock("./UserPermissionsDrawer", () => ({
  default: () => null,
}));

describe("UsersClient", () => {
  beforeEach(() => {
    vi.stubEnv("NEXT_PUBLIC_PASSWORD_HASH_SECRET_KEY", "oid900=rjfreipwhefdk");
    resetPasswordUnwrap.mockResolvedValue({ status: "success" });
  });

  afterEach(() => {
    cleanup();
    resetPassword.mockClear();
    resetPasswordUnwrap.mockReset();
    toastSuccess.mockReset();
    toastError.mockReset();
    vi.unstubAllEnvs();
  });

  it("renders copyable usernames for both account username and custom username", () => {
    render(<UsersClient initialUsers={mockUsers} />);

    expect(screen.getAllByText("tec-adm-100").length).toBeGreaterThan(0);
    expect(screen.getAllByText("admin_custom").length).toBeGreaterThan(0);
    expect(screen.getAllByText("tec-coach-200").length).toBeGreaterThan(0);

    const copyButtons = screen.getAllByRole("button", { name: /نسخ اسم المستخدم/i });
    expect(copyButtons.length).toBeGreaterThanOrEqual(3);
  });

  it("sends the hashed default password when an administrator resets a user", async () => {
    render(<UsersClient initialUsers={mockUsers} />);

    fireEvent.click(screen.getAllByRole("button", { name: "إعادة تعيين" })[0]);
    const dialog = screen.getByRole("dialog");
    fireEvent.click(within(dialog).getByRole("button", { name: "إعادة تعيين" }));

    await waitFor(() => expect(resetPassword).toHaveBeenCalledOnce());
    expect(resetPassword).toHaveBeenCalledWith({
      user_id: 2,
      password: "119f6226667c1bc87396838134392ef4f4d38e68f1719aed7b2dff13be62d5ed",
    });
    expect(toastSuccess).toHaveBeenCalledWith(expect.stringContaining("12345678"));
  });
});
