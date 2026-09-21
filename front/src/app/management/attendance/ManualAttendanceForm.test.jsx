import { cleanup, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import ManualAttendanceForm from "./ManualAttendanceForm";

vi.mock("@/lib/api/membersApi", () => ({
  useGetMembersQuery: () => ({ currentData: { data: [] }, isFetching: false }),
}));

vi.mock("@/lib/api/staffApi", () => ({
  useGetStaffQuery: () => ({ currentData: { data: [] }, isFetching: false }),
}));

vi.mock("@/lib/api/subscriptionPlansApi", () => ({
  useGetSubscriptionPlansQuery: () => ({ currentData: { data: [] }, isFetching: false }),
}));

afterEach(cleanup);

describe("ManualAttendanceForm", () => {
  it("shows the optional check-in time for members", () => {
    render(
      <ManualAttendanceForm
        attendance={{
          branchId: "3",
          branchOptions: [{ value: "3", label: "الفرع الرئيسي" }],
          attendanceRows: [],
          isManualCheckingIn: false,
          isManualCheckingOut: false,
          isBulkCheckingOut: false,
          setBranchId: vi.fn(),
          handleManualCheckIn: vi.fn(),
          handleManualCheckOut: vi.fn(),
          handleBulkCheckOut: vi.fn(),
        }}
      />,
    );

    expect(screen.getByText("وقت الدخول (اختياري)")).toBeInTheDocument();
    expect(screen.getByPlaceholderText("HH:MM")).toBeInTheDocument();
  });
});
