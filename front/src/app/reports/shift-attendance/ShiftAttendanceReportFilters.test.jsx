import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import ShiftAttendanceReportFilters from "./ShiftAttendanceReportFilters";
import { DEFAULT_SHIFT_ATTENDANCE_FILTERS } from "./shiftAttendanceReportUtils";

describe("ShiftAttendanceReportFilters", () => {
  afterEach(cleanup);

  it("renders with dropdowns and handles mode change", () => {
    const onChange = vi.fn();
    const { container } = render(
      <ShiftAttendanceReportFilters
        filters={DEFAULT_SHIFT_ATTENDANCE_FILTERS}
        activities={[{ value: "3", label: "أجهزة خاص" }]}
        shifts={[{ value: "2", label: "شيفت الظهر" }]}
        validationError=""
        isFetching={false}
        onChange={onChange}
        onApply={() => true}
        onReset={() => {}}
      />
    );

    expect(container.querySelector("select")).not.toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "نمط التصفية الزمنية" }));
    fireEvent.click(screen.getByRole("option", { name: "شهر محدد (تصفية شهرية)" }));

    expect(onChange).toHaveBeenCalledWith("mode", "month");
  });

  it("toggles the filter card accordion", () => {
    render(
      <ShiftAttendanceReportFilters
        filters={DEFAULT_SHIFT_ATTENDANCE_FILTERS}
        activities={[]}
        shifts={[]}
        validationError=""
        isFetching={false}
        onChange={() => {}}
        onApply={() => true}
        onReset={() => {}}
      />
    );

    const toggleButton = screen.getByRole("button", { name: /فلاتر التقرير/ });
    expect(toggleButton).toHaveAttribute("aria-expanded", "true");
    expect(screen.getByRole("button", { name: "نمط التصفية الزمنية" })).toBeInTheDocument();

    fireEvent.click(toggleButton);

    expect(toggleButton).toHaveAttribute("aria-expanded", "false");
    expect(screen.queryByRole("button", { name: "نمط التصفية الزمنية" })).not.toBeInTheDocument();
  });
});
