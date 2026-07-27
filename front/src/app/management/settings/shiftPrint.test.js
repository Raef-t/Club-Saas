import { describe, expect, it } from "vitest";
import { createShiftPrintDocument } from "./shiftPrint";

describe("shift print document", () => {
  it("renders Arabic labels and escapes backend values", () => {
    const document = createShiftPrintDocument({
      branchName: '<img src="x">',
      shifts: [
        {
          name: "<script>alert(1)</script>",
          start_time: "08:00:00",
          end_time: "16:00:00",
          gender_allowed: "mixed",
        },
      ],
      formatTime: (value) => value.slice(0, 5),
      printedAt: new Date("2026-01-01T00:00:00Z"),
    });

    expect(document).toContain("جدول ورديات العمل اليومية");
    expect(document).toContain("&lt;script&gt;alert(1)&lt;/script&gt;");
    expect(document).toContain("&lt;img src=&quot;x&quot;&gt;");
    expect(document).not.toContain("<script>");
  });
});
