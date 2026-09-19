import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it } from "vitest";
import SubscriptionAmountBadges from "./SubscriptionAmountBadges";

describe("subscription amount badges", () => {
  afterEach(() => cleanup());

  it("shows the net price without the label and displays paid amount in tooltip", () => {
    render(
      <SubscriptionAmountBadges
        subscription={{
          total_amount: 750,
          paid_amount: 500,
          plan: { base_price: 750 },
        }}
      />,
    );

    expect(screen.queryByText("الصافي:")).not.toBeInTheDocument();
    expect(screen.queryByText("المدفوع:")).not.toBeInTheDocument();
    expect(screen.getByText("750 ل.س")).toBeInTheDocument();

    const trigger = screen.getByTestId("app-tooltip-trigger");
    fireEvent.mouseEnter(trigger);
    const tooltip = screen.getByRole("tooltip");
    expect(tooltip).toHaveTextContent("تفاصيل المبلغ");
    expect(tooltip).toHaveTextContent("الصافي:");
    expect(tooltip).toHaveTextContent("750 ل.س");
    expect(tooltip).toHaveTextContent("المدفوع:");
    expect(tooltip).toHaveTextContent("500 ل.س");
  });

  it("keeps the discount context while showing amount and details in tooltip", () => {
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
    expect(screen.getByText("800 ل.س")).toBeInTheDocument();

    const trigger = screen.getByTestId("app-tooltip-trigger");
    fireEvent.mouseEnter(trigger);
    const tooltip = screen.getByRole("tooltip");
    expect(tooltip).toHaveTextContent("تفاصيل المبلغ");
    expect(tooltip).toHaveTextContent("الصافي:");
    expect(tooltip).toHaveTextContent("800 ل.س");
    expect(tooltip).toHaveTextContent("المدفوع:");
    expect(tooltip).toHaveTextContent("600 ل.س");
    expect(tooltip).toHaveTextContent("حسم 20%");
    expect(tooltip).toHaveTextContent("حسم خاص");
  });

  it("includes coach and club prices in the tooltip for private subscriptions", () => {
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

    expect(screen.queryByText("سعر الكوتش:")).not.toBeInTheDocument();
    expect(screen.queryByText("سعر النادي:")).not.toBeInTheDocument();

    const trigger = screen.getByTestId("app-tooltip-trigger");
    fireEvent.mouseEnter(trigger);
    const tooltip = screen.getByRole("tooltip");
    expect(tooltip).toHaveTextContent("تفاصيل المبلغ");
    expect(tooltip).toHaveTextContent("الصافي:");
    expect(tooltip).toHaveTextContent("700 ل.س");
    expect(tooltip).toHaveTextContent("المدفوع:");
    expect(tooltip).toHaveTextContent("700 ل.س");
    expect(tooltip).toHaveTextContent("سعر الكوتش:");
    expect(tooltip).toHaveTextContent("400 ل.س");
    expect(tooltip).toHaveTextContent("سعر النادي:");
    expect(tooltip).toHaveTextContent("300 ل.س");
  });
});
