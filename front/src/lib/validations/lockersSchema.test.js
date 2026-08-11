import { describe, expect, it } from "vitest";
import { reserveLockerSchema } from "./lockersSchema";

describe("locker reservation validation", () => {
  const baseReservation = {
    reservation_type: "assign",
    holder_id: 41,
    start_date: "2026-08-08",
  };

  it.each(["member", "coach", "staff"])("accepts the %s holder type", (holderType) => {
    expect(
      reserveLockerSchema.safeParse({
        ...baseReservation,
        holder_type: holderType,
      }).success,
    ).toBe(true);
  });

  it("rejects holder types unsupported by the reservation endpoint", () => {
    expect(
      reserveLockerSchema.safeParse({
        ...baseReservation,
        holder_type: "guest",
      }).success,
    ).toBe(false);
  });
});
