import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import MemberDetails from "./MemberDetails";

describe("member comprehensive profile", () => {
  afterEach(() => cleanup());

  it("shows identity, subscription, attendance, finances, locker, and emergency contact", () => {
    const onShowSubscription = vi.fn();
    const subscription = {
      id: 10,
      member_id: 7,
      status: "active",
      plan: { name: { ar: "لياقة شهرية" } },
      start_date: "2026-08-01",
      end_date: "2026-09-01",
      paid_amount: 250,
      remaining_amount: 50,
      items: [
        {
          sessions_allocated: 12,
          sessions_consumed: 4,
          activity: { name: { ar: "أيروبيك" } },
        },
      ],
    };

    render(
      <MemberDetails
        member={{
          id: 7,
          member_number: "MEM-0007",
          branch_id: 2,
          username: "player-7",
          membership_status: "active",
          person: {
            full_name: "دانية مولوي",
            gender: "female",
            dob: "2000-01-01",
            contacts: [
              { relation: "self", phone_number: "999111222", country_code: "+963" },
              {
                relation: "Mother",
                name: "والدة دانية",
                phone_number: "999333444",
                country_code: "+963",
              },
            ],
          },
        }}
        branches={[{ id: 2, name: { ar: "الفرع الرئيسي" } }]}
        subscription={subscription}
        subscriptions={[subscription]}
        attendances={[
          {
            id: 1,
            member_id: 7,
            check_in: "2026-08-08T10:00:00Z",
            check_out: "2026-08-08T11:00:00Z",
            status: "checked_out",
          },
        ]}
        lockers={[{ id: 3, locker_number: "L-12", key_number: "K-12" }]}
        summary={{
          subscriptionsCount: 1,
          attendanceCount: 1,
          paidAmount: 250,
          remainingAmount: 50,
          lockerCount: 1,
          lastAttendance: { check_in: "2026-08-08T10:00:00Z" },
        }}
        onShowSubscription={onShowSubscription}
      />,
    );

    expect(screen.getByText("دانية مولوي")).toBeInTheDocument();
    expect(screen.getAllByText("لياقة شهرية").length).toBeGreaterThan(0);
    expect(screen.getByText("أيروبيك")).toBeInTheDocument();
    expect(screen.getByText("خزانة L-12")).toBeInTheDocument();
    expect(screen.getByText("والدة دانية")).toBeInTheDocument();
    expect(screen.getByText("سجل الاشتراكات")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "عرض كل تفاصيل الاشتراك" }));
    expect(onShowSubscription).toHaveBeenCalledOnce();
  });
});
