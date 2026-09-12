import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import ReportPageShell from "./ReportPageShell";

describe("ReportPageShell", () => {
  afterEach(cleanup);

  it("shows report identity, scope and runs the shared actions", () => {
    const onRefresh = vi.fn();
    const onPrint = vi.fn();

    render(
      <ReportPageShell
        title="تقرير تجريبي"
        description="وصف التقرير"
        branchName="فرع دمشق"
        onRefresh={onRefresh}
        onPrint={onPrint}
      >
        <div>محتوى التقرير</div>
      </ReportPageShell>,
    );

    expect(screen.getByRole("heading", { name: "تقرير تجريبي" })).toBeInTheDocument();
    expect(screen.getByText("فرع دمشق")).toBeInTheDocument();
    expect(screen.getByText("بيانات مباشرة")).toBeInTheDocument();
    expect(screen.getByText("محتوى التقرير")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "تحديث البيانات" }));
    fireEvent.click(screen.getByRole("button", { name: "طباعة التقرير" }));

    expect(onRefresh).toHaveBeenCalledOnce();
    expect(onPrint).toHaveBeenCalledOnce();
  });

  it("announces refresh progress and disables the refresh action", () => {
    render(
      <ReportPageShell
        title="تقرير تجريبي"
        description="وصف التقرير"
        branchName="كل الفروع"
        isRefreshing
        onRefresh={() => {}}
      >
        <div />
      </ReportPageShell>,
    );

    expect(screen.getByText("جاري تحديث البيانات")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "جاري التحديث" })).toBeDisabled();
  });
});
