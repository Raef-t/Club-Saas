import { describe, expect, it } from "vitest";
import {
  formatSubscriptionMoney,
  getSubscriptionDetail,
  getSubscriptionRows,
  parseSubscriptionAmount,
} from "./subscriptionUtils";

describe("subscription utilities", () => {
  it("normalizes invalid amounts", () => {
    expect(parseSubscriptionAmount("12.5")).toBe(12.5);
    expect(parseSubscriptionAmount("invalid")).toBe(0);
  });

  it("formats subscription money consistently", () => {
    expect(formatSubscriptionMoney("1200.5")).toBe("$1,200.5");
  });

  it("supports nested and direct collection responses", () => {
    const rows = [{ id: 1 }];

    expect(getSubscriptionRows({ data: { data: rows } })).toEqual(rows);
    expect(getSubscriptionRows({ data: rows })).toEqual(rows);
    expect(getSubscriptionRows(null)).toEqual([]);
  });

  it("extracts a subscription detail safely", () => {
    expect(getSubscriptionDetail({ data: { id: 1 } })).toEqual({ id: 1 });
    expect(getSubscriptionDetail(null)).toBeNull();
  });
});
