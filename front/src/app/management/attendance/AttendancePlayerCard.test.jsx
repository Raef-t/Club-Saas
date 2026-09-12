import { cleanup, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import AttendancePlayerCard from "./AttendancePlayerCard";

const baseProps = {
  member: { id: 12, name: "أحمد علي", number: "M-12", avatar: "أ" },
  selectedActivity: { label: "اللياقة", coach: "محمد" },
  selectedSubscriptionIds: ["108"],
  attendanceNote: "",
  attendanceErrorMessage: "",
  lockerNumber: "",
  availableLockerOptions: [],
  isMemberLoading: false,
  memberErrorMessage: "",
  isSubscriptionsLoading: false,
  subscriptionsErrorMessage: "",
  isAvailableLockersLoading: false,
  availableLockersErrorMessage: "",
  isRegistered: false,
  isPendingDeduction: true,
  isRegistering: false,
  onRetryMember: vi.fn(),
  onRetrySubscriptions: vi.fn(),
  onRetryAvailableLockers: vi.fn(),
  onSubscriptionToggle: vi.fn(),
  onLockerChange: vi.fn(),
  onAttendanceNoteChange: vi.fn(),
  onRegister: vi.fn(),
};

afterEach(cleanup);

function renderCard(requiresOverrideReason) {
  const subscription = {
    id: "108",
    label: "الخطة الشهرية",
    remaining: 6,
    endsAt: "30/09/2026",
    todaySessionsCount: 2,
    requiresOverrideReason,
  };

  render(
    <AttendancePlayerCard
      {...baseProps}
      selectedSubscription={subscription}
      playerSubscriptions={[subscription]}
      requiresCheckInNote={requiresOverrideReason}
    />,
  );
}

describe("AttendancePlayerCard", () => {
  it("shows today's session data and requires an override reason when needed", () => {
    renderCard(true);

    expect(screen.getByText("جلسات اليوم")).toBeInTheDocument();
    expect(screen.getByLabelText("سبب الحضور خارج الموعد *")).toBeRequired();
    expect(screen.getByRole("button", { name: "تسجيل الدخول والخصم" })).toBeDisabled();
  });

  it("does not show the override field for an in-schedule subscription", () => {
    renderCard(false);

    expect(screen.queryByLabelText(/سبب الحضور خارج الموعد/)).not.toBeInTheDocument();
    expect(screen.getByRole("button", { name: "تسجيل الدخول والخصم" })).toBeEnabled();
  });
});
