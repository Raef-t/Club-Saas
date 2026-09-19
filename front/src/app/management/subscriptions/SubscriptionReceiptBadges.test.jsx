import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it } from "vitest";
import SubscriptionReceiptBadges from "./SubscriptionReceiptBadges";

describe("SubscriptionReceiptBadges", () => {
  afterEach(() => cleanup());

  it("renders a single receipt number without the label for regular subscriptions", () => {
    render(
      <SubscriptionReceiptBadges
        subscription={{
          receipt_number: "REC-9912",
        }}
      />,
    );

    expect(screen.queryByText("الإيصال:")).not.toBeInTheDocument();
    expect(screen.getByText("REC-9912")).toBeInTheDocument();

    const trigger = screen.getByTestId("app-tooltip-trigger");
    fireEvent.mouseEnter(trigger);

    const tooltip = screen.getByRole("tooltip");
    expect(tooltip).toHaveTextContent("تفاصيل الإيصالات");
    expect(tooltip).toHaveTextContent("رقم الإيصال:");
    expect(tooltip).toHaveTextContent("REC-9912");
  });

  it("renders coach and branch receipt numbers compactly for private subscriptions", () => {
    render(
      <SubscriptionReceiptBadges
        subscription={{
          coach_receipt_number: "17",
          branch_receipt_number: "219",
          plan: { coach_price: 200, branch_price: 150 },
        }}
      />,
    );

    expect(screen.queryByText("إيصال الكوتش:")).not.toBeInTheDocument();
    expect(screen.queryByText("إيصال النادي:")).not.toBeInTheDocument();
    expect(screen.getByText("17")).toBeInTheDocument();
    expect(screen.getByText("219")).toBeInTheDocument();

    const trigger = screen.getByTestId("app-tooltip-trigger");
    fireEvent.mouseEnter(trigger);

    const tooltip = screen.getByRole("tooltip");
    expect(tooltip).toHaveTextContent("تفاصيل الإيصالات");
    expect(tooltip).toHaveTextContent("إيصال الكوتش:");
    expect(tooltip).toHaveTextContent("17");
    expect(tooltip).toHaveTextContent("إيصال النادي:");
    expect(tooltip).toHaveTextContent("219");
  });

  it("renders dash when there are no receipts", () => {
    render(<SubscriptionReceiptBadges subscription={{}} />);
    expect(screen.getByText("-")).toBeInTheDocument();
  });
});
