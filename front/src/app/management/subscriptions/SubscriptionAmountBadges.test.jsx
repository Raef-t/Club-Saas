import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it } from "vitest";
import SubscriptionAmountBadges from "./SubscriptionAmountBadges";

describe("subscription amount badges", () => {
  afterEach(() => cleanup());

  it("shows the amount badge and paid badge if partially paid", () => {
    render(
      <SubscriptionAmountBadges
        subscription={{
          total_amount: 750,
          paid_amount: 500,
          plan: { base_price: 750 },
        }}
      />,
    );

    const amountLabel = screen.getByText("المبلغ:");
    expect(amountLabel).toBeInTheDocument();
    expect(amountLabel.closest("span.rounded-full")).toHaveClass("text-app-green");
    expect(screen.getByText("المدفوع:")).toBeInTheDocument();
    expect(screen.getByText("750 ل.س")).toBeInTheDocument();
    expect(screen.getByText("500 ل.س")).toBeInTheDocument();

    const trigger = screen.getByTestId("app-tooltip-trigger");
    fireEvent.mouseEnter(trigger);
    const tooltip = screen.getByRole("tooltip");
    expect(tooltip).toHaveTextContent("تفاصيل المبلغ");
    expect(tooltip).toHaveTextContent("المبلغ:");
    expect(tooltip).toHaveTextContent("750 ل.س");
    expect(tooltip).toHaveTextContent("المدفوع:");
    expect(tooltip).toHaveTextContent("500 ل.س");
  });

  it("keeps the discount context while showing amount directly in table", () => {
    render(
      <SubscriptionAmountBadges
        subscription={{
          is_discount: true,
          original_total_amount: 1000,
          discount_percentage: 20,
          total_amount: 800,
          paid_amount: 800,
          discount_reason: "حسم خاص",
          plan: { base_price: 1000 },
        }}
      />,
    );

    expect(screen.getByText("حسم 20%")).toHaveAttribute("title", "حسم خاص");
    expect(screen.getByText("المبلغ:")).toBeInTheDocument();
    expect(screen.getByText("800 ل.س")).toBeInTheDocument();
    expect(screen.queryByText("المدفوع:")).not.toBeInTheDocument();

    const trigger = screen.getByTestId("app-tooltip-trigger");
    fireEvent.mouseEnter(trigger);
    const tooltip = screen.getByRole("tooltip");
    expect(tooltip).toHaveTextContent("تفاصيل المبلغ");
    expect(tooltip).toHaveTextContent("المبلغ:");
    expect(tooltip).toHaveTextContent("800 ل.س");
    expect(tooltip).toHaveTextContent("حسم 20%");
    expect(tooltip).toHaveTextContent("حسم خاص");
  });

  it("displays coach, branch, and total amounts directly in table for private subscriptions", () => {
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

    expect(screen.getByText("المبلغ:")).toBeInTheDocument();
    expect(screen.getByText("700 ل.س")).toBeInTheDocument();
    expect(screen.getByText("الكوتش:")).toBeInTheDocument();
    expect(screen.getByText("400 ل.س")).toBeInTheDocument();
    expect(screen.getByText("النادي:")).toBeInTheDocument();
    expect(screen.getByText("300 ل.س")).toBeInTheDocument();

    const trigger = screen.getByTestId("app-tooltip-trigger");
    fireEvent.mouseEnter(trigger);
    const tooltip = screen.getByRole("tooltip");
    expect(tooltip).toHaveTextContent("تفاصيل المبلغ");
    expect(tooltip).toHaveTextContent("المبلغ:");
    expect(tooltip).toHaveTextContent("700 ل.س");
    expect(tooltip).toHaveTextContent("الكوتش:");
    expect(tooltip).toHaveTextContent("400 ل.س");
    expect(tooltip).toHaveTextContent("النادي:");
    expect(tooltip).toHaveTextContent("300 ل.س");
  });
});
