import { render, screen, fireEvent, cleanup } from "@testing-library/react";
import { describe, expect, it, vi, afterEach } from "vitest";
import DatePickerSmart, { MONTHS } from "./DatePickerSmart";

describe("DatePickerSmart component", () => {
  afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
  });

  it("renders with given value and formats it", () => {
    render(<DatePickerSmart value="2026-09-15" onChange={() => {}} />);
    const input = screen.getByRole("textbox");
    expect(input).toHaveValue("15/09/2026");
  });

  it("opens calendar popup on input click and shows Levantine month and year", () => {
    render(<DatePickerSmart value="2026-09-15" onChange={() => {}} />);
    const input = screen.getByRole("textbox");
    fireEvent.click(input);

    expect(screen.getByTitle("اختر الشهر")).toHaveTextContent("أيلول");
    expect(screen.getByTitle("اختر السنة")).toHaveTextContent("2026");
  });

  it("switches to month mode and displays all 12 Levantine Arabic months", () => {
    render(<DatePickerSmart value="2026-09-15" onChange={() => {}} />);
    const input = screen.getByRole("textbox");
    fireEvent.click(input);

    const monthHeaderBtn = screen.getByTitle("اختر الشهر");
    fireEvent.click(monthHeaderBtn);

    expect(screen.getByRole("button", { name: "كانون الثاني" })).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "شباط" })).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "آذار" })).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "نيسان" })).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "أيار" })).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "حزيران" })).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "تموز" })).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "آب" })).toBeInTheDocument();
    expect(screen.getAllByRole("button", { name: "أيلول" }).length).toBeGreaterThanOrEqual(1);
    expect(screen.getByRole("button", { name: "تشرين الأول" })).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "تشرين الثاني" })).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "كانون الأول" })).toBeInTheDocument();
  });

  it("switches to year mode and displays bounded years including current year", () => {
    render(<DatePickerSmart value="2026-09-15" onChange={() => {}} minYear={2020} maxYear={2030} />);
    const input = screen.getByRole("textbox");
    fireEvent.click(input);

    const yearHeaderBtn = screen.getByTitle("اختر السنة");
    fireEvent.click(yearHeaderBtn);

    expect(screen.getByRole("button", { name: "2020" })).toBeInTheDocument();
    expect(screen.getAllByRole("button", { name: "2026" }).length).toBe(2);
    expect(screen.getByRole("button", { name: "2030" })).toBeInTheDocument();
    expect(screen.queryByRole("button", { name: "2019" })).not.toBeInTheDocument();
    expect(screen.queryByRole("button", { name: "2031" })).not.toBeInTheDocument();
  });

  it("centers the current year when the year list opens", () => {
    vi.stubGlobal("requestAnimationFrame", (callback) => {
      callback();
      return 1;
    });

    const currentYear = new Date().getFullYear();
    const minYear = currentYear - 40;
    render(
      <DatePickerSmart
        value={`${currentYear - 20}-09-15`}
        onChange={() => {}}
        minYear={minYear}
        maxYear={currentYear + 10}
      />,
    );

    fireEvent.click(screen.getByRole("textbox"));
    fireEvent.click(screen.getByTitle("اختر السنة"));

    const currentYearButton = screen.getByRole("button", { name: String(currentYear) });
    const expectedScrollTop = Math.max(0, Math.floor((currentYear - minYear) / 4) * 44 - 88);

    expect(currentYearButton).toHaveAttribute("aria-current", "date");
    expect(screen.getByLabelText("قائمة السنوات").scrollTop).toBe(expectedScrollTop);
  });

  it("rejects typed dates outside the configured year bounds", () => {
    const handleChange = vi.fn();
    render(<DatePickerSmart value="" onChange={handleChange} minYear={2020} maxYear={2030} />);

    const input = screen.getByRole("textbox");
    fireEvent.click(input);
    fireEvent.change(input, { target: { value: "15/09/2031" } });
    fireEvent.keyDown(input, { key: "Enter" });

    expect(handleChange).not.toHaveBeenCalled();
  });

  it("does not navigate beyond the configured maximum year", () => {
    render(<DatePickerSmart value="2030-12-15" onChange={() => {}} minYear={2020} maxYear={2030} />);

    fireEvent.click(screen.getByRole("textbox"));
    fireEvent.click(screen.getByTitle("التالي"));

    expect(screen.getByTitle("اختر الشهر")).toHaveTextContent("كانون الأول");
    expect(screen.getByTitle("اختر السنة")).toHaveTextContent("2030");
  });

  it("selects a year and switches back to day view", () => {
    render(<DatePickerSmart value="2026-09-15" onChange={() => {}} minYear={2020} maxYear={2030} />);
    const input = screen.getByRole("textbox");
    fireEvent.click(input);

    const yearHeaderBtn = screen.getByTitle("اختر السنة");
    fireEvent.click(yearHeaderBtn);

    const targetYear = screen.getByRole("button", { name: "2028" });
    fireEvent.click(targetYear);

    expect(screen.getByTitle("اختر السنة")).toHaveTextContent("2028");
  });

  it("selects a month and switches back to day view", () => {
    render(<DatePickerSmart value="2026-09-15" onChange={() => {}} />);
    const input = screen.getByRole("textbox");
    fireEvent.click(input);

    const monthHeaderBtn = screen.getByTitle("اختر الشهر");
    fireEvent.click(monthHeaderBtn);

    const targetMonth = screen.getByRole("button", { name: "نيسان" });
    fireEvent.click(targetMonth);

    expect(screen.getByTitle("اختر الشهر")).toHaveTextContent("نيسان");
  });

  it("picks today when today button is clicked", () => {
    const handleChange = vi.fn();
    render(<DatePickerSmart value="" onChange={handleChange} />);
    const input = screen.getByRole("textbox");
    fireEvent.click(input);

    const todayBtn = screen.getByRole("button", { name: "اليوم" });
    fireEvent.click(todayBtn);

    expect(handleChange).toHaveBeenCalled();
  });
});
