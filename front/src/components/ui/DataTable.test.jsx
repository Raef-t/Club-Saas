import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it } from "vitest";
import DataTable from "./DataTable";

describe("DataTable sorting functionality", () => {
  afterEach(() => {
    cleanup();
  });

  const sampleColumns = [
    { key: "id", label: "الرقم" },
    { key: "name", label: "الاسم" },
    { key: "salary", label: "الراتب" },
    { key: "actions", label: "الإجراءات", sortable: false },
  ];

  const sampleRows = [
    { id: "3", name: "خالد", salary: "1,500 ل.س" },
    { id: "1", name: "أحمد", salary: "200 ل.س" },
    { id: "2", name: "باسم", salary: "30 ل.س" },
  ];

  it("sorts text columns alphabetically in ascending and descending order, and resets on third click", () => {
    render(<DataTable columns={sampleColumns} rows={sampleRows} title="جدول الاختبار" />);

    const nameHeader = screen.getByTestId("data-table-header-name");

    // Initial order: خالد, أحمد, باسم
    let rows = screen.getAllByTestId("data-table-row");
    expect(rows[0]).toHaveTextContent("خالد");
    expect(rows[1]).toHaveTextContent("أحمد");
    expect(rows[2]).toHaveTextContent("باسم");

    // Click 1 -> Ascending (أحمد, باسم, خالد)
    fireEvent.click(nameHeader);
    rows = screen.getAllByTestId("data-table-row");
    expect(rows[0]).toHaveTextContent("أحمد");
    expect(rows[1]).toHaveTextContent("باسم");
    expect(rows[2]).toHaveTextContent("خالد");

    // Click 2 -> Descending (خالد, باسم, أحمد)
    fireEvent.click(nameHeader);
    rows = screen.getAllByTestId("data-table-row");
    expect(rows[0]).toHaveTextContent("خالد");
    expect(rows[1]).toHaveTextContent("باسم");
    expect(rows[2]).toHaveTextContent("أحمد");

    // Click 3 -> Reset to original (خالد, أحمد, باسم)
    fireEvent.click(nameHeader);
    rows = screen.getAllByTestId("data-table-row");
    expect(rows[0]).toHaveTextContent("خالد");
    expect(rows[1]).toHaveTextContent("أحمد");
    expect(rows[2]).toHaveTextContent("باسم");
  });

  it("sorts currency and numeric strings numerically rather than strictly alphabetically", () => {
    render(<DataTable columns={sampleColumns} rows={sampleRows} title="جدول الاختبار" />);

    const salaryHeader = screen.getByTestId("data-table-header-salary");

    // Click 1 -> Ascending (30 ل.س, 200 ل.س, 1,500 ل.س)
    fireEvent.click(salaryHeader);
    let rows = screen.getAllByTestId("data-table-row");
    expect(rows[0]).toHaveTextContent("باسم"); // 30
    expect(rows[1]).toHaveTextContent("أحمد"); // 200
    expect(rows[2]).toHaveTextContent("خالد"); // 1500

    // Click 2 -> Descending (1,500 ل.س, 200 ل.س, 30 ل.س)
    fireEvent.click(salaryHeader);
    rows = screen.getAllByTestId("data-table-row");
    expect(rows[0]).toHaveTextContent("خالد"); // 1500
    expect(rows[1]).toHaveTextContent("أحمد"); // 200
    expect(rows[2]).toHaveTextContent("باسم"); // 30
  });

  it("sorts localized objects with { ar, en } seamlessly", () => {
    const localizedColumns = [
      { key: "id", label: "الرقم" },
      { key: "localizedName", label: "النشاط" },
    ];
    const localizedRows = [
      { id: "1", localizedName: { ar: "سباحة", en: "Swimming" } },
      { id: "2", localizedName: { ar: "حديد", en: "Gym" } },
      { id: "3", localizedName: { ar: "كاراتيه", en: "Karate" } },
    ];

    render(<DataTable columns={localizedColumns} rows={localizedRows} title="الأنشطة" />);

    const nameHeader = screen.getByTestId("data-table-header-localizedName");

    // Click 1 -> Ascending (حديد, سباحة, كاراتيه)
    fireEvent.click(nameHeader);
    let rows = screen.getAllByTestId("data-table-row");
    expect(rows[0]).toHaveTextContent("حديد");
    expect(rows[1]).toHaveTextContent("سباحة");
    expect(rows[2]).toHaveTextContent("كاراتيه");

    // Click 2 -> Descending (كاراتيه, سباحة, حديد)
    fireEvent.click(nameHeader);
    rows = screen.getAllByTestId("data-table-row");
    expect(rows[0]).toHaveTextContent("كاراتيه");
    expect(rows[1]).toHaveTextContent("سباحة");
    expect(rows[2]).toHaveTextContent("حديد");
  });

  it("respects custom sortValue functions on columns", () => {
    const customColumns = [
      { key: "id", label: "الرقم" },
      {
        key: "member",
        label: "العضو",
        sortValue: (row) => row.person?.full_name || "",
        render: (_, row) => <span>{row.person?.full_name}</span>,
      },
    ];

    const memberRows = [
      { id: "1", person: { full_name: "سامر" } },
      { id: "2", person: { full_name: "أمجد" } },
      { id: "3", person: { full_name: "ياسر" } },
    ];

    render(<DataTable columns={customColumns} rows={memberRows} title="الأعضاء" />);

    const header = screen.getByTestId("data-table-header-member");
    fireEvent.click(header);

    // Ascending: أمجد, سامر, ياسر
    let rows = screen.getAllByTestId("data-table-row");
    expect(rows[0]).toHaveTextContent("أمجد");
    expect(rows[1]).toHaveTextContent("سامر");
    expect(rows[2]).toHaveTextContent("ياسر");
  });

  it("does not trigger sort on non-sortable columns like actions", () => {
    render(<DataTable columns={sampleColumns} rows={sampleRows} title="جدول الاختبار" />);

    const actionsHeader = screen.getByTestId("data-table-header-actions");
    fireEvent.click(actionsHeader);

    // Order should remain unchanged: خالد, أحمد, باسم
    const rows = screen.getAllByTestId("data-table-row");
    expect(rows[0]).toHaveTextContent("خالد");
    expect(rows[1]).toHaveTextContent("أحمد");
    expect(rows[2]).toHaveTextContent("باسم");
  });

  it("applies defaultSortColumn on initial render", () => {
    render(
      <DataTable
        columns={sampleColumns}
        rows={sampleRows}
        title="جدول الاختبار"
        defaultSortColumn="name"
      />,
    );

    // Initial order should be sorted ascending by name: أحمد, باسم, خالد
    const rows = screen.getAllByTestId("data-table-row");
    expect(rows[0]).toHaveTextContent("أحمد");
    expect(rows[1]).toHaveTextContent("باسم");
    expect(rows[2]).toHaveTextContent("خالد");
  });
});
