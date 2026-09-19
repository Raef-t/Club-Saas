import { cleanup, fireEvent, render, screen, waitFor, within } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import UsersClient from "./UsersClient";

const {
  resetPassword,
  resetPasswordUnwrap,
  toggleUserStatus,
  toggleUserStatusUnwrap,
  toastSuccess,
  toastError,
} = vi.hoisted(() => {
  const resetPasswordUnwrap = vi.fn();
  const toggleUserStatusUnwrap = vi.fn();

  return {
    resetPasswordUnwrap,
    resetPassword: vi.fn(() => ({ unwrap: resetPasswordUnwrap })),
    toggleUserStatusUnwrap,
    toggleUserStatus: vi.fn(() => ({ unwrap: toggleUserStatusUnwrap })),
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
    is_active: true,
    must_change_password: false,
    is_password_changed: true,
  },
  {
    id: 2,
    name: "سارة أحمد",
    username: "tec-coach-200",
    custom_username: null,
    roles: ["coach"],
    is_active: true,
    must_change_password: true,
    is_password_changed: false,
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

let mockCurrentUser = { id: 99, username: "current_admin", roles: ["admin"] };

vi.mock("./useUsers", () => ({
  useUsers: ({ initialUsers }) => ({
    search: "",
    setSearch: vi.fn(),
    roleFilter: "",
    setRoleFilter: vi.fn(),
    users: initialUsers || mockUsers,
    stats: [{ title: "إجمالي الحسابات", value: "3", tone: "yellow" }],
    roleOptions: [],
    totalResults: 3,
    isLoading: false,
    isRefreshing: false,
    errorMessage: null,
    retry: vi.fn(),
  }),
}));

vi.mock("@/lib/PermissionContext", () => ({
  usePermissions: () => ({
    user: mockCurrentUser,
    can: () => true,
    isSuperAdmin: false,
  }),
}));

vi.mock("@/lib/api/authApi", () => ({
  useResetPasswordMutation: () => [resetPassword, { isLoading: false }],
}));

vi.mock("@/lib/api/usersApi", () => ({
  useToggleUserStatusMutation: () => [toggleUserStatus, { isLoading: false }],
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
    toggleUserStatusUnwrap.mockResolvedValue({
      status: "success",
      message: "تم إيقاف الحساب وإلغاء جميع جلسات الدخول الفعالة بنجاح.",
    });
    mockCurrentUser = { id: 99, username: "current_admin", roles: ["admin"] };
  });

  afterEach(() => {
    cleanup();
    resetPassword.mockClear();
    resetPasswordUnwrap.mockReset();
    toggleUserStatus.mockClear();
    toggleUserStatusUnwrap.mockReset();
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

  it("renders account status switches and respects protection rules", () => {
    render(<UsersClient initialUsers={mockUsers} />);

    expect(screen.getAllByText("حالة الحساب").length).toBeGreaterThan(0);
    expect(screen.getAllByText("نشط").length).toBeGreaterThan(0);
    expect(screen.getAllByText("موقوف").length).toBeGreaterThan(0);

    // Super admin account cannot be deactivated
    const superAdminSwitches = screen.getAllByRole("checkbox", {
      name: /تبديل حالة حساب مدير النظام/i,
    });
    expect(superAdminSwitches[0]).toBeDisabled();

    // Regular coach account can be toggled
    const coachSwitches = screen.getAllByRole("checkbox", {
      name: /تبديل حالة حساب سارة أحمد/i,
    });
    expect(coachSwitches[0]).not.toBeDisabled();
  });

  it("opens confirmation dialog and toggles user status successfully", async () => {
    render(<UsersClient initialUsers={mockUsers} />);

    const coachSwitch = screen.getAllByRole("checkbox", {
      name: /تبديل حالة حساب سارة أحمد/i,
    })[0];

    fireEvent.click(coachSwitch);

    const dialog = screen.getByRole("dialog");
    expect(within(dialog).getByText("إيقاف حساب المستخدم")).toBeInTheDocument();
    expect(
      within(dialog).getByText(/هل أنت متأكد من رغبتك في إيقاف حساب/i),
    ).toBeInTheDocument();

    fireEvent.click(within(dialog).getByRole("button", { name: "إيقاف الحساب" }));

    await waitFor(() => expect(toggleUserStatus).toHaveBeenCalledOnce());
    expect(toggleUserStatus).toHaveBeenCalledWith(2);
    expect(toastSuccess).toHaveBeenCalledWith("تم إيقاف الحساب وإلغاء جميع جلسات الدخول الفعالة بنجاح.");
  });

  it("disables deactivating the administrator's own account", () => {
    mockCurrentUser = { id: 2, username: "tec-coach-200", roles: ["admin"] };
    render(<UsersClient initialUsers={mockUsers} />);

    const ownSwitches = screen.getAllByRole("checkbox", {
      name: /تبديل حالة حساب سارة أحمد/i,
    });
    expect(ownSwitches[0]).toBeDisabled();
  });
});
