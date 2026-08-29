import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import LockerReleaseDialog from "./LockerReleaseDialog";

const FUTURE_END_DATE = "2099-08-20T12:00:00.000Z";

afterEach(cleanup);

describe("LockerReleaseDialog", () => {
  it("does not show or submit a reason for a free assignment", () => {
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

    expect(screen.queryByLabelText(/سبب فك الحجز المبكر/)).not.toBeInTheDocument();
    fireEvent.click(screen.getByRole("button", { name: "فك الحجز" }));
    expect(onConfirm).toHaveBeenCalledWith(undefined);
  });

  it("requires and submits a reason for ending a rental early", () => {
    const onConfirm = vi.fn();
    render(
      <LockerReleaseDialog
        locker={{
          id: 2,
          locker_number: "L-2",
          current_reservation: {
            reservation_type: "rental",
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
    expect(screen.getByRole("alert")).toHaveTextContent("سبب فك الحجز المبكر مطلوب");

    fireEvent.change(screen.getByLabelText(/سبب فك الحجز المبكر/), {
      target: { value: "طلب المستأجر إنهاء الحجز" },
    });
    fireEvent.click(submitButton);

    expect(onConfirm).toHaveBeenCalledWith("طلب المستأجر إنهاء الحجز");
  });
});
