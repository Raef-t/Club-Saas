import { describe, expect, it } from "vitest";
import {
  createCoachFormInitialValues,
  getEmploymentTypeForWorkTypes,
} from "./coachFormUtils";

describe("coach form utilities", () => {
  it("selects the first branch for a new coach", () => {
    const values = createCoachFormInitialValues(null, [{ id: "12" }]);

    expect(values.branch_ids).toEqual([12]);
    expect(values).not.toHaveProperty("national_id");
    expect(values).not.toHaveProperty("start_date");
  });

  it("preserves edit values", () => {
    const values = createCoachFormInitialValues({
      first_name: "Test",
      last_name: "Coach",
      dob: "1990-01-01",
      branch_ids: [3],
    });
    expect(values.first_name).toBe("Test");
    expect(values.last_name).toBe("Coach");
    expect(values.dob).toBe("1990-01-01");
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
