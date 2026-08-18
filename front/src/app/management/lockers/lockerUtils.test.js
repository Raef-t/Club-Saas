import { describe, expect, it } from "vitest";
import {
  createLockerBranchOptions,
  createLockerQueryParams,
  createLockerReservationPayload,
  createLockerUpdatePayload,
  filterLockers,
  getLockerHolderLabel,
  getLockerRecord,
  getLockerReservationEndDate,
  isLockerEarlyRelease,
  isLockerOccupied,
} from "./lockerUtils";

describe("locker utilities", () => {
  const lockers = [
    {
      id: 1,
      locker_number: "L-001",
      branch_id: 2,
      status: "available",
    },
    {
      id: 2,
      locker_number: "L-002",
      branch_id: 3,
      status: "with_member",
      holder_id: 9,
      holder_type: "member",
    },
  ];

  it("detects occupied lockers", () => {
    expect(isLockerOccupied(lockers[0])).toBe(false);
    expect(isLockerOccupied(lockers[1])).toBe(true);
  });

  it("detects an early release from the nested current reservation end date", () => {
    const locker = {
      current_reservation: { end_date: "2026-08-20T12:00:00.000Z" },
    };

    expect(getLockerReservationEndDate(locker)).toBe("2026-08-20T12:00:00.000Z");
    expect(isLockerEarlyRelease(locker, new Date("2026-08-18T12:00:00.000Z"))).toBe(true);
    expect(isLockerEarlyRelease(locker, new Date("2026-08-21T12:00:00.000Z"))).toBe(false);
  });

  it("extracts nested locker detail responses", () => {
    expect(getLockerRecord({ data: { data: lockers[0] } })).toEqual(lockers[0]);
  });

  it("filters by safe locker number, branch, and aggregate status", () => {
    expect(
      filterLockers(lockers, {
        search: "002",
        branch: "3",
        status: "occupied",
      }),
    ).toEqual([lockers[1]]);
    expect(filterLockers([{ locker_number: null }], { search: "1" })).toEqual([]);
  });

  it("does not send the aggregate occupied status to the backend", () => {
    expect(createLockerQueryParams("2", "occupied")).toEqual({
      branch_id: "2",
      status: undefined,
    });
  });

  it("normalizes branch response shapes", () => {
    expect(createLockerBranchOptions({ data: [{ id: 2, name: { ar: "الرئيسي" } }] }, true)).toEqual(
      [
        { value: "all", label: "كل الفروع" },
        { value: "2", label: "الرئيسي" },
      ],
    );
  });

  it("resolves a member holder from prepared options", () => {
    expect(getLockerHolderLabel(lockers[1], [{ value: "9", label: "أحمد علي" }])).toBe("أحمد علي");
  });

  it("omits holder fields when updating an available locker", () => {
    expect(
      createLockerUpdatePayload({
        locker_number: " L-010 ",
        status: "available",
        holder_type: "member",
        holder_id: "9",
        holder_name: "قديم",
      }),
    ).toEqual({
      locker_number: "L-010",
      key_number: null,
      status: "available",
    });
  });

  it("omits rental price from a free assignment", () => {
    expect(
      createLockerReservationPayload({
        reservation_type: "assign",
        holder_type: "member",
        holder_id: "9",
        price: "50",
        start_date: "2026-01-01",
        end_date: "",
      }),
    ).toEqual({
      reservation_type: "assign",
      holder_type: "member",
      holder_id: 9,
      start_date: "2026-01-01",
    });
  });

  it("keeps coach identifiers and omits start_date and end_date in coach reservation payloads", () => {
    expect(
      createLockerReservationPayload({
        reservation_type: "assign",
        holder_type: "coach",
        holder_id: "41",
        price: "",
        start_date: "2026-08-08",
        end_date: "2026-08-09",
      }),
    ).toEqual({
      reservation_type: "assign",
      holder_type: "coach",
      holder_id: 41,
    });
  });

  it("includes start_date and end_date for member free assignments", () => {
    expect(
      createLockerReservationPayload({
        reservation_type: "assign",
        holder_type: "member",
        holder_id: "9",
        price: "",
        start_date: "2026-08-15",
        end_date: "2026-08-15",
      }),
    ).toEqual({
      reservation_type: "assign",
      holder_type: "member",
      holder_id: 9,
      start_date: "2026-08-15",
      end_date: "2026-08-15",
    });
  });
});
