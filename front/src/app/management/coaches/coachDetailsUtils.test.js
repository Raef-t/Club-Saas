import { describe, expect, it } from "vitest";
import {
  getCoachBranchNames,
  getUnassignedActivities,
} from "./coachDetailsUtils";

describe("coach details utilities", () => {
  it("resolves localized branch names", () => {
    const coach = { branch_ids: [2, 7] };
    const branches = [{ id: 2, name: { ar: "الرئيسي" } }];
    expect(getCoachBranchNames(coach, branches)).toBe("الرئيسي، فرع #7");
  });

  it("filters already assigned activities", () => {
    expect(
      getUnassignedActivities([{ id: 1 }], [{ id: 1 }, { id: 2 }]),
    ).toEqual([{ id: 2 }]);
  });
});
