import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import SubscriptionsReportFilters from "./SubscriptionsReportFilters";
import { DEFAULT_SUBSCRIPTIONS_REPORT_FILTERS } from "./useSubscriptionsReport";

describe("SubscriptionsReportFilters", () => {
  afterEach(cleanup);

  it("uses the shared Dropdown component instead of native select elements", () => {
    const onChange = vi.fn();
    const { container } = render(
      <SubscriptionsReportFilters
        filters={DEFAULT_SUBSCRIPTIONS_REPORT_FILTERS}
        plans={[]}
        coaches={[]}
        validationError=""
        isFetching={false}
        onChange={onChange}
        onApply={() => {}}
        onReset={() => {}}
      />,
    );

    expect(container.querySelector("select")).not.toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "حالة الاشتراك" }));
    fireEvent.click(screen.getByRole("option", { name: "فعال" }));

    expect(onChange).toHaveBeenCalledWith("status", "active");
  });

  it("opens and closes the filters as an accessible accordion", () => {
    render(
      <SubscriptionsReportFilters
        filters={DEFAULT_SUBSCRIPTIONS_REPORT_FILTERS}
        plans={[]}
        coaches={[]}
        validationError=""
        isFetching={false}
        onChange={() => {}}
        onApply={() => true}
        onReset={() => {}}
      />,
    );

    const accordionButton = screen.getByRole("button", { name: /فلاتر التقرير/ });
    expect(accordionButton).toHaveAttribute("aria-expanded", "true");
    expect(screen.getByRole("button", { name: "حالة الاشتراك" })).toBeInTheDocument();

    fireEvent.click(accordionButton);

    expect(accordionButton).toHaveAttribute("aria-expanded", "false");
    expect(screen.queryByRole("button", { name: "حالة الاشتراك" })).not.toBeInTheDocument();
  });

  it("summarizes selected filters as readable chips", () => {
    render(
      <SubscriptionsReportFilters
        filters={{
          ...DEFAULT_SUBSCRIPTIONS_REPORT_FILTERS,
          status: "active",
          planId: "10",
          search: "سارة",
        }}
        plans={[{ value: "10", label: "الخطة الذهبية" }]}
        coaches={[]}
        validationError=""
        isFetching={false}
        onChange={() => {}}
        onApply={() => true}
        onReset={() => {}}
      />,
    );

    expect(screen.getByText("حالة الاشتراك: فعال")).toBeInTheDocument();
    expect(screen.getByText("الخطة: الخطة الذهبية")).toBeInTheDocument();
    expect(screen.getByText("البحث: سارة")).toBeInTheDocument();
  });
});
