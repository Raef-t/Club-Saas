import { describe, expect, it } from "vitest";
import { getDropdownMenuPosition } from "./dropdownPosition";

describe("dropdown positioning", () => {
  it("opens downward when the actual menu fits below the control", () => {
    const position = getDropdownMenuPosition(
      { top: 100, right: 300, bottom: 140, left: 100, width: 200 },
      {
        optionCount: 2,
        viewportHeight: 300,
        viewportWidth: 500,
      },
    );

    expect(position.opensUpward).toBe(false);
    expect(position.top).toBe(148);
  });

  it("opens upward when the menu only fits above the control", () => {
    const position = getDropdownMenuPosition(
      { top: 220, right: 300, bottom: 260, left: 100, width: 200 },
      {
        optionCount: 4,
        viewportHeight: 300,
        viewportWidth: 500,
      },
    );

    expect(position.opensUpward).toBe(true);
    expect(position.top).toBe(44);
  });

  it("keeps the menu inside the horizontal viewport", () => {
    const position = getDropdownMenuPosition(
      { top: 20, right: 390, bottom: 60, left: 290, width: 100 },
      {
        optionCount: 1,
        viewportHeight: 300,
        viewportWidth: 320,
      },
    );

    expect(position.left).toBe(212);
    expect(position.width).toBe(100);
  });
});
