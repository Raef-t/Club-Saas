import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import ClubForm from "./ClubForm";

describe("ClubForm", () => {
  it("submits an edited club when its existing logo is a relative backend path", () => {
    const onSubmit = vi.fn();

    render(
      <ClubForm
        mode="edit"
        initialValues={{
          id: 1,
          name: "technogym",
          logo_url: "storage/clubs/techno_gym_logo.png",
          is_active: true,
        }}
        onSubmit={onSubmit}
        onCancel={vi.fn()}
        isLoading={false}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "حفظ التعديل" }));

    expect(onSubmit).toHaveBeenCalledWith({
      name: "technogym",
      logo: null,
      logo_url: null,
      is_active: true,
    });
  });
});
