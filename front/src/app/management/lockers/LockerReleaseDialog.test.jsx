import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import LockerReleaseDialog from "./LockerReleaseDialog";

const FUTURE_END_DATE = "2099-08-20T12:00:00.000Z";

afterEach(cleanup);

describe("LockerReleaseDialog", () => {
  it("always requires and submits a release reason", () => {
    const onConfirm = vi.fn();
    render(
      <LockerReleaseDialog
        locker={{
          id: 1,
          locker_number: "L-1",
          current_reservation: {
            reservation_type: "assign",
            end_date: FUTURE_END_DATE,
          },
        }}
        onClose={vi.fn()}
        onConfirm={onConfirm}
      />,
    );

    const submitButton = screen.getByRole("button", { name: "فك الحجز" });
    fireEvent.click(submitButton);
    expect(onConfirm).not.toHaveBeenCalled();
    expect(screen.getByRole("alert")).toHaveTextContent("سبب فك الحجز مطلوب");

    fireEvent.change(screen.getByLabelText(/سبب فك الحجز/), {
      target: { value: "طلب المشترك فك الحجز" },
    });
    fireEvent.click(submitButton);
    expect(onConfirm).toHaveBeenCalledWith({
      reason: "طلب المشترك فك الحجز",
      is_refund: false,
    });
  });

  it("requires a reason for a rental and submits refund details", () => {
    const onConfirm = vi.fn();
    render(
      <LockerReleaseDialog
        locker={{
          id: 2,
          locker_number: "L-2",
          current_reservation: {
            reservation_type: "rental",
            end_date: "2020-08-20T12:00:00.000Z",
            price: 35,
          },
        }}
        onClose={vi.fn()}
        onConfirm={onConfirm}
      />,
    );

    const submitButton = screen.getByRole("button", { name: "فك الحجز" });
    fireEvent.click(submitButton);
    expect(onConfirm).not.toHaveBeenCalled();
    expect(screen.getByRole("alert")).toHaveTextContent("سبب فك الحجز مطلوب");

    fireEvent.change(screen.getByLabelText(/سبب فك الحجز/), {
      target: { value: "طلب المستأجر إنهاء الحجز" },
    });
    fireEvent.click(screen.getByRole("checkbox", { name: "إعادة مبلغ الإيجار للمشترك" }));
    expect(screen.getByRole("spinbutton", { name: /قيمة المبلغ المعاد/ })).toHaveValue(35);
    fireEvent.click(submitButton);

    expect(onConfirm).toHaveBeenCalledWith({
      reason: "طلب المستأجر إنهاء الحجز",
      is_refund: true,
      refund_amount: 35,
    });
  });
});
