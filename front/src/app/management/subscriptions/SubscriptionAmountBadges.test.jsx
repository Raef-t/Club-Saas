import { cleanup, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it } from "vitest";
import SubscriptionAmountBadges from "./SubscriptionAmountBadges";

describe("subscription amount badges", () => {
  afterEach(() => cleanup());

  it("always shows the net price and paid amount as separate badges", () => {
    render(
      <SubscriptionAmountBadges
        subscription={{
          total_amount: 750,
          paid_amount: 500,
          plan: { base_price: 750 },
        }}
      />,
    );

    expect(screen.getByText("الصافي:")).toBeInTheDocument();
    expect(screen.getByText("المدفوع:")).toBeInTheDocument();
    expect(screen.getByTitle("الصافي: 750 ل.س")).toBeInTheDocument();
    expect(screen.getByTitle("المدفوع: 500 ل.س")).toBeInTheDocument();
  });

  it("keeps the discount context while showing both current amounts", () => {
    render(
      <SubscriptionAmountBadges
        subscription={{
          is_discount: true,
          original_total_amount: 1000,
          discount_percentage: 20,
          total_amount: 800,
          paid_amount: 600,
          discount_reason: "حسم خاص",
          plan: { base_price: 1000 },
        }}
      />,
    );

    expect(screen.getByText("حسم 20%")).toHaveAttribute("title", "حسم خاص");
    expect(screen.getByTitle("الصافي: 800 ل.س")).toBeInTheDocument();
    expect(screen.getByTitle("المدفوع: 600 ل.س")).toBeInTheDocument();
  });

  it("separates the coach and club prices for private subscriptions", () => {
    render(
      <SubscriptionAmountBadges
        subscription={{
          months_count: 2,
          total_amount: 700,
          paid_amount: 700,
          plan: {
            base_price: 350,
            coach_price: 200,
            branch_price: 150,
            is_private_plan: true,
          },
        }}
      />,
    );

    expect(screen.getByLabelText("تفصيل سعر الكوتش والنادي")).toBeInTheDocument();
    expect(screen.getByTitle("سعر الكوتش: 400 ل.س")).toBeInTheDocument();
    expect(screen.getByTitle("سعر النادي: 300 ل.س")).toBeInTheDocument();
  });
});
