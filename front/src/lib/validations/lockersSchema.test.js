import { describe, expect, it } from "vitest";
import {
  createInitialReserveLockerForm,
  initialReserveLockerForm,
  reserveLockerSchema,
} from "./lockersSchema";

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

  it("allows coach reservation without start_date and end_date", () => {
    expect(
      reserveLockerSchema.safeParse({
        reservation_type: "assign",
        holder_type: "coach",
        holder_id: 41,
      }).success,
    ).toBe(true);
  });

  it("requires start_date for non-coach holder types", () => {
    expect(
      reserveLockerSchema.safeParse({
        reservation_type: "assign",
        holder_type: "member",
        holder_id: 41,
      }).success,
    ).toBe(false);

    expect(
      reserveLockerSchema.safeParse({
        reservation_type: "assign",
        holder_type: "staff",
        holder_id: 41,
      }).success,
    ).toBe(false);
  });

  it("rejects holder types unsupported by the reservation endpoint", () => {
    expect(
      reserveLockerSchema.safeParse({
        ...baseReservation,
        holder_type: "guest",
      }).success,
    ).toBe(false);
  });

  it("defaults initial reservation form to free assignment with today as start and end date for members", () => {
    expect(initialReserveLockerForm.reservation_type).toBe("assign");
    const testDate = new Date(2026, 7, 15);
    const initialForm = createInitialReserveLockerForm(testDate);
    expect(initialForm).toEqual({
      reservation_type: "assign",
      holder_type: "member",
      holder_id: "",
      price: "",
      start_date: "2026-08-15",
      end_date: "2026-08-15",
    });
  });
});
