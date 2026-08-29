import { describe, expect, it } from "vitest";
import { buildReportsPrintHtml, escapeReportHtml } from "./reportPrint";

describe("report print utilities", () => {
  it("escapes dynamic printable values", () => {
    expect(escapeReportHtml('<script>"x"</script>')).toBe(
      "&lt;script&gt;&quot;x&quot;&lt;/script&gt;",
    );
  });

  it("includes the logo, selected branch, metrics, and rows", () => {
    const html = buildReportsPrintHtml({
      reports: [
        {
          title: "تقرير الحضور",
          description: "بيانات مباشرة",
          metrics: [{ label: "المجموع", value: 2 }],
          columns: [{ key: "member", label: "المشترك" }],
          rows: [{ member: "أحمد" }],
          emptyMessage: "لا توجد بيانات",
        },
      ],
      branchName: "فرع الشباب",
      logoUrl: "http://localhost/img/techno_gym_logo.png",
      generatedAt: new Date(2026, 6, 27, 10, 30),
    });

    expect(html).toContain("فرع الشباب");
    expect(html).toContain("techno_gym_logo.png");
    expect(html).toContain("تقرير الحضور");
    expect(html).toContain("أحمد");
  });
});
