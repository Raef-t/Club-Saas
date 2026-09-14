import { describe, expect, it } from "vitest";
import { getMemberStats } from "./memberStats";

describe("member statistics", () => {
  it("uses the endpoint aggregates instead of the current page length", () => {
    expect(
      getMemberStats({
        data: [{ id: 1 }],
        stats: {
          total_members: 120,
          active_members: 93,
          male_members: 70,
          female_members: 50,
        },
      }),
    ).toEqual({
      totalMembers: 120,
      activeMembers: 93,
      maleMembers: 70,
      femaleMembers: 50,
    });
  });
});
