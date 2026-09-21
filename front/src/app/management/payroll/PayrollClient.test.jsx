import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import PayrollClient from "./PayrollClient";

const mocks = vi.hoisted(() => ({
  generatePayslips: vi.fn(),
  confirmPayslips: vi.fn(),
  markNotificationRead: vi.fn(),
  setSelectedBranchId: vi.fn(),
  toast: {
    error: vi.fn(),
    success: vi.fn(),
    warning: vi.fn(),
  },
}));

vi.mock("next/link", () => ({
  default: ({ children }) => children,
}));

vi.mock("@/lib/ManagementBranchContext", () => ({
  useManagementBranch: () => ({
    branches: [{ id: 11, name: "الفرع الرئيسي" }],
    selectedBranchId: "11",
    selectedBranch: { id: 11, name: "الفرع الرئيسي" },
    isAllBranches: false,
    setSelectedBranchId: mocks.setSelectedBranchId,
  }),
}));

vi.mock("@/lib/api/branchesApi", () => ({
  useGetBranchSettingsQuery: () => ({
    data: { data: { payroll_end_day: 28 } },
    error: null,
    isLoading: false,
    isFetching: false,
    refetch: vi.fn(),
  }),
  useUpdateBranchSettingsMutation: () => [vi.fn(), { isLoading: false }],
}));

vi.mock("@/lib/api/payslipsApi", () => ({
  useGetPayslipsQuery: () => ({
    data: { data: [] },
    error: null,
    isLoading: false,
    isFetching: false,
    refetch: vi.fn(),
  }),
  useGeneratePayslipsMutation: () => [mocks.generatePayslips, { isLoading: false }],
  useUpdatePayslipMutation: () => [vi.fn(), { isLoading: false }],
  useConfirmPayslipsMutation: () => [mocks.confirmPayslips, { isLoading: false }],
}));

vi.mock("@/lib/api/notificationsApi", () => ({
  useMarkNotificationReadMutation: () => [mocks.markNotificationRead, { isLoading: false }],
}));

vi.mock("@/components/ui/Toast", () => ({
  useToast: () => mocks.toast,
}));

vi.mock("@/components/common/PageHeader", () => ({ default: () => null }));
vi.mock("@/components/ui/Dropdown", () => ({ default: () => null }));
vi.mock("@/components/ui/StatsGrid", () => ({ default: () => null }));
vi.mock("./PayslipEditorModal", () => ({ default: () => null }));
vi.mock("@/components/ui/DataTable", () => ({
  default: ({ toolbarMeta }) => <div>{toolbarMeta}</div>,
}));
vi.mock("@/components/ui/Button", () => ({
  default: ({ children, onClick }) => (
    <button type="button" onClick={onClick}>
      {children}
    </button>
  ),
}));
vi.mock("@/components/ui/ConfirmDialog", () => ({
  default: ({ open, onConfirm, confirmLabel }) =>
    open ? (
      <button type="button" onClick={onConfirm}>
        {confirmLabel}
      </button>
    ) : null,
}));

describe("PayrollClient notification completion", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.generatePayslips.mockReturnValue({
      unwrap: () =>
        Promise.resolve({
          data: {
            period_start: "2026-08-03",
            period_end: "2026-09-02",
            payslips: [
              {
                staff_id: 5,
                staff_name: "موظف تجريبي",
                base_pay: 1000,
                commission_pay: 0,
                net_pay: 1000,
              },
            ],
          },
        }),
    });
    mocks.confirmPayslips.mockReturnValue({
      unwrap: () => Promise.resolve({ message: "تم اعتماد الرواتب" }),
    });
    mocks.markNotificationRead.mockReturnValue({
      unwrap: () => Promise.resolve({ status: true }),
    });
  });

  it("marks the originating notification as read after payroll approval", async () => {
    render(
      <PayrollClient
        initialAction={{
          type: "generate",
          branchId: "11",
          notificationId: "400",
          recipientId: "399",
          periodStart: "2026-08-03",
          periodEnd: "2026-09-02",
        }}
      />,
    );

    const reviewButton = await screen.findByRole("button", {
      name: "تثبيت واعتماد الرواتب",
    });
    fireEvent.click(reviewButton);
    fireEvent.click(screen.getByRole("button", { name: "تثبيت واعتماد" }));

    await waitFor(() => expect(mocks.confirmPayslips).toHaveBeenCalledOnce());
    await waitFor(() => expect(mocks.markNotificationRead).toHaveBeenCalledWith("399"));
    expect(mocks.confirmPayslips.mock.invocationCallOrder[0]).toBeLessThan(
      mocks.markNotificationRead.mock.invocationCallOrder[0],
    );
  });
});
