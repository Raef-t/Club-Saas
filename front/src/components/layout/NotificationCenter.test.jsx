import { fireEvent, render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import NotificationCenter from "./NotificationCenter";

const push = vi.fn();
const setSelectedBranchId = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: () => ({ push }),
}));

vi.mock("@/lib/ManagementBranchContext", () => ({
  useOptionalManagementBranch: () => ({ setSelectedBranchId }),
}));

vi.mock("@/lib/api/notificationsApi", () => ({
  useGetNotificationsQuery: () => ({
    data: {
      data: [
        {
          recipient_id: 399,
          notification_id: 400,
          title: "تنبيه: تم توليد الرواتب",
          preview: "يرجى مراجعة الرواتب واعتمادها.",
          is_read: false,
          created_at_human: "الآن",
          target_snapshot: {
            type: "payroll_due",
            branch_id: 11,
            period_start: "2026-08-03",
            period_end: "2026-09-02",
          },
        },
      ],
    },
    isLoading: false,
    isFetching: false,
    error: null,
    refetch: vi.fn(),
  }),
}));

describe("NotificationCenter payroll action", () => {
  beforeEach(() => {
    push.mockClear();
    setSelectedBranchId.mockClear();
  });

  it("minimizes the floating notification and runs payroll generation from the circle", () => {
    render(<NotificationCenter />);

    expect(screen.getByLabelText("إشعار استحقاق الرواتب")).toBeInTheDocument();
    fireEvent.click(screen.getByRole("button", { name: "تصغير الإشعار إلى دائرة" }));

    const minimizedAction = screen.getByRole("button", {
      name: "توليد مسودة الرواتب من الإشعار",
    });
    fireEvent.click(minimizedAction);

    expect(setSelectedBranchId).toHaveBeenCalledWith("11");
    expect(push).toHaveBeenCalledWith(
      "/management/payroll?payroll_action=generate&branch_id=11&notification_id=400&period_start=2026-08-03&period_end=2026-09-02",
    );
  });
});
