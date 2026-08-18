import { describe, expect, it } from "vitest";
import { modificationReasonSchema } from "./modificationReasonSchema";

describe("modification reason validation", () => {
  it("trims a required reason", () => {
    expect(modificationReasonSchema.parse("  تصحيح البيانات  ")).toBe("تصحيح البيانات");
  });

  it("rejects an empty reason", () => {
    expect(modificationReasonSchema.safeParse("   ").success).toBe(false);
  });
});
