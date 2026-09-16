import { cleanup, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it } from "vitest";
import SubscriptionDetails from "./SubscriptionDetails";

describe("subscription details", () => {
  afterEach(() => cleanup());

  it("shows the preferred account name without exposing the membership number", () => {
    render(
      <SubscriptionDetails
        subscription={{
          id: 10,
          status: "active",
          member: {
            member_number: "MEM-2026-0034",
            generated_username: "tec-ply-18755",
            custom_username: "engy.player",
            person: { full_name: "انجي مؤذن", phone: "955430964" },
          },
          plan: { name: "أجهزة عام" },
          items: [],
        }}
        showActions={false}
      />,
    );

    expect(screen.getAllByText("engy.player")).toHaveLength(2);
    expect(screen.getByText("اسم الحساب")).toBeInTheDocument();
    expect(screen.queryByText("tec-ply-18755")).not.toBeInTheDocument();
    expect(screen.queryByText("MEM-2026-0034")).not.toBeInTheDocument();
  });

  it("uses the profile member as a fallback for subscription summaries", () => {
    render(
      <SubscriptionDetails
        subscription={{
          id: 11,
          status: "active",
          member: { id: 7 },
          plan: {},
          items: [],
        }}
        memberFallback={{
          member_number: "MEM-11",
          generated_username: "tec-ply-11000",
          person: { full_name: "لاعب تجريبي" },
        }}
        showActions={false}
      />,
    );

    expect(screen.getAllByText("tec-ply-11000")).toHaveLength(2);
    expect(screen.getAllByText("لاعب تجريبي")).toHaveLength(2);
  });

  it("does not show coach or club receipt labels for a non-private subscription type", () => {
    render(
      <SubscriptionDetails
        subscription={{
          id: 12,
          status: "active",
          receipt_number: "REC-GENERAL-01",
          coach_receipt_number: "STALE-COACH",
          branch_receipt_number: "STALE-CLUB",
          member: { person: { full_name: "لاعب تجريبي" } },
          plan: {
            name: "حصة جماعية",
            activity_types: [
              { code: "group_class", name: "حصة جماعية", is_private_equipment: false },
            ],
          },
          items: [],
        }}
        showActions={false}
      />,
    );

    expect(screen.getByText("REC-GENERAL-01")).toBeInTheDocument();
    expect(screen.queryByText("إيصال الكوتش:")).not.toBeInTheDocument();
    expect(screen.queryByText("إيصال النادي:")).not.toBeInTheDocument();
  });

  it("shows the original amount, discount, net amount, and reason", () => {
    render(
      <SubscriptionDetails
        subscription={{
          id: 13,
          status: "active",
          is_discount: true,
          original_total_amount: 300000,
          discount_percentage: 50,
          discount_amount: 150000,
          discount_reason: "حسم خاص للمشتركة",
          total_amount: 150000,
          paid_amount: 100000,
          remaining_amount: 50000,
          member: { person: { full_name: "سارة الأحمد" } },
          plan: { name: "اشتراك شهري", base_price: 300000 },
          items: [],
        }}
        showActions={false}
      />,
    );

    expect(screen.getByText("حسم 50%")).toBeInTheDocument();
    expect(screen.getByText("السعر الأصلي")).toBeInTheDocument();
    expect(screen.getByText("قيمة الحسم (50%)")).toBeInTheDocument();
    expect(screen.getByText("الصافي المستحق")).toBeInTheDocument();
    expect(screen.getByText("حسم خاص للمشتركة")).toBeInTheDocument();
  });
});
