import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import RenewalStatusReportFilters from "./RenewalStatusReportFilters";
import { DEFAULT_RENEWAL_REPORT_FILTERS } from "./renewalStatusReportUtils";

describe("RenewalStatusReportFilters", () => {
  afterEach(cleanup);

  it("renders with shared Dropdowns and triggers change on option selection", () => {
    const onChange = vi.fn();
    const { container } = render(
      <RenewalStatusReportFilters
        filters={DEFAULT_RENEWAL_REPORT_FILTERS}
        plans={[{ value: "1", label: "خطة عامة" }]}
        coaches={[{ value: "2", label: "كوتش أحمد" }]}
        validationError=""
        isFetching={false}
        onChange={onChange}
        onApply={() => true}
        onReset={() => {}}
      />
    );

    expect(container.querySelector("select")).not.toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "نوع التقرير" }));
    fireEvent.click(screen.getByRole("option", { name: "منتهي ولم يجدد" }));

    expect(onChange).toHaveBeenCalledWith("type", "expired_non_renewed");
  });

  it("toggles the filter accordion when clicking the header", () => {
    render(
      <RenewalStatusReportFilters
        filters={DEFAULT_RENEWAL_REPORT_FILTERS}
        plans={[]}
        coaches={[]}
        validationError=""
        isFetching={false}
        onChange={() => {}}
        onApply={() => true}
        onReset={() => {}}
      />
    );

    const toggleButton = screen.getByRole("button", { name: /فلاتر التقرير/ });
    expect(toggleButton).toHaveAttribute("aria-expanded", "true");
    expect(screen.getByRole("button", { name: "نوع التقرير" })).toBeInTheDocument();

    fireEvent.click(toggleButton);

    expect(toggleButton).toHaveAttribute("aria-expanded", "false");
    expect(screen.queryByRole("button", { name: "نوع التقرير" })).not.toBeInTheDocument();
  });
});
