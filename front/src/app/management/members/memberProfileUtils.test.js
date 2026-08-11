import { describe, expect, it } from "vitest";
import {
  createMemberProfileSummary,
  getMemberProfileAttendances,
  getMemberProfileLockers,
  getMemberProfileRecord,
  getMemberProfileSubscriptions,
} from "./memberProfileUtils";

describe("member profile utilities", () => {
  it("extracts a member record from supported API wrappers", () => {
    expect(getMemberProfileRecord({ data: { id: 7, member_number: "MEM-7" } })).toEqual({
      id: 7,
      member_number: "MEM-7",
    });
    expect(getMemberProfileRecord({ data: { data: { id: 8 } } })).toEqual({ id: 8 });
    expect(getMemberProfileRecord({ data: { member: { id: 9 } } })).toEqual({ id: 9 });
    expect(getMemberProfileRecord([])).toBeNull();
  });

  it("keeps only the selected member records and orders attendance by recency", () => {
    const subscriptions = getMemberProfileSubscriptions(
      {
        data: [
          { id: 1, member_id: 7, status: "expired" },
          { id: 2, member_id: 8, status: "active" },
          { id: 3, member_id: 7, status: "active" },
        ],
      },
      7,
    );
    const attendances = getMemberProfileAttendances(
      {
        data: [
          { id: 1, member_id: 7, check_in: "2026-08-01T10:00:00Z" },
          { id: 2, member_id: 8, check_in: "2026-08-03T10:00:00Z" },
          { id: 3, member_id: 7, check_in: "2026-08-05T10:00:00Z" },
        ],
      },
      7,
    );

    expect(subscriptions.map((item) => item.id)).toEqual([3, 1]);
    expect(attendances.map((item) => item.id)).toEqual([3, 1]);
  });

  it("finds assigned lockers and creates the financial and activity summary", () => {
    const lockers = getMemberProfileLockers(
      {
        data: {
          lockers: [
            { id: 1, holder_type: "member", holder_id: 7 },
            { id: 2, holder_type: "coach", holder_id: 7 },
            { id: 3, holder_type: "member", holder_id: 8 },
          ],
        },
      },
      7,
    );
    const subscriptions = [
      { id: 1, member_id: 7, status: "expired", paid_amount: 100, remaining_amount: 20 },
      { id: 2, member_id: 7, status: "active", paid_amount: 300, remaining_amount: 0 },
    ];
    const attendances = [{ id: 4 }, { id: 3 }];

    expect(
      createMemberProfileSummary({ subscriptions, attendances, lockers, memberId: 7 }),
    ).toMatchObject({
      currentSubscription: { id: 2 },
      subscriptionsCount: 2,
      attendanceCount: 2,
      paidAmount: 400,
      remainingAmount: 20,
      lockerCount: 1,
      lastAttendance: { id: 4 },
    });
  });
});
