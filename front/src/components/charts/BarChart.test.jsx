import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import BarChart from "./BarChart";

describe("BarChart component", () => {
  it("renders empty state message when data is empty", () => {
    render(<BarChart data={[]} />);
    expect(screen.getByText("لا توجد بيانات متاحة")).toBeInTheDocument();
  });

  it("renders bars with correct labels and numerical values", () => {
    const data = [
      { label: "الأحد - شيفت صباحي", value: 45 },
      { label: "الأحد - شيفت الظهر", value: 30 },
      { label: "الأحد - شيفت مسائي", value: 15 },
    ];

    render(<BarChart data={data} />);

    expect(screen.getAllByText("45")[0]).toBeInTheDocument();
    expect(screen.getAllByText("30")[0]).toBeInTheDocument();
    expect(screen.getAllByText("15")[0]).toBeInTheDocument();
    expect(screen.getByText("شيفت صباحي")).toBeInTheDocument();
    expect(screen.getByText("شيفت الظهر")).toBeInTheDocument();
    expect(screen.getByText("شيفت مسائي")).toBeInTheDocument();
  });

  it("filters out undefined strings from labels", () => {
    const data = [
      { label: "undefined - وردية الفجر", value: 45 },
      { label: "undefined - وردية الليل", value: 15 },
    ];

    render(<BarChart data={data} />);

    expect(screen.queryByText(/undefined/)).not.toBeInTheDocument();
    expect(screen.getByText("وردية الفجر")).toBeInTheDocument();
    expect(screen.getByText("وردية الليل")).toBeInTheDocument();
  });
});
