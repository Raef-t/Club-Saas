import { describe, expect, it } from "vitest";
import {
  createCoachFormInitialValues,
  getEmploymentTypeForWorkTypes,
} from "./coachFormUtils";

describe("coach form utilities", () => {
  it("selects the first branch for a new coach", () => {
    expect(createCoachFormInitialValues(null, [{ id: "12" }]).branch_ids).toEqual([
      12,
    ]);
  });

  it("preserves edit values", () => {
    const values = createCoachFormInitialValues({
      full_name: "Test Coach",
      branch_ids: [3],
    });
    expect(values.full_name).toBe("Test Coach");
    expect(values.branch_ids).toEqual([3]);
  });

  it("maps work types to employment types", () => {
    expect(getEmploymentTypeForWorkTypes(["equipment"])).toBe("fixed_salary");
    expect(getEmploymentTypeForWorkTypes(["activities"])).toBe(
      "commission_based",
    );
    expect(getEmploymentTypeForWorkTypes(["equipment", "activities"])).toBe(
      "hybrid",
    );
  });
});
